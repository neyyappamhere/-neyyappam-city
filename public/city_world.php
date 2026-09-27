<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Neyyappam City</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root{
    --bg:#08010f; --surface:#110920; --card:#180d2a;
    --border:rgba(255,255,255,.07);
    --pk:#FE019A; --pkd:#c2006b; --pu:#9b5de5; --gold:#FFD700;
    --green:#00f593; --red:#ff4d6d;
    --text:rgba(255,255,255,.85); --muted:rgba(255,255,255,.35);
  }
  *{ box-sizing:border-box; }
  html,body{ margin:0; height:100%; overflow:hidden; font-family:'Space Grotesk',sans-serif; background:var(--bg); color:var(--text); }
  #canvas-wrap{ position:absolute; inset:0; }

  #topbar{
    position:absolute; top:0; left:0; right:0; z-index:5;
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 14px; pointer-events:none;
  }
  #brand{ display:flex; align-items:center; gap:8px; pointer-events:auto; }
  #brand img{ width:32px; height:32px; border-radius:8px; background:#fff; object-fit:contain; }
  #brand span{
    font-family:'Syne',sans-serif; font-weight:800; font-size:1.1em;
    background:linear-gradient(135deg,#FE019A 0%,#ff80d5 50%,#FFD700 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
  }

  #hud-right{ display:flex; gap:8px; pointer-events:none; }
  #status-pill, #balloon-pill{
    pointer-events:auto; display:flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:20px; font-size:.82em; font-weight:700;
    background:var(--card); border:1px solid var(--border); box-shadow:0 6px 18px rgba(0,0,0,.4);
  }
  #status-pill{ color:var(--muted); }
  .live-dot{ width:7px; height:7px; border-radius:50%; background:var(--green); animation:pulse 1.6s infinite; }
  @keyframes pulse{ 0%{box-shadow:0 0 0 0 rgba(0,245,147,.6);} 70%{box-shadow:0 0 0 8px rgba(0,245,147,0);} 100%{box-shadow:0 0 0 0 rgba(0,245,147,0);} }
  #balloon-pill{ color:var(--gold); }
  #balloon-pill i{ color:var(--pk); }
  #save-note{
    position:absolute; top:52px; right:14px; z-index:5; font-size:.68em; color:var(--muted);
    max-width:180px; text-align:right; pointer-events:none;
  }

  #voice-btn{
    position:absolute; top:64px; left:14px; z-index:5;
    background:var(--card); color:var(--text); border:1px solid var(--border);
    border-radius:24px; padding:9px 16px; font-weight:700; font-size:.82em; cursor:pointer;
  }
  #voice-btn.on{ background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border-color:transparent; }

  #map-btn, #stats-btn, #shop-btn, #inventory-btn{
    position:absolute; top:64px; z-index:5;
    background:var(--card); color:var(--text); border:1px solid var(--border);
    border-radius:24px; padding:9px 16px; font-weight:700; font-size:.82em; cursor:pointer;
  }
  #map-btn{ left:110px; }
  #stats-btn{ left:196px; }
  #shop-btn{ left:282px; }
  #inventory-btn{ left:368px; }

  /* --- Shop / Inventory panels (reuse the map/stats overlay look) --- */
  #shop-overlay, #inventory-overlay{
    position:absolute; inset:0; z-index:20; display:flex; align-items:center; justify-content:center;
    background:rgba(5,2,10,.75);
  }
  #shop-panel, #inventory-panel{
    background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.6); width:min(92vw, 460px); max-height:80vh; overflow-y:auto;
  }
  #shop-header, #inventory-header{
    display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;
    font-weight:700; font-size:.85em; position:sticky; top:0; background:var(--card); padding-bottom:6px;
  }
  #shop-header button, #inventory-header button{ background:none; border:none; color:var(--muted); font-size:1em; cursor:pointer; }
  .shop-balance{ color:var(--gold); font-weight:700; font-size:.82em; margin-bottom:10px; }
  .shop-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(120px,1fr)); gap:10px; }
  .shop-item{
    background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:10px;
    text-align:center; display:flex; flex-direction:column; gap:6px;
  }
  .shop-item .emoji{ font-size:1.8em; }
  .shop-item .name{ font-size:.75em; font-weight:700; }
  .shop-item .price{ font-size:.7em; color:var(--gold); }
  .shop-item button{
    margin-top:2px; padding:6px; border:none; border-radius:10px; font-weight:700; font-size:.72em; cursor:pointer;
    background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff;
  }
  .shop-item button:disabled{ background:var(--border); color:var(--muted); cursor:not-allowed; }
  .inv-row{
    display:flex; align-items:center; justify-content:space-between; gap:8px;
    padding:8px 0; border-bottom:1px solid var(--border); font-size:.85em;
  }
  .inv-row:last-child{ border-bottom:none; }
  .inv-row .inv-left{ display:flex; align-items:center; gap:8px; }
  .inv-row button{
    padding:5px 10px; border:none; border-radius:10px; font-weight:700; font-size:.72em; cursor:pointer;
    background:var(--surface); color:var(--text); border:1px solid var(--border);
  }
  .inv-empty{ color:var(--muted); font-size:.82em; text-align:center; padding:20px 0; }

  /* --- Nearby-player interact panel --- */
  #interact-panel{
    position:absolute; left:50%; bottom:100px; transform:translateX(-50%); z-index:6;
    background:rgba(20,15,30,.92); border:1px solid var(--border); border-radius:16px;
    padding:10px; display:none; flex-direction:column; gap:6px; min-width:200px;
    box-shadow:0 10px 30px rgba(0,0,0,.5);
  }
  #interact-panel .who{ font-size:.78em; color:var(--muted); text-align:center; margin-bottom:2px; }
  #interact-panel button{
    padding:9px 12px; border:none; border-radius:10px; font-weight:700; font-size:.8em; cursor:pointer;
    background:var(--surface); color:var(--text); border:1px solid var(--border); text-align:left;
  }
  #interact-panel button:hover{ background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }

  /* --- Toasts --- */
  #toast-stack{
    position:absolute; top:110px; right:14px; z-index:30; display:flex; flex-direction:column; gap:8px;
    align-items:flex-end; pointer-events:none;
  }
  .toast{
    background:var(--card); border:1px solid var(--border); color:var(--text);
    padding:10px 14px; border-radius:12px; font-size:.8em; font-weight:600;
    box-shadow:0 10px 25px rgba(0,0,0,.5); max-width:260px;
    animation:toastIn .25s ease-out, toastOut .3s ease-in 3.2s forwards;
  }
  @keyframes toastIn{ from{ opacity:0; transform:translateY(-8px);} to{ opacity:1; transform:translateY(0);} }
  @keyframes toastOut{ to{ opacity:0; transform:translateY(-8px);} }

  /* --- Proposal modal --- */
  #proposal-modal{
    position:absolute; inset:0; z-index:35; display:none; align-items:center; justify-content:center;
    background:rgba(5,2,10,.75);
  }
  #proposal-card{
    background:var(--card); border:1px solid var(--border); border-radius:18px; padding:24px;
    text-align:center; width:min(90vw,320px); box-shadow:0 20px 50px rgba(0,0,0,.6);
  }
  #proposal-card .heart{ font-size:2.4em; margin-bottom:6px; }
  #proposal-card .msg{ font-size:.9em; margin-bottom:16px; }
  #proposal-card .row{ display:flex; gap:10px; }
  #proposal-card button{
    flex:1; padding:10px; border:none; border-radius:12px; font-weight:700; cursor:pointer; font-size:.85em;
  }
  #proposal-accept{ background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  #proposal-reject{ background:var(--surface); color:var(--text); border:1px solid var(--border); }

  #job-banner{
    position:absolute; top:64px; left:50%; transform:translateX(-50%); z-index:5;
    background:var(--card); border:1px solid var(--border); color:#FFD700;
    padding:8px 16px; border-radius:16px; font-size:.8em; font-weight:600;
    display:none; max-width:280px; text-align:center;
  }
  #waypoint-readout{
    position:absolute; top:104px; left:50%; transform:translateX(-50%); z-index:5;
    background:rgba(0,0,0,.5); color:#fff; padding:5px 14px; border-radius:12px; font-size:.72em;
  }

  #map-overlay, #stats-overlay{
    position:absolute; inset:0; z-index:20; display:flex; align-items:center; justify-content:center;
    background:rgba(5,2,10,.75);
  }
  #map-panel, #stats-panel{
    background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.6); width:min(90vw, 400px);
  }
  #map-header, #stats-header{
    display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;
    font-weight:700; font-size:.85em;
  }
  #map-header button, #stats-header button{
    background:none; border:none; color:var(--muted); font-size:1em; cursor:pointer;
  }
  #map-canvas{ width:100%; height:auto; border-radius:12px; background:#2a2438; cursor:crosshair; }
  .stat-row{
    display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);
    font-size:.85em;
  }
  .stat-row:last-child{ border-bottom:none; }
  .stat-row span:first-child{ color:var(--muted); }

  #chat-toggle{
    position:absolute; z-index:6; left:14px; bottom:14px;
    background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border:none; border-radius:50%;
    width:52px; height:52px; font-size:1.2em; box-shadow:0 8px 20px rgba(254,1,154,.4); cursor:pointer;
    display:none; align-items:center; justify-content:center;
  }
  #chat-box{
    position:absolute; bottom:14px; left:14px; width:320px; max-height:230px;
    display:flex; flex-direction:column; z-index:5;
    background:var(--card); border:1px solid var(--border); border-radius:16px; overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.5);
  }
  #chat-log{ padding:10px; height:160px; overflow-y:auto; font-size:13px; }
  #chat-log div{ margin-bottom:6px; line-height:1.35; }
  #chat-log b{ color:var(--pk); }
  #chat-form{ display:flex; border-top:1px solid var(--border); }
  #chat-input{ flex:1; padding:10px 12px; border:none; outline:none; font-size:13px; font-family:inherit; background:var(--surface); color:var(--text); }
  #chat-input::placeholder{ color:var(--muted); }
  #chat-form button{ padding:0 16px; border:none; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; cursor:pointer; font-weight:700; }

  #controls-hint{
    position:absolute; bottom:14px; right:14px; z-index:5; color:var(--muted);
    font-size:11px; background:rgba(0,0,0,.4); padding:6px 10px; border-radius:8px;
  }

  #name-gate{
    position:absolute; inset:0; z-index:10; display:flex; align-items:center; justify-content:center;
    background:radial-gradient(circle at 20% 10%, rgba(254,1,154,.25) 0%, transparent 55%),
               radial-gradient(circle at 80% 90%, rgba(155,93,229,.25) 0%, transparent 55%),
               var(--bg);
  }
  #name-gate .card{
    background:var(--card); border:1px solid var(--border); padding:30px 28px; border-radius:22px;
    text-align:center; width:310px; box-shadow:0 20px 50px rgba(0,0,0,.6);
  }
  #name-gate h3{ margin:6px 0 2px; font-family:'Syne',sans-serif; font-weight:800; font-size:1.4em;
    background:linear-gradient(135deg,#FE019A 0%,#ff80d5 50%,#FFD700 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  #name-gate p.sub{ color:var(--muted); font-size:.85em; margin:0 0 16px; }
  #name-gate input{
    width:100%; padding:12px; margin:6px 0 14px; box-sizing:border-box;
    border:1.5px solid var(--border); border-radius:12px; font-size:14px; font-family:inherit;
    outline:none; background:var(--surface); color:var(--text);
  }
  #name-gate input:focus{ border-color:var(--pk); }
  .gender-row{ display:flex; gap:8px; margin-bottom:16px; }
  .gender-pill{
    flex:1; padding:10px 4px; border-radius:20px; border:1.5px solid var(--border);
    background:var(--surface); cursor:pointer; font-weight:600; font-size:.82em; color:var(--muted);
    transition:all .15s;
  }
  .gender-pill.selected{ border-color:var(--pk); background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  .picker-label{ text-align:left; font-size:.72em; color:var(--muted); margin-bottom:6px; font-weight:600; }
  .swatch-row{ display:flex; gap:8px; margin-bottom:16px; }
  .swatch{ width:32px; height:32px; border-radius:50%; border:2px solid var(--border); cursor:pointer; padding:0; }
  .swatch.selected{ border-color:#fff; box-shadow:0 0 0 2px var(--pk); }
  #name-gate button.enter{
    width:100%; padding:12px; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border:none;
    border-radius:12px; font-weight:700; cursor:pointer; font-size:14px; font-family:'Syne',sans-serif;
  }
  #name-gate .note{ margin-top:12px; font-size:.7em; color:var(--muted); }
  #name-gate .note.logged-in{ color:var(--green); }

  #joystick-zone{ position:absolute; left:20px; bottom:90px; width:120px; height:120px; z-index:6; display:none; touch-action:none; }
  #joystick-base{ width:100%; height:100%; border-radius:50%; background:rgba(255,255,255,.08); border:2px solid rgba(255,255,255,.25); }
  #joystick-stick{ position:absolute; top:35px; left:35px; width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg,#FE019A,#9b5de5); box-shadow:0 4px 14px rgba(0,0,0,.4); }

  @media (max-width:700px){
    #chat-box{ display:none; width:calc(100% - 28px); }
    #chat-box.open{ display:flex; }
    #chat-toggle{ display:flex; }
    #controls-hint{ display:none; }
    #joystick-zone{ display:block; }
    #voice-btn{ top:64px; left:auto; right:14px; }
    #map-btn{ top:110px; left:auto; right:14px; }
    #stats-btn{ top:156px; left:auto; right:14px; }
    #job-banner{ top:64px; max-width:70vw; font-size:.72em; }
    #waypoint-readout{ top:100px; }
  }
</style>
</head>
<body>

<div id="name-gate">
  <div class="card">
    <h3>Neyyappam City</h3>
    <p class="sub">Free-roam the city, collect balloons — no account needed</p>
    <input id="name-input" placeholder="Pick a name to play as" maxlength="20" />
    <div class="gender-row">
      <button type="button" class="gender-pill" data-gender="male">Male</button>
      <button type="button" class="gender-pill" data-gender="female">Female</button>
      <button type="button" class="gender-pill" data-gender="other">Other</button>
    </div>
    <div class="picker-label">Outfit color</div>
    <div class="swatch-row">
      <button type="button" class="swatch" data-color="#FE019A" style="background:#FE019A"></button>
      <button type="button" class="swatch" data-color="#3b82f6" style="background:#3b82f6"></button>
      <button type="button" class="swatch" data-color="#3fae55" style="background:#3fae55"></button>
      <button type="button" class="swatch" data-color="#9b5de5" style="background:#9b5de5"></button>
      <button type="button" class="swatch" data-color="#f2c230" style="background:#f2c230"></button>
      <button type="button" class="swatch" data-color="#d83c3c" style="background:#d83c3c"></button>
    </div>
    <div class="picker-label">Hairstyle</div>
    <div class="gender-row">
      <button type="button" class="gender-pill hair-pill" data-hair="short">Short</button>
      <button type="button" class="gender-pill hair-pill" data-hair="pony">Pony</button>
      <button type="button" class="gender-pill hair-pill" data-hair="bandana">Bandana</button>
    </div>
    <button class="enter" id="join-btn">Enter City</button>
    <div class="note" id="save-status">Guest mode — balloons won't be saved to an account.</div>
  </div>
</div>

<div id="canvas-wrap"></div>

<div id="topbar">
  <div id="brand">
    <img src="https://neyyappam.com/assets/logos/neyyappamicon.png" onerror="this.style.display='none'" />
    <span>Neyyappam City</span>
  </div>
  <div id="hud-right">
    <div id="status-pill"><span class="live-dot"></span> <span id="online-count">1</span> online</div>
    <div id="balloon-pill"><i class="fa-solid fa-circle" style="border-radius:50%;"></i> 🎈 <span id="balloon-val">0</span></div>
  </div>
</div>

<button id="voice-btn"><i class="fa-solid fa-microphone"></i> Voice</button>
<button id="map-btn"><i class="fa-solid fa-map"></i> Map</button>
<button id="stats-btn"><i class="fa-solid fa-user"></i> Stats</button>

<div id="job-banner"></div>
<div id="waypoint-readout" style="display:none;"></div>

<div id="map-overlay" style="display:none;">
  <div id="map-panel">
    <div id="map-header">
      <span>City Map — tap to set a waypoint</span>
      <button id="map-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <canvas id="map-canvas" width="360" height="360"></canvas>
  </div>
</div>

<div id="stats-overlay" style="display:none;">
  <div id="stats-panel">
    <div id="stats-header">
      <span>Player Stats</span>
      <button id="stats-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="stat-row"><span>Name</span><span id="stat-name">—</span></div>
    <div class="stat-row"><span>Look</span><span id="stat-look">—</span></div>
    <div class="stat-row"><span>Balloons</span><span id="stat-balloons">0</span></div>
    <div class="stat-row"><span>Distance traveled</span><span id="stat-distance">0 m</span></div>
    <div class="stat-row"><span>Deliveries completed</span><span id="stat-deliveries">0</span></div>
    <div class="stat-row"><span>Cars driven</span><span id="stat-cars">0</span></div>
  </div>
</div>

<div id="chat-box">
  <div id="chat-log"></div>
  <form id="chat-form">
    <input id="chat-input" placeholder="Say something..." autocomplete="off" />
    <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>
<button id="chat-toggle"><i class="fa-solid fa-comment"></i></button>

<div id="controls-hint">WASD to move &nbsp;•&nbsp; Drag to look</div>
<button id="vehicle-prompt" style="display:none; position:absolute; left:50%; bottom:100px; transform:translateX(-50%); z-index:6; background:rgba(20,15,10,.85); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer;">Press E to enter</button>

<div id="joystick-zone">
  <div id="joystick-base"></div>
  <div id="joystick-stick"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="/socket.io/socket.io.js"></script>
<script>
/* =========================================================
   0) OPTIONAL LOGGED-IN HANDOFF FROM PHP
   city.php can embed this as:
     <iframe src="https://city.neyyappam.com?name=X&uid=123&balloons=450">
   If uid is present, this session is tied to a real account and
   balloons collected here get persisted server-side (see server.js).
   If uid is absent, it's a guest — fully playable, nothing persists.
   ========================================================= */
const params = new URLSearchParams(location.search);
const handoffName = params.get('name');
const handoffUid = params.get('uid');
const handoffBalloons = parseInt(params.get('balloons') || '0', 10);

/* =========================================================
   1) CITY GENERATION — bright low-poly daytime style (blue sky, brick buildings, toon cars)
   ========================================================= */
const scene = new THREE.Scene();
scene.fog = new THREE.Fog(0xdfd9c0, 45, 160);

const camera = new THREE.PerspectiveCamera(60, innerWidth/innerHeight, 0.1, 1000);
camera.position.set(0, 3, 6);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(innerWidth, innerHeight);
renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
document.getElementById('canvas-wrap').appendChild(renderer.domElement);

// --- Daytime gradient sky: blue overhead fading to a warm haze at the horizon ---
function makeSky() {
  const canvas = document.createElement('canvas');
  canvas.width = 2; canvas.height = 256;
  const ctx = canvas.getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 256);
  grad.addColorStop(0, '#2e86d8');
  grad.addColorStop(0.55, '#a9d3e8');
  grad.addColorStop(0.8, '#e8d9ae');
  grad.addColorStop(1, '#dfc98f');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, 2, 256);
  const tex = new THREE.CanvasTexture(canvas);
  const sky = new THREE.Mesh(
    new THREE.SphereGeometry(400, 16, 16),
    new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false })
  );
  scene.add(sky);
}
makeSky();

