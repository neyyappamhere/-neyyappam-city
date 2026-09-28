// server.js — real-time positions, chat, voice tokens, balloons, cars,
// deliveries, treasure hunt and shop for Neyyappam City.
//
// Balloon crediting: if a player joined with a real `uid` (passed from your
// PHP embed page for logged-in users), balloon changes are POSTed to your PHP
// endpoint (award_balloons.php). Guests (no uid) get a session-only counter.
// NOTE: award_balloons.php must accept NEGATIVE amounts (shop purchases debit).

const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const { AccessToken } = require('livekit-server-sdk');

const app = express();
const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: '*', // TODO: set to 'https://neyyappam.com' before going live
    methods: ['GET', 'POST']
  }
});

// --- LiveKit voice config (set as env vars on your host) ---
const LIVEKIT_URL = process.env.LIVEKIT_URL || '';
const LIVEKIT_API_KEY = process.env.LIVEKIT_API_KEY || '';
const LIVEKIT_API_SECRET = process.env.LIVEKIT_API_SECRET || '';
const VOICE_ROOM = 'neyyappam-city';

app.use(express.static('public', {
  setHeaders: (res, filePath) => {
    if (filePath.endsWith('.php')) res.setHeader('Content-Type', 'text/html; charset=utf-8');
  }
}));

app.get(['/', '/city_world.php'], (req, res) => {
  res.type('html');
  res.sendFile(__dirname + '/public/city_world.php');
});

app.get('/livekit-token', async (req, res) => {
  const identity = String(req.query.identity || '').slice(0, 64);
  if (!identity) return res.status(400).json({ error: 'identity required' });
  if (!LIVEKIT_API_KEY || !LIVEKIT_API_SECRET || !LIVEKIT_URL) {
    return res.status(500).json({ error: 'Voice chat is not configured on the server yet.' });
  }
  try {
    const at = new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET, { identity });
    at.addGrant({ roomJoin: true, room: VOICE_ROOM, canPublish: true, canSubscribe: true });
    const token = await at.toJwt();
    res.json({ token, url: LIVEKIT_URL });
  } catch (err) {
    console.error('Failed to create LiveKit token:', err.message);
    res.status(500).json({ error: 'Failed to create voice token' });
  }
});

// --- Config for talking back to your PHP site ---
const AWARD_ENDPOINT = process.env.AWARD_ENDPOINT || 'https://neyyappam.com/ajax/award_balloons.php';
const SHARED_SECRET = process.env.CITY_SHARED_SECRET || 'change-this-to-a-long-random-string';

// --- World layout: identical to how the client builds its city grid ---
const BLOCK = 20, GRID = 6;
const blockCenters = [];
for (let gx = -GRID / 2; gx < GRID / 2; gx++) {
  for (let gz = -GRID / 2; gz < GRID / 2; gz++) {
    if (gx === 0 && gz === 0) continue; // center plaza is skipped by the client
    blockCenters.push({ x: gx * BLOCK + BLOCK / 2, z: gz * BLOCK + BLOCK / 2 });
  }
}
// The client creates exactly one balloon ('balloon_N') and one parked car
// ('car_N') per block, so N ranges over 0..blockCenters.length-1.
const SPOT_COUNT = blockCenters.length;
function isValidSpotId(prefix, id) {
  if (typeof id !== 'string' || !id.startsWith(prefix)) return false;
  const tail = id.slice(prefix.length);
  if (!/^\d+$/.test(tail)) return false;
  return parseInt(tail, 10) < SPOT_COUNT;
}

const players = {};              // socket.id -> player state
const claimedPickups = new Set(); // balloon ids already collected
const occupiedCars = {};         // carId -> socket.id of current driver
const carPositions = {};         // carId -> last known {x,y,z,rotY} (so newcomers see moved cars)

// --- Balloon values: the client only spawns classic balloons ('balloon_N') ---
const BALLOON_VALUE = 5;

