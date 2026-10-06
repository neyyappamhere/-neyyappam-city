/* =========================================================================
   NEYYAPPAM CITY — Health, Ambulance, Hospital & Medical Store add-on
   -------------------------------------------------------------------------
   Load this file AFTER the main game <script>, just before </body>:
       <script src="health-system.js"></script>
   Everything here is client-side (health / ambulance are not synced to other
   players). Tweak the numbers in CFG.
   ========================================================================= */
(function () {
'use strict';
if (typeof THREE === 'undefined' || typeof myAvatar === 'undefined' || typeof socket === 'undefined') {
  console.error('[health] main game not found — load this file after the main script'); return;
}

const CFG = {
  regenPerSec: 60 / 600,      // 40% -> 100% in 10 minutes of play
  hospitalHp: 40,             // health you leave the hospital with
  hospitalStaySec: 25,        // forced treatment time
  ambSpeed: 15,               // ambulance speed (m/s)
  punchDmg: [7, 12],          // damage when a pedestrian punches you
  goatDmg: 5, slapDmg: 4,
  crashMin: 6, crashMax: 45,  // car crash damage range (scaled by speed)
  // 'local'  = buying medicine deducts the on-screen balloons only (good for testing)
  // 'server' = sends buyItem to your server (add the items to the server catalog — see med-items-server.js)
  medMode: 'local'
};

const $ = id => document.getElementById(id);
const rnd = (a, b) => a + Math.random() * (b - a);
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
const wrapPi = a => { while (a > Math.PI) a -= Math.PI * 2; while (a < -Math.PI) a += Math.PI * 2; return a; };
const pick = a => a[Math.floor(Math.random() * a.length)];

const BAY = { x: 20, z: 68.5 };            // ambulance parking bay (under the emergency canopy)
const HOSP = { x: 30, z: 79 };             // hospital centre (south of the city)
const PHARM = { x: 2, z: 74 };             // medical store
const SPAWN_OUT = { x: 28, z: 69.8 };      // where you appear after discharge

/* =========================================================
   1) CSS + HTML
   ========================================================= */
const css = document.createElement('style');
css.textContent = `
#hp-hud{position:fixed;left:50%;bottom:14px;transform:translateX(-50%);z-index:6;width:min(280px,58vw);display:flex;flex-direction:column;gap:6px;pointer-events:none;font-family:'Space Grotesk',sans-serif}
#hp-row{display:flex;align-items:center;gap:8px;background:rgba(20,10,30,.85);border:1px solid rgba(255,255,255,.14);border-radius:16px;padding:6px 10px;box-shadow:0 6px 18px rgba(0,0,0,.45)}
#hp-track{position:relative;flex:1;height:16px;border-radius:9px;background:#2a1a30;overflow:hidden;border:1px solid rgba(255,255,255,.18)}
#hp-ghost{position:absolute;left:0;top:0;bottom:0;background:#ffd23f;width:100%;transition:width .9s ease .25s}
#hp-fill{position:absolute;left:0;top:0;bottom:0;width:100%;transition:width .15s linear,background .3s}
#hp-num{color:#fff;font-weight:800;font-size:.85em;min-width:44px;text-align:right}
#hp-hud.low #hp-row{animation:hpPulse .75s infinite}
#hp-hud.shake #hp-row{animation:hpShake .35s}
@keyframes hpPulse{0%,100%{box-shadow:0 0 0 0 rgba(255,60,60,.0)}50%{box-shadow:0 0 18px 4px rgba(255,60,60,.75)}}
@keyframes hpShake{0%,100%{transform:translateX(0)}20%{transform:translateX(-6px)}40%{transform:translateX(6px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}}
#amb-btn{pointer-events:auto;display:none;align-self:center;border:none;border-radius:20px;padding:10px 18px;font-weight:800;font-size:.85em;cursor:pointer;color:#fff;font-family:inherit;
  background:linear-gradient(135deg,#d62828,#ff5d5d);box-shadow:0 8px 20px rgba(214,40,40,.5)}
#hp-flash{position:fixed;inset:0;z-index:25;pointer-events:none;opacity:0;background:radial-gradient(ellipse at center,rgba(255,0,0,0) 35%,rgba(220,0,0,.75) 100%)}
#hp-flash.heal{background:radial-gradient(ellipse at center,rgba(0,255,140,0) 40%,rgba(0,220,120,.55) 100%)}
#hp-low{position:fixed;inset:0;z-index:2;pointer-events:none;opacity:0;background:radial-gradient(ellipse at center,rgba(0,0,0,0) 45%,rgba(160,0,0,.55) 100%);transition:opacity .5s}
#hp-low.on{opacity:1;animation:lowBeat 1.1s infinite}
@keyframes lowBeat{0%,100%{opacity:.55}50%{opacity:1}}
.hp-float{position:fixed;left:50%;bottom:64px;z-index:26;font-weight:800;font-size:1.3em;pointer-events:none;transform:translateX(-50%);animation:hpFloat 1.1s ease-out forwards;text-shadow:0 2px 6px #000}
@keyframes hpFloat{from{opacity:1;transform:translate(-50%,0)}to{opacity:0;transform:translate(-50%,-60px)}}
#amb-chip{position:fixed;top:170px;left:50%;transform:translateX(-50%);z-index:27;display:none;background:rgba(214,40,40,.92);color:#fff;padding:7px 16px;border-radius:18px;font-weight:800;font-size:.8em;text-align:center;max-width:90vw}
#h-fade{position:fixed;inset:0;z-index:45;background:#000;opacity:0;pointer-events:none;transition:opacity .6s;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.2em;text-align:center;padding:20px}
#hosp-card{position:fixed;top:64px;left:50%;transform:translateX(-50%);z-index:31;display:none;background:rgba(15,40,60,.92);border:1px solid rgba(255,255,255,.2);color:#fff;border-radius:16px;padding:12px 18px;text-align:center;min-width:240px;max-width:92vw}
#hosp-card .t{font-weight:800;margin-bottom:6px}
#hosp-bar{height:10px;border-radius:6px;background:rgba(255,255,255,.18);overflow:hidden;margin:6px 0}
#hosp-bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,#2ecc71,#00f593);transition:width .25s linear}
#hosp-out{display:none;margin-top:8px;border:none;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer;color:#fff;background:linear-gradient(135deg,#00b37a,#00f593);color:#05210f;font-family:inherit}
body.h-lock #vehicle-prompt,body.h-lock #fight-btn,body.h-lock #social-ui,body.h-lock #emote-bar,body.h-lock #sit-prompt,body.h-lock #shop-prompt,
body.h-lock #radio-ui,body.h-lock #cart-call,body.h-lock #shop-btn,body.h-lock #private-btn,body.h-lock #fly-btns,body.h-lock #hand-prompt{display:none !important}
@media (max-width:700px){#amb-chip{top:150px}}
`;
document.head.appendChild(css);

const hud = document.createElement('div');
hud.id = 'hp-hud';
hud.innerHTML = '<button id="amb-btn" type="button">🚑 Call Ambulance (C)</button>' +
  '<div id="hp-row"><span>❤️</span><div id="hp-track"><div id="hp-ghost"></div><div id="hp-fill"></div></div><b id="hp-num">100%</b></div>';
document.body.appendChild(hud);
const extra = document.createElement('div');
extra.innerHTML = '<div id="hp-flash"></div><div id="hp-low"></div><div id="amb-chip"></div><div id="h-fade"></div>' +
  '<div id="hosp-card"><div class="t" id="hosp-title">🏥 Treatment</div><div id="hosp-bar"><i></i></div><div id="hosp-sub">—</div><button id="hosp-out" type="button">🚪 Discharge &amp; go outside</button></div>';
while (extra.firstChild) document.body.appendChild(extra.firstChild);

/* =========================================================
   2) STATE + HEALTH
   ========================================================= */
const KEY = 'neyyappamCityHp';
function loadHp() {
  try { const v = JSON.parse(localStorage.getItem(KEY)); if (v && typeof v.hp === 'number') return v.hp <= 0 ? CFG.hospitalHp : clamp(v.hp, 1, 100); } catch (e) {}
  return 100;
}
function saveHp() { try { localStorage.setItem(KEY, JSON.stringify({ hp: ST.hp, t: Date.now() })); } catch (e) {} }

const ST = {
  phase: 'ok',          // ok | wait (ambulance coming / carrying) | hospital
  step: null, hp: loadHp(), down: false, last: performance.now(), crashCool: 0, shake: 0,
  A: null, medics: [], str: null, t0: 0, unlocked: false, treat: null, interior: null, sirenOn: false,
  lastSpeed: 0, lastCarPos: null, prevY: 0, prevVy: 0, paintAt: 0, saveAt: 0, hintShown: false, lift: null, load: null
};

function paintHud() {
  const p = clamp(ST.hp, 0, 100), col = p > 60 ? 'linear-gradient(90deg,#2ecc71,#7bed9f)' : p > 30 ? 'linear-gradient(90deg,#f39c12,#f1c40f)' : 'linear-gradient(90deg,#c0392b,#ff4d6d)';
  const f = $('hp-fill'); f.style.width = p + '%'; f.style.background = col;
  $('hp-ghost').style.width = p + '%';
  $('hp-num').textContent = Math.ceil(p) + '%';
  hud.classList.toggle('low', p < 25 && p > 0 && inGame());
  $('hp-low').classList.toggle('on', p < 25 && inGame());
}
function floatText(txt, color) {
  const d = document.createElement('div'); d.className = 'hp-float'; d.textContent = txt; d.style.color = color; document.body.appendChild(d);
  setTimeout(() => d.remove(), 1200);
}
function flash(kind) {
  const el = $('hp-flash'); el.classList.toggle('heal', kind === 'heal');
  el.style.transition = 'none'; el.style.opacity = kind === 'heal' ? '0.7' : '0.85'; void el.offsetWidth;
  el.style.transition = 'opacity .7s'; el.style.opacity = '0';
}
function hurt(n, why) {
  if (ST.phase !== 'ok' || !inGame() || privScene) return;
  n = Math.round(n); if (n <= 0) return;
  ST.hp = Math.max(0, ST.hp - n);
  flash('hit'); floatText('-' + n, '#ff4d6d');
  hud.classList.remove('shake'); void hud.offsetWidth; hud.classList.add('shake');
  ST.shake = Math.max(ST.shake, Math.min(0.6, n / 40));
  paintHud();
  if (ST.hp <= 0) startKO();
  else if (ST.hp < 25 && !ST.hintShown) { ST.hintShown = true; showToast('⚠️ Health valare kuravaanu! Medicine kazhikku allengil 🚑 ambulance vilikku (C)'); }
  if (ST.hp > 40) ST.hintShown = false;
}
function heal(n, label) {
  ST.hp = Math.min(100, ST.hp + n);
  flash('heal'); floatText('+' + n, '#00f593'); paintHud();
  if (label) showToast(label);
  if (typeof actx !== 'undefined' && actx && soundOn) blip(880, 0.12, 0.2, 1320);
}

/* damage listeners */
socket.on('slapReceived', () => hurt(CFG.slapDmg, 'slap'));
{
  const oh = window.headbutt;
  if (typeof oh === 'function') window.headbutt = function () { const r = oh.apply(this, arguments); hurt(CFG.goatDmg, 'goat'); return r; };
}

/* =========================================================
   3) SOUND — siren (wail / yelp / hi-lo), engine, doors, ECG beep
   ========================================================= */
function audioOK() { return typeof actx !== 'undefined' && actx && soundOn && master; }
function blip(f0, dur, vol, f1) {
  if (!audioOK()) return;
  const t = actx.currentTime, o = actx.createOscillator(), g = actx.createGain();
  o.type = 'sine'; o.frequency.setValueAtTime(f0, t); if (f1) o.frequency.exponentialRampToValueAtTime(f1, t + dur);
  g.gain.setValueAtTime(vol, t); g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
  o.connect(g); g.connect(master); o.start(t); o.stop(t + dur + 0.05);
}
function thud(vol) {
  if (!audioOK() || vol < 0.01) return;
  const t = actx.currentTime, o = actx.createOscillator(), g = actx.createGain();
  o.type = 'sine'; o.frequency.setValueAtTime(110, t); o.frequency.exponentialRampToValueAtTime(40, t + 0.18);
  g.gain.setValueAtTime(0.6 * vol, t); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.22);
  o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.25);
  const ns = actx.createBufferSource(), lp = actx.createBiquadFilter(), ng = actx.createGain();
  ns.buffer = noiseBuffer(); lp.type = 'lowpass'; lp.frequency.value = 900;
  ng.gain.setValueAtTime(0.5 * vol, t); ng.gain.exponentialRampToValueAtTime(0.0001, t + 0.12);
  ns.connect(lp); lp.connect(ng); ng.connect(master); ns.start(t); ns.stop(t + 0.15);
}
const AU = { siren: null, eng: null, prevD: null };
function makeSiren() {
  const o = actx.createOscillator(), o2 = actx.createOscillator(), g2 = actx.createGain(), ws = actx.createWaveShaper(),
        hp = actx.createBiquadFilter(), lp = actx.createBiquadFilter(), g = actx.createGain();
  o.type = 'sawtooth'; o2.type = 'square'; g2.gain.value = 0.22;
  const curve = new Float32Array(256); for (let i = 0; i < 256; i++) { const x = i / 128 - 1; curve[i] = Math.tanh(x * 3); } ws.curve = curve;
  hp.type = 'highpass'; hp.frequency.value = 380; lp.type = 'lowpass'; lp.frequency.value = 3200; lp.Q.value = 1.5; g.gain.value = 0;
  o.connect(ws); o2.connect(g2); g2.connect(ws); ws.connect(hp); hp.connect(lp); lp.connect(g); g.connect(master);
  o.start(); o2.start();
  return { o, o2, lp, g };
}
function makeEngine() {
  const ns = actx.createBufferSource(), lp = actx.createBiquadFilter(), g = actx.createGain(), o = actx.createOscillator(), lp2 = actx.createBiquadFilter(), g2 = actx.createGain();
  ns.buffer = noiseBuffer(); ns.loop = true; lp.type = 'lowpass'; lp.frequency.value = 240; g.gain.value = 0;
  o.type = 'sawtooth'; o.frequency.value = 42; lp2.type = 'lowpass'; lp2.frequency.value = 200; g2.gain.value = 0;
  ns.connect(lp); lp.connect(g); g.connect(master); o.connect(lp2); lp2.connect(g2); g2.connect(master);
  ns.start(); o.start();
  return { ns, o, g, g2 };
}
function stopNodes(n, keys) {
  if (!n) return; const t = actx.currentTime;
  keys.forEach(k => { try { n[k].gain.cancelScheduledValues(t); n[k].gain.setTargetAtTime(0, t, 0.08); } catch (e) {} });
  setTimeout(() => { Object.values(n).forEach(x => { try { x.stop && x.stop(); } catch (e) {} try { x.disconnect(); } catch (e) {} }); }, 500);
}
function audioTick(now, dt) {
  const A = ST.A, want = ST.sirenOn && A && audioOK();
  if (want) {
    if (!AU.siren) AU.siren = makeSiren();
    if (!AU.eng) AU.eng = makeEngine();
    const q = listenerPos(), d = Math.hypot(q.x - A.x, q.z - A.z), t = now / 1000, vol = earVol(A.x, A.z, 120);
    let f; const cyc = t % 14;
    if (cyc < 6) f = 650 + 600 * (0.5 - 0.5 * Math.cos(2 * Math.PI * t / 3.4));                       // wail
    else if (cyc < 9) f = 700 + 650 * (0.5 - 0.5 * Math.cos(2 * Math.PI * t / 0.33));                  // yelp
    else f = (Math.floor(t / 0.55) % 2) ? 960 : 770;                                                     // hi-lo "pee-po"
    const vr = AU.prevD === null ? 0 : (AU.prevD - d) / Math.max(dt, 0.016); AU.prevD = d;
    f *= clamp(343 / (343 - clamp(vr * 2.2, -60, 60)), 0.9, 1.12);
    const tc = actx.currentTime;
    AU.siren.o.frequency.setTargetAtTime(f, tc, cyc >= 9 ? 0.012 : 0.03);
    AU.siren.o2.frequency.setTargetAtTime(f * 2, tc, 0.03);
    AU.siren.lp.frequency.setTargetAtTime(1200 + 2400 * clamp(1 - d / 120, 0, 1), tc, 0.1);
    AU.siren.g.gain.setTargetAtTime(vol * 0.3, tc, 0.08);
    const sp = clamp(Math.abs(A.speed) / CFG.ambSpeed, 0, 1);
    AU.eng.g.gain.setTargetAtTime(vol * (0.05 + 0.2 * sp), tc, 0.1);
    AU.eng.g2.gain.setTargetAtTime(vol * (0.12 + 0.25 * sp), tc, 0.1);
    AU.eng.o.frequency.setTargetAtTime(38 + sp * 55, tc, 0.12);
  } else {
    if (AU.siren) { stopNodes(AU.siren, ['g']); AU.siren = null; }
    if (AU.eng) { stopNodes(AU.eng, ['g', 'g2']); AU.eng = null; }
    AU.prevD = null;
  }
}