scene.add(new THREE.HemisphereLight(0xffffff, 0x8f8464, 1.15));
const sun = new THREE.DirectionalLight(0xfff3d6, 1.0);
sun.position.set(10, 25, 8);
scene.add(sun);

const BLOCK = 20, GRID = 6, ROAD_W = 6;

// --- Flat gray asphalt (clean, not gritty — matches the toy-city reference) ---
const ground = new THREE.Mesh(
  new THREE.PlaneGeometry(GRID*BLOCK + 60, GRID*BLOCK + 60),
  new THREE.MeshStandardMaterial({ color: 0x55565c })
);
ground.rotation.x = -Math.PI/2;
scene.add(ground);

function addLaneMarkings() {
  const mat = new THREE.MeshStandardMaterial({ color: 0xf5f5f0 });
  for (let gx = -GRID/2; gx <= GRID/2; gx++) {
    for (let d = -GRID*BLOCK/2; d < GRID*BLOCK/2; d += 4) {
      const dashV = new THREE.Mesh(new THREE.BoxGeometry(0.3, 0.03, 2), mat);
      dashV.position.set(gx*BLOCK, 0.02, d);
      scene.add(dashV);
      const dashH = new THREE.Mesh(new THREE.BoxGeometry(2, 0.03, 0.3), mat);
      dashH.position.set(d, 0.02, gx*BLOCK);
      scene.add(dashH);
    }
  }
}
addLaneMarkings();

