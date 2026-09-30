// server.js — real-time positions, chat, voice signaling, and balloon pickups.
//
// Balloon crediting: if a player joined with a real `uid` (passed from your
// PHP embed page for logged-in users), collected balloons are POSTed to your
// PHP endpoint (award_balloons.php) so they land in the real `users.balloons`
// column and `balloon_transactions` ledger. Guests (no uid) just get a
// session-only counter that resets when they leave — never touches the DB.

const express = require('express');
const http = require('http');
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
    if (filePath.endsWith('.php')) res.setHeader('Content-Type', 'text/html; charset=utf-8');
  }
}));

// --- Voice chat: issues a LiveKit access token. The client (city_world.php) calls
// /livekit-token and expects JSON { token, url }. Set these env vars on the server:
//   wss://neyyappam-city-36cwtto5.livekit.cloud         e.g. wss://your-project.livekit.cloud
//   APIgb7j67qobq6H     from your LiveKit project settings
//   ••••••••••••••••••••••••••••••••  from your LiveKit project settings
const { AccessToken } = require('livekit-server-sdk');
app.get('/livekit-token', async (req, res) => {
  const { LIVEKIT_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET } = process.env;
  if (!LIVEKIT_URL || !LIVEKIT_API_KEY || !LIVEKIT_API_SECRET) {
    return res.status(503).json({ error: 'Voice chat is not configured on the server yet (missing LIVEKIT_* settings).' });
  }
  try {
    const identity = String(req.query.identity || '').slice(0, 64) || 'guest-' + Math.random().toString(36).slice(2, 10);
    const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, { identity, ttl: '2h' });
    at.addGrant({ roomJoin: true, room: 'neyyappam-city', canPublish: true, canSubscribe: true });
    res.json({ token: await at.toJwt(), url: LIVEKIT_URL });
  } catch (err) {
    console.error('livekit-token failed:', err.message);
    res.status(500).json({ error: 'Could not create a voice token.' });
  }
});

app.get(['/', '/city_world.php'], (req, res) => {
  res.type('html');
  res.sendFile(__dirname + '/public/city_world.php');
});

// --- Config for talking back to your PHP site ---
const AWARD_ENDPOINT = process.env.AWARD_ENDPOINT || 'https://neyyappam.com/ajax/award_balloons.php';
const SHARED_SECRET = process.env.CITY_SHARED_SECRET || 'change-this-to-a-long-random-string';

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
// Shared radio: one on/off + station index for the whole town. The client owns the station list
// (STATIONS in city_world.php) and plays the live stream locally, so everyone hears the same broadcast.
let radio = { on: true, station: 0 };
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

io.on('connection', (socket) => {
  console.log('connected:', socket.id);

  socket.on('join', (data) => {
    players[socket.id] = {
      id: socket.id,
      name: (data.name || 'Guest').slice(0, 24),
      gender: ['male', 'female', 'other'].includes(data.gender) ? data.gender : 'other',
      outfitColor: /^#?[0-9a-fA-F]{6}$/.test(data.outfitColor || '') ? data.outfitColor : null,
      hairStyle: ['short', 'pony', 'bandana'].includes(data.hairStyle) ? data.hairStyle : 'short',
      x: 0, y: 0, z: 0, rotY: 0,
      uid: data.uid || null, // real logged-in user id, or null for a guest
      balloons: Number.isFinite(data.startingBalloons) ? data.startingBalloons : 0,
      drivingCarId: null,
      inventory: {},        // itemId -> count
      flowersGiven: 0,
      flowersReceived: 0,
      treasuresFound: 0,
      collectibles: []
    };
    socket.emit('currentPlayers', players);
    socket.emit('currentCars', occupiedCars); // let the newcomer know which cars are already taken
    socket.emit('deliveryUpdated', deliveryJob);
    socket.emit('shopCatalog', ITEMS);
    socket.emit('radioState', radio);
    socket.emit('treasureSpots', treasureSpots.filter(t => !claimedTreasures.has(t.id)));
    socket.broadcast.emit('playerJoined', players[socket.id]);
  });

  socket.on('move', (pos) => {
    const p = players[socket.id];
    if (!p) return;
    p.x = pos.x; p.y = pos.y; p.z = pos.z; p.rotY = pos.rotY;
    socket.broadcast.emit('playerMoved', p);
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
    const counter = item.shop === 'tea' ? TEA_POS : SHOP_POS;
    if (Math.hypot(p.x - counter.x, p.z - counter.z) > SHOP_RANGE) { socket.emit('purchaseDenied', { itemId, reason: 'far' }); return; }
    if (p.balloons < item.price) { socket.emit('purchaseDenied', { itemId, reason: 'insufficient' }); return; }
    p.balloons -= item.price;
    p.inventory[itemId] = (p.inventory[itemId] || 0) + 1;
    socket.emit('purchaseOk', { itemId, balloons: p.balloons, inventory: p.inventory });
    if (p.uid) creditRealAccount(p.uid, -item.price); // debit the real account's ledger too
  });

  // --- Gift a carried item to a nearby player ---
  socket.on('giftItem', ({ targetId, itemId }) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || !p.inventory[itemId]) return;
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
    if (!p || !t) return;
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
    if (!p || !t) return;
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
    if (p.lastRadio && now - p.lastRadio < 600) return; // light throttle
    p.lastRadio = now;
    const st = Number.isInteger(station) && station >= 0 && station < 32 ? station : radio.station;
    radio = { on: !!on, station: st };
    io.emit('radioState', { ...radio, by: p.name });
  });

  socket.on('chatMessage', (text) => {
    const p = players[socket.id];
    if (!p || !text) return;
    io.emit('chatMessage', { id: socket.id, name: p.name, text: String(text).slice(0, 200) });
  });

  socket.on('voice-offer', ({ target, offer }) => io.to(target).emit('voice-offer', { from: socket.id, offer }));
  socket.on('voice-answer', ({ target, answer }) => io.to(target).emit('voice-answer', { from: socket.id, answer }));
  socket.on('voice-ice', ({ target, candidate }) => io.to(target).emit('voice-ice', { from: socket.id, candidate }));

  socket.on('disconnect', () => {
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
    delete players[socket.id];
    io.emit('playerLeft', socket.id);
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => console.log(`Neyyappam City running on port ${PORT}`));