// --- Shop catalog. Categories match the client's CATEGORY_LABELS ---
const ITEMS = [
  { id: 'rose',          name: 'Rose',            category: 'flower',    price: 10, emoji: '🌹' },
  { id: 'lily',          name: 'Lily',            category: 'flower',    price: 15, emoji: '🌷' },
  { id: 'burger',        name: 'Burger',          category: 'food',      price: 12, emoji: '🍔' },
  { id: 'pizza',         name: 'Pizza Slice',     category: 'food',      price: 10, emoji: '🍕' },
  { id: 'soda',          name: 'Soda',            category: 'drink',     price: 8,  emoji: '🥤' },
  { id: 'coffee',        name: 'Coffee',          category: 'drink',     price: 10, emoji: '☕' },
  { id: 'mocktail',      name: 'Party Mocktail',  category: 'drink',     price: 14, emoji: '🍹' },
  { id: 'party_hat',     name: 'Party Hat',       category: 'party',     price: 12, emoji: '🎉' },
  { id: 'confetti',      name: 'Confetti Popper', category: 'party',     price: 10, emoji: '🎊' },
  { id: 'balloon_bunch', name: 'Balloon Bunch',   category: 'party',     price: 20, emoji: '🎈' },
  { id: 'sunglasses',    name: 'Sunglasses',      category: 'accessory', price: 18, emoji: '🕶️' },
  { id: 'necklace',      name: 'Gold Necklace',   category: 'accessory', price: 25, emoji: '📿' },
  { id: 'cap',           name: 'Snapback Cap',    category: 'clothing',  price: 16, emoji: '🧢' },
  { id: 'jacket',        name: 'Bomber Jacket',   category: 'clothing',  price: 30, emoji: '🧥' },
];
const ITEMS_BY_ID = Object.fromEntries(ITEMS.map(i => [i.id, i]));
const FLOWER_IDS = ITEMS.filter(i => i.category === 'flower').map(i => i.id);

// --- Delivery job: one shared job at a time ---
let deliveryJob = null;
function generateDeliveryJob() {
  const a = blockCenters[Math.floor(Math.random() * blockCenters.length)];
  let b = a;
  while (b === a) b = blockCenters[Math.floor(Math.random() * blockCenters.length)];
  deliveryJob = { id: 'job_' + Date.now(), pickup: a, dropoff: b, status: 'available', carrierId: null };
  return deliveryJob;
}
generateDeliveryJob();

// --- Treasure hunt ---
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

// Chests respawn periodically
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

const num = (v) => (Number.isFinite(v) ? v : 0);
const near = (p, target, range) => Math.hypot(p.x - target.x, p.z - target.z) <= range;