/* =========================================================
   4) AMBULANCE MODEL
   ========================================================= */
function crossTex() {
  const c = document.createElement('canvas'); c.width = c.height = 128; const g = c.getContext('2d');
  g.fillStyle = '#ffffff'; g.fillRect(0, 0, 128, 128); g.fillStyle = '#d62828'; g.fillRect(48, 14, 32, 100); g.fillRect(14, 48, 100, 32);
  return new THREE.CanvasTexture(c);
}
function mirrorTex(text) {
  const c = document.createElement('canvas'); c.width = 512; c.height = 96; const g = c.getContext('2d');
  g.fillStyle = '#ffffff'; g.fillRect(0, 0, 512, 96); g.translate(512, 0); g.scale(-1, 1);
  g.fillStyle = '#d62828'; g.font = 'bold 64px sans-serif'; g.textAlign = 'center'; g.textBaseline = 'middle'; g.fillText(text, 256, 50);
  return new THREE.CanvasTexture(c);
}
function buildAmbulance() {
  const g = new THREE.Group(), white = 0xf7f8fa, red = 0xd62828, glass = { roughness: 0.1, metalness: 0.4 };
  pbox(g, 4.7, 0.45, 1.7, 0xe9ecef, 0, 0.62, 0);
  pbox(g, 2.82, 1.75, 1.78, white, -0.71, 1.72, 0);
  pbox(g, 1.35, 1.25, 1.74, white, 1.55, 1.35, 0);
  pbox(g, 0.95, 0.55, 1.72, white, 2.2, 0.95, 0);
  pbox(g, 0.06, 0.78, 1.5, 0x1d2f3d, 2.22, 1.6, 0, glass);
  [-1, 1].forEach(s => { pbox(g, 0.95, 0.55, 0.06, 0x1d2f3d, 1.65, 1.68, s * 0.88, glass); });
  pbox(g, 4.25, 0.3, 1.82, red, -0.125, 1.2, 0);
  pbox(g, 0.2, 0.3, 1.8, 0x333840, 2.72, 0.55, 0); pbox(g, 0.2, 0.3, 1.8, 0x333840, -2.36, 0.55, 0);
  [-1, 1].forEach(s => {
    pbox(g, 0.06, 0.2, 0.3, 0xfff6cf, 2.69, 0.95, s * 0.6, { emissive: 0xfff2b0, emissiveIntensity: 0.8 });
    pbox(g, 0.05, 0.35, 0.15, 0xff2020, -2.27, 1.0, s * 0.8, { emissive: 0xff1010, emissiveIntensity: 0.5 });
    pbox(g, 0.12, 0.25, 0.1, 0x222222, 2.0, 1.7, s * 0.98);
  });
  // light bars with flashing red / blue lamps
  const matR = new THREE.MeshStandardMaterial({ color: 0xff2222, emissive: 0xff1111, emissiveIntensity: 0 });
  const matB = new THREE.MeshStandardMaterial({ color: 0x2255ff, emissive: 0x1144ff, emissiveIntensity: 0 });
  const lamp = (x, y, z, w, m) => { const l = new THREE.Mesh(new THREE.BoxGeometry(w, 0.15, 0.6), m); l.position.set(x, y, z); g.add(l); };
  pbox(g, 0.5, 0.08, 1.5, 0x222222, 1.5, 2.02, 0); lamp(1.5, 2.12, 0.4, 0.4, matR); lamp(1.5, 2.12, -0.4, 0.4, matB);
  pbox(g, 0.4, 0.08, 1.4, 0x222222, -2.0, 2.62, 0); lamp(-2.0, 2.71, 0.4, 0.3, matB); lamp(-2.0, 2.71, -0.4, 0.3, matR);
  [-1, 1].forEach(s => { const m = s > 0 ? matR : matB; const f = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.1, 0.35), m); f.position.set(2.7, 0.78, s * 0.3); g.add(f); });
  const lr = new THREE.PointLight(0xff2222, 0, 16); lr.position.set(1.5, 2.6, 0.6); g.add(lr);
  const lb = new THREE.PointLight(0x2255ff, 0, 16); lb.position.set(1.5, 2.6, -0.6); g.add(lb);
  // markings
  const cross = crossTex();
  [-1, 1].forEach(s => {
    const t = new THREE.Mesh(new THREE.PlaneGeometry(2.0, 0.5), new THREE.MeshBasicMaterial({ map: makeSignTex('ആംബുലൻസ്', '#ffffff', '#d62828') }));
    t.position.set(-0.2, 2.1, s * 0.905); if (s < 0) t.rotation.y = Math.PI; g.add(t);
    const c = new THREE.Mesh(new THREE.PlaneGeometry(0.8, 0.8), new THREE.MeshBasicMaterial({ map: cross }));
    c.position.set(-1.65, 1.85, s * 0.905); if (s < 0) c.rotation.y = Math.PI; g.add(c);
  });
  const front = new THREE.Mesh(new THREE.PlaneGeometry(1.5, 0.22), new THREE.MeshBasicMaterial({ map: mirrorTex('AMBULANCE') }));
  front.position.set(2.686, 1.1, 0); front.rotation.y = Math.PI / 2; g.add(front);
  // wheels
  const wheels = [];
  [[1.55, 0.92], [1.55, -0.92], [-1.35, 0.92], [-1.35, -0.92]].forEach(([x, z]) => {
    const wg = new THREE.Group(); wg.position.set(x, 0.42, z); g.add(wg);
    const tire = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.42, 0.3, 16), pm(0x15161a)); tire.rotation.x = Math.PI / 2; wg.add(tire);
    const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.22, 0.32, 12), pm(0xcfd4da, { metalness: 0.7, roughness: 0.3 })); hub.rotation.x = Math.PI / 2; wg.add(hub);
    const sp = new THREE.Mesh(new THREE.BoxGeometry(0.6, 0.07, 0.34), pm(0x8a9098)); wg.add(sp);
    wheels.push(wg);
  });
  // rear interior + doors
  pbox(g, 0.1, 1.55, 1.55, 0x30343b, -2.17, 1.72, 0);
  pbox(g, 0.02, 0.08, 1.2, 0xffffff, -2.12, 2.45, 0, { emissive: 0xffffff, emissiveIntensity: 1 });
  const doors = [];
  [1, -1].forEach(s => {
    const h = new THREE.Group(); h.position.set(-2.25, 1.72, s * 0.89); g.add(h);
    pbox(h, 0.06, 1.65, 0.86, white, 0, 0, -s * 0.43);
    pbox(h, 0.07, 0.5, 0.5, 0x1d2f3d, -0.01, 0.35, -s * 0.43, glass);
    const c = new THREE.Mesh(new THREE.PlaneGeometry(0.5, 0.5), new THREE.MeshBasicMaterial({ map: cross })); c.position.set(-0.04, -0.3, -s * 0.43); c.rotation.y = -Math.PI / 2; h.add(c);
    doors.push({ h, s });
  });
  scene.add(g);
  return { g, wheels, doors, matR, matB, lr, lb, x: BAY.x, z: BAY.z, r: Math.PI / 2, speed: 0, wp: [], wi: 0, door: 0, doorT: 0, flashAt: 0 };
}
function wpos(A, lx, lz) { const c = Math.cos(A.r), s = Math.sin(A.r); return { x: A.x + lx * c + lz * s, z: A.z - lx * s + lz * c }; }
function applyAmb() {
  const A = ST.A; A.g.position.set(A.x, 0, A.z); A.g.rotation.y = A.r;
  A.door += (A.doorT - A.door) * 0.14;
  A.doors.forEach(d => { d.h.rotation.y = d.s * A.door; });
}
function ambLights(now) {
  const A = ST.A, on = ST.sirenOn, ph = Math.floor(now / 140) % 2;
  A.matR.emissiveIntensity = on && ph === 0 ? 2.2 : 0; A.matB.emissiveIntensity = on && ph === 1 ? 2.2 : 0;
  A.lr.intensity = on && ph === 0 ? 2.2 : 0; A.lb.intensity = on && ph === 1 ? 2.2 : 0;
}
function drive(A, dt) {
  if (A.wi >= A.wp.length) { A.speed = Math.max(0, A.speed - 28 * dt); return A.speed < 0.2; }
  const w = A.wp[A.wi], dx = w.x - A.x, dz = w.z - A.z, dist = Math.hypot(dx, dz), diff = wrapPi(Math.atan2(-dz, dx) - A.r), last = A.wi === A.wp.length - 1;
  let tgt = CFG.ambSpeed * (1 - Math.min(0.75, Math.abs(diff) * 0.9));
  if (last) tgt = Math.min(tgt, Math.max(2.2, dist * 1.2)); else if (dist < 6) tgt = Math.min(tgt, 8);
  A.speed += clamp(tgt - A.speed, -30 * dt, 9 * dt);
  const mt = 2.6 * dt * Math.min(1, 0.4 + A.speed / 8); A.r += clamp(diff, -mt, mt);
  const step = A.speed * dt, pdx = Math.cos(A.r) * step, pdz = -Math.sin(A.r) * step; A.x += pdx; A.z += pdz;
  A.wheels.forEach(w => { w.rotation.z -= step / 0.42; });
  if (dist < (last ? 1.2 : 3.5)) { A.wi++; if (last) { A.speed = Math.max(A.speed, 0.5); return true; } }
  return false;
}
function planRoute(P) {
  const cl = v => clamp(v, -60, 60), rx = Math.round(cl(P.x) / 20) * 20, rz = Math.round(cl(P.z) / 20) * 20;
  const V = { x: rx, z: cl(P.z) }, Hh = { x: cl(P.x), z: rz }, dV = Math.hypot(P.x - V.x, P.z - V.z), dH = Math.hypot(P.x - Hh.x, P.z - Hh.z);
  const wp = [{ x: BAY.x, z: 60 }]; let Rp;
  if (dV <= dH) { Rp = V; if (rx !== BAY.x) wp.push({ x: rx, z: 60 }); wp.push({ x: rx, z: V.z }); }
  else {
    Rp = Hh; const vx = Math.round(cl(Hh.x) / 20) * 20;
    if (rz === 60) wp.push({ x: Hh.x, z: 60 }); else { if (vx !== BAY.x) wp.push({ x: vx, z: 60 }); wp.push({ x: vx, z: rz }); wp.push({ x: Hh.x, z: rz }); }
  }
  const dist = Math.hypot(P.x - Rp.x, P.z - Rp.z);
  if (dist > 9) { const k = (dist - 6) / dist, T = { x: Rp.x + (P.x - Rp.x) * k, z: Rp.z + (P.z - Rp.z) * k }; if (T.x > RIVER.x1 - 4) T.x = RIVER.x1 - 4; wp.push(T); }
  return wp.filter((p, i) => i === 0 || Math.hypot(p.x - wp[i - 1].x, p.z - wp[i - 1].z) > 1);
}
function returnRoute(wp) {
  const r = wp.slice(0, -1).reverse();
  if (!r.length || Math.hypot(r[r.length - 1].x - BAY.x, r[r.length - 1].z - 60) > 1) r.push({ x: BAY.x, z: 60 });
  r.push({ x: BAY.x, z: BAY.z }); return r;
}

