// server.js — real-time positions, chat, voice signaling, and balloon pickups.
//
// Balloon crediting: if a player joined with a verified login token (issued by ajax/city_auth.php), collected balloons are POSTed to your
// PHP endpoint (award_balloons.php) so they land in the real `users.balloons`
// column and `balloon_transactions` ledger. Guests (no uid) just get a
// session-only counter that resets when they leave — never touches the DB.

const express = require('express');
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const net = require('net');
const tls = require('tls');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: '*', // TODO: set to 'https://neyyappam.com' before going live
    methods: ['GET', 'POST']
  }
});

// The game's HTML file is named city_world.php (matching your site's PHP
// naming convention) even though it's plain static HTML/JS with no PHP
// interpreter involved — Node just serves it as a file. Because ".php" isn't
// normally mapped to text/html, we force the correct header explicitly here,
// otherwise some browsers would try to download it instead of rendering it.
app.use(express.static('public', {
  setHeaders: (res, filePath) => {
    if (filePath.endsWith('.php')) { res.setHeader('Content-Type', 'text/html; charset=utf-8'); res.setHeader('Cache-Control', 'no-cache'); }   // always revalidate so updates reach phones immediately
  }
}));

// --- Voice chat: issues a LiveKit access token. The client (city_world.php) calls
// /livekit-token and expects JSON { token, url }. Set these env vars on the server:
//   LIVEKIT_URL         e.g. wss://your-project.livekit.cloud
//   LIVEKIT_API_KEY     from your LiveKit project settings
//   LIVEKIT_API_SECRET  from your LiveKit project settings
const { AccessToken } = require('livekit-server-sdk');
// Token-based (no cookies), so a permissive CORS policy on this one route is safe and lets the game run on another origin.
app.use('/livekit-token', (req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type');
  res.setHeader('Cache-Control', 'no-store');
  if (req.method === 'OPTIONS') return res.sendStatus(204);
  next();
});
app.get('/livekit-token', async (req, res) => {
  // Voice chat is for logged-in members only: a valid signed login token is required.
  const bearer = String(req.headers.authorization || '').replace(/^Bearer\s+/i, '');
  const who = verifyCityToken(bearer || String(req.query.token || ''));
  if (!who) return res.status(401).json({ error: 'Please log in to use the microphone.', login_required: true });
  const { LIVEKIT_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET } = process.env;
  if (!LIVEKIT_URL || !LIVEKIT_API_KEY || !LIVEKIT_API_SECRET) {
    return res.status(503).json({ error: 'Voice chat is not configured on the server yet (missing LIVEKIT_* settings).' });
  }
  try {
    const identity = 'u' + who.uid + '-' + String(req.query.identity || '').replace(/[^\w-]/g, '').slice(0, 24);
    const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, { identity, name: who.name, ttl: '2h' });
    at.addGrant({ roomJoin: true, room: 'neyyappam-city', canPublish: true, canSubscribe: true });
    res.json({ token: await at.toJwt(), url: LIVEKIT_URL });
  } catch (err) {
    console.error('livekit-token failed:', err.message);
    res.status(500).json({ error: 'Could not create a voice token.' });
  }
});

app.get(['/', '/city_world.php'], (req, res) => {
  res.type('html');
  res.setHeader('Cache-Control', 'no-cache');
  res.sendFile(__dirname + '/public/city_world.php');
});

// --- Config for talking back to your PHP site ---
const AWARD_ENDPOINT = process.env.AWARD_ENDPOINT || 'https://neyyappam.com/ajax/award_balloons.php';
const SHARED_SECRET = process.env.CITY_SHARED_SECRET || 'change-this-to-a-long-random-string';

// --- Signed login tokens issued by ajax/city_auth.php (payload.signature, HMAC-SHA256) ---
// The browser can no longer claim "I am user 123": the uid/name/balloons below come only from a token
// signed with CITY_SHARED_SECRET, which only your PHP site can produce.
function b64urlDecode(s) { return Buffer.from(String(s).replace(/-/g, '+').replace(/_/g, '/'), 'base64'); }
function verifyCityToken(token) {
  if (typeof token !== 'string' || token.length > 2000 || !token.includes('.')) return null;
  if (!SHARED_SECRET || SHARED_SECRET === 'change-this-to-a-long-random-string') return null;
  const [payload, sig] = token.split('.');
  const good = crypto.createHmac('sha256', SHARED_SECRET).update(payload).digest();
  const given = b64urlDecode(sig);
  if (given.length !== good.length || !crypto.timingSafeEqual(given, good)) return null;
  try {
    const d = JSON.parse(b64urlDecode(payload).toString('utf8'));
    if (!d || !Number.isInteger(d.uid) || d.uid <= 0 || !(d.exp > Date.now() / 1000)) return null;
    const username = String(d.name || 'player').slice(0, 24);
    return { uid: d.uid, name: String(d.disp || username).slice(0, 24), username, balloons: Math.max(0, parseInt(d.bal, 10) || 0) };
  } catch (e) { return null; }
}

const players = {}; // socket.id -> { id, name, gender, outfitColor, hairStyle, x, y, z, rotY, uid, balloons, drivingCarId, inventory, flowersGiven, flowersReceived, treasuresFound }
const claimedPickups = new Set();
const occupiedCars = {}; // carId -> socket.id of whoever is currently driving it

// --- Balloon economy: type/value is derived from the id itself so every
// client (which generates ids in the same deterministic order while
// building the city) and the server always agree on what a given balloon
// is worth, with no extra network round-trip needed. ---
function balloonValueForId(id) {
  if (id.startsWith('rainbow_')) return 50;
  if (id.startsWith('bonus_')) {
    const n = parseInt(id.split('_')[1], 10) || 0;
    return (n % 2 === 0) ? 15 : 10; // gold vs purple bonus balloons
  }
  return 5; // classic pink balloon
}