// --- Streetlight with a curved arm, like the reference image ---
function addStreetlight(x, z, faceInward) {
  const group = new THREE.Group();
  const poleMat = new THREE.MeshStandardMaterial({ color: 0x8a8a8f });
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.1, 4.2, 8), poleMat);
  pole.position.y = 2.1;
  const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 1.6, 6), poleMat);
  arm.position.set(faceInward ? 0.55 : -0.55, 4.05, 0);
  arm.rotation.z = faceInward ? 1.1 : -1.1;
  const lampHead = new THREE.Mesh(
    new THREE.BoxGeometry(0.5, 0.15, 0.28),
    new THREE.MeshStandardMaterial({ color: 0xFFD866, emissive: 0xFFD866, emissiveIntensity: 0.9 })
  );
  lampHead.position.set(faceInward ? 1.1 : -1.1, 4.55, 0);
  group.add(pole, arm, lampHead);
  group.position.set(x, 0, z);
  scene.add(group);
  const glow = new THREE.PointLight(0xFFE8B0, 0.4, 7);
  glow.position.set(x + (faceInward ? 1.1 : -1.1), 4.55, z);
  scene.add(glow);
}

// --- Boxy toon car (angled windshield, headlight/taillight blocks, light hubcaps) ---
const carColors = [0x2f5fd0, 0xd83c3c, 0xf2c230, 0x3fae55, 0x555a63, 0xffffff];
const cars = {}; // carId -> { id, group, occupiedBy }
let carIdCounter = 0;

function addParkedCar(x, z, rotY) {
  const group = new THREE.Group();
  const color = carColors[Math.floor(Math.random()*carColors.length)];
  const bodyMat = new THREE.MeshStandardMaterial({ color });
  const glassMat = new THREE.MeshStandardMaterial({ color: 0x9fc4e8 });

  const lowerBody = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.5, 0.95), bodyMat);
  lowerBody.position.y = 0.42;
  const hood = new THREE.Mesh(new THREE.BoxGeometry(0.7, 0.15, 0.9), bodyMat);
  hood.position.set(0.6, 0.72, 0);
  const cabin = new THREE.Mesh(new THREE.BoxGeometry(1.1, 0.5, 0.85), bodyMat);
  cabin.position.set(-0.15, 0.85, 0);
  const windshield = new THREE.Mesh(new THREE.BoxGeometry(1.02, 0.42, 0.8), glassMat);
  windshield.position.set(-0.15, 0.85, 0);
  group.add(lowerBody, hood, cabin, windshield);

  const lightMat = new THREE.MeshStandardMaterial({ color: 0xffb347, emissive: 0xffb347, emissiveIntensity: 0.6 });
  const plateMat = new THREE.MeshStandardMaterial({ color: 0xf2f2f2 });
  [-0.32, 0.32].forEach(zOff => {
    const light = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.14, 0.18), lightMat);
    light.position.set(0.95, 0.45, zOff);
    group.add(light);
  });
  const plate = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.14, 0.35), plateMat);
  plate.position.set(0.96, 0.35, 0);
  group.add(plate);

  const wheelGeo = new THREE.CylinderGeometry(0.24, 0.24, 0.22, 12);
  const wheelMat = new THREE.MeshStandardMaterial({ color: 0x1a1a1a });
  const hubMat = new THREE.MeshStandardMaterial({ color: 0xc9c9c9 });
  [[-0.6,-0.5],[-0.6,0.5],[0.6,-0.5],[0.6,0.5]].forEach(([wx,wz]) => {
    const w = new THREE.Mesh(wheelGeo, wheelMat);
    w.rotation.z = Math.PI/2;
    w.position.set(wx, 0.24, wz);
    group.add(w);
    const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.1,0.1,0.24,10), hubMat);
    hub.rotation.z = Math.PI/2;
    hub.position.set(wx, 0.24, wz);
    group.add(hub);
  });

  group.position.set(x, 0, z);
  group.rotation.y = rotY;
  scene.add(group);

  const id = 'car_' + (carIdCounter++);
  cars[id] = { id, group, occupiedBy: null };
  return id;
}