/* paramedics + stretcher */
function mkMedic(female) {
  const g = makeAvatarMesh(female ? 'female' : 'male', '#2ec4b6', 'short', null, { skin: pick([0xc68a5d, 0xae7a50, 0xe0ac82]), pants: 0x1f6f5c, hair: 0x1d1411 });
  g.userData.walkAmp = 0; g.add(makeLabel('Paramedic 🚑'));
  const b = g.userData.body;
  const plate = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.16, 0.02), new THREE.MeshBasicMaterial({ color: 0xffffff })); plate.position.set(0.07, 1.27, 0.14); b.add(plate);
  const r1 = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.03, 0.025), new THREE.MeshBasicMaterial({ color: 0xd62828 })); r1.position.set(0.07, 1.27, 0.145); b.add(r1);
  const r2 = new THREE.Mesh(new THREE.BoxGeometry(0.03, 0.1, 0.025), new THREE.MeshBasicMaterial({ color: 0xd62828 })); r2.position.set(0.07, 1.27, 0.145); b.add(r2);
  scene.add(g); return g;
}
function mkStretcher() {
  const g = new THREE.Group();
  pbox(g, 2.0, 0.07, 0.62, 0x9aa1a9, 0, 0.62, 0); pbox(g, 1.9, 0.13, 0.58, 0xffffff, 0, 0.72, 0);
  pbox(g, 0.55, 0.14, 0.58, 0xd9ecf7, 0.7, 0.8, 0); pbox(g, 1.0, 0.06, 0.6, 0x2ec4b6, -0.4, 0.8, 0);
  [-0.8, 0.8].forEach(x => [-0.3, 0.3].forEach(z => { pbox(g, 0.05, 0.4, 0.05, 0x777c83, x, 0.4, z); const w = new THREE.Mesh(new THREE.SphereGeometry(0.07, 8, 6), pm(0x222222)); w.position.set(x, 0.07, z); g.add(w); }));
  [-0.3, 0.3].forEach(z => pbox(g, 2.1, 0.04, 0.04, 0xc7ccd2, 0, 0.9, z * 1.1));
  scene.add(g); return g;
}
function setStretcher(x, z, hx, hz) { const s = ST.str; s.x = x; s.z = z; s.hx = hx; s.hz = hz; s.g.position.set(x, 0, z); s.g.rotation.y = Math.atan2(-hz, hx); }
function placeMedics(carry) {
  const s = ST.str; if (!s) return;
  const ends = [1.3, -1.3];
  ST.medics.forEach((m, i) => {
    m.position.set(s.x + s.hx * ends[i], 0, s.z + s.hz * ends[i]); m.rotation.y = Math.atan2(s.hx, s.hz);
  });
  ST.medics.forEach(m => { m.userData.walkAmp = carry ? 0.4 : 0; });
}
function armsForward() { ST.medics.forEach(m => { const u = m.userData; if (u.arms) { u.arms[0].rotation.x = u.arms[1].rotation.x = -1.2; } }); }
function avatarOnStretcher(yUp) {
  const s = ST.str; myAvatar.rotation.order = 'YXZ'; myAvatar.rotation.x = -Math.PI / 2; myAvatar.rotation.y = Math.atan2(-s.hx, -s.hz);
  myAvatar.position.set(s.x - s.hx * 0.85, yUp === undefined ? 0.98 : yUp, s.z - s.hz * 0.85);
}
function layDown() { myAvatar.rotation.order = 'YXZ'; myAvatar.rotation.x = -Math.PI / 2; myAvatar.position.y = 0.12; }
function standUp() { myAvatar.rotation.order = 'XYZ'; myAvatar.rotation.x = 0; myAvatar.position.y = 0; myAvatar.visible = true; }

