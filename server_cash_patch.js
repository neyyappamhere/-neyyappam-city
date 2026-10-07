// ===== PASTE 1 of 3: right after the creditRealAccount() function in server.js =====
// CASH + GARAGE: game-only money saved in MySQL through ajax/city_cash.php (balloons are untouched).
const CASH_ENDPOINT = process.env.CASH_ENDPOINT || 'https://neyyappam.com/ajax/city_cash.php';
const CASH_ROOM = { x: 8000, z: 8000 };   // must match the client
const CASH_PILES = [[-4, -4], [4, -4], [0, 0], [-4, 4], [4, 4], [0, -5]].map(([dx, dz], i) => ({ i, x: CASH_ROOM.x + dx, z: CASH_ROOM.z + dz, until: 0 }));
const CAR_CATALOG = {   // generic names on purpose; price = buy, rent = 30 minutes. Tune freely.
  sport_coupe: { name: 'Sport Coupe', price: 40000, rent: 2500 },
  luxury_suv:  { name: 'Luxury SUV',  price: 35000, rent: 2200 },
  exec_sedan:  { name: 'Executive Sedan', price: 30000, rent: 1800 },
};
async function cashApi(params) {
  try {
    const r = await fetch(CASH_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ secret: SHARED_SECRET, ...params }) });
    return await r.json();
  } catch (e) { console.error('cash api failed:', e.message); return null; }
}
const pileFlags = () => CASH_PILES.map(p => (p.until > Date.now() ? 0 : 1));
async function cashLoad(socket) {
  const p = players[socket.id];
  socket.emit('carCatalog', CAR_CATALOG); socket.emit('cashPiles', pileFlags());
  if (!p || !p.uid) { socket.emit('cashState', { cash: 0, cars: [], guest: true }); return; }
  const s = await cashApi({ action: 'get', uid: p.uid });
  if (s && !s.error) socket.emit('cashState', s);
}

// ===== PASTE 2 of 3: inside io.on('connection') just before socket.on('disconnect', ...) =====
socket.on('collectCash', async (i) => {
  const p = players[socket.id], pile = CASH_PILES[i];
  if (!p || !p.uid || !pile || Date.now() < pile.until) return;
  if (Math.hypot(p.x - pile.x, p.z - pile.z) > 3) return;           // must really be standing at it
  pile.until = Date.now() + 40000;                                  // respawns after 40 s
  const amt = 50 + Math.floor(Math.random() * 151);
  const s = await cashApi({ action: 'earn', uid: p.uid, amount: amt });
  if (s && !s.error) socket.emit('cashState', Object.assign(s, { gained: amt }));
  io.emit('cashPiles', pileFlags());
});
socket.on('buyCar', async ({ carId, mode } = {}) => {
  const p = players[socket.id], c = CAR_CATALOG[carId];
  if (!p || !p.uid || !c) return;
  const rent = mode === 'rent';
  const s = await cashApi({ action: 'buy', uid: p.uid, car_id: carId, amount: rent ? c.rent : c.price, until: rent ? Math.floor(Date.now() / 1000) + 1800 : 0 });
  socket.emit('cashState', s ? Object.assign(s, s.error ? {} : { purchased: carId }) : { error: 'fail' });
});

// ===== PASTE 3 of 3: call cashLoad(socket); at the END of the 'join' handler AND at the end of the 'auth' handler =====
// e.g.  ...socket.to('space:public').emit('playerJoined', players[socket.id]);  cashLoad(socket);