// --- Building: brick body, a real grid of geometric windows (not a texture), tan shopfront base ---
const brickColors = [0xa8503a, 0xb85f42, 0x9a4832, 0xae5a3e];
function addWindows(building, w, h, wallZOffset, faceSign) {
  const rows = Math.max(2, Math.floor((h - 3) / 3));
  const cols = Math.max(2, Math.floor(w / 3));
  const frameMat = new THREE.MeshStandardMaterial({ color: 0xf3f0e6 });
  const glassMat = new THREE.MeshStandardMaterial({ color: 0xbcd9ef });
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const wx = -w/2 + (c + 0.5) * (w / cols);
      const wy = 3 + r * 3;
      if (wy > h - 1.2) continue;
      const frame = new THREE.Mesh(new THREE.BoxGeometry(1.1, 1.3, 0.1), frameMat);
      frame.position.set(wx, wy, wallZOffset);
      building.add(frame);
      const glass = new THREE.Mesh(new THREE.PlaneGeometry(0.85, 1.0), glassMat);
      glass.position.set(wx, wy, wallZOffset + faceSign*0.06);
      if (faceSign < 0) glass.rotation.y = Math.PI;
      building.add(glass);
    }
  }
}

const balloonSpots = [];
let idCounter = 0;
let cornerToggle = false;

for (let gx = -GRID/2; gx < GRID/2; gx++) {
  for (let gz = -GRID/2; gz < GRID/2; gz++) {
    if (gx === 0 && gz === 0) continue;
    const cx = gx*BLOCK + BLOCK/2, cz = gz*BLOCK + BLOCK/2;
    const footprint = BLOCK - ROAD_W;

    // Light gray tiled sidewalk with a slightly raised curb
    const sidewalk = new THREE.Mesh(
      new THREE.BoxGeometry(footprint, 0.25, footprint),
      new THREE.MeshStandardMaterial({ color: 0xd8d6cc })
    );
    sidewalk.position.set(cx, 0.12, cz);
    scene.add(sidewalk);
    const curb = new THREE.Mesh(
      new THREE.BoxGeometry(footprint + 0.3, 0.32, footprint + 0.3),
      new THREE.MeshStandardMaterial({ color: 0xb7b4a8 })
    );
    curb.position.set(cx, 0.06, cz);
    scene.add(curb);

    const h = 8 + Math.random()*20;
    const w = footprint * (0.55 + Math.random()*0.25);

    // Tan ground-floor shopfront base
    const base = new THREE.Mesh(
      new THREE.BoxGeometry(w + 0.4, 3, w + 0.4),
      new THREE.MeshStandardMaterial({ color: 0xc9a565 })
    );
    base.position.set(cx, 1.5, cz);
    scene.add(base);

    // Brick upper structure
    const building = new THREE.Mesh(
      new THREE.BoxGeometry(w, h, w),
      new THREE.MeshStandardMaterial({ color: brickColors[Math.floor(Math.random()*brickColors.length)] })
    );
    building.position.set(cx, h/2 + 1, cz);
    scene.add(building);
    addWindows(building, w, h, w/2 + 0.01, 1);
    addWindows(building, w, h, -w/2 - 0.01, -1);

    // Storefront glass strip on the tan base, facing the sidewalk
    const shopGlass = new THREE.Mesh(
      new THREE.PlaneGeometry(w*0.7, 1.4),
      new THREE.MeshStandardMaterial({ color: 0x8fb7d6 })
    );
    shopGlass.position.set(cx, 1.4, cz + w/2 + 0.21);
    scene.add(shopGlass);

    // Rooftop AC/water-tank prop
    const roofProp = new THREE.Mesh(
      new THREE.BoxGeometry(w*0.22, 0.7, w*0.22),
      new THREE.MeshStandardMaterial({ color: 0x8a8a8f })
    );
    roofProp.position.set(cx + w*0.2, h + 1.35, cz - w*0.2);
    scene.add(roofProp);

    // Streetlight at one corner, alternating which side it faces the road
    cornerToggle = !cornerToggle;
    addStreetlight(cx - footprint/2 - 1, cz - footprint/2 - 1, cornerToggle);
    // 1-2 parked cars along the curb, engine-first toward the road
    addParkedCar(cx + (Math.random()-0.5)*footprint*0.6, cz + footprint/2 + 1.7, Math.random() > 0.5 ? 0 : Math.PI);

    const id = 'balloon_' + (idCounter++);
    balloonSpots.push({ id, x: cx + (Math.random()-0.5)*4, z: cz + (Math.random()-0.5)*4 });
  }
}

// Floating balloon pickups — matches the site's real currency, not generic cash
function makeBalloonSprite() {
  const canvas = document.createElement('canvas');
  canvas.width = 128; canvas.height = 128;
  const ctx = canvas.getContext('2d');
  ctx.font = '92px serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.shadowColor = '#FE019A'; ctx.shadowBlur = 18;
  ctx.fillText('🎈', 64, 62);
  const tex = new THREE.CanvasTexture(canvas);
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
  sprite.scale.set(1, 1, 1);
  return sprite;
}
const balloonMeshes = {};
balloonSpots.forEach(spot => {
  const s = makeBalloonSprite();
  s.position.set(spot.x, 1, spot.z);
  s.userData.bobOffset = Math.random()*Math.PI*2;
  scene.add(s);
  balloonMeshes[spot.id] = s;
});

// --- Delivery job markers: a package crate at the pickup, a flag at the dropoff.
// Positions come straight from the server (job.pickup / job.dropoff), so every
// player sees them in the exact same spot — unlike the balloons above, which
// are cosmetic and randomized per-client. ---
function makeCrateMesh() {
  const group = new THREE.Group();
  const crate = new THREE.Mesh(new THREE.BoxGeometry(0.8,0.8,0.8), new THREE.MeshStandardMaterial({ color: 0xc9a565 }));
  crate.position.y = 0.4;
  const strap = new THREE.Mesh(new THREE.BoxGeometry(0.85,0.15,0.85), new THREE.MeshStandardMaterial({ color: 0x5a3a1a }));
  strap.position.y = 0.4;
  group.add(crate, strap);
  return group;
}
function makeFlagMesh() {
  const group = new THREE.Group();
  const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.04,0.04,2,6), new THREE.MeshStandardMaterial({ color: 0x8a8a8f }));
  pole.position.y = 1;
  const flag = new THREE.Mesh(new THREE.PlaneGeometry(0.6,0.4), new THREE.MeshStandardMaterial({ color: 0xFE019A, side: THREE.DoubleSide }));
  flag.position.set(0.32, 1.7, 0);
  group.add(pole, flag);
  return group;
}
const pickupMarker = makeCrateMesh();
const dropoffMarker = makeFlagMesh();
pickupMarker.visible = false;
dropoffMarker.visible = false;
scene.add(pickupMarker, dropoffMarker);
let currentJob = null;