// --- Shop catalog: cosmetic / social items purchased with balloons.
// Entirely virtual props with no real-world function — same idea as
// props in any social sim game. ---
const ITEMS = [
  { id: 'rose',        name: 'Rose',            category: 'flower',    price: 10, emoji: '🌹' },
  { id: 'lily',        name: 'Lily',            category: 'flower',    price: 15, emoji: '🌷' },
  { id: 'sunflower',   name: 'Sunflower',       category: 'flower',    price: 12, emoji: '🌻' },
  { id: 'bouquet',     name: 'Bouquet',         category: 'flower',    price: 28, emoji: '💐' },
  { id: 'burger',      name: 'Burger',          category: 'food',      price: 8,  emoji: '🍔' },
  { id: 'pizza_slice', name: 'Pizza Slice',     category: 'food',      price: 9,  emoji: '🍕' },
  { id: 'donut',       name: 'Donut',           category: 'food',      price: 6,  emoji: '🍩' },
  { id: 'icecream',    name: 'Ice Cream',       category: 'food',      price: 7,  emoji: '🍦' },
  { id: 'soda',        name: 'Soda',            category: 'drink',     price: 8,  emoji: '🥤' },
  { id: 'coffee',      name: 'Coffee',          category: 'drink',     price: 10, emoji: '☕' },
  { id: 'mocktail',    name: 'Party Mocktail',  category: 'drink',     price: 14, emoji: '🍹' },
  { id: 'cigar_pack',  name: 'Cigarette Pack',  category: 'drink',     price: 12, emoji: '🚬' },
  { id: 'water_gun',   name: 'Water Gun',       category: 'weapon',    price: 22, emoji: '🔫' },
  { id: 'toy_blaster', name: 'Toy Blaster',     category: 'weapon',    price: 30, emoji: '🔫' },
  { id: 'nerf_bow',    name: 'Foam Dart Bow',   category: 'weapon',    price: 26, emoji: '🏹' },
  { id: 'pistol',      name: 'Pistol',          category: 'weapon',    price: 60, emoji: '🔫' },
  { id: 'shotgun',     name: 'Shotgun',         category: 'weapon',    price: 90, emoji: '🔫' },
  { id: 'rose_dozen',  name: 'Dozen Roses',     category: 'flower',    price: 90, emoji: '🌹' },
  { id: 'party_hat',   name: 'Party Hat',       category: 'party',     price: 12, emoji: '🎉' },
  { id: 'confetti',    name: 'Confetti Popper', category: 'party',     price: 10, emoji: '🎊' },
  { id: 'balloon_bunch',name:'Balloon Bunch',   category: 'party',     price: 20, emoji: '🎈' },
  { id: 'sunglasses',  name: 'Sunglasses',      category: 'accessory', price: 18, emoji: '🕶️' },
  { id: 'necklace',    name: 'Gold Necklace',   category: 'accessory', price: 25, emoji: '📿' },
  { id: 'cap',         name: 'Snapback Cap',    category: 'clothing',  price: 16, emoji: '🧢' },
  { id: 'jacket',      name: 'Bomber Jacket',   category: 'clothing',  price: 30, emoji: '🧥' },
  // --- Chayakkada menu (bought at the tea shop counter, not the main Shop) ---
  { id: 'chaya',        name: 'Chaya',            category: 'chayakkada', price: 4, emoji: '🍵', shop: 'tea' },
  { id: 'sulaimani',    name: 'Sulaimani',        category: 'chayakkada', price: 5, emoji: '🍋', shop: 'tea' },
  { id: 'parippuvada',  name: 'Parippu Vada',     category: 'chayakkada', price: 5, emoji: '🧆', shop: 'tea' },
  { id: 'pazhampori',   name: 'Pazhampori',       category: 'chayakkada', price: 6, emoji: '🍌', shop: 'tea' },
  { id: 'unniyappam',   name: 'Unniyappam',       category: 'chayakkada', price: 7, emoji: '🥮', shop: 'tea' },
  { id: 'halwa',        name: 'Kozhikodan Halwa', category: 'chayakkada', price: 9, emoji: '🍬', shop: 'tea' },
  // --- Karavakkari chechi's milk stall ---
  { id: 'palu',         name: 'Fresh Palu (milk)', category: 'dairy', price: 5, emoji: '🥛', shop: 'milk' },
  { id: 'thairu',       name: 'Thairu (curd)',     category: 'dairy', price: 6, emoji: '🥣', shop: 'milk' },
  { id: 'sambaram',     name: 'Sambaram (buttermilk)', category: 'dairy', price: 4, emoji: '🧋', shop: 'milk' },
  { id: 'nei',          name: 'Nei (ghee)',        category: 'dairy', price: 12, emoji: '🧈', shop: 'milk' },
];
// The Shop building stands on the central plaza. Purchases are only accepted
// when the buyer is actually standing at the counter (client uses 7, the
// server allows a little slack for network lag).
const SHOP_POS = { x: 10, z: 15.5 };
const SHOP_RANGE = 8;
// Chayakkada (tea shop) counter + the old radio sitting on it. Must match the client constants.
const TEA_POS = { x: -10, z: 8 };
const RADIO_POS = { x: -12.8, z: 9.7 };
const RADIO_RANGE = 8;
const MILK_POS = { x: -6, z: -5 };   // Karavakkari chechi's milk stall (must match the client)

/* =====================================================================
   RADIO HUB — one shared Malayalam radio for the whole town.
   * Stations are discovered automatically from the open Radio Browser directory (language=malayalam,
     working streams only, "old songs"-style names first), plus anything you list in stations.json:
         [ { "name": "My Old Songs FM", "url": "https://example.com/stream.mp3" } ]
   * The server opens ONE connection per station and relays it to every listener over your own https,
     so http-only streams work, nothing is blocked as mixed content, and everyone hears the same broadcast.
   * Dead stations are detected, marked down for 2 minutes, and the radio skips to the next one.
   ===================================================================== */
const UA = 'NeyyappamCity/1.0 (+https://neyyappam.com)';
const RB_MIRRORS = ['https://de1.api.radio-browser.info', 'https://nl1.api.radio-browser.info', 'https://at1.api.radio-browser.info'];
const OLD_RE = /old|melod|gold|evergreen|classic|nostalg|retro|vintage|\b(70|80|90)s\b/i;
const hub = { list: [], byId: {} };
let radio = { on: true, station: 0 };

function stationId(url) { return crypto.createHash('sha1').update(url).digest('hex').slice(0, 10); }
function makeStation(name, url, kind) {
  return { id: stationId(url), name: String(name).trim().slice(0, 40) || 'Radio', url, kind,
           clients: new Set(), up: null, connecting: false, ready: false, ctype: 'audio/mpeg',
           recent: [], recentBytes: 0, waiters: [], downUntil: 0, idleTimer: null };
}
function radioPayload() {
  const now = Date.now();
  return { on: radio.on, station: radio.station,
           stations: hub.list.map(s => ({ id: s.id, name: s.name, kind: s.kind, down: s.downUntil > now })) };
}

