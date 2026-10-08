// Usage (in the folder that has server.js):  node apply_cash_patch.js
// Reads server_cash_patch.js (same folder) and writes the cash + car-shop code into your real server.js. A backup is saved as server.js.bak.
const fs = require('fs');
const file = process.argv[2] || 'server.js';
let src = fs.readFileSync(file, 'utf8');
if (src.includes('CASH + GARAGE')) { console.log('server.js already has the cash patch - nothing to do.'); process.exit(0); }
const parts = fs.readFileSync(__dirname + '/server_cash_patch.js', 'utf8').split(/^\/\/ ===== PASTE \d of 3[^\n]*\n/m);
const block1 = parts[1].trimEnd() + '\n', block2 = parts[2].trimEnd().split('\n').map(l => '  ' + l).join('\n') + '\n\n';
function must(re, what) { const m = src.match(re); if (!m) { console.error('Could not find: ' + what + ' - server.js is different from what I expected. Nothing was changed.'); process.exit(1); } return m; }
let m = must(/async function creditRealAccount[\s\S]*?\n}\n/, 'creditRealAccount()');
src = src.replace(m[0], m[0] + '\n' + block1);
must(/\n  socket\.on\('disconnect', \(\) => \{/, "socket.on('disconnect')");
src = src.replace(/\n  socket\.on\('disconnect', \(\) => \{/, '\n' + block2 + "  socket.on('disconnect', () => {");
const j = "socket.to('space:public').emit('playerJoined', players[socket.id]);";
must(new RegExp(j.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), 'join handler end');
src = src.replace(j, j + '\n    cashLoad(socket);');
const a = "io.to(spaceRoom(p)).emit('playerRenamed', { id: socket.id, name: p.name, username: p.username });";
must(new RegExp(a.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), 'auth handler end');
src = src.replace(a, a + '\n    cashLoad(socket);');
fs.copyFileSync(file, file + '.bak'); fs.writeFileSync(file, src);
console.log('Done. Backup: ' + file + '.bak');