// --- Waypoint: set from the map, shown as a pulsing beacon you can walk toward ---
const waypointBeacon = new THREE.Mesh(
  new THREE.ConeGeometry(0.4, 1.2, 6),
  new THREE.MeshStandardMaterial({ color: 0xFFD700, emissive: 0xFFD700, emissiveIntensity: 0.6, transparent: true, opacity: 0.9 })
);
waypointBeacon.visible = false;
scene.add(waypointBeacon);
let myWaypoint = null; // {x, z} or null

/* =========================================================
   2) AVATARS — distinct male / female / other silhouettes, not just color
   ========================================================= */
const genderColors = { male: 0x3b82f6, female: 0xFE019A, other: 0x9b5de5 };
const skinTone = 0xffe0c2;

function makeAvatarMesh(gender, outfitColor, hairStyle) {
  const color = outfitColor ? parseInt(outfitColor.replace('#',''), 16) : (genderColors[gender] || genderColors.other);
  const style = hairStyle || 'short';
  const group = new THREE.Group();
  const skinMat = new THREE.MeshStandardMaterial({ color: skinTone });
  const hairMat = new THREE.MeshStandardMaterial({ color: 0x2a1a1a });
  const outfitMat = new THREE.MeshStandardMaterial({ color });
  const pantsMat = new THREE.MeshStandardMaterial({ color: 0x2e2e38 });
  const shoeMat = new THREE.MeshStandardMaterial({ color: 0x161616 });
  const eyeMat = new THREE.MeshStandardMaterial({ color: 0x1a1a1a });

  // Body proportions differ slightly by gender; everything else (limbs, face,
  // hair) is built the same articulated way for all three.
  const build = gender === 'female'
    ? { shoulderW: 0.42, torsoW: 0.4, hipW: 0.34, armLen: 0.46 }
    : gender === 'male'
    ? { shoulderW: 0.58, torsoW: 0.52, hipW: 0.4,  armLen: 0.52 }
    : { shoulderW: 0.48, torsoW: 0.46, hipW: 0.36, armLen: 0.48 };

  const legTop = 0.82, torsoH = 0.62;
  const shoulderY = legTop + torsoH;
  const headY = shoulderY + 0.34;

  // Legs + shoes
  [-1, 1].forEach(side => {
    const leg = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.09, legTop, 8), pantsMat);
    leg.position.set(side * build.hipW/2, legTop/2, 0);
    const shoe = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.1, 0.26), shoeMat);
    shoe.position.set(side * build.hipW/2, 0.05, 0.04);
    group.add(leg, shoe);
  });

  // Torso (tapered slightly at the waist using two stacked boxes) + skirt for female
  const torso = new THREE.Mesh(new THREE.BoxGeometry(build.torsoW, torsoH, 0.28), outfitMat);
  torso.position.y = legTop + torsoH/2;
  group.add(torso);
  if (gender === 'female') {
    const skirt = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.36, 0.32, 10), outfitMat);
    skirt.position.y = legTop - 0.02;
    group.add(skirt);
  }

  // Arms + hands (simple articulated limbs, angled slightly outward)
  [-1, 1].forEach(side => {
    const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.075, 0.065, build.armLen, 8), skinMat);
    arm.position.set(side * (build.shoulderW/2 + 0.06), shoulderY - build.armLen/2 - 0.05, 0);
    arm.rotation.z = side * 0.07;
    const hand = new THREE.Mesh(new THREE.SphereGeometry(0.08, 8, 8), skinMat);
    hand.position.set(side * (build.shoulderW/2 + 0.06 + side*0.02), shoulderY - build.armLen - 0.08, 0);
    group.add(arm, hand);
  });

  // Neck + rounded head
  const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.1, 0.12, 8), skinMat);
  neck.position.y = shoulderY + 0.06;
  const head = new THREE.Mesh(new THREE.SphereGeometry(0.26, 12, 12), skinMat);
  head.position.y = headY;
  group.add(neck, head);

  // Simple face: two eyes + a small mouth on the front of the head
  [-1, 1].forEach(side => {
    const eye = new THREE.Mesh(new THREE.SphereGeometry(0.03, 6, 6), eyeMat);
    eye.position.set(side * 0.09, headY + 0.03, 0.235);
    group.add(eye);
  });
  const mouth = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.02, 0.02), eyeMat);
  mouth.position.set(0, headY - 0.09, 0.245);
  group.add(mouth);

  // Hairstyle is chosen independently of gender
  if (style === 'pony') {
    const hairTop = new THREE.Mesh(new THREE.SphereGeometry(0.27, 10, 10, 0, Math.PI*2, 0, Math.PI/1.7), hairMat);
    hairTop.position.y = headY + 0.03;
    const ponytail = new THREE.Mesh(new THREE.CylinderGeometry(0.05,0.09,0.45,6), hairMat);
    ponytail.position.set(0, headY - 0.05, -0.26);
    ponytail.rotation.x = 0.5;
    group.add(hairTop, ponytail);
  } else if (style === 'bandana') {
    const bandana = new THREE.Mesh(new THREE.TorusGeometry(0.27, 0.05, 8, 16, Math.PI*1.5), new THREE.MeshStandardMaterial({ color: 0xFFD700 }));
    bandana.rotation.set(Math.PI/2, 0, 0.3);
    bandana.position.y = headY + 0.04;
    group.add(bandana);
  } else {
    const hairTop = new THREE.Mesh(new THREE.SphereGeometry(0.27, 10, 10, 0, Math.PI*2, 0, Math.PI/2.1), hairMat);
    hairTop.position.y = headY + 0.03;
    group.add(hairTop);
  }
  return group;
}
function makeLabel(text) {
  const canvas = document.createElement('canvas');
  canvas.width = 256; canvas.height = 64;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#180d2a';
  const r = 16;
  ctx.beginPath();
  ctx.moveTo(r,0); ctx.arcTo(256,0,256,64,r); ctx.arcTo(256,64,0,64,r);
  ctx.arcTo(0,64,0,0,r); ctx.arcTo(0,0,256,0,r); ctx.closePath(); ctx.fill();
  ctx.fillStyle = '#FE019A';
  ctx.font = 'bold 28px sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText(text, 128, 42);
  const tex = new THREE.CanvasTexture(canvas);
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
  sprite.scale.set(2, 0.5, 1);
  sprite.position.y = 2.2;
  return sprite;
}

let myGender = 'other';
let myOutfitColor = '#9b5de5';
let myHairStyle = 'short';
let myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle);
scene.add(myAvatar);
const others = {};

/* =========================================================
   3) NAME GATE
   ========================================================= */
document.querySelectorAll('.gender-pill[data-gender]').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.gender-pill[data-gender]').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    myGender = btn.dataset.gender;
  };
});
document.querySelector('.gender-pill[data-gender="other"]').classList.add('selected');

document.querySelectorAll('.hair-pill').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.hair-pill').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    myHairStyle = btn.dataset.hair;
  };
});
document.querySelector('.hair-pill[data-hair="short"]').classList.add('selected');

document.querySelectorAll('.swatch').forEach(btn => {
  btn.onclick = () => {
    document.querySelectorAll('.swatch').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    myOutfitColor = btn.dataset.color;
  };
});
document.querySelector('.swatch[data-color="#9b5de5"]').classList.add('selected');

if (handoffUid) {
  document.getElementById('save-status').textContent = 'Signed in — balloons collected here are saved to your account.';
  document.getElementById('save-status').classList.add('logged-in');
}
if (handoffName) document.getElementById('name-input').value = handoffName;

/* =========================================================
   4) MOVEMENT
   ========================================================= */
const keys = {};
addEventListener('keydown', e => keys[e.key.toLowerCase()] = true);
addEventListener('keyup', e => keys[e.key.toLowerCase()] = false);