io.on('connection', (socket) => {
  console.log('connected:', socket.id);
  let lastChat = 0;

  socket.on('join', (data) => {
    data = data || {};
    const start = Number.isFinite(data.startingBalloons) ? Math.max(0, Math.floor(data.startingBalloons)) : 0;
    players[socket.id] = {
      id: socket.id,
      name: String(data.name || 'Guest').slice(0, 24),
      gender: ['male', 'female', 'other'].includes(data.gender) ? data.gender : 'other',
      outfitColor: /^#?[0-9a-fA-F]{6}$/.test(data.outfitColor || '') ? data.outfitColor : null,
      hairStyle: ['short', 'pony', 'bandana'].includes(data.hairStyle) ? data.hairStyle : 'short',
      x: 0, y: 0, z: 0, rotY: 0,
      uid: data.uid || null,
      balloons: start,
      drivingCarId: null,
      inventory: {},
      flowersGiven: 0,
      flowersReceived: 0,
      treasuresFound: 0,
      collectibles: []
    };

    socket.emit('currentPlayers', players);
    socket.emit('currentCars', occupiedCars);

    // Bring the newcomer up to date on the world state
    for (const carId in carPositions) {
      const pos = carPositions[carId];
      if (occupiedCars[carId]) socket.emit('carMoved', { carId, ...pos });
      else socket.emit('carExited', { carId, driverId: null, ...pos }); // parked where last left
    }
    claimedPickups.forEach(pickupId => {
      socket.emit('balloonCollected', { pickupId, by: null, balloons: 0 }); // hides already-taken balloons
    });

    socket.emit('deliveryUpdated', deliveryJob);
    socket.emit('shopCatalog', ITEMS);
    socket.emit('inventoryUpdated', players[socket.id].inventory);
    socket.emit('treasureSpots', treasureSpots.filter(t => !claimedTreasures.has(t.id)));
    socket.broadcast.emit('playerJoined', players[socket.id]);
  });

  socket.on('move', (pos) => {
    const p = players[socket.id];
    if (!p || !pos) return;
    p.x = num(pos.x); p.y = num(pos.y); p.z = num(pos.z); p.rotY = num(pos.rotY);
    socket.broadcast.emit('playerMoved', p);
  });

  // --- Vehicles ---
  socket.on('enterCar', (carId) => {
    const p = players[socket.id];
    if (!p || p.drivingCarId || !isValidSpotId('car_', carId)) return;
    if (occupiedCars[carId]) {
      socket.emit('carDenied', { carId });
      return;
    }
    occupiedCars[carId] = socket.id;
    p.drivingCarId = carId;
    io.emit('carEntered', { carId, driverId: socket.id, driverName: p.name });
  });

  socket.on('driveCar', (data) => {
    const p = players[socket.id];
    if (!p || !data || p.drivingCarId !== data.carId) return;
    const pos = { x: num(data.x), y: num(data.y), z: num(data.z), rotY: num(data.rotY) };
    carPositions[data.carId] = pos;
    p.x = pos.x; p.z = pos.z; // keep server-side position current while driving
    socket.broadcast.emit('carMoved', { carId: data.carId, ...pos });
  });

  socket.on('exitCar', (data) => {
    const p = players[socket.id];
    if (!p || !data || p.drivingCarId !== data.carId) return;
    const pos = { x: num(data.x), y: num(data.y), z: num(data.z), rotY: num(data.rotY) };
    carPositions[data.carId] = pos;
    delete occupiedCars[data.carId];
    p.drivingCarId = null;
    io.emit('carExited', { carId: data.carId, driverId: socket.id, ...pos });
  });

  // --- Balloons (ids validated so clients can't farm made-up ids) ---
  socket.on('collectBalloon', (pickupId) => {
    const p = players[socket.id];
    if (!p || !isValidSpotId('balloon_', pickupId)) return;
    if (claimedPickups.has(pickupId)) return;
    claimedPickups.add(pickupId);
    p.balloons += BALLOON_VALUE;
    io.emit('balloonCollected', { pickupId, by: socket.id, balloons: p.balloons, value: BALLOON_VALUE });
    if (p.uid) creditRealAccount(p.uid, BALLOON_VALUE);
  });

  // --- Shop ---
  socket.on('buyItem', (itemId) => {
    const p = players[socket.id];
    const item = ITEMS_BY_ID[itemId];
    if (!p || !item) return;
    if (p.balloons < item.price) { socket.emit('purchaseDenied', { itemId, reason: 'insufficient' }); return; }
    p.balloons -= item.price;
    p.inventory[itemId] = (p.inventory[itemId] || 0) + 1;
    socket.emit('purchaseOk', { itemId, balloons: p.balloons, inventory: p.inventory });
    if (p.uid) creditRealAccount(p.uid, -item.price);
  });

  // --- Gift an item to a nearby player ---
  socket.on('giftItem', ({ targetId, itemId } = {}) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || targetId === socket.id || !p.inventory[itemId]) return;
    if (!near(p, t, 3.5)) { socket.emit('giftDenied', { reason: 'range' }); return; }
    p.inventory[itemId] -= 1;
    if (p.inventory[itemId] <= 0) delete p.inventory[itemId];
    t.inventory[itemId] = (t.inventory[itemId] || 0) + 1;
    socket.emit('inventoryUpdated', p.inventory);
    io.to(targetId).emit('inventoryUpdated', t.inventory);
    io.to(targetId).emit('giftReceived', { from: p.name, fromId: socket.id, itemId });
  });

  // --- Give a flower to a nearby player ---
  socket.on('giveFlower', ({ targetId } = {}) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || targetId === socket.id) return;
    const flowerId = FLOWER_IDS.find(f => p.inventory[f] > 0);
    if (!flowerId) { socket.emit('flowerDenied', { reason: 'none' }); return; }
    if (!near(p, t, 3.5)) { socket.emit('flowerDenied', { reason: 'range' }); return; }
    p.inventory[flowerId] -= 1;
    if (p.inventory[flowerId] <= 0) delete p.inventory[flowerId];
    p.flowersGiven += 1;
    t.flowersReceived += 1;
    socket.emit('inventoryUpdated', p.inventory);
    io.to(targetId).emit('flowerReceived', { from: p.name, fromId: socket.id, itemId: flowerId });
  });

  // --- Proposals ---
  socket.on('sendProposal', ({ targetId } = {}) => {
    const p = players[socket.id], t = players[targetId];
    if (!p || !t || targetId === socket.id) return;
    if (!near(p, t, 3.5)) { socket.emit('proposalDenied', { reason: 'range' }); return; }
    io.to(targetId).emit('proposalReceived', { from: p.name, fromId: socket.id });
  });
  socket.on('respondProposal', ({ proposerId, accepted } = {}) => {
    const p = players[socket.id];
    if (!p || !players[proposerId]) return;
    io.to(proposerId).emit('proposalResult', { by: p.name, accepted: !!accepted });
    if (accepted) {
      io.emit('coupleFormed', { aId: proposerId, bId: socket.id, aName: players[proposerId].name, bName: p.name });
    }
  });

  // --- Treasure hunt ---
  socket.on('claimTreasure', (chestId) => {
    const p = players[socket.id];
    const spot = treasureSpots.find(t => t.id === chestId);
    if (!p || !spot || claimedTreasures.has(chestId)) return;
    if (!near(p, spot, 3)) return; // must actually be there (slightly generous for move-update lag)
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
    io.emit('treasureOpened', { chestId });
    socket.emit('treasureReward', { chestId, reward: detail, balloons: p.balloons, inventory: p.inventory });
  });

  // --- Delivery job (now validates that the player is actually at the marker) ---
  socket.on('pickupDelivery', (jobId) => {
    const p = players[socket.id];
    if (!p || !deliveryJob || deliveryJob.id !== jobId || deliveryJob.status !== 'available') return;
    if (!near(p, deliveryJob.pickup, 4)) return;
    deliveryJob.status = 'inProgress';
    deliveryJob.carrierId = socket.id;
    io.emit('deliveryUpdated', deliveryJob);
  });

  socket.on('completeDelivery', (jobId) => {
    const p = players[socket.id];
    if (!p || !deliveryJob || deliveryJob.id !== jobId) return;
    if (deliveryJob.status !== 'inProgress' || deliveryJob.carrierId !== socket.id) return;
    if (!near(p, deliveryJob.dropoff, 4)) return;
    p.balloons += 30;
    io.emit('balloonCollected', { pickupId: null, by: socket.id, balloons: p.balloons });
    if (p.uid) creditRealAccount(p.uid, 30);
    generateDeliveryJob();
    io.emit('deliveryUpdated', deliveryJob);
  });

  socket.on('chatMessage', (text) => {
    const p = players[socket.id];
    if (!p || !text) return;
    const now = Date.now();
    if (now - lastChat < 500) return; // basic spam limit
    lastChat = now;
    io.emit('chatMessage', { id: socket.id, name: p.name, text: String(text).slice(0, 200) });
  });

  socket.on('disconnect', () => {
    const p = players[socket.id];
    if (p && p.drivingCarId) {
      delete occupiedCars[p.drivingCarId];
      io.emit('carFreed', { carId: p.drivingCarId }); // car stays parked where it was left
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