/* =========================================================
   5) KO / CALL AMBULANCE / RESCUE STATE MACHINE
   ========================================================= */
function lockControls(on) {
  document.body.classList.toggle('h-lock', on);
  if (on) {
    Object.keys(keys).forEach(k => { keys[k] = false; });
    ['shop-overlay', 'stats-overlay', 'inventory-overlay', 'map-overlay'].forEach(id => { const e = $(id); if (e) e.style.display = 'none'; });
    if (typeof mapOpen !== 'undefined') mapOpen = false;
    if (typeof sitting !== 'undefined' && sitting) toggleSit();
  }
}
function startKO() {
  if (ST.phase !== 'ok') return;
  ST.down = true;
  if (drivingCarId) exitVehicle();
  PEDS.forEach(p => { if (p.state === 'fight') { say(p, 'quit'); resumePath(p); } });
  layDown();
  showToast('😵 Ningal veenu! Ambulance varunnu…');
  callAmbulance(true);
}
function callAmbulance(auto) {
  if (ST.phase !== 'ok' || !inGame() || privScene) return;
  if (drivingCarId) { showToast('🚗 Aadyam vandi nirthi irangu, ennitt ambulance vilikku'); return; }
  if (!auto && ST.hp >= 100) { showToast('❤️ Health full aanu — ambulance venda 😄'); return; }
  if (!ST.A) return;
  const A = ST.A; A.x = BAY.x; A.z = BAY.z; A.r = Math.PI / 2; A.speed = 0; A.doorT = 0;
  A.wp = planRoute(myAvatar.position); A.wi = 0; ST.fullWp = A.wp.slice();
  ST.phase = 'wait'; ST.step = 'route'; ST.sirenOn = true; ST.t0 = performance.now(); ST.unlocked = false;
  lockControls(true);
  showToast(auto ? '🚑 Ambulance vilichittund — ivide thanne kidannolu!' : '🚑 Ambulance varunnu — ivide thanne nilkku!');
  if (!soundOn && !ST.hintShownSnd) { ST.hintShownSnd = true; setTimeout(() => showToast('🔊 Sound button on aakkiyaal ambulance siren kelkkaam'), 1200); }
  if (typeof ensureAudio === 'function' && soundOn) ensureAudio();
}
function chip(txt) { const c = $('amb-chip'); if (txt) { c.textContent = txt; c.style.display = 'block'; } else c.style.display = 'none'; }
function fade(on, text) { const f = $('h-fade'); f.textContent = text || ''; f.style.opacity = on ? '1' : '0'; }

function rescueTick(now, dt) {
  const A = ST.A, P = myAvatar.position, el = (now - ST.t0) / 1000;
  switch (ST.step) {
    case 'route': {
      const done = drive(A, dt);
      chip('🚑 Ambulance varunnu — ' + Math.round(Math.hypot(A.x - P.x, A.z - P.z)) + ' m' + (soundOn ? '' : '  •  🔇 sound on aakku'));
      if (done) { ST.step = 'open'; ST.t0 = now; A.doorT = 1.75; thud(earVol(A.x, A.z, 40)); }
      break;
    }
    case 'open': {
      A.speed = Math.max(0, A.speed - 20 * dt); A.x += Math.cos(A.r) * A.speed * dt; A.z += -Math.sin(A.r) * A.speed * dt;
      chip('🚑 Ambulance ethi — paramedics varunnu');
      if (el > 0.9) {
        const rp = wpos(A, -3.1, 0), fx = -Math.cos(A.r), fz = Math.sin(A.r);
        ST.medics = [mkMedic(false), mkMedic(true)]; ST.str = mkStretcher();
        setStretcher(rp.x, rp.z, fx, fz); placeMedics(true);
        const dx = P.x - rp.x, dz = P.z - rp.z, d = Math.hypot(dx, dz) || 1;
        ST.tp = { x: P.x - dx / d * 1.7, z: P.z - dz / d * 1.7, hx: dx / d, hz: dz / d };
        ST.step = 'medicsGo'; ST.t0 = now;
        showToast(pick(['🧑‍⚕️ "Pedikkaanda, njangal ethi!"', '🧑‍⚕️ "Kuzhappamilla, hospitalil pokaam!"', '👩‍⚕️ "Pettannu stretcherilekku, aa…"']));
      }
      break;
    }
    case 'medicsGo': {
      const s = ST.str, dx = ST.tp.x - s.x, dz = ST.tp.z - s.z, d = Math.hypot(dx, dz);
      chip('🚑 Paramedics ningale edukkaan varunnu');
      if (d < 0.25) {
        ST.step = 'lift'; ST.t0 = now; ST.lift = { x: P.x, z: P.z, y: P.y };
        if (!ST.down) layDown();
        setStretcher(s.x, s.z, ST.tp.hx, ST.tp.hz); placeMedics(false);
      } else {
        const sp = Math.min(d, 2.6 * dt); setStretcher(s.x + dx / d * sp, s.z + dz / d * sp, dx / d, dz / d); placeMedics(true);
      }
      break;
    }
    case 'lift': {
      const k = clamp(el / 1.1, 0, 1), s = ST.str, tx = s.x - s.hx * 0.85, tz = s.z - s.hz * 0.85, L = ST.lift;
      myAvatar.rotation.order = 'YXZ'; myAvatar.rotation.x = -Math.PI / 2; myAvatar.rotation.y = Math.atan2(-s.hx, -s.hz);
      myAvatar.position.set(L.x + (tx - L.x) * k, 0.12 + (0.98 - 0.12) * k + Math.sin(k * Math.PI) * 0.3, L.z + (tz - L.z) * k);
      ST.medics.forEach(m => { m.rotation.y = Math.atan2(P.x - m.position.x, P.z - m.position.z); });
      chip('🚑 Ningale stretcherilekku edukkunnu…');
      if (k >= 1) { ST.step = 'medicsBack'; ST.t0 = now; showToast('👩‍⚕️ "Pidichu, pettannu ambulanceilekku!"'); }
      break;
    }
    case 'medicsBack': {
      const s = ST.str, rp = wpos(A, -3.1, 0), dx = rp.x - s.x, dz = rp.z - s.z, d = Math.hypot(dx, dz);
      chip('🚑 Ambulanceilekku konduvarunnu');
      if (d < 0.35) { ST.step = 'load'; ST.t0 = now; ST.load = { x: s.x, z: s.z }; thud(0.5); }
      else { const sp = Math.min(d, 2.6 * dt); setStretcher(s.x + dx / d * sp, s.z + dz / d * sp, dx / d, dz / d); placeMedics(true); avatarOnStretcher(); }
      break;
    }
    case 'load': {
      const k = clamp(el / 1.4, 0, 1), s = ST.str, ins = wpos(A, -0.9, 0), fx = Math.cos(A.r), fz = -Math.sin(A.r), L = ST.load;
      setStretcher(L.x + (ins.x - L.x) * k, L.z + (ins.z - L.z) * k, fx, fz); placeMedics(false);
      avatarOnStretcher();
      if (k > 0.75) { s.g.visible = false; myAvatar.visible = false; }
      if (k > 0.85) ST.medics.forEach(m => { m.visible = false; });
      if (k >= 1) { A.doorT = 0; ST.step = 'close'; ST.t0 = now; }
      break;
    }
    case 'close': {
      chip('🚑 Hospitalilekku pokunnu…');
      if (el > 0.9) {
        thud(earVol(A.x, A.z, 40));
        const full = ST.fullWp; A.wp = returnRoute(full); A.wi = 0; ST.step = 'return'; ST.t0 = now;
        ST.medics.forEach(m => { scene.remove(m); }); ST.medics = [];
        if (ST.str) { scene.remove(ST.str.g); ST.str = null; }
      }
      break;
    }
    case 'return': {
      const done = drive(A, dt), d = Math.hypot(A.x - BAY.x, A.z - BAY.z);
      myAvatar.position.set(A.x, 0.5, A.z);
      chip('🚑 Hospitalilekku — ' + Math.round(d) + ' m' + (soundOn ? '' : '  •  🔇 sound on aakku'));
      if (done || d < 2) { ST.step = 'fade'; ST.t0 = now; A.wp = []; A.speed = 0; fade(true, '🏥 Hospitalil ethi…'); ST.sirenOn = false; thud(0.5); }
      break;
    }
    case 'fade': {
      A.speed = Math.max(0, A.speed - 20 * dt);
      if (el > 1.1) enterHospital();
      break;
    }
  }
  ST.medics.forEach(m => tickAvatar(m, now));
  if (ST.step === 'medicsGo' || ST.step === 'medicsBack' || ST.step === 'load') armsForward();
  applyAmb();
}