let camYaw = 0, camPitch = 0.15, dragging = false, lastX = 0, lastY = 0;
function startDrag(x,y){ dragging = true; lastX = x; lastY = y; }
function moveDrag(x,y){
  if(!dragging) return;
  camYaw -= (x-lastX)*0.006;
  camPitch += (y-lastY)*0.004;
  camPitch = Math.max(-0.85, Math.min(1.0, camPitch)); // clamp so you can't flip past straight up/down
  lastX = x; lastY = y;
}
function endDrag(){ dragging = false; }
renderer.domElement.addEventListener('mousedown', e => startDrag(e.clientX, e.clientY));
addEventListener('mouseup', endDrag);
addEventListener('mousemove', e => moveDrag(e.clientX, e.clientY));
renderer.domElement.addEventListener('touchstart', e => { if(e.touches.length) startDrag(e.touches[0].clientX, e.touches[0].clientY); }, {passive:true});
renderer.domElement.addEventListener('touchmove', e => { if(e.touches.length) moveDrag(e.touches[0].clientX, e.touches[0].clientY); }, {passive:true});
renderer.domElement.addEventListener('touchend', endDrag);

let joyVec = { x:0, y:0 };
const joyZone = document.getElementById('joystick-zone');
const joyStick = document.getElementById('joystick-stick');
let joyActive = false, joyStartX = 0, joyStartY = 0;
joyZone.addEventListener('touchstart', e => { joyActive = true; const t=e.touches[0]; joyStartX=t.clientX; joyStartY=t.clientY; }, {passive:true});
joyZone.addEventListener('touchmove', e => {
  if(!joyActive) return;
  const t = e.touches[0];
  let dx = t.clientX-joyStartX, dy = t.clientY-joyStartY;
  const max=40, len=Math.hypot(dx,dy);
  if(len>max){ dx=dx/len*max; dy=dy/len*max; }
  joyStick.style.transform = `translate(${dx}px, ${dy}px)`;
  joyVec.x = dx/max; joyVec.y = dy/max;
}, {passive:true});
joyZone.addEventListener('touchend', () => { joyActive=false; joyVec={x:0,y:0}; joyStick.style.transform='translate(0,0)'; });

const speed = 0.14;
let drivingCarId = null;   // id of the car I'm currently driving, or null if on foot
let carSpeed = 0;          // current forward/backward speed while driving
const vehiclePrompt = document.getElementById('vehicle-prompt');

// --- Stats panel counters ---
const stats = { distanceTraveled: 0, deliveriesCompleted: 0, carsDriven: 0 };

function findNearbyCar() {
  let nearest = null, nearestDist = 2.2; // must be within ~2 units to interact
  for (const id in cars) {
    const c = cars[id];
    const d = c.group.position.distanceTo(myAvatar.position);
    if (d < nearestDist) { nearest = c; nearestDist = d; }
  }
  return nearest;
}

addEventListener('keydown', (e) => {
  if (e.key.toLowerCase() !== 'e') return;
  triggerVehicleAction();
});
vehiclePrompt.addEventListener('click', triggerVehicleAction);

function triggerVehicleAction() {
  if (document.getElementById('name-gate').style.display !== 'none') return; // not in the world yet
  if (drivingCarId) {
    exitVehicle();
  } else {
    const nearby = findNearbyCar();
    if (nearby && !nearby.occupiedBy) socket.emit('enterCar', nearby.id);
  }
}

function enterVehicle(carId) {
  const c = cars[carId];
  if (!c) return;
  drivingCarId = carId;
  carSpeed = 0;
  myAvatar.visible = false;
  vehiclePrompt.textContent = 'Press E to exit';
  vehiclePrompt.style.display = 'block';
  document.getElementById('controls-hint').textContent = 'WASD to drive • E to exit';
  stats.carsDriven++;
}

function exitVehicle() {
  if (!drivingCarId) return;
  const c = cars[drivingCarId];
  const forward = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
  // Step out to the left side of the car rather than through it
  myAvatar.position.set(
    c.group.position.x - forward.z * 1.6,
    0,
    c.group.position.z + forward.x * 1.6
  );
  myAvatar.rotation.y = c.group.rotation.y;
  myAvatar.visible = true;
  socket.emit('exitCar', { carId: drivingCarId, x: c.group.position.x, y: 0, z: c.group.position.z, rotY: c.group.rotation.y });
  drivingCarId = null;
  carSpeed = 0;
  vehiclePrompt.textContent = 'Press E to enter';
  document.getElementById('controls-hint').textContent = 'WASD to move • Drag to look';
}

function updateDriving() {
  const c = cars[drivingCarId];
  if (!c) { drivingCarId = null; return; }

  let throttle = 0, steer = 0;
  if (keys['w']) throttle += 1;
  if (keys['s']) throttle -= 1;
  if (keys['a']) steer += 1;
  if (keys['d']) steer -= 1;
  throttle += -joyVec.y; steer += -joyVec.x;

  const accel = 0.012, maxSpeed = 0.32, friction = 0.985, turnRate = 0.045;
  carSpeed += throttle * accel;
  carSpeed *= friction;
  carSpeed = Math.max(-maxSpeed*0.6, Math.min(maxSpeed, carSpeed));

  if (Math.abs(carSpeed) > 0.005) {
    c.group.rotation.y += steer * turnRate * (carSpeed > 0 ? 1 : -1);
  }
  const forward = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
  c.group.position.x += forward.x * carSpeed;
  c.group.position.z += forward.z * carSpeed;
  stats.distanceTraveled += Math.abs(carSpeed);

  // Chase camera behind the car — now with vertical pitch for a full 360° view
  const camDist = 7;
  camera.position.x = c.group.position.x - forward.x * camDist * Math.cos(camPitch);
  camera.position.z = c.group.position.z - forward.z * camDist * Math.cos(camPitch);
  camera.position.y = 1.5 + camDist * Math.sin(camPitch) + 1.5;
  camera.lookAt(c.group.position.x, 1, c.group.position.z);

  socket.emit('driveCar', { carId: drivingCarId, x: c.group.position.x, y: 0, z: c.group.position.z, rotY: c.group.rotation.y });
}

function updateMovement() {
  if (drivingCarId) { updateDriving(); return; }

  let dx=0, dz=0;
  if (keys['w']) dz -= 1;
  if (keys['s']) dz += 1;
  if (keys['a']) dx -= 1;
  if (keys['d']) dx += 1;
  dx += joyVec.x; dz += joyVec.y;
  if (dx || dz) {
    const len = Math.hypot(dx,dz) || 1;
    dx/=len; dz/=len;
    const moveX = dx*Math.cos(camYaw) - dz*Math.sin(camYaw);
    const moveZ = dx*Math.sin(camYaw) + dz*Math.cos(camYaw);
    myAvatar.position.x += moveX*speed;
    myAvatar.position.z += moveZ*speed;
    myAvatar.rotation.y = Math.atan2(moveX, moveZ);
    stats.distanceTraveled += speed;
    checkBalloonPickup();
    checkDeliveryProximity();
  }
  const camDist = 6;
  camera.position.x = myAvatar.position.x - Math.sin(camYaw)*camDist*Math.cos(camPitch);
  camera.position.z = myAvatar.position.z - Math.cos(camYaw)*camDist*Math.cos(camPitch);
  camera.position.y = myAvatar.position.y + 1.2 + camDist*Math.sin(camPitch);
  camera.lookAt(myAvatar.position.x, myAvatar.position.y+1, myAvatar.position.z);

  // Show/hide the "Press E to enter" prompt based on proximity to a free car
  const nearby = findNearbyCar();
  vehiclePrompt.style.display = (nearby && !nearby.occupiedBy) ? 'block' : 'none';
}

function checkDeliveryProximity() {
  if (!currentJob) return;
  if (currentJob.status === 'available' && pickupMarker.position.distanceTo(myAvatar.position) < 1.5) {
    socket.emit('pickupDelivery', currentJob.id);
  } else if (currentJob.status === 'inProgress' && currentJob.carrierId === socket.id
             && dropoffMarker.position.distanceTo(myAvatar.position) < 1.5) {
    socket.emit('completeDelivery', currentJob.id);
    stats.deliveriesCompleted++;
  }
}