function broadcast(st, chunk) {
  st.recent.push(chunk); st.recentBytes += chunk.length;
  while (st.recentBytes > 16384 && st.recent.length > 1) st.recentBytes -= st.recent.shift().length;
  for (const c of st.clients) {
    if (c.writableLength > 1500000) { c.destroy(); st.clients.delete(c); continue; } // listener too slow: drop it
    c.write(chunk);
  }
}
function hubStationDown(st) {
  const cur = hub.list[radio.station];
  if (cur === st && hub.list.length > 1) {
    for (let i = 1; i < hub.list.length; i++) {
      const j = (radio.station + i) % hub.list.length;
      if (hub.list[j].downUntil < Date.now()) { radio.station = j; break; }
    }
  }
  io.emit('radioState', radioPayload());
}
function failUp(st, why) {
  st.connecting = false; st.up = null; st.ready = false;
  st.downUntil = Date.now() + 2 * 60 * 1000;
  console.warn(`[radio] ${st.name} is off air: ${why}`);
  st.waiters.splice(0).forEach(fn => fn(false));
  hubStationDown(st);
}
function startUpstream(st) {
  if (st.up || st.connecting) return;
  st.connecting = true; st.ready = false;
  // Stream is connected: remember the handle, flush waiting listeners, reconnect automatically if it drops.
  const live = (handle, contentType) => {
    st.ctype = /audio|mpeg|aac|ogg/i.test(contentType || '') ? contentType : 'audio/mpeg';
    st.connecting = false; st.up = handle; st.ready = true; st.downUntil = 0; st.recent = []; st.recentBytes = 0;
    console.log(`[radio] ${st.name} is live`);
    st.waiters.splice(0).forEach(fn => fn(true));
    return () => {
      if (st.up !== handle) return;
      st.up = null; st.ready = false;
      if (st.clients.size) setTimeout(() => startUpstream(st), 1500);
    };
  };
  // Fallback for old Shoutcast servers that answer "ICY 200 OK", which Node's HTTP parser refuses.
  const rawGo = (u, hop) => {
    const secure = u.protocol === 'https:', port = +u.port || (secure ? 443 : 80);
    const sock = (secure ? tls : net).connect({ host: u.hostname, port, servername: u.hostname });
    let buf = Buffer.alloc(0), headerDone = false, ended = null;
    sock.setTimeout(10000, () => sock.destroy(new Error('timed out')));
    sock.on(secure ? 'secureConnect' : 'connect', () => sock.write(
      `GET ${u.pathname}${u.search} HTTP/1.0\r\nHost: ${u.host}\r\nUser-Agent: ${UA}\r\nIcy-MetaData: 0\r\nAccept: */*\r\nConnection: close\r\n\r\n`));
    sock.on('data', (d) => {
      if (headerDone) return broadcast(st, d);
      buf = Buffer.concat([buf, d]);
      const end = buf.indexOf('\r\n\r\n');
      if (end < 0) { if (buf.length > 16384) sock.destroy(new Error('bad headers')); return; }
      headerDone = true;
      const head = buf.slice(0, end).toString('latin1').split('\r\n'), body = buf.slice(end + 4);
      const status = parseInt((head[0].match(/\s(\d{3})/) || [])[1], 10), headers = {};
      head.slice(1).forEach(l => { const k = l.indexOf(':'); if (k > 0) headers[l.slice(0, k).trim().toLowerCase()] = l.slice(k + 1).trim(); });
      if ([301, 302, 303, 307, 308].includes(status) && headers.location && hop < 5) { sock.destroy(); return go(new URL(headers.location, u).toString(), hop + 1); }
      if (status !== 200) { sock.destroy(); return failUp(st, 'HTTP ' + status); }
      ended = live(sock, headers['content-type']);
      if (body.length) broadcast(st, body);
    });
    sock.on('error', (e) => { if (!st.ready) failUp(st, e.message); });
    sock.on('close', () => { if (ended) ended(); });
  };
  const go = (urlStr, hop) => {
    let u;
    try { u = new URL(urlStr); } catch (e) { return failUp(st, 'bad url'); }
    const lib = u.protocol === 'https:' ? https : http;
    const req = lib.get(u, { headers: { 'User-Agent': UA, 'Icy-MetaData': '0', 'Accept': '*/*' }, timeout: 10000 }, (res) => {
      if ([301, 302, 303, 307, 308].includes(res.statusCode) && res.headers.location && hop < 5) {
        res.resume(); return go(new URL(res.headers.location, u).toString(), hop + 1);
      }
      if (res.statusCode !== 200) { res.resume(); return failUp(st, 'HTTP ' + res.statusCode); }
      const ended = live(req, res.headers['content-type']);
      res.on('data', (chunk) => broadcast(st, chunk));
      res.on('end', ended); res.on('close', ended); res.on('error', ended);
    });
    req.on('timeout', () => req.destroy(new Error('timed out')));
    req.on('error', (e) => {
      if (st.ready) return;
      if (/Parse Error|HPE_/.test(e.message + (e.code || ''))) return rawGo(u, hop);   // ICY-style reply
      failUp(st, e.message);
    });
  };
  go(st.url, 0);
}
function scheduleIdleClose(st) {
  clearTimeout(st.idleTimer);
  st.idleTimer = setTimeout(() => {
    if (!st.clients.size && st.up) { const r = st.up; st.up = null; st.ready = false; r.destroy(); }
  }, 20000);
}

app.get('/radio/stream/:id', (req, res) => {
  const st = hub.byId[req.params.id];
  if (!st) return res.status(404).end();
  if (!st.ready && st.downUntil > Date.now()) return res.status(503).end();
  clearTimeout(st.idleTimer);
  res.on('error', () => {});
  const attach = () => {
    if (res.destroyed || res.writableEnded) return;
    res.writeHead(200, { 'Content-Type': st.ctype, 'Cache-Control': 'no-store, no-transform', 'Connection': 'keep-alive',
                         'X-Accel-Buffering': 'no', 'Access-Control-Allow-Origin': '*' });
    if (st.recent.length) res.write(Buffer.concat(st.recent));   // tiny pre-roll so playback starts fast
    st.clients.add(res);
  };
  const waiter = (ok) => { if (ok) attach(); else if (!res.headersSent) res.status(502).end(); };
  req.on('close', () => {
    st.clients.delete(res);
    const k = st.waiters.indexOf(waiter); if (k >= 0) st.waiters.splice(k, 1);
    if (!st.clients.size) scheduleIdleClose(st);
  });
  if (st.ready) attach(); else { st.waiters.push(waiter); startUpstream(st); }
});
// Handy for diagnosing: open https://<your-city-domain>/radio/stations
app.get('/radio/stations', (req, res) => {
  res.json(hub.list.map(s => ({ id: s.id, name: s.name, kind: s.kind, url: s.url, live: s.ready, listeners: s.clients.size, down: s.downUntil > Date.now() })));
});

