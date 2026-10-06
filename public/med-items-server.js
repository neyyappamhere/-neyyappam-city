/* Server-side additions (only needed when CFG.medMode = 'server' in health-system.js).
   1) Add these to your shop catalog array (the one you send with socket.emit('shopCatalog', ...)):
*/
const MED_CATALOG = [
  { id: 'med_bandage',     name: 'Bandage',                  emoji: '🩹', price: 3,  category: 'medicine', shop: 'med' },
  { id: 'med_paracetamol', name: 'Paracetamol',              emoji: '💊', price: 5,  category: 'medicine', shop: 'med' },
  { id: 'med_gel',         name: 'Pain Relief Gel',          emoji: '🧴', price: 7,  category: 'medicine', shop: 'med' },
  { id: 'med_antibiotic',  name: 'Antibiotic Course',        emoji: '💉', price: 14, category: 'medicine', shop: 'med' },
  { id: 'med_firstaid',    name: 'First-Aid Kit',            emoji: '⛑️', price: 22, category: 'medicine', shop: 'med' },
  { id: 'en_coconut',      name: 'Karikku (Tender Coconut)', emoji: '🥥', price: 4,  category: 'energy',   shop: 'med' },
  { id: 'en_ors',          name: 'ORS Drink',                emoji: '🧃', price: 5,  category: 'energy',   shop: 'med' },
  { id: 'en_boost',        name: 'Boost / Horlicks',         emoji: '🥛', price: 6,  category: 'energy',   shop: 'med' },
  { id: 'en_energy',       name: 'Energy Drink',             emoji: '🥫', price: 10, category: 'energy',   shop: 'med' }
];
/* 2) In your 'buyItem' handler, the distance check must accept the medical store:
      shop 'med' is at x: 2, z: 69.5, range: 6   (same as the tea / milk / main shops)
   3) Medicine is consumed by the client the moment 'purchaseOk' arrives, so you can skip
      adding these items to the player's inventory and skip the Chayakkada/Karavakkari commission.
*/
module.exports = { MED_CATALOG };