function updateWaypointReadout() {
  const readout = document.getElementById('waypoint-readout');
  if (!myWaypoint) { readout.style.display = 'none'; return; }
  const d = Math.hypot(myWaypoint.x - myAvatar.position.x, myWaypoint.z - myAvatar.position.z);
  if (d < 2) { // arrived — clear it
    myWaypoint = null;
    waypointBeacon.visible = false;
    readout.style.display = 'none';
    return;
  }
  readout.style.display = 'block';
  readout.textContent = `🚩 ${Math.round(d)}m to waypoint`;
}

function checkBalloonPickup() {
  for (const id in balloonMeshes) {
    const mesh = balloonMeshes[id];
    if (!mesh.visible) continue;
    if (mesh.position.distanceTo(myAvatar.position) < 1.2) {
      socket.emit('collectBalloon', id);
    }
  }
}

/* =========================================================
   5) NETWORKING
   ========================================================= */
const socket = io();
let myName = 'Guest';
const balloonEl = document.getElementById('balloon-val');
const onlineEl = document.getElementById('online-count');
balloonEl.textContent = handoffBalloons;

document.getElementById('join-btn').onclick = enterCity;
document.getElementById('name-input').addEventListener('keydown', e => { if (e.key==='Enter') enterCity(); });

function enterCity() {
  myName = document.getElementById('name-input').value.trim() || 'Guest';
  scene.remove(myAvatar);
  myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle);
  scene.add(myAvatar);
  document.getElementById('name-gate').style.display = 'none';
  socket.emit('join', { name: myName, gender: myGender, outfitColor: myOutfitColor, hairStyle: myHairStyle, uid: handoffUid || null, startingBalloons: handoffBalloons });
}

function refreshOnlineCount(){ onlineEl.textContent = Object.keys(others).length + 1; }

socket.on('currentPlayers', (players) => {
  Object.values(players).forEach(p => { if (p.id !== socket.id) addOtherPlayer(p); });
  refreshOnlineCount();
});
socket.on('playerJoined', (p) => { addOtherPlayer(p); refreshOnlineCount(); });
socket.on('playerMoved', (p) => { const o = others[p.id]; if (o) o.target = p; });
socket.on('playerLeft', (id) => {
  if (others[id]) { scene.remove(others[id].group); delete others[id]; }
  closePeerConnection(id);
  refreshOnlineCount();
});
socket.on('balloonCollected', ({ pickupId, by, balloons }) => {
  const mesh = balloonMeshes[pickupId];
  if (mesh) mesh.visible = false;
  if (by === socket.id) balloonEl.textContent = balloons;
});

// --- Vehicles ---
socket.on('currentCars', (occupied) => {
  Object.keys(occupied).forEach(carId => { if (cars[carId]) cars[carId].occupiedBy = occupied[carId]; });
});
socket.on('carEntered', ({ carId, driverId }) => {
  const c = cars[carId];
  if (!c) return;
  c.occupiedBy = driverId;
  if (driverId === socket.id) {
    enterVehicle(carId);
  } else if (others[driverId]) {
    others[driverId].group.visible = false; // hide their walking avatar while they drive
  }
});
socket.on('carMoved', ({ carId, x, y, z, rotY }) => {
  const c = cars[carId];
  if (c) c.target = { x, y, z, rotY };
});
socket.on('carExited', ({ carId, driverId, x, y, z, rotY }) => {
  const c = cars[carId];
  if (!c) return;
  c.occupiedBy = null;
  c.group.position.set(x, y, z);
  c.group.rotation.y = rotY;
  c.target = null;
  if (driverId !== socket.id && others[driverId]) {
    others[driverId].group.visible = true;
    others[driverId].group.position.set(x, 0, z);
  }
});
socket.on('carFreed', ({ carId }) => { if (cars[carId]) cars[carId].occupiedBy = null; });
socket.on('carDenied', () => { /* someone else got there first — no action needed */ });

// --- Delivery job updates ---
const jobBanner = document.getElementById('job-banner');
socket.on('deliveryUpdated', (job) => {
  currentJob = job;
  if (!job) { pickupMarker.visible = false; dropoffMarker.visible = false; jobBanner.style.display = 'none'; return; }
  if (job.status === 'available') {
    pickupMarker.position.set(job.pickup.x, 0, job.pickup.z);
    pickupMarker.visible = true;
    dropoffMarker.visible = false;
    jobBanner.textContent = '📦 A delivery is waiting — walk to the marked crate';
    jobBanner.style.display = 'block';
  } else if (job.status === 'inProgress') {
    pickupMarker.visible = false;
    dropoffMarker.position.set(job.dropoff.x, 0, job.dropoff.z);
    dropoffMarker.visible = true;
    if (job.carrierId === socket.id) {
      jobBanner.textContent = '🚚 Delivering — head to the flag to complete it (+30 🎈)';
    } else {
      jobBanner.textContent = '🚚 Someone else is delivering right now';
    }
    jobBanner.style.display = 'block';
  }
});

function addOtherPlayer(p) {
  const group = makeAvatarMesh(p.gender, p.outfitColor, p.hairStyle);
  group.add(makeLabel(p.name));
  scene.add(group);
  others[p.id] = { group, target: p };
}

setInterval(() => {
  if (document.getElementById('name-gate').style.display === 'none' && !drivingCarId) {
    socket.emit('move', { x: myAvatar.position.x, y: myAvatar.position.y, z: myAvatar.position.z, rotY: myAvatar.rotation.y });
  }
}, 100);

/* =========================================================
   6) CHAT
   ========================================================= */
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatLog = document.getElementById('chat-log');
const chatBox = document.getElementById('chat-box');
document.getElementById('chat-toggle').onclick = () => chatBox.classList.toggle('open');
chatForm.addEventListener('submit', e => {
  e.preventDefault();
  const text = chatInput.value.trim();
  if (!text) return;
  socket.emit('chatMessage', text);
  chatInput.value = '';
});
socket.on('chatMessage', ({ name, text }) => {
  const line = document.createElement('div');
  line.innerHTML = `<b>${escapeHtml(name)}:</b> ${escapeHtml(text)}`;
  chatLog.appendChild(line);
  chatLog.scrollTop = chatLog.scrollHeight;
});
function escapeHtml(s){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

/* =========================================================
   7) VOICE
   ========================================================= */
let localStream = null;
const peers = {};
const voiceBtn = document.getElementById('voice-btn');
let voiceOn = false;
voiceBtn.onclick = async () => {
  if (!voiceOn) {
    try {
      localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      voiceOn = true; voiceBtn.classList.add('on');
      Object.keys(others).forEach(id => { if (!peers[id]) callPeer(id); });
    } catch(e) { alert('Microphone access denied or unavailable.'); }
  } else {
    voiceOn = false; voiceBtn.classList.remove('on');
    localStream.getTracks().forEach(t => t.stop());
    Object.keys(peers).forEach(closePeerConnection);
  }
};
function createPeerConnection(targetId) {
  const pc = new RTCPeerConnection({ iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'turn:openrelay.metered.ca:80', username: 'openrelayproject', credential: 'openrelayproject' },
    { urls: 'turn:openrelay.metered.ca:443', username: 'openrelayproject', credential: 'openrelayproject' },
    { urls: 'turn:openrelay.metered.ca:443?transport=tcp', username: 'openrelayproject', credential: 'openrelayproject' }
  ] });
  if (localStream) localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
  pc.onicecandidate = e => { if (e.candidate) socket.emit('voice-ice', { target: targetId, candidate: e.candidate }); };
  pc.ontrack = e => {
    let audioEl = document.getElementById('audio-'+targetId);
    if (!audioEl) {
      audioEl = document.createElement('audio');
      audioEl.id = 'audio-'+targetId;
      audioEl.autoplay = true;
      audioEl.setAttribute('playsinline', '');
      document.body.appendChild(audioEl);
    }
    audioEl.srcObject = e.streams[0];
    // Mobile Chrome sometimes silently blocks autoplay on elements created
    // after the initial page load, even with autoplay set — explicitly
    // calling play() here works around that.
    audioEl.play().catch(err => console.warn('Voice audio playback blocked, will retry on next user tap:', err));
  };
  peers[targetId] = pc;
  return pc;
}
async function callPeer(targetId) {
  const pc = createPeerConnection(targetId);
  const offer = await pc.createOffer();
  await pc.setLocalDescription(offer);
  socket.emit('voice-offer', { target: targetId, offer });
}
socket.on('voice-offer', async ({ from, offer }) => {
  // Glare handling: if both sides call each other at nearly the same moment,
  // only one offer should win. The peer with the higher socket id is "polite"
  // and yields — it drops its own pending offer and accepts the incoming one.
  // The peer with the lower socket id is "impolite" and ignores incoming
  // offers while it already has one in flight; its own offer will win instead.
  const polite = socket.id > from;
  if (peers[from] && !polite) return;
  if (peers[from]) closePeerConnection(from);
  const pc = createPeerConnection(from);
  await pc.setRemoteDescription(offer);
  const answer = await pc.createAnswer();
  await pc.setLocalDescription(answer);
  socket.emit('voice-answer', { target: from, answer });
});
socket.on('voice-answer', async ({ from, answer }) => { const pc = peers[from]; if (pc) await pc.setRemoteDescription(answer); });
socket.on('voice-ice', async ({ from, candidate }) => { const pc = peers[from]; if (pc) { try { await pc.addIceCandidate(candidate); } catch(e){} } });
function closePeerConnection(id) {
  if (peers[id]) { peers[id].close(); delete peers[id]; }
  const el = document.getElementById('audio-'+id);
  if (el) el.remove();
}
// Extra safety net: some mobile browsers keep blocking audio playback even
// after an explicit play() call. Retrying on the next tap anywhere on the
// page catches those cases, since a tap always counts as a user gesture.
document.addEventListener('click', () => {
  document.querySelectorAll('audio[id^="audio-"]').forEach(el => {
    if (el.paused) el.play().catch(() => {});
  });
});