async function rbSearch(query) {
  for (const base of RB_MIRRORS) {
    try {
      const r = await fetch(`${base}/json/stations/search?${query}&hidebroken=true&order=clickcount&reverse=true&limit=80`,
        { headers: { 'User-Agent': UA }, signal: AbortSignal.timeout(8000) });
      if (r.ok) { const j = await r.json(); if (Array.isArray(j)) return j; }
    } catch (e) { /* try the next mirror */ }
  }
  return [];
}
async function refreshStations() {
  const found = [];
  const add = (name, url, kind) => {
    const id = stationId(url), nm = String(name).trim().toLowerCase();
    if (found.some(s => s.id === id || s.name.toLowerCase() === nm)) return;
    found.push(makeStation(name, url, kind));
  };
  try { // 1) your own list always comes first
    const arr = JSON.parse(fs.readFileSync(path.join(__dirname, 'stations.json'), 'utf8'));
    (Array.isArray(arr) ? arr : []).forEach(s => { if (s && /^https?:\/\//i.test(s.url || '')) add(s.name || 'Radio', s.url, s.kind || 'custom'); });
  } catch (e) { if (e.code !== 'ENOENT') console.warn('[radio] stations.json:', e.message); }
  // 2) auto-discover working Malayalam streams
  const pool = [];
  for (const q of ['language=malayalam', 'tag=malayalam', 'name=malayalam']) pool.push(...await rbSearch(q));
  const seen = new Set();
  const cands = pool.filter(s => {
    const url = s.url_resolved || s.url;
    if (!url || !/^https?:\/\//i.test(url) || s.hls === 1 || /\.(m3u8?|pls)(\?|$)/i.test(url)) return false;
    if (s.lastcheckok !== 1 || seen.has(url)) return false;
    if (s.codec && !/mp3|aac|mpeg|ogg|unknown/i.test(s.codec)) return false;
    seen.add(url); return true;
  });
  cands.sort((a, b) => (OLD_RE.test(b.name) - OLD_RE.test(a.name)) || ((b.clickcount || 0) - (a.clickcount || 0)));
  cands.slice(0, 10).forEach(s => add(s.name || 'Malayalam FM', s.url_resolved || s.url, OLD_RE.test(s.name) ? 'old songs' : 'fm'));
  // 3) last resort so the radio is never empty
  if (!found.length) add('Radio Mango 91.9 (fallback)', 'https://stream.radiomango.fm/live', 'fm');

  const next = found.map(s => hub.byId[s.id] || s);
  const curId = hub.list[radio.station] && hub.list[radio.station].id;
  const changed = next.map(s => s.id).join() !== hub.list.map(s => s.id).join();
  hub.list.filter(s => !next.includes(s)).forEach(s => { s.clients.forEach(c => c.end()); if (s.up) s.up.destroy(); });
  hub.list = next; hub.byId = Object.fromEntries(next.map(s => [s.id, s]));
  const k = next.findIndex(s => s.id === curId); radio.station = k >= 0 ? k : 0;
  console.log(`[radio] ${next.length} station(s): ` + next.map(s => s.name).join(' | '));
  if (changed) io.emit('radioState', radioPayload());
}
refreshStations().catch(e => console.error('[radio] refresh failed:', e.message));
setInterval(() => refreshStations().catch(() => {}), 6 * 60 * 60 * 1000);
const ITEMS_BY_ID = Object.fromEntries(ITEMS.map(i => [i.id, i]));
const FLOWER_IDS = ITEMS.filter(i => i.category === 'flower').map(i => i.id);



// --- Block-center coordinates, computed the exact same way the client builds
// its city grid, so job markers land on real sidewalks on both sides. ---
const BLOCK = 20, GRID = 6;
const blockCenters = [];
for (let gx = -GRID/2; gx < GRID/2; gx++) {
  for (let gz = -GRID/2; gz < GRID/2; gz++) {
    if (gx === 0 && gz === 0) continue; // center plaza, skip like the client does
    blockCenters.push({ x: gx*BLOCK + BLOCK/2, z: gz*BLOCK + BLOCK/2 });
  }
}

// --- Delivery job: one shared job at a time. First player to reach the
// pickup marker claims it; only they can complete it at the dropoff. ---
let deliveryJob = null;
function generateDeliveryJob() {
  const a = blockCenters[Math.floor(Math.random() * blockCenters.length)];
  let b = a;
  while (b === a) b = blockCenters[Math.floor(Math.random() * blockCenters.length)];
  deliveryJob = { id: 'job_' + Date.now(), pickup: a, dropoff: b, status: 'available', carrierId: null };
  return deliveryJob;
}
generateDeliveryJob();

// --- Treasure hunt: chests scattered around the city (deterministic
// positions derived the same way as delivery pickup spots, so every
// client places them identically). Rewards are randomized server-side
// at claim time since only the claimant needs to see the exact result. ---
const treasureSpots = [];
blockCenters.forEach((b, i) => {
  if (i % 3 === 0) treasureSpots.push({ id: 'chest_' + i, x: b.x + 2.6, z: b.z - 2.6 });
});
let claimedTreasures = new Set();

const TREASURE_REWARDS = [
  { type: 'balloons', amount: 20, weight: 40 },
  { type: 'balloons', amount: 60, weight: 15 },
  { type: 'item', itemId: 'rose', weight: 12 },
  { type: 'item', itemId: 'party_hat', weight: 10 },
  { type: 'item', itemId: 'sunglasses', weight: 8 },
  { type: 'collectible', name: 'Golden Feather', weight: 8 },
  { type: 'collectible', name: 'Lucky Coin', weight: 7 },
];
function pickWeighted(list) {
  const total = list.reduce((s, x) => s + x.weight, 0);
  let r = Math.random() * total;
  for (const x of list) { r -= x.weight; if (r <= 0) return x; }
  return list[list.length - 1];
}

// Chests respawn periodically so the hunt stays alive as people find them.
setInterval(() => {
  claimedTreasures = new Set();
  io.emit('treasureSpots', treasureSpots);
}, 4 * 60 * 1000);

async function creditRealAccount(uid, amount) {
  try {
    const res = await fetch(AWARD_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ uid: String(uid), amount: String(amount), secret: SHARED_SECRET })
    });
    if (!res.ok) console.error('award_balloons.php responded with', res.status);
  } catch (err) {
    console.error('Failed to reach award_balloons.php:', err.message);
  }
}

// Roles: real players can run the chayakkada or the milk stall (one player per role). While a role is
// empty the client shows a stand-in character; as soon as a player takes it the stand-in disappears.
const roles = { chayakkaran: null, karavakkari: null };
function roleStatePayload() {
  const o = {};
  for (const r of Object.keys(roles)) o[r] = roles[r] && players[roles[r]] ? { id: roles[r], name: players[roles[r]].name } : null;
  return o;
}

// =====================================================================
// PRIVATE VOICE ROOMS
//  - a member creates a room at a place -> the server makes a Room ID + 6-digit passcode
//  - partners find the room by ID (or open the share link) and must enter the passcode
//  - each room is its own LiveKit room, so nobody outside can hear it; max people is chosen by the host (2-5)
//  - admins can look at the live list and join VISIBLY as a listen-only "Moderator" (players are told),
//    and every event is written to the DB via ajax/private_room_log.php. Audio is never recorded.
// =====================================================================
const PRIVATE_PLACES = {           // <<< ADD MORE AREAS HERE:  id: { name: 'Shown name', emoji: '🌴' }
  happycup:  { name: 'Happy Cup',      emoji: '☕' },
  sarovaram: { name: 'Sarovaram Park', emoji: '🌳' },
  beach:     { name: 'Beach',          emoji: '🏖️' },
  hugamug:   { name: 'Hug a Mug',      emoji: '🫶' }
};
const PRIVATE_MAX = 5;                         // most people allowed in one room (including the host)
const PRIVATE_IDLE_MS = 30 * 60 * 1000;        // a room with fewer than 2 people for 30 minutes is closed
const PRIVATE_LOG_ENDPOINT = process.env.PRIVATE_LOG_ENDPOINT || 'https://neyyappam.com/ajax/private_room_log.php';
const ROOM_ID_CHARS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';   // no 0/O/1/I so IDs are easy to read out
// Each private room is its own "space": a themed area far from the city. Players in one space never see, hear or message players in another.
const PRIVATE_ORIGIN = { x: 5000, z: 5000 };   // must match the client; far outside the city so nothing overlaps
const publicPositions = {};                     // sid -> where to put a player back when they leave a private room
const spaceRoom = (p) => 'space:' + ((p && p.space) || 'public');
function enterSpace(sid, space, place) {
  const p = players[sid]; const sock = io.sockets.sockets.get(sid);
  if (!p || !sock) return;
  const old = p.space || 'public';
  if (old === space) return;
  sock.to('space:' + old).emit('playerLeft', sid);            // vanish from the old space
  sock.leave('space:' + old); sock.join('space:' + space);
  let spawn;
  if (space === 'public') {
    spawn = publicPositions[sid] || { x: 0, y: 0, z: 0, rotY: 0 };
    delete publicPositions[sid];
  } else {
    if (old === 'public') publicPositions[sid] = { x: p.x, y: p.y, z: p.z, rotY: p.rotY };
    const a = Math.random() * Math.PI * 2;
    spawn = { x: PRIVATE_ORIGIN.x + Math.cos(a) * 3, y: 0, z: PRIVATE_ORIGIN.z + Math.sin(a) * 3, rotY: 0 };
  }
  p.x = spawn.x; p.y = spawn.y; p.z = spawn.z; p.rotY = spawn.rotY;
  p.space = space;
  const list = {};
  Object.values(players).forEach(q => { if ((q.space || 'public') === space) list[q.id] = q; });
  sock.emit('spaceChanged', { place: space === 'public' ? null : place, spawn });
  sock.emit('currentPlayers', list);                           // only the people in the new space
  sock.to('space:' + space).emit('playerJoined', p);
}
const privateRooms = {};                       // ID -> { id, place, passcode, capacity, hostSid, members:Set, info:Map(sid->{uid,name,username}), startedAt, aloneSince }
const privAttempts = new Map();                // "uid:roomId" -> { n, until }   (wrong-passcode lockout)

function newRoomId() { for (;;) { let id = ''; for (let i = 0; i < 6; i++) id += ROOM_ID_CHARS[crypto.randomInt(ROOM_ID_CHARS.length)]; if (!privateRooms[id]) return id; } }
function newPasscode() { return String(crypto.randomInt(0, 1000000)).padStart(6, '0'); }
function normRoomId(x) { return String(x || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8); }
function logPrivate(ev) {
  try {
    fetch(PRIVATE_LOG_ENDPOINT, { method: 'POST', body: new URLSearchParams(Object.assign({ secret: SHARED_SECRET }, ev)) })
      .catch(e => console.error('[private] log failed:', e.message));
  } catch (e) { console.error('[private] log failed:', e.message); }
}
function roomOfSid(sid) { return Object.values(privateRooms).find(r => r.members.has(sid)) || null; }
function privatePublic(r) {
  const place = PRIVATE_PLACES[r.place] || { name: r.place, emoji: '🔒' };
  return {
    id: r.id, place: r.place, placeName: place.name, emoji: place.emoji, capacity: r.capacity, startedAt: r.startedAt, hostSid: r.hostSid,
    members: [...r.members].map(sid => ({ sid, name: r.info.get(sid).name, username: r.info.get(sid).username }))
  };
}
function privateBroadcast(r) { const pub = privatePublic(r); r.members.forEach(sid => io.to(sid).emit('privateRoomState', pub)); }
function closePrivateRoom(r, reason, adminId) {
  if (!privateRooms[r.id]) return;
  delete privateRooms[r.id];
  r.members.forEach(sid => io.to(sid).emit('privateRoomClosed', { reason }));
  [...r.members].forEach(sid => enterSpace(sid, 'public'));    // everyone goes back to the city
  logPrivate({ event: 'room_end', room_id: r.id, place: r.place, admin_id: adminId || '', detail: reason });
}
function leavePrivate(sid, reason) {
  const r = roomOfSid(sid); if (!r) return;
  const who = r.info.get(sid) || {};
  r.members.delete(sid);
  if (r.hostSid === sid && r.members.size) r.hostSid = [...r.members][0];   // someone else becomes host
  logPrivate({ event: 'member_leave', room_id: r.id, place: r.place, user_id: who.uid || '', detail: reason || 'left' });
  enterSpace(sid, 'public');                                   // back to the city (no-op if the socket is already gone)
  if (r.members.size === 0) closePrivateRoom(r, 'ended');
  else privateBroadcast(r);
}
function privateInfoForAdmin(r) {
  const pub = privatePublic(r);
  return { id: r.id, place: r.place, placeName: pub.placeName, capacity: r.capacity, startedAt: r.startedAt,
           members: [...r.members].map(sid => ({ uid: r.info.get(sid).uid, name: r.info.get(sid).name, username: r.info.get(sid).username })) };
}
// close rooms that have sat with fewer than 2 people for too long
setInterval(() => {
  const now = Date.now();
  Object.values(privateRooms).forEach(r => {
    if (r.members.size >= 2) { r.aloneSince = null; return; }
    if (!r.aloneSince) { r.aloneSince = now; return; }
    if (now - r.aloneSince > PRIVATE_IDLE_MS) closePrivateRoom(r, 'expired');
  });
  privAttempts.forEach((v, k) => { if (v.until && v.until < now) privAttempts.delete(k); });
}, 60 * 1000);

// ---- admin API (called only by your PHP admin page, with the shared secret) ----
function adminAuth(req, res, next) {
  const got = Buffer.from(String(req.headers['x-city-secret'] || ''));
  const want = Buffer.from(String(SHARED_SECRET || ''));
  if (!SHARED_SECRET || SHARED_SECRET === 'change-this-to-a-long-random-string' || got.length !== want.length || !crypto.timingSafeEqual(got, want)) {
    return res.status(403).json({ error: 'forbidden' });
  }
  next();
}
app.use('/admin', express.json(), adminAuth);
app.get('/admin/private-rooms', (req, res) => res.json({ rooms: Object.values(privateRooms).map(privateInfoForAdmin) }));
app.post('/admin/private-rooms/:id/join', async (req, res) => {
  const r = privateRooms[req.params.id];
  if (!r) return res.status(404).json({ error: 'That room has ended.' });
  const { LIVEKIT_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET } = process.env;
  if (!LIVEKIT_URL || !LIVEKIT_API_KEY || !LIVEKIT_API_SECRET) return res.status(503).json({ error: 'LiveKit is not configured.' });
  const adminId = String((req.body && req.body.adminId) || '0').replace(/[^\w-]/g, '').slice(0, 20);
  try {
    // identity starts with "mod-": the players' game client sees this and shows "A moderator joined" - listening is never secret.
    const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, { identity: 'mod-' + adminId + '-' + crypto.randomBytes(3).toString('hex'), name: 'Moderator', ttl: '30m' });
    at.addGrant({ roomJoin: true, room: 'private-' + r.id, canPublish: false, canSubscribe: true });
    logPrivate({ event: 'moderator_join', room_id: r.id, place: r.place, admin_id: adminId, detail: String((req.body && req.body.adminName) || '').slice(0, 80) });
    res.json({ token: await at.toJwt(), url: LIVEKIT_URL, room: 'private-' + r.id });
  } catch (e) { console.error('[private] mod token failed:', e.message); res.status(500).json({ error: 'Could not create a moderator token.' }); }
});
app.post('/admin/private-rooms/:id/leave', (req, res) => {
  const r = privateRooms[req.params.id];
  logPrivate({ event: 'moderator_leave', room_id: req.params.id, place: r ? r.place : '', admin_id: String((req.body && req.body.adminId) || '').slice(0, 20) });
  res.json({ ok: true });
});
app.post('/admin/private-rooms/:id/close', (req, res) => {
  const r = privateRooms[req.params.id];
  if (!r) return res.status(404).json({ error: 'That room has ended.' });
  closePrivateRoom(r, 'closed_by_admin', String((req.body && req.body.adminId) || '').slice(0, 20));
  res.json({ ok: true });
});