/* =========================================================
   6) HOSPITAL INTERIOR + TREATMENT
   ========================================================= */
function mkStaff(kind) {
  const f = kind !== 'doc';
  const g = makeAvatarMesh(f ? 'female' : 'male', kind === 'nurse' ? '#2ec4b6' : kind === 'recep' ? '#4a90e2' : '#ffffff', f ? 'pony' : 'short', null, { skin: pick([0xe0ac82, 0xc68a5d, 0xefc6a2]), pants: 0x2b4c7e });
  g.userData.walkAmp = 0;
  if (kind === 'doc') {
    const coat = new THREE.Mesh(new THREE.CylinderGeometry(0.21, 0.28, 0.85, 16), pm(0xffffff)); coat.position.set(0, 0.95, 0); g.userData.body.add(coat);
    const st = new THREE.Mesh(new THREE.TorusGeometry(0.1, 0.012, 6, 14), pm(0x222222)); st.position.set(0, 1.42, 0.1); st.rotation.x = Math.PI / 2.2; g.userData.body.add(st);
  }
  g.add(makeLabel(kind === 'doc' ? 'Doctor 🩺' : kind === 'nurse' ? 'Nurse 💉' : 'Reception 🛎️'));
  return g;
}
function buildInterior() {
  const g = new THREE.Group(); g.position.set(PRIVATE_ORIGIN.x, 0, PRIVATE_ORIGIN.z);
  const HX = 18, HZ = 13, H = 4.4, wall = 0xe9f3f1, npcs = [], I = { g, npcs };
  // floor tiles
  const fc = document.createElement('canvas'); fc.width = fc.height = 64; const fx = fc.getContext('2d');
  fx.fillStyle = '#f2f6f8'; fx.fillRect(0, 0, 64, 64); fx.fillStyle = '#e4ecf1'; fx.fillRect(0, 0, 32, 32); fx.fillRect(32, 32, 32, 32);
  const ft = new THREE.CanvasTexture(fc); ft.wrapS = ft.wrapT = THREE.RepeatWrapping; ft.repeat.set(HX, HZ);
  const floor = new THREE.Mesh(new THREE.PlaneGeometry(HX * 2 + 2, HZ * 2 + 2), new THREE.MeshStandardMaterial({ map: ft, roughness: 0.3, metalness: 0.05 }));
  floor.rotation.x = -Math.PI / 2; floor.position.y = 0.01; g.add(floor);
  const ceil = pplane(g, HX * 2 + 2, HZ * 2 + 2, 0xf7f9fb, 0, H, 0, { side: THREE.DoubleSide }); ceil.rotation.x = Math.PI / 2;
  pbox(g, HX * 2 + 1, H, 0.5, wall, 0, H / 2, -HZ); pbox(g, HX * 2 + 1, H, 0.5, wall, 0, H / 2, HZ);
  pbox(g, 0.5, H, HZ * 2 + 1, wall, -HX, H / 2, 0); pbox(g, 0.5, H, HZ * 2 + 1, wall, HX, H / 2, 0);
  pbox(g, HX * 2, 1.0, 0.55, 0x3f8fb5, 0, 0.5, -HZ + 0.02); pbox(g, HX * 2, 1.0, 0.55, 0x3f8fb5, 0, 0.5, HZ - 0.02);
  pbox(g, 0.55, 1.0, HZ * 2, 0x3f8fb5, -HX + 0.02, 0.5, 0); pbox(g, 0.55, 1.0, HZ * 2, 0x3f8fb5, HX - 0.02, 0.5, 0);
  pbox(g, 0.5, H, 10, wall, 0, H / 2, -8); pbox(g, 0.5, H, 10, wall, 0, H / 2, 8); pbox(g, 0.5, 0.9, 6, wall, 0, H - 0.45, 0);
  // ceiling lights
  [-12, -6, 6, 12].forEach(x => [-8, 0, 8].forEach(z => pbox(g, 2.4, 0.08, 0.7, 0xffffff, x, H - 0.05, z, { emissive: 0xffffff, emissiveIntensity: 1.2 })));
  const amb = new THREE.AmbientLight(0xffffff, 0.75); g.add(amb);
  plight(g, 0xffffff, 0.9, 30, -9, 3.6, 0); plight(g, 0xffffff, 0.9, 30, 9, 3.6, -4); plight(g, 0xffffff, 0.9, 30, 9, 3.6, 7);
  // signs
  const sg = (txt, bg, fg, w, h, x, y, z, ry) => { const m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ map: makeSignTex(txt, bg, fg) })); m.position.set(x, y, z); m.rotation.y = ry || 0; g.add(m); return m; };
  sg('റിസപ്ഷൻ · Reception', '#2b6cb0', '#ffffff', 6, 1.4, -9, 3.3, -HZ + 0.3, 0);
  sg('വാർഡ് · Ward', '#1f9d55', '#ffffff', 4, 1.0, 0.28, 3.7, 0, Math.PI / 2);
  sg('EXIT · പുറത്തേക്ക്', '#0a7d3b', '#ffffff', 3.4, 0.85, -9, 3.6, HZ - 0.3, Math.PI);
  // reception
  pbox(g, 6.5, 1.1, 1.2, 0xf6f6f2, -9, 0.55, -7.5); pbox(g, 6.8, 0.1, 1.5, 0x2b6cb0, -9, 1.15, -7.5);
  pbox(g, 0.7, 0.45, 0.06, 0x20262e, -8, 1.45, -7.4); pbox(g, 0.5, 0.05, 0.4, 0x20262e, -8, 1.22, -7.4);
  const rec = mkStaff('recep'); rec.position.set(-9, 0, -9.2); g.add(rec); npcs.push(rec);
  // waiting chairs
  [3, 6].forEach(z => { for (let x = -15; x <= -3; x += 3) { pbox(g, 1.3, 0.12, 0.9, 0x2b6cb0, x, 0.5, z); pbox(g, 1.3, 0.7, 0.12, 0x2b6cb0, x, 0.9, z - 0.4); pbox(g, 0.1, 0.5, 0.1, 0x555555, x - 0.5, 0.25, z); pbox(g, 0.1, 0.5, 0.1, 0x555555, x + 0.5, 0.25, z); } });
  const tv = sg('ആരോഗ്യം ശ്രദ്ധിക്കൂ ❤️', '#111827', '#ffd166', 3.2, 1.8, -4, 2.6, HZ - 0.3, Math.PI);
  [[-16, -11], [-16, 10], [-2, -11], [-2, 11], [16.5, 11.5]].forEach(c => { pcyl(g, 0.45, 0.35, 0.7, 0xc47a8f, c[0], 0.35, c[1], 10); psph(g, 0.75, 0x2f9e44, c[0], 1.3, c[1]); });
  pcyl(g, 0.3, 0.3, 1.0, 0xcfe8f5, -17, 0.5, -3, 10); pbox(g, 0.5, 0.4, 0.5, 0x2b6cb0, -17, 1.2, -3);
  // exit door
  pbox(g, 3.4, 3.4, 0.25, 0x8a9098, -9, 1.7, HZ - 0.2); pbox(g, 2.8, 3.0, 0.3, 0x7ee2a8, -9, 1.5, HZ - 0.3, { emissive: 0x2ecc71, emissiveIntensity: 0.6 });
  // ward: beds, curtains
  const bedZ = [-8, -2.5, 3, 8.5];
  bedZ.forEach(z => {
    pbox(g, 2.2, 0.3, 1.1, 0x9aa1a9, 11, 0.5, z); pbox(g, 2.1, 0.18, 1.0, 0xffffff, 11, 0.74, z); pbox(g, 0.45, 0.12, 0.7, 0xd9ecf7, 11.7, 0.88, z);
    pbox(g, 0.1, 0.95, 1.15, 0x7a8089, 12.15, 0.8, z);
    [-0.5, 0.5].forEach(zz => { pbox(g, 0.06, 0.4, 0.06, 0x555555, 10.1, 0.2, z + zz); pbox(g, 0.06, 0.4, 0.06, 0x555555, 11.9, 0.2, z + zz); });
  });
  [-5.25, 0.25, 5.75].forEach(z => {
    pbox(g, 0.05, 0.05, 6, 0xb8bec6, 11, 3.4, z);
    const c = new THREE.Mesh(new THREE.PlaneGeometry(5.6, 2.2), new THREE.MeshStandardMaterial({ color: 0x9fe3d6, transparent: true, opacity: 0.55, side: THREE.DoubleSide }));
    c.rotation.y = Math.PI / 2; c.position.set(11, 2.2, z); c.rotation.y = Math.PI / 2; g.add(c);
  });
  // player's bed items: IV stand, monitor
  I.bed = { x: 11, z: -8 };
  pcyl(g, 0.04, 0.04, 2.3, 0xb8bec6, 12.8, 1.15, -9.3, 6); pbox(g, 0.5, 0.05, 0.5, 0xb8bec6, 12.8, 0.03, -9.3);
  const bag = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.32, 0.07), new THREE.MeshStandardMaterial({ color: 0xbfe9ff, transparent: true, opacity: 0.7 })); bag.position.set(12.8, 2.2, -9.3); g.add(bag);
  pcyl(g, 0.01, 0.01, 1.4, 0xdddddd, 12.2, 1.6, -9.0, 4);
  pcyl(g, 0.05, 0.05, 1.7, 0x8a9098, 13.5, 0.85, -8, 8); pbox(g, 0.18, 1.0, 1.5, 0x20262e, 13.4, 1.9, -8);
  const ecg = document.createElement('canvas'); ecg.width = 256; ecg.height = 160; const ex = ecg.getContext('2d'); ex.fillStyle = '#06140c'; ex.fillRect(0, 0, 256, 160);
  const etex = new THREE.CanvasTexture(ecg);
  const scr = new THREE.Mesh(new THREE.PlaneGeometry(1.3, 0.85), new THREE.MeshBasicMaterial({ map: etex })); scr.position.set(13.29, 1.9, -8); scr.rotation.y = -Math.PI / 2; g.add(scr);
  I.ecg = { c: ecg, x: ex, tex: etex, at: 0, ly: 100, beat: false };
  // other patient on bed 3 + blanket over player's bed
  const pat = makeAvatarMesh('male', '#a9c8e8', 'short', null, { skin: 0xae7a50 }); pat.userData.walkAmp = 0; pat.rotation.order = 'YXZ'; pat.rotation.x = -Math.PI / 2; pat.rotation.y = -Math.PI / 2; pat.position.set(10.15, 0.9, 3); g.add(pat); npcs.push(pat);
  pbox(g, 1.2, 0.1, 1.0, 0x5aa9e6, 10.6, 0.93, 3);
  I.blanket = pbox(g, 1.2, 0.1, 1.0, 0x5aa9e6, 10.5, 0.96, -8); I.blanket.visible = false;
  // staff
  const doc = mkStaff('doc'); doc.position.set(8.8, 0, -5.6); doc.rotation.y = Math.atan2(2.2, -2.4); g.add(doc); npcs.push(doc);
  const nurse = mkStaff('nurse'); nurse.position.set(6, 0, -1); g.add(nurse); npcs.push(nurse); I.nurse = nurse;
  // equipment
  pbox(g, 1.2, 1.1, 0.8, 0xd62828, 16.4, 0.55, -11.6); pbox(g, 1.0, 0.1, 0.7, 0xffffff, 16.4, 1.15, -11.6);
  pcyl(g, 0.2, 0.2, 1.1, 0x2f9e44, 16.9, 0.55, -10.4, 10);
  pbox(g, 0.6, 0.1, 0.6, 0x20262e, 4, 0.55, -11); pbox(g, 0.6, 0.6, 0.08, 0x20262e, 4, 0.9, -11.3);
  [-0.35, 0.35].forEach(x => { const w = new THREE.Mesh(new THREE.TorusGeometry(0.3, 0.03, 6, 14), pm(0x20262e)); w.position.set(4 + x, 0.32, -11); w.rotation.y = Math.PI / 2; g.add(w); });
  pbox(g, 0.4, 2.0, 5, 0xdfe6ea, 17.5, 1.0, 5); pbox(g, 0.1, 1.6, 4.6, 0x9fd6f0, 17.25, 1.1, 5, { transparent: true, opacity: 0.6 });
  sg('മരുന്ന് സൂക്ഷിക്കുക 💊', '#2b6cb0', '#ffffff', 3.4, 0.8, 17.2, 3.0, 5, -Math.PI / 2);
  I.exit = { x: -9, z: 11.6 };
  return I;
}
function ecgY(ph) {
  if (ph < 0.12) return 0.12 * Math.sin(ph / 0.12 * Math.PI); if (ph > 0.28 && ph < 0.30) return -0.15;
  if (ph >= 0.30 && ph < 0.34) return 1 - Math.abs((ph - 0.32) / 0.02); if (ph >= 0.34 && ph < 0.36) return -0.25;
  if (ph > 0.5 && ph < 0.7) return 0.25 * Math.sin((ph - 0.5) / 0.2 * Math.PI); return 0;
}
function interiorTick(now, dt) {
  const I = ST.interior; if (!I) return;
  const E = I.ecg, bpm = 70 + (ST.treat ? 0 : 10), ph = (now / 1000 * bpm / 60) % 1;
  if (now - E.at > 70) {
    E.at = now; const c = E.x; c.drawImage(E.c, -4, 0); c.fillStyle = '#06140c'; c.fillRect(250, 0, 6, 160);
    const y = 100 - ecgY(ph) * 60; c.strokeStyle = '#37ff7a'; c.lineWidth = 2; c.beginPath(); c.moveTo(247, E.ly); c.lineTo(253, y); c.stroke(); E.ly = y;
    c.fillStyle = '#06140c'; c.fillRect(0, 0, 100, 30); c.fillStyle = '#37ff7a'; c.font = 'bold 22px monospace'; c.fillText('HR ' + bpm, 6, 24); E.tex.needsUpdate = true;
  }
  if (ph > 0.32 && ph < 0.36) { if (!E.beat) { E.beat = true; blip(1000, 0.09, 0.08 * earVol(PRIVATE_ORIGIN.x + 13, PRIVATE_ORIGIN.z - 8, 40) + 0.02); } } else E.beat = false;
  const n = I.nurse, t = now / 1000, z = 2.5 + Math.sin(t * 0.35) * 6, dz = Math.cos(t * 0.35);
  n.position.z = z; n.position.x = 6; n.rotation.y = dz > 0 ? 0 : Math.PI; n.userData.walkAmp = 0.45;
  I.npcs.forEach(m => tickAvatar(m, now));
}
function enterHospital() {
  ST.phase = 'hospital'; ST.step = null; chip(null);
  const I = buildInterior(); ST.interior = I; scene.add(I.g);
  privScene = { group: I.g, radius: 22, prevBg: scene.background, prevFog: scene.fog, back: { x: SPAWN_OUT.x, y: 0, z: SPAWN_OUT.z, rotY: Math.PI }, hospital: true };
  scene.background = new THREE.Color(0xcfe3ee); scene.fog = new THREE.Fog(0xdfe9f0, 35, 90);
  ST.pseudo = privScene;
  const bx = PRIVATE_ORIGIN.x + I.bed.x, bz = PRIVATE_ORIGIN.z + I.bed.z;
  myAvatar.visible = true; myAvatar.rotation.order = 'YXZ'; myAvatar.rotation.x = -Math.PI / 2; myAvatar.rotation.y = -Math.PI / 2;
  myAvatar.position.set(bx - 0.85, 0.93, bz);
  I.blanket.position.set(PRIVATE_ORIGIN.x + 0, 0, 0); I.blanket.position.set(10.5, 0.97, -8); I.blanket.visible = true;
  camYaw = Math.PI * 0.75; camPitch = 0.35;
  socket.emit('move', { x: myAvatar.position.x, y: myAvatar.position.y, z: myAvatar.position.z, rotY: myAvatar.rotation.y });
  ST.treat = { t0: performance.now(), hp0: ST.hp, said: false };
  ST.unlocked = false;
  $('hosp-card').style.display = 'block'; $('hosp-out').style.display = 'none';
  $('hosp-title').textContent = '🏥 Treatment — vishramikku'; $('hosp-bar').firstElementChild.style.width = '0%';
  fade(false);
  setTimeout(() => showToast('👨‍⚕️ Doctor: "Ayyo, nalla adi kittiyallo! Kidannolu, njan nokkaam."'), 700);
}
function hospitalTick(now, dt) {
  if (ST.pseudo !== privScene && !ST.leaving) { leaveHospital(true); return; }
  interiorTick(now, dt);
  const T = ST.treat;
  if (T) {
    const k = clamp((now - T.t0) / (CFG.hospitalStaySec * 1000), 0, 1);
    ST.hp = Math.max(T.hp0, T.hp0 + (CFG.hospitalHp - T.hp0) * k);
    $('hosp-bar').firstElementChild.style.width = (k * 100) + '%';
    $('hosp-sub').textContent = '⏳ ' + Math.ceil(CFG.hospitalStaySec * (1 - k)) + ' sec koodi — pinne pokaam';
    if (k > 0.45 && !T.said) { T.said = true; showToast('👩‍⚕️ Nurse: "Drip ittittund, angane kidannolu 😊"'); }
    if (k >= 1) {
      ST.treat = null; ST.unlocked = true; lockControls(false); standUp();
      const I = ST.interior; I.blanket.visible = false;
      myAvatar.position.set(PRIVATE_ORIGIN.x + 9.2, 0, PRIVATE_ORIGIN.z - 5.0); myAvatar.rotation.y = Math.PI; camYaw = Math.PI;
      $('hosp-title').textContent = '✅ Treatment kazhinju — ' + CFG.hospitalHp + '% health'; $('hosp-sub').textContent = 'Exit door-ilekku nadakku allengil button amarthu';
      $('hosp-out').style.display = 'inline-block';
      showToast('👨‍⚕️ Doctor: "Ini fight-um accident-um ozhivaakku! ' + CFG.hospitalHp + '% maathrame ullu — medicine vaangi kazhikku."');
    }
  } else if (ST.unlocked && ST.interior) {
    const e = ST.interior.exit, P = myAvatar.position;
    if (Math.hypot(P.x - (PRIVATE_ORIGIN.x + e.x), P.z - (PRIVATE_ORIGIN.z + e.z)) < 2.2) leaveHospital(false);
    if (Math.abs(P.x - PRIVATE_ORIGIN.x) < 0.7 && Math.abs(P.z - PRIVATE_ORIGIN.z) > 3.2) P.x = PRIVATE_ORIGIN.x + (ST.sideX || 1) * 0.7;
    else if (Math.abs(P.x - PRIVATE_ORIGIN.x) >= 0.7) ST.sideX = Math.sign(P.x - PRIVATE_ORIGIN.x);
  }
  paintHud();
}
$('hosp-out').onclick = () => { if (ST.phase === 'hospital' && ST.unlocked) leaveHospital(false); };
function leaveHospital(emergency) {
  if (ST.leaving) return; ST.leaving = true;
  fade(true, '🚪 Hospital-il ninnu purathekku…');
  setTimeout(() => {
    if (ST.interior) { scene.remove(ST.interior.g); disposeGroup(ST.interior.g); ST.interior = null; }
    if (privScene === ST.pseudo && privScene) { scene.background = privScene.prevBg; scene.fog = privScene.prevFog; privScene = null; }
    else if (!privScene) { scene.background = null; }
    ST.pseudo = null;
    standUp(); myAvatar.visible = true;
    myAvatar.position.set(SPAWN_OUT.x, 0, SPAWN_OUT.z); myAvatar.rotation.y = Math.PI; camYaw = Math.PI; camPitch = 0.2;
    socket.emit('move', { x: myAvatar.position.x, y: 0, z: myAvatar.position.z, rotY: myAvatar.rotation.y });
    ST.hp = Math.max(ST.hp, CFG.hospitalHp); ST.down = false;
    const A = ST.A; A.x = BAY.x; A.z = BAY.z; A.r = Math.PI / 2; A.speed = 0; A.doorT = 0; A.door = 0; A.wp = []; applyAmb();
    ST.phase = 'ok'; ST.step = null; ST.unlocked = false; ST.leaving = false; ST.treat = null; ST.sirenOn = false;
    lockControls(false); $('hosp-card').style.display = 'none'; chip(null); paintHud(); saveHp(); fade(false);
    showToast('💊 Ningalkku ippo ' + Math.round(ST.hp) + '% health maathram. Medical store-il ninnu medicine vaangu — allengil 10 min kaliyuka, health melle kooduum.');
  }, 700);
}