/* =========================================================
   8) MAP + STATS PANELS
   ========================================================= */
const WORLD_HALF = (GRID*BLOCK)/2 + 20; // matches the ground size built earlier
let mapOpen = false;
const mapCanvas = document.getElementById('map-canvas');
const mapCtx = mapCanvas.getContext('2d');

document.getElementById('map-btn').onclick = () => { mapOpen = true; document.getElementById('map-overlay').style.display = 'flex'; };
document.getElementById('map-close').onclick = () => { mapOpen = false; document.getElementById('map-overlay').style.display = 'none'; };

function worldToMap(x, z) {
  return {
    px: ((x + WORLD_HALF) / (WORLD_HALF*2)) * mapCanvas.width,
    py: ((z + WORLD_HALF) / (WORLD_HALF*2)) * mapCanvas.height
  };
}
function mapToWorld(px, py) {
  return {
    x: (px / mapCanvas.width) * (WORLD_HALF*2) - WORLD_HALF,
    z: (py / mapCanvas.height) * (WORLD_HALF*2) - WORLD_HALF
  };
}

function drawMap() {
  mapCtx.fillStyle = '#241a34';
  mapCtx.fillRect(0, 0, mapCanvas.width, mapCanvas.height);

  mapCtx.strokeStyle = 'rgba(255,255,255,.08)';
  for (let gx = -GRID/2; gx <= GRID/2; gx++) {
    const { px } = worldToMap(gx*BLOCK, 0);
    mapCtx.beginPath(); mapCtx.moveTo(px, 0); mapCtx.lineTo(px, mapCanvas.height); mapCtx.stroke();
  }
  for (let gz = -GRID/2; gz <= GRID/2; gz++) {
    const { py } = worldToMap(0, gz*BLOCK);
    mapCtx.beginPath(); mapCtx.moveTo(0, py); mapCtx.lineTo(mapCanvas.width, py); mapCtx.stroke();
  }

  mapCtx.fillStyle = '#8fc4e8';
  Object.values(others).forEach(o => {
    const { px, py } = worldToMap(o.group.position.x, o.group.position.z);
    mapCtx.beginPath(); mapCtx.arc(px, py, 4, 0, Math.PI*2); mapCtx.fill();
  });

  if (currentJob) {
    if (currentJob.status === 'available') {
      const { px, py } = worldToMap(currentJob.pickup.x, currentJob.pickup.z);
      mapCtx.fillStyle = '#c9a565';
      mapCtx.fillRect(px-5, py-5, 10, 10);
    } else if (currentJob.status === 'inProgress') {
      const { px, py } = worldToMap(currentJob.dropoff.x, currentJob.dropoff.z);
      mapCtx.fillStyle = '#FE019A';
      mapCtx.fillRect(px-5, py-5, 10, 10);
    }
  }

  if (myWaypoint) {
    const { px, py } = worldToMap(myWaypoint.x, myWaypoint.z);
    mapCtx.fillStyle = '#FFD700';
    mapCtx.beginPath();
    for (let i = 0; i < 5; i++) {
      const a = (i * 4 * Math.PI/5) - Math.PI/2;
      mapCtx[i===0?'moveTo':'lineTo'](px + Math.cos(a)*7, py + Math.sin(a)*7);
    }
    mapCtx.closePath(); mapCtx.fill();
  }

  const me = worldToMap(myAvatar.position.x, myAvatar.position.z);
  mapCtx.fillStyle = '#FE019A';
  mapCtx.beginPath(); mapCtx.arc(me.px, me.py, 6, 0, Math.PI*2); mapCtx.fill();
  mapCtx.strokeStyle = '#fff'; mapCtx.lineWidth = 2; mapCtx.stroke();
}

mapCanvas.addEventListener('click', (e) => {
  const rect = mapCanvas.getBoundingClientRect();
  const px = (e.clientX - rect.left) * (mapCanvas.width / rect.width);
  const py = (e.clientY - rect.top) * (mapCanvas.height / rect.height);
  myWaypoint = mapToWorld(px, py);
  waypointBeacon.position.set(myWaypoint.x, 1.2, myWaypoint.z);
  waypointBeacon.visible = true;
  mapOpen = false;
  document.getElementById('map-overlay').style.display = 'none';
});

document.getElementById('stats-btn').onclick = () => {
  document.getElementById('stat-name').textContent = myName;
  document.getElementById('stat-look').textContent = `${myGender}, ${myHairStyle} hair`;
  document.getElementById('stat-balloons').textContent = balloonEl.textContent;
  document.getElementById('stat-distance').textContent = Math.round(stats.distanceTraveled) + ' m';
  document.getElementById('stat-deliveries').textContent = stats.deliveriesCompleted;
  document.getElementById('stat-cars').textContent = stats.carsDriven;
  document.getElementById('stats-overlay').style.display = 'flex';
};
document.getElementById('stats-close').onclick = () => { document.getElementById('stats-overlay').style.display = 'none'; };

/* =========================================================
   9) RENDER LOOP
   ========================================================= */
const clock = new THREE.Clock();
function animate() {
  requestAnimationFrame(animate);
  const t = clock.getElapsedTime();
  updateMovement();
  updateWaypointReadout();
  if (waypointBeacon.visible) waypointBeacon.position.y = 1.2 + Math.sin(t*3)*0.15;
  if (mapOpen) drawMap();
  Object.values(balloonMeshes).forEach(m => { if (m.visible) m.position.y = 1 + Math.sin(t*2 + m.userData.bobOffset)*0.2; });
  Object.values(others).forEach(o => {
    o.group.position.lerp(new THREE.Vector3(o.target.x, o.target.y, o.target.z), 0.2);
    o.group.rotation.y = o.target.rotY;
  });
  Object.values(cars).forEach(c => {
    if (c.target && c.occupiedBy && c.occupiedBy !== socket.id) {
      c.group.position.lerp(new THREE.Vector3(c.target.x, c.target.y, c.target.z), 0.25);
      c.group.rotation.y = c.target.rotY;
    }
  });
  renderer.render(scene, camera);
}
animate();

addEventListener('resize', () => {
  camera.aspect = innerWidth/innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
});
</script>
</body>
</html>