io.on('connection', (socket) => {
  console.log('connected:', socket.id);
  socket.emit('roleState', roleStatePayload());

  socket.on('join', (data) => {
    const wantRole = ['chayakkaran', 'karavakkari'].includes(data.role) ? data.role : null;
    const role = wantRole && !(roles[wantRole] && players[roles[wantRole]]) ? wantRole : null;
    const auth = verifyCityToken(data.token);     // null => guest
    players[socket.id] = {
      id: socket.id,
      name: auth ? auth.name : (String(data.name || 'Guest').slice(0, 24)),
      username: auth ? auth.username : null,
      gender: role === 'chayakkaran' ? 'male' : role === 'karavakkari' ? 'female' : (['male', 'female', 'other'].includes(data.gender) ? data.gender : 'other'),
      role,
      noKiss: false,
      kissesGiven: 0,
      outfitColor: /^#?[0-9a-fA-F]{6}$/.test(data.outfitColor || '') ? data.outfitColor : null,
      hairStyle: ['short', 'pony', 'bandana'].includes(data.hairStyle) ? data.hairStyle : 'short',
      x: 0, y: 0, z: 0, rotY: 0,
      uid: auth ? auth.uid : null, // real logged-in user id (from a verified token), or null for a guest
      balloons: auth ? auth.balloons : 0,
      drivingCarId: null,
      inventory: {},        // itemId -> count
      flowersGiven: 0,
      flowersReceived: 0,
      treasuresFound: 0,
      collectibles: []
    };
    players[socket.id].space = 'public';
    socket.join('space:public');
    if (role) roles[role] = socket.id;
    socket.emit('roleResult', { role, denied: !!wantRole && !role });
    socket.emit('authState', { loggedIn: !!auth, name: auth ? auth.name : null, username: auth ? auth.username : null, balloons: players[socket.id].balloons });
    io.emit('roleState', roleStatePayload());
    socket.emit('currentPlayers', Object.fromEntries(Object.entries(players).filter(([, q]) => (q.space || 'public') === 'public')));
    socket.emit('currentCars', occupiedCars); // let the newcomer know which cars are already taken
    socket.emit('deliveryUpdated', deliveryJob);
    socket.emit('shopCatalog', ITEMS);
    socket.emit('radioState', radioPayload());
    socket.emit('treasureSpots', treasureSpots.filter(t => !claimedTreasures.has(t.id)));
    socket.to('space:public').emit('playerJoined', players[socket.id]);
  });

  // A guest logs in through the popup (or the token is refreshed): upgrade this connection without rejoining.
  socket.on('auth', ({ token } = {}) => {
    const p = players[socket.id]; if (!p) return;
    const a = verifyCityToken(token);
    if (!a) { socket.emit('authState', { loggedIn: false, error: 'bad_token' }); return; }
    const wasGuest = !p.uid;
    p.uid = a.uid; p.name = a.name; p.username = a.username;
    if (wasGuest) p.balloons = a.balloons;      // keep the live balance on a token refresh
    socket.emit('authState', { loggedIn: true, name: p.name, username: p.username, balloons: p.balloons, upgraded: wasGuest });
    io.to(spaceRoom(p)).emit('playerRenamed', { id: socket.id, name: p.name, username: p.username });
  });
  socket.on('deauth', () => {
    const p = players[socket.id]; if (!p) return;
    leavePrivate(socket.id, 'logout');
    p.uid = null; p.username = null; p.balloons = 0; p.name = 'Guest';
    socket.emit('authState', { loggedIn: false, loggedOut: true, balloons: 0 });
    io.to(spaceRoom(p)).emit('playerRenamed', { id: socket.id, name: p.name });
  });

  // Change look in game settings (the NAME can never be changed here - it comes from the account).
  socket.on('updateLook', (d = {}) => {
    const p = players[socket.id]; if (!p) return;
    if (!p.role && ['male', 'female', 'other'].includes(d.gender)) p.gender = d.gender;   // tea-shop roles keep their fixed gender
    if (/^#?[0-9a-fA-F]{6}$/.test(d.outfitColor || '')) p.outfitColor = d.outfitColor;
    if (['short', 'pony', 'bandana'].includes(d.hairStyle)) p.hairStyle = d.hairStyle;
    socket.to(spaceRoom(p)).emit('playerLook', { id: socket.id, gender: p.gender, outfitColor: p.outfitColor, hairStyle: p.hairStyle });
  });


  // ---------------- Private voice rooms ----------------
  socket.on('privateConfig', (cb) => {
    if (typeof cb !== 'function') return;
    cb({ places: Object.entries(PRIVATE_PLACES).map(([id, v]) => ({ id, name: v.name, emoji: v.emoji })), max: PRIVATE_MAX });
  });

  // Create: server picks the Room ID and the passcode. Only the creator is told the passcode.
  socket.on('privateCreate', (d = {}, cb) => {
    cb = typeof cb === 'function' ? cb : () => {};
    const p = players[socket.id];
    if (!p || !p.uid) return cb({ ok: false, error: 'Log in to create a private room.' });
    if (roomOfSid(socket.id)) return cb({ ok: false, error: 'You are already in a private room. Leave it first.' });
    if (p.drivingCarId) return cb({ ok: false, error: 'Get out of your vehicle first.' });
    if (!PRIVATE_PLACES[d.place]) return cb({ ok: false, error: 'Pick a place first.' });
    const now = Date.now();
    if (p.lastPrivCreate && now - p.lastPrivCreate < 3000) return cb({ ok: false, error: 'Please wait a moment.' });
    p.lastPrivCreate = now;
    const capacity = Math.max(2, Math.min(PRIVATE_MAX, parseInt(d.capacity, 10) || 2));
    const id = newRoomId();
    const r = { id, place: d.place, passcode: newPasscode(), capacity, hostSid: socket.id, members: new Set([socket.id]),
                info: new Map([[socket.id, { uid: p.uid, name: p.name, username: p.username }]]), startedAt: now, aloneSince: now };
    privateRooms[id] = r;
    logPrivate({ event: 'room_start', room_id: id, place: r.place, user_id: p.uid, detail: 'capacity ' + capacity });
    privateBroadcast(r);
    enterSpace(socket.id, 'priv:' + id, r.place);
    const pl = PRIVATE_PLACES[r.place];
    cb({ ok: true, roomId: id, passcode: r.passcode, capacity, place: r.place, placeName: pl.name, emoji: pl.emoji });
  });

  // Search by Room ID: tells you the place and how full it is (never who is inside, never the passcode).
  socket.on('privateLookup', (d = {}, cb) => {
    cb = typeof cb === 'function' ? cb : () => {};
    const p = players[socket.id];
    if (!p || !p.uid) return cb({ ok: false, error: 'Log in to search for a room.' });
    const now = Date.now();
    p.lookups = (p.lookups || []).filter(t => now - t < 60000);
    if (p.lookups.length >= 12) return cb({ ok: false, error: 'Too many searches. Wait a minute and try again.' });
    p.lookups.push(now);
    const r = privateRooms[normRoomId(d.roomId)];
    if (!r) return cb({ ok: false, error: 'No open room with that ID.' });
    const pl = PRIVATE_PLACES[r.place] || { name: r.place, emoji: '🔒' };
    cb({ ok: true, roomId: r.id, placeName: pl.name, emoji: pl.emoji, count: r.members.size, capacity: r.capacity, full: r.members.size >= r.capacity });
  });

  // Join: Room ID + passcode. 5 wrong passcodes lock that member out of that room for 10 minutes.
  socket.on('privateJoin', (d = {}, cb) => {
    cb = typeof cb === 'function' ? cb : () => {};
    const p = players[socket.id];
    if (!p || !p.uid) return cb({ ok: false, error: 'Log in to join a private room.' });
    if (roomOfSid(socket.id)) return cb({ ok: false, error: 'You are already in a private room. Leave it first.' });
    if (p.drivingCarId) return cb({ ok: false, error: 'Get out of your vehicle first.' });
    const id = normRoomId(d.roomId); const r = privateRooms[id];
    if (!r) return cb({ ok: false, error: 'No open room with that ID.' });
    const key = p.uid + ':' + id; const now = Date.now();
    const a = privAttempts.get(key) || { n: 0, until: 0 };
    if (a.until > now) return cb({ ok: false, error: 'Too many wrong passcodes. Try again in ' + Math.ceil((a.until - now) / 60000) + ' min.' });
    const given = Buffer.from(String(d.passcode || '').replace(/\D/g, '').padStart(6, '0').slice(0, 6));
    const want = Buffer.from(r.passcode);
    if (given.length !== want.length || !crypto.timingSafeEqual(given, want)) {
      a.n += 1; if (a.n >= 5) { a.until = now + 10 * 60 * 1000; a.n = 0; }
      privAttempts.set(key, a);
      return cb({ ok: false, error: 'Wrong passcode.' });
    }
    if (r.members.size >= r.capacity) return cb({ ok: false, error: 'This room is full (' + r.capacity + ' people).' });
    privAttempts.delete(key);
    r.members.add(socket.id);
    r.info.set(socket.id, { uid: p.uid, name: p.name, username: p.username });
    logPrivate({ event: 'member_join', room_id: r.id, place: r.place, user_id: p.uid });
    privateBroadcast(r);
    enterSpace(socket.id, 'priv:' + r.id, r.place);
    cb({ ok: true, roomId: r.id });
  });

  // Each member gets a token for ONLY their own room.
  socket.on('privateJoinToken', async (cb) => {
    cb = typeof cb === 'function' ? cb : () => {};
    const r = roomOfSid(socket.id); const p = players[socket.id];
    if (!r || !p || !p.uid) return cb({ ok: false, error: 'You are not in a private room.' });
    const { LIVEKIT_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET } = process.env;
    if (!LIVEKIT_URL || !LIVEKIT_API_KEY || !LIVEKIT_API_SECRET) return cb({ ok: false, error: 'Voice chat is not configured on the server yet.' });
    try {
      const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, { identity: 'u' + p.uid + '-' + socket.id.slice(0, 8), name: p.name, ttl: '2h' });
      at.addGrant({ roomJoin: true, room: 'private-' + r.id, canPublish: true, canSubscribe: true });
      cb({ ok: true, token: await at.toJwt(), url: LIVEKIT_URL, room: 'private-' + r.id });
    } catch (e) { console.error('[private] token failed:', e.message); cb({ ok: false, error: 'Could not create a voice token.' }); }
  });

  socket.on('privateLeave', () => leavePrivate(socket.id, 'left'));

  socket.on('move', (pos) => {
    const p = players[socket.id];
    if (!p) return;
    p.x = pos.x; p.y = pos.y; p.z = pos.z; p.rotY = pos.rotY;
    socket.to(spaceRoom(p)).emit('playerMoved', p);
  });

  // --- Vehicles ---
  socket.on('enterCar', (carId) => {
    const p = players[socket.id];
    if (!p || p.drivingCarId) return; // already driving something
    if (occupiedCars[carId]) {
      socket.emit('carDenied', { carId }); // someone else already got there first
      return;
    }
    occupiedCars[carId] = socket.id;
    p.drivingCarId = carId;
    io.emit('carEntered', { carId, driverId: socket.id, driverName: p.name });
  });

  socket.on('driveCar', (data) => {
    const p = players[socket.id];
    if (!p || p.drivingCarId !== data.carId) return; // ignore spoofed updates
    socket.broadcast.emit('carMoved', { carId: data.carId, x: data.x, y: data.y, z: data.z, rotY: data.rotY });
  });

  socket.on('exitCar', (data) => {
    const p = players[socket.id];
    if (!p || p.drivingCarId !== data.carId) return;
    delete occupiedCars[data.carId];
    p.drivingCarId = null;
    io.emit('carExited', { carId: data.carId, driverId: socket.id, x: data.x, y: data.y, z: data.z, rotY: data.rotY });
  });

  socket.on('collectBalloon', (pickupId) => {
    const p = players[socket.id];
    if (!p) return;
    if (claimedPickups.has(pickupId)) return; // already taken
    claimedPickups.add(pickupId);
    const value = balloonValueForId(pickupId);
    p.balloons += value;
    io.emit('balloonCollected', { pickupId, by: socket.id, balloons: p.balloons, value });

    // Only real accounts get this written back to MySQL
    if (p.uid) creditRealAccount(p.uid, value);
  });

  // --- Shop: buy a cosmetic/social item with balloons ---
  socket.on('buyItem', (itemId) => {
    const p = players[socket.id];
    const item = ITEMS.find(i => i.id === itemId);
    if (!p || !item) return;
    const counter = item.shop === 'tea' ? TEA_POS : item.shop === 'milk' ? MILK_POS : SHOP_POS;
    if (Math.hypot(p.x - counter.x, p.z - counter.z) > SHOP_RANGE) { socket.emit('purchaseDenied', { itemId, reason: 'far' }); return; }
    if (p.balloons < item.price) { socket.emit('purchaseDenied', { itemId, reason: 'insufficient' }); return; }
    p.balloons -= item.price;
    p.inventory[itemId] = (p.inventory[itemId] || 0) + 1;
    socket.emit('purchaseOk', { itemId, balloons: p.balloons, inventory: p.inventory });
    // If a real player is running this stall, they earn half the price as a tip.
    const holderRole = item.shop === 'tea' ? 'chayakkaran' : item.shop === 'milk' ? 'karavakkari' : null;
    const hid = holderRole && roles[holderRole];
    if (hid && hid !== socket.id && players[hid]) {
      const tip = Math.max(1, Math.floor(item.price / 2));
      players[hid].balloons += tip;
      io.to(hid).emit('commission', { amount: tip, balloons: players[hid].balloons, from: p.name, itemId });
      if (players[hid].uid) creditRealAccount(players[hid].uid, tip);
    }
    if (p.uid) creditRealAccount(p.uid, -item.price); // debit the real account's ledger too
  });

  // --- Gift a carried item to a nearby player ---
  socket.on('giftItem', ({ targetId, itemId }) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || !p.inventory[itemId] || t.space !== p.space) return;
    if (Math.hypot(p.x - t.x, p.z - t.z) > 3.5) { socket.emit('giftDenied', { reason: 'range' }); return; }
    p.inventory[itemId] -= 1;
    if (p.inventory[itemId] <= 0) delete p.inventory[itemId];
    t.inventory[itemId] = (t.inventory[itemId] || 0) + 1;
    socket.emit('inventoryUpdated', p.inventory);
    io.to(targetId).emit('inventoryUpdated', t.inventory);
    io.to(targetId).emit('giftReceived', { from: p.name, fromId: socket.id, itemId });
  });

  // --- Give a flower to a nearby player (consumes one from inventory) ---
  socket.on('giveFlower', ({ targetId }) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || t.space !== p.space) return;
    const flowerId = FLOWER_IDS.find(f => p.inventory[f] > 0);
    if (!flowerId) { socket.emit('flowerDenied', { reason: 'none' }); return; }
    if (Math.hypot(p.x - t.x, p.z - t.z) > 3.5) { socket.emit('flowerDenied', { reason: 'range' }); return; }
    p.inventory[flowerId] -= 1;
    if (p.inventory[flowerId] <= 0) delete p.inventory[flowerId];
    p.flowersGiven += 1;
    t.flowersReceived += 1;
    socket.emit('inventoryUpdated', p.inventory);
    io.to(targetId).emit('flowerReceived', { from: p.name, fromId: socket.id, itemId: flowerId });
  });

  // --- Proposals: propose to a nearby player, they accept or reject ---
  socket.on('sendProposal', ({ targetId }) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || t.space !== p.space) return;
    if (Math.hypot(p.x - t.x, p.z - t.z) > 3.5) { socket.emit('proposalDenied', { reason: 'range' }); return; }
    io.to(targetId).emit('proposalReceived', { from: p.name, fromId: socket.id });
  });
  socket.on('respondProposal', ({ proposerId, accepted }) => {
    const p = players[socket.id];
    if (!p || !players[proposerId]) return;
    io.to(proposerId).emit('proposalResult', { by: p.name, accepted });
    if (accepted) {
      io.emit('coupleFormed', { aId: proposerId, bId: socket.id, aName: players[proposerId].name, bName: p.name });
    }
  });

  // --- Treasure hunt: open a chest for a randomized reward ---
  socket.on('claimTreasure', (chestId) => {
    const p = players[socket.id];
    const spot = treasureSpots.find(t => t.id === chestId);
    if (!p || !spot || claimedTreasures.has(chestId)) return;
    if (Math.hypot(p.x - spot.x, p.z - spot.z) > 2.5) return; // must actually be there
    claimedTreasures.add(chestId);
    const reward = pickWeighted(TREASURE_REWARDS);
    let detail;
    if (reward.type === 'balloons') {
      p.balloons += reward.amount;
      if (p.uid) creditRealAccount(p.uid, reward.amount);
      detail = { type: 'balloons', amount: reward.amount };
    } else if (reward.type === 'item') {
      p.inventory[reward.itemId] = (p.inventory[reward.itemId] || 0) + 1;
      detail = { type: 'item', itemId: reward.itemId };
    } else {
      p.collectibles.push(reward.name);
      detail = { type: 'collectible', name: reward.name };
    }
    p.treasuresFound += 1;
    io.emit('treasureOpened', { chestId }); // remove the chest for everyone
    socket.emit('treasureReward', { chestId, reward: detail, balloons: p.balloons, inventory: p.inventory });
  });

  // --- Delivery job ---
  socket.on('pickupDelivery', (jobId) => {
    const p = players[socket.id];
    if (!p || !deliveryJob || deliveryJob.id !== jobId || deliveryJob.status !== 'available') return;
    deliveryJob.status = 'inProgress';
    deliveryJob.carrierId = socket.id;
    io.emit('deliveryUpdated', deliveryJob);
  });

  socket.on('completeDelivery', (jobId) => {
    const p = players[socket.id];
    if (!p || !deliveryJob || deliveryJob.id !== jobId) return;
    if (deliveryJob.status !== 'inProgress' || deliveryJob.carrierId !== socket.id) return;
    p.balloons += 30;
    io.emit('balloonCollected', { pickupId: null, by: socket.id, balloons: p.balloons });
    if (p.uid) creditRealAccount(p.uid, 30);
    generateDeliveryJob();
    io.emit('deliveryUpdated', deliveryJob);
  });

  // --- Chayakkada radio: anyone standing at the counter can switch it on/off or change station ---
  socket.on('radioSet', ({ on, station } = {}) => {
    const p = players[socket.id];
    if (!p) return;
    if (Math.hypot(p.x - RADIO_POS.x, p.z - RADIO_POS.z) > RADIO_RANGE) return;
    const now = Date.now();
    if (p.lastRadio && now - p.lastRadio < 500) return; // light throttle
    p.lastRadio = now;
    const n = hub.list.length;
    const st = Number.isInteger(station) && station >= 0 && station < n ? station : radio.station;
    radio = { on: !!on, station: st };
    io.emit('radioState', { ...radioPayload(), by: p.name });
  });

  // A listener's browser could not play the current station several times in a row: skip it.
  socket.on('radioFail', ({ station } = {}) => {
    const p = players[socket.id];
    if (!p) return;
    const now = Date.now();
    if (p.lastFail && now - p.lastFail < 10000) return;
    p.lastFail = now;
    const st = hub.list[radio.station];
    if (!st || Number(station) !== radio.station) return;
    st.downUntil = now + 2 * 60 * 1000;
    hubStationDown(st);
  });

  // The kaalavandi driver calls the oxen: everyone nearby hears the moo
  socket.on('cartCall', ({ carId } = {}) => {
    const p = players[socket.id];
    if (!p || typeof carId !== 'string' || !carId.startsWith('cart_') || p.drivingCarId !== carId) return;
    const now = Date.now();
    if (p.lastCall && now - p.lastCall < 1500) return;
    p.lastCall = now;
    io.emit('cartCall', { carId });
  });

  // --- Emotes (dance / laugh / aiyyo) are shown to everyone ---
  socket.on('emote', ({ type } = {}) => {
    const p = players[socket.id];
    if (!p || !['dance', 'laugh', 'aiyyo'].includes(type)) return;
    const now = Date.now();
    if (p.lastEmote && now - p.lastEmote < 1500) return;
    p.lastEmote = now;
    io.to(spaceRoom(p)).emit('emote', { id: socket.id, type });
  });

  // --- Kisses: a short, consensual animation. Needs to be close, has a cooldown, and anyone can opt out. ---
  socket.on('kiss', ({ targetId } = {}) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || targetId === socket.id || t.space !== p.space) return;
    const now = Date.now();
    if (p.lastKiss && now - p.lastKiss < 4000) { socket.emit('kissDenied', { reason: 'cooldown' }); return; }
    if (Math.hypot(p.x - t.x, p.z - t.z) > 3.6) { socket.emit('kissDenied', { reason: 'range' }); return; }
    if (t.noKiss) { socket.emit('kissDenied', { reason: 'blocked' }); return; }
    p.lastKiss = now; p.kissesGiven = (p.kissesGiven || 0) + 1;
    io.to(spaceRoom(p)).emit('kissFx', { fromId: socket.id, toId: targetId });
    io.to(targetId).emit('kissReceived', { from: p.name, fromId: socket.id });
  });
  socket.on('setNoKiss', (v) => { const p = players[socket.id]; if (p) p.noKiss = !!v; });

  socket.on('chatMessage', (text) => {
    const p = players[socket.id];
    if (!p || !text) return;
    if (!p.uid) { socket.emit('authRequired', { feature: 'chat' }); return; }   // chat is for logged-in members
    const now = Date.now();
    if (p.lastChat && now - p.lastChat < 600) return;                          // light flood control
    p.lastChat = now;
    io.to(spaceRoom(p)).emit('chatMessage', { id: socket.id, name: p.name, text: String(text).slice(0, 200) });   // chat stays inside the player's space
  });

  socket.on('voice-offer', ({ target, offer }) => io.to(target).emit('voice-offer', { from: socket.id, offer }));
  socket.on('voice-answer', ({ target, answer }) => io.to(target).emit('voice-answer', { from: socket.id, answer }));
  socket.on('voice-ice', ({ target, candidate }) => io.to(target).emit('voice-ice', { from: socket.id, candidate }));

  socket.on('disconnect', () => {
    leavePrivate(socket.id, 'disconnected');
    const p = players[socket.id];
    if (p && p.drivingCarId) {
      delete occupiedCars[p.drivingCarId];
      io.emit('carFreed', { carId: p.drivingCarId }); // car stays parked wherever it was left
    }
    if (deliveryJob && deliveryJob.carrierId === socket.id) {
      deliveryJob.status = 'available';
      deliveryJob.carrierId = null;
      io.emit('deliveryUpdated', deliveryJob);
    }
    for (const r of Object.keys(roles)) if (roles[r] === socket.id) roles[r] = null;
    const leftRoom = spaceRoom(p);
    delete players[socket.id]; delete publicPositions[socket.id];
    io.to(leftRoom).emit('playerLeft', socket.id);
    io.emit('roleState', roleStatePayload());
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => console.log(`Neyyappam City running on port ${PORT}`));