/* =========================================================
   7) WORLD BUILD — hospital + medical store, map markers
   ========================================================= */
function signPlane(g, txt, bg, fg, w, h, x, y, z, ry) {
  const m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ map: makeSignTex(txt, bg, fg) })); m.position.set(x, y, z); m.rotation.y = ry === undefined ? Math.PI : ry; g.add(m); return m;
}
function buildHospitalExterior() {
  const g = new THREE.Group(); g.position.set(HOSP.x, 0, HOSP.z); scene.add(g);
  const glassO = { roughness: 0.15, metalness: 0.3 };
  pbox(g, 34, 12, 14, 0xf3f5f7, 0, 6, 0); pbox(g, 34.3, 1.1, 14.3, 0x2b6cb0, 0, 8.2, 0); pbox(g, 34.6, 0.5, 14.6, 0x1f4e80, 0, 12.2, 0);
  [6.2, 10.0].forEach(y => { for (let x = -15; x <= 15; x += 3) {
    if (y < 8 && (Math.abs(x) < 5 || (x >= -14 && x <= -5))) continue;
    pbox(g, 1.7, 1.7, 0.12, 0x8fcbe8, x, y, -7.05, glassO); pbox(g, 0.08, 1.7, 0.14, 0xffffff, x, y, -7.06); } });
  for (let x = 8; x <= 15; x += 3) pbox(g, 1.7, 1.6, 0.12, 0x8fcbe8, x, 2.3, -7.05, glassO);
  // main entrance + canopy
  pbox(g, 4.2, 3.2, 0.15, 0x9fd6f0, 0, 1.7, -7.1, glassO); pbox(g, 0.1, 3.2, 0.18, 0xffffff, 0, 1.7, -7.12);
  pbox(g, 9, 0.35, 4, 0xffffff, 0, 4.4, -9); [-4, 4].forEach(x => pcyl(g, 0.25, 0.25, 4.4, 0xffffff, x, 2.2, -10.8, 10));
  signPlane(g, 'ആശുപത്രി · HOSPITAL', '#1f4e80', '#ffffff', 9, 1.3, 0, 5.7, -7.12);
  // emergency bay
  pbox(g, 3.2, 3.0, 0.15, 0xd62828, -10, 1.6, -7.1); pbox(g, 10, 0.4, 5, 0xd62828, -10, 4.6, -9.5);
  [-14.5, -5.5].forEach(x => pcyl(g, 0.25, 0.25, 4.6, 0xffffff, x, 2.3, -11.8, 10));
  signPlane(g, 'അത്യാഹിതം · EMERGENCY', '#d62828', '#ffffff', 9, 1.1, -10, 5.7, -7.12);
  pplane(g, 44, 8, 0xd8dce0, 0, 0.035, -11); pplane(g, 9, 5, 0xe8a4a0, -10, 0.045, -11);
  for (let i = 0; i < 6; i++) pbox(g, 0.25, 0.02, 4.6, 0xffffff, -13.5 + i * 1.4, 0.06, -11);
  // roof cross
  pbox(g, 3.4, 3.4, 0.4, 0xffffff, 0, 14.4, -3); pbox(g, 2.4, 0.8, 0.5, 0xd62828, 0, 14.4, -3, { emissive: 0xd62828, emissiveIntensity: 0.5 }); pbox(g, 0.8, 2.4, 0.5, 0xd62828, 0, 14.4, -3, { emissive: 0xd62828, emissiveIntensity: 0.5 });
  const em = emojiSprite('🏥', 4); em.position.set(0, 18, -3); g.add(em);
  // garden
  [[-19, -10], [19, -10], [-19, 0], [19, 0]].forEach(p => ppalm(g, p[0], p[1]));
  pbench(g, -15, -12.5); pbench(g, 15, -12.5); plamp(g, -8, -12.5); plamp(g, 8, -12.5);
  [-10, 10].forEach(x => { pbox(g, 7, 0.5, 1.3, 0x7a5230, x, 0.25, -13.5); for (let i = 0; i < 7; i++) psph(g, 0.22, [0xff5c8a, 0xffd23f, 0xffffff, 0xb57bff][i % 4], x - 3 + i, 0.65, -13.5); });
  plight(g, 0xfff1d0, 0.7, 20, 0, 3.8, -10); plight(g, 0xff6a6a, 0.6, 18, -10, 3.8, -10);
  // driveways to the road
  [BAY.x, 30].forEach(x => pplane(scene, 6, 4, 0x55565c, x, 0.027, 64.2));
  // parked ambulance label
  const st = emojiSprite('🚑', 1.6); st.position.set(BAY.x, 5.4, BAY.z); scene.add(st);
}
function buildPharmacy() {
  const g = new THREE.Group(); g.position.set(PHARM.x, 0, PHARM.z); scene.add(g);
  pplane(g, 10, 7, 0xdfe7e1, 0, 0.04, 0);
  pbox(g, 10, 5, 0.4, 0xe9f5ec, 0, 2.5, 3.3); [-4.8, 4.8].forEach(x => pbox(g, 0.4, 5, 7, 0xe9f5ec, x, 2.5, 0));
  pbox(g, 10.6, 0.3, 7.6, 0x1f9d55, 0, 5.15, 0); pbox(g, 10, 1.2, 0.4, 0x1f9d55, 0, 4.4, -3.3);
  signPlane(g, 'മെഡിക്കൽ സ്റ്റോർ', '#0f7b6c', '#ffffff', 7, 1.0, 0, 4.4, -3.52);
  pbox(g, 7, 1.05, 0.9, 0xfdfdfb, 0, 0.55, -2.2); pbox(g, 7.2, 0.08, 1.1, 0x1f9d55, 0, 1.1, -2.2);
  for (let r = 0; r < 3; r++) for (let x = -4; x <= 4; x += 0.55) pbox(g, 0.4, 0.5, 0.35, [0xff6b6b, 0xffd23f, 0x4dd4ac, 0x6ea8fe, 0xffffff][(Math.round(x * 2) + r) % 5 < 0 ? 0 : (Math.round(x * 2) + r) % 5], x, 1.0 + r * 0.9, 2.95);
  pbox(g, 9.4, 0.08, 0.5, 0x8b5a2b, 0, 1.5, 2.95); pbox(g, 9.4, 0.08, 0.5, 0x8b5a2b, 0, 2.4, 2.95); pbox(g, 9.4, 0.08, 0.5, 0x8b5a2b, 0, 3.3, 2.95);
  pbox(g, 2.2, 2.2, 0.3, 0xffffff, 0, 6.4, -3.2); pbox(g, 1.6, 0.5, 0.4, 0x1f9d55, 0, 6.4, -3.2); pbox(g, 0.5, 1.6, 0.4, 0x1f9d55, 0, 6.4, -3.2);
  const pill = emojiSprite('💊', 1.8); pill.position.set(0, 8.2, -3.2); g.add(pill);
  const ph = makeAvatarMesh('female', '#ffffff', 'pony'); ph.position.set(0, 0.04, -0.8); ph.rotation.y = Math.PI; ph.add(makeLabel('Pharmacist 🤖'));
  const coat = new THREE.Mesh(new THREE.CylinderGeometry(0.21, 0.28, 0.85, 16), pm(0xffffff)); coat.position.set(0, 0.95, 0); ph.userData.body.add(coat); g.add(ph);
  plight(g, 0xd7ffe6, 0.8, 16, 0, 3.8, 0);
  pbench(g, -7, -5); plamp(g, 6.5, -5.5);
  PHARM.npc = ph;
}
function setupShop() {
  SHOPS.med = { x: 2, z: 69.5, range: 6, title: '💊 Medical Store · മെഡിക്കൽ സ്റ്റോർ', prompt: '💊 Press B for medicine & energy drinks' };
  CATEGORY_LABELS.medicine = '💊 Medicines'; CATEGORY_LABELS.energy = '⚡ Energy Drinks';
}
const MED_ITEMS = [
  { id: 'med_bandage', name: 'Bandage', emoji: '🩹', price: 3, category: 'medicine', shop: 'med', hp: 8 },
  { id: 'med_paracetamol', name: 'Paracetamol', emoji: '💊', price: 5, category: 'medicine', shop: 'med', hp: 12 },
  { id: 'med_gel', name: 'Pain Relief Gel', emoji: '🧴', price: 7, category: 'medicine', shop: 'med', hp: 15 },
  { id: 'med_antibiotic', name: 'Antibiotic Course', emoji: '💉', price: 14, category: 'medicine', shop: 'med', hp: 30 },
  { id: 'med_firstaid', name: 'First-Aid Kit', emoji: '⛑️', price: 22, category: 'medicine', shop: 'med', hp: 45 },
  { id: 'en_coconut', name: 'Karikku (Tender Coconut)', emoji: '🥥', price: 4, category: 'energy', shop: 'med', hp: 10 },
  { id: 'en_ors', name: 'ORS Drink', emoji: '🧃', price: 5, category: 'energy', shop: 'med', hp: 12 },
  { id: 'en_boost', name: 'Boost / Horlicks', emoji: '🥛', price: 6, category: 'energy', shop: 'med', hp: 15 },
  { id: 'en_energy', name: 'Energy Drink', emoji: '🥫', price: 10, category: 'energy', shop: 'med', hp: 20 }
];
const MED = {}; MED_ITEMS.forEach(i => { MED[i.id] = i; });
function mergeCatalog() { MED_ITEMS.forEach(i => { if (!shopCatalog.find(x => x.id === i.id)) shopCatalog.push(i); }); }
function applyMed(id) {
  const it = MED[id]; if (!it) return;
  if (ST.hp >= 100) showToast(it.emoji + ' ' + it.name + ' kazhichu — health munpe full aayirunnu 😅');
  heal(it.hp, it.emoji + ' ' + it.name + ' kazhichu  +' + it.hp + '% health');
}
function medBuy(id) {
  if (nearShopMode() !== 'med') { showToast('Medical store-inte adutthu ethuka'); return; }
  const it = MED[id], bal = parseInt(balloonEl.textContent, 10) || 0;
  if (bal < it.price) { showToast('Not enough balloons 🎈 for that'); return; }
  balloonEl.textContent = bal - it.price; applyMed(id);
  if (PHARM.npc && Math.random() < 0.6) setTimeout(() => showToast(pick(['👩‍⚕️ "Jaagrathayode irikku, vegam sheri aakum!"', '👩‍⚕️ "Dose thettikkalle, ketto!"', '👩‍⚕️ "Ini fight-inu pokalle, kuttyé!"'])), 600);
  renderShop();
}
const origEmit = socket.emit.bind(socket);
socket.emit = function (ev) {
  if (ev === 'buyItem' && arguments[1] && MED[arguments[1]] && CFG.medMode === 'local') { medBuy(arguments[1]); return socket; }
  return origEmit.apply(socket, arguments);
};
socket.on('purchaseOk', (d) => { if (d && MED[d.itemId] && CFG.medMode === 'server') applyMed(d.itemId); });
socket.on('shopCatalog', () => { mergeCatalog(); renderShop(); });

{
  const od = window.drawMap;
  if (typeof od === 'function') window.drawMap = function () {
    od();
    mapCtx.font = '15px serif'; mapCtx.textAlign = 'center'; mapCtx.textBaseline = 'middle';
    const h = worldToMap(HOSP.x, 76); mapCtx.fillText('🏥', h.px, Math.min(h.py, mapCanvas.height - 8));
    const p = worldToMap(2, 71); mapCtx.fillText('💊', p.px, Math.min(p.py, mapCanvas.height - 8));
    if (ST.A && ST.phase === 'wait') { const a = worldToMap(ST.A.x, ST.A.z); mapCtx.fillText('🚑', a.px, a.py); }
  };
}

/* =========================================================
   8) DAMAGE DETECTION (after normal movement)
   ========================================================= */
function houseAt(x, z) {
  const gx = Math.floor(x / BLOCK), gz = Math.floor(z / BLOCK);
  if (gx < -GRID / 2 || gx >= GRID / 2 || gz < -GRID / 2 || gz >= GRID / 2) return null;
  if (gx === 0 && gz === 0) return null; if (gx === -1 && (gz === 0 || gz === -1)) return null;
  const cx = gx * BLOCK + BLOCK / 2, cz = gz * BLOCK + BLOCK / 2;
  return (Math.abs(x - cx) < 3.9 && Math.abs(z - cz) < 3.9) ? { cx, cz } : null;
}
function crashDmg(sp) { return clamp(Math.abs(sp) / 0.32 * CFG.crashMax, CFG.crashMin, CFG.crashMax); }
function detect(now) {
  if (ST.phase !== 'ok' || privScene) return;
  const me = myAvatar.position;
  PEDS.forEach(p => {
    if (p.state === 'fight') {
      const e = p.g.userData.emote;
      if (e && e.type === 'punch' && e !== p._he) { p._he = e; if (Math.hypot(p.g.position.x - me.x, p.g.position.z - me.z) < 2.6) hurt(rnd(CFG.punchDmg[0], CFG.punchDmg[1]), 'punch'); }
    }
  });
  const c = drivingCarId && cars[drivingCarId];
  if (c && !c.isPlane) {
    const p = c.group.position, sp = carSpeed, cool = now > ST.crashCool;
    let hit = false;
    if (cool && Math.abs(ST.lastSpeed) > 0.1 && sp === 0 && p.x >= RIVER.x1 - 2.6) { hurt(crashDmg(ST.lastSpeed), 'wall'); hit = true; }
    if (cool && !hit && Math.abs(sp) > 0.06 && houseAt(p.x, p.z)) {
      hurt(crashDmg(sp), 'house'); hit = true;
      if (ST.lastCarPos) { p.x = ST.lastCarPos.x; p.z = ST.lastCarPos.z; }
      carSpeed = -sp * 0.3;
    }
    if (cool && !hit && Math.abs(sp) > 0.08) {
      for (const id in cars) { if (id === drivingCarId) continue; const o = cars[id]; if (Math.hypot(o.group.position.x - p.x, o.group.position.z - p.z) < 2.5) {
        hurt(crashDmg(sp), 'car'); hit = true; if (ST.lastCarPos) { p.x = ST.lastCarPos.x; p.z = ST.lastCarPos.z; } carSpeed = -sp * 0.35; break; } }
    }
    if (cool && !hit && Math.abs(sp) > 0.1) {
      PEDS.forEach(pd => {
        if (pd.state === 'down' || !pd.g.visible) return;
        if (Math.hypot(pd.g.position.x - p.x, pd.g.position.z - p.z) < 1.8) {
          pd.state = 'down'; pd.hp = 0; pd.downUntil = now + 4500; say(pd, 'down'); pd.g.rotation.order = 'YXZ'; pd.g.rotation.x = -Math.PI / 2; pd.g.position.y = 0.15;
          hurt(4, 'ped'); hit = true;
        }
      });
    }
    if (hit) ST.crashCool = now + 700;
    ST.lastCarPos = { x: c.group.position.x, z: c.group.position.z };
  } else if (c && c.isPlane) {
    const y = c.group.position.y;
    if (ST.prevY > 0.05 && y <= 0.001 && ST.prevVy < -0.1 && now > ST.crashCool) { hurt(clamp(-ST.prevVy * 170, 10, 60), 'plane'); ST.crashCool = now + 900; showToast('✈️ Crash landing! 💥'); }
    ST.prevY = y; ST.prevVy = planeVy;
  } else { ST.lastCarPos = null; ST.prevY = 0; ST.prevVy = 0; }
  ST.lastSpeed = drivingCarId ? carSpeed : 0;
}

/* =========================================================
   9) MAIN LOOP HOOK
   ========================================================= */
function isLocked() { return ST.phase !== 'ok' && !(ST.phase === 'hospital' && ST.unlocked); }
function orbit(p, d) {
  camera.position.x = p.x - Math.sin(camYaw) * d * Math.cos(camPitch);
  camera.position.z = p.z - Math.cos(camYaw) * d * Math.cos(camPitch);
  camera.position.y = p.y + 1.2 + d * Math.sin(camPitch);
  camera.lookAt(p.x, p.y + 1, p.z);
}
function chase() {
  const A = ST.A, fx = Math.cos(A.r), fz = -Math.sin(A.r);
  camera.position.lerp(new THREE.Vector3(A.x - fx * 11, 5.2, A.z - fz * 11), 0.08);
  camera.lookAt(A.x + fx * 2, 1.5, A.z + fz * 2);
}
function pre(now, dt) {
  if (!inGame()) return;
  if (ST.phase === 'ok' && !ST.down && ST.hp < 100) ST.hp = Math.min(100, ST.hp + CFG.regenPerSec * dt);
  if (ST.phase === 'wait') rescueTick(now, dt); else if (ST.phase === 'hospital') hospitalTick(now, dt);
  ambLights(now); audioTick(now, dt);
  if (ST.A && ST.phase !== 'wait') { /* parked */ applyAmb(); }
  if (now - ST.paintAt > 100) { ST.paintAt = now; paintHud(); }
  if (now - ST.saveAt > 2500) { ST.saveAt = now; saveHp(); }
  const showBtn = ST.phase === 'ok' && ST.hp < 100 && !privScene && !drivingCarId;
  $('amb-btn').style.display = showBtn ? 'block' : 'none';
  if (ST.phase === 'ok') chip(null);
}
function post(now) {
  detect(now);
  if (ST.shake > 0.01) {
    camera.position.x += (Math.random() - 0.5) * ST.shake; camera.position.y += (Math.random() - 0.5) * ST.shake * 0.7; camera.position.z += (Math.random() - 0.5) * ST.shake;
    ST.shake *= 0.86;
  }
}
const origUM = window.updateMovement;
window.updateMovement = function () {
  const now = performance.now(), dt = Math.min(0.1, (now - ST.last) / 1000); ST.last = now;
  try { pre(now, dt); } catch (e) { console.error('[health] pre', e); }
  if (isLocked()) {
    try { if (ST.phase === 'wait' && (ST.step === 'return' || ST.step === 'fade')) chase(); else orbit(myAvatar.position, ST.phase === 'hospital' ? 4.5 : 6); } catch (e) { console.error(e); }
    return;
  }
  origUM();
  try { post(now); } catch (e) { console.error('[health] post', e); }
};

/* input blocking while locked */
addEventListener('keydown', e => {
  if (!document.body.classList.contains('h-lock')) return;
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  if (e.key === 'Escape') return;
  e.stopImmediatePropagation(); e.preventDefault();
}, true);
addEventListener('keydown', e => {
  if (e.key.toLowerCase() === 'c' && !/INPUT|TEXTAREA/.test((e.target.tagName || '')) && inGame() && !document.body.classList.contains('h-lock')) callAmbulance(false);
});
$('amb-btn').onclick = () => callAmbulance(false);
{ const fb = document.getElementById('fight-btn'); if (fb && typeof doPunch === 'function') fb.onclick = () => { if (!document.body.classList.contains('h-lock')) window.doPunch(); }; }

/* =========================================================
   10) INIT
   ========================================================= */
setupShop(); mergeCatalog();
buildHospitalExterior(); buildPharmacy();
ST.A = buildAmbulance(); applyAmb();
paintHud();
addEventListener('beforeunload', saveHp);
console.log('[health] add-on ready — hp', ST.hp);
})();
