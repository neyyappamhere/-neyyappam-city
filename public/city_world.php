<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>Neyyappam City</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&family=Noto+Sans+Malayalam:wght@500;700&display=swap" rel="stylesheet">
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
  html,body{ margin:0; height:100%; overflow:hidden; font-family:'Space Grotesk','Noto Sans Malayalam',sans-serif; background:var(--bg); color:var(--text); }
  #canvas-wrap{ position:absolute; inset:0; }
  #topbar{ position:absolute; top:0; left:0; right:0; z-index:5; display:flex; align-items:center; justify-content:space-between; padding:12px 14px; pointer-events:none; }
  #brand{ display:flex; align-items:center; gap:8px; pointer-events:auto; }
  #brand img{ width:32px; height:32px; border-radius:8px; background:#fff; object-fit:contain; }
  #brand span{ font-family:'Syne',sans-serif; font-weight:800; font-size:1.1em;
    background:linear-gradient(135deg,#FE019A 0%,#ff80d5 50%,#FFD700 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  #hud-right{ display:flex; gap:8px; pointer-events:none; }
  #status-pill, #balloon-pill{ pointer-events:auto; display:flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:20px; font-size:.82em; font-weight:700;
    background:var(--card); border:1px solid var(--border); box-shadow:0 6px 18px rgba(0,0,0,.4); }
  #status-pill{ color:var(--muted); }
  .live-dot{ width:7px; height:7px; border-radius:50%; background:var(--green); animation:pulse 1.6s infinite; }
  @keyframes pulse{ 0%{box-shadow:0 0 0 0 rgba(0,245,147,.6);} 70%{box-shadow:0 0 0 8px rgba(0,245,147,0);} 100%{box-shadow:0 0 0 0 rgba(0,245,147,0);} }
  #balloon-pill{ color:var(--gold); }
  #balloon-pill i{ color:var(--pk); }
  #mic-btn, #sound-btn{ position:absolute; top:64px; z-index:5; width:44px; height:44px; padding:0;
    display:flex; align-items:center; justify-content:center; font-size:1.05em;
    background:var(--card); color:var(--muted); border:1px solid var(--border); border-radius:50%; cursor:pointer; }
  #mic-btn{ left:14px; } #sound-btn{ left:66px; }
  #mic-btn.on{ background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border-color:transparent; }
  #sound-btn.on{ background:linear-gradient(135deg,#00b37a,#00f593); color:#05210f; border-color:transparent; }
  .rbtn{ background:#5b3416; color:#ffd166; border:1px solid #c98a3a; border-radius:20px; padding:10px 16px; font-weight:700; font-size:.82em; cursor:pointer; }
  .role-pill{ font-size:.7em; padding:8px 4px; } .role-pill:disabled{ opacity:.4; cursor:not-allowed; }
  #vignette{ position:absolute; inset:0; pointer-events:none; z-index:2; background:radial-gradient(ellipse at center, rgba(0,0,0,0) 55%, rgba(60,30,0,.38) 100%); }
  #canvas-wrap canvas{ filter:sepia(.2) saturate(1.1) contrast(1.05); }
  body.novintage #canvas-wrap canvas{ filter:none; } body.novintage #vignette{ display:none; }
  #map-btn, #stats-btn, #shop-btn, #inventory-btn{ position:absolute; top:64px; z-index:5;
    background:var(--card); color:var(--text); border:1px solid var(--border);
    border-radius:24px; padding:9px 16px; font-weight:700; font-size:.82em; cursor:pointer; }
  #map-btn{ left:118px; } #stats-btn{ left:204px; } #shop-btn{ left:290px; } #inventory-btn{ left:376px; }
  #shop-overlay, #inventory-overlay{ position:absolute; inset:0; z-index:20; display:flex; align-items:center; justify-content:center; background:rgba(5,2,10,.75); }
  #shop-panel, #inventory-panel{ background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.6); width:min(92vw, 460px); max-height:80vh; overflow-y:auto; }
  #shop-header, #inventory-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;
    font-weight:700; font-size:.85em; position:sticky; top:0; background:var(--card); padding-bottom:6px; }
  #shop-header button, #inventory-header button{ background:none; border:none; color:var(--muted); font-size:1em; cursor:pointer; }
  .shop-balance{ color:var(--gold); font-weight:700; font-size:.82em; margin-bottom:10px; }
  .shop-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(120px,1fr)); gap:10px; }
  .shop-item{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:10px;
    text-align:center; display:flex; flex-direction:column; gap:6px; }
  .shop-item .emoji{ font-size:1.8em; }
  .shop-item .name{ font-size:.75em; font-weight:700; }
  .shop-item .price{ font-size:.7em; color:var(--gold); }
  .shop-item button{ margin-top:2px; padding:6px; border:none; border-radius:10px; font-weight:700; font-size:.72em; cursor:pointer;
    background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  .shop-item button:disabled{ background:var(--border); color:var(--muted); cursor:not-allowed; }
  .shop-cat-label{ font-size:.72em; font-weight:700; color:var(--muted); margin:14px 0 8px; text-transform:uppercase; letter-spacing:.04em; }
  .shop-cat-label:first-of-type{ margin-top:0; }
  .inv-row{ display:flex; align-items:center; justify-content:space-between; gap:8px;
    padding:8px 0; border-bottom:1px solid var(--border); font-size:.85em; }
  .inv-row:last-child{ border-bottom:none; }
  .inv-row .inv-left{ display:flex; align-items:center; gap:8px; }
  .inv-row button{ padding:5px 10px; border:none; border-radius:10px; font-weight:700; font-size:.72em; cursor:pointer;
    background:var(--surface); color:var(--text); border:1px solid var(--border); }
  .inv-empty{ color:var(--muted); font-size:.82em; text-align:center; padding:20px 0; }
  #toast-stack{ position:absolute; top:110px; right:14px; z-index:30; display:flex; flex-direction:column; gap:8px;
    align-items:flex-end; pointer-events:none; }
  .toast{ background:var(--card); border:1px solid var(--border); color:var(--text);
    padding:10px 14px; border-radius:12px; font-size:.8em; font-weight:600;
    box-shadow:0 10px 25px rgba(0,0,0,.5); max-width:260px;
    animation:toastIn .25s ease-out, toastOut .3s ease-in 3.2s forwards; }
  @keyframes toastIn{ from{ opacity:0; transform:translateY(-8px);} to{ opacity:1; transform:translateY(0);} }
  @keyframes toastOut{ to{ opacity:0; transform:translateY(-8px);} }
  #job-banner{ position:absolute; top:64px; left:50%; transform:translateX(-50%); z-index:5;
    background:var(--card); border:1px solid var(--border); color:#FFD700;
    padding:8px 16px; border-radius:16px; font-size:.8em; font-weight:600; display:none; max-width:280px; text-align:center; }
  #waypoint-readout{ position:absolute; top:104px; left:50%; transform:translateX(-50%); z-index:5;
    background:rgba(0,0,0,.5); color:#fff; padding:5px 14px; border-radius:12px; font-size:.72em; }
  #map-overlay, #stats-overlay{ position:absolute; inset:0; z-index:20; display:flex; align-items:center; justify-content:center; background:rgba(5,2,10,.75); }
  #map-panel, #stats-panel{ background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.6); width:min(90vw, 400px); }
  #map-header, #stats-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-weight:700; font-size:.85em; }
  #map-header button, #stats-header button{ background:none; border:none; color:var(--muted); font-size:1em; cursor:pointer; }
  #map-canvas{ width:100%; height:auto; border-radius:12px; background:#2a2438; cursor:crosshair; }
  .stat-row{ display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border); font-size:.85em; }
  .stat-row:last-child{ border-bottom:none; }
  .stat-row span:first-child{ color:var(--muted); }
  #chat-toggle{ position:absolute; z-index:6; left:14px; bottom:14px;
    background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border:none; border-radius:50%;
    width:52px; height:52px; font-size:1.2em; box-shadow:0 8px 20px rgba(254,1,154,.4); cursor:pointer;
    display:none; align-items:center; justify-content:center; }
  #chat-box{ position:absolute; bottom:14px; left:14px; width:320px; max-height:230px;
    display:flex; flex-direction:column; z-index:5;
    background:var(--card); border:1px solid var(--border); border-radius:16px; overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.5); }
  #chat-log{ padding:10px; height:160px; overflow-y:auto; font-size:13px; }
  #chat-log div{ margin-bottom:6px; line-height:1.35; }
  #chat-log b{ color:var(--pk); }
  #chat-form{ display:flex; border-top:1px solid var(--border); }
  #chat-input{ flex:1; padding:10px 12px; border:none; outline:none; font-size:13px; font-family:inherit; background:var(--surface); color:var(--text); }
  #chat-input::placeholder{ color:var(--muted); }
  #chat-form button{ padding:0 16px; border:none; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; cursor:pointer; font-weight:700; }
  #controls-hint{ position:absolute; bottom:14px; right:14px; z-index:5; color:var(--muted);
    font-size:11px; background:rgba(0,0,0,.4); padding:6px 10px; border-radius:8px; }
  #name-gate{ position:absolute; inset:0; z-index:10; display:flex; align-items:center; justify-content:center;
    background:radial-gradient(circle at 20% 10%, rgba(254,1,154,.25) 0%, transparent 55%),
               radial-gradient(circle at 80% 90%, rgba(155,93,229,.25) 0%, transparent 55%),
               var(--bg); }
  #name-gate .card{ background:var(--card); border:1px solid var(--border); padding:30px 28px; border-radius:22px;
    text-align:center; width:310px; box-shadow:0 20px 50px rgba(0,0,0,.6); }
  #name-gate h3{ margin:6px 0 2px; font-family:'Syne',sans-serif; font-weight:800; font-size:1.4em;
    background:linear-gradient(135deg,#FE019A 0%,#ff80d5 50%,#FFD700 100%);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  #name-gate p.sub{ color:var(--muted); font-size:.85em; margin:0 0 16px; }
  #name-gate input{ width:100%; padding:12px; margin:6px 0 14px; box-sizing:border-box;
    border:1.5px solid var(--border); border-radius:12px; font-size:14px; font-family:inherit;
    outline:none; background:var(--surface); color:var(--text); }
  #name-gate input:focus{ border-color:var(--pk); }
  .gender-row{ display:flex; gap:8px; margin-bottom:16px; }
  .gender-pill{ flex:1; padding:10px 4px; border-radius:20px; border:1.5px solid var(--border);
    background:var(--surface); cursor:pointer; font-weight:600; font-size:.82em; color:var(--muted); transition:all .15s; }
  .gender-pill.selected{ border-color:var(--pk); background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  .picker-label{ text-align:left; font-size:.72em; color:var(--muted); margin-bottom:6px; font-weight:600; }
  .swatch-row{ display:flex; gap:8px; margin-bottom:16px; }
  .swatch{ width:32px; height:32px; border-radius:50%; border:2px solid var(--border); cursor:pointer; padding:0; }
  .swatch.selected{ border-color:#fff; box-shadow:0 0 0 2px var(--pk); }
  #name-gate button.enter{ width:100%; padding:12px; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border:none;
    border-radius:12px; font-weight:700; cursor:pointer; font-size:14px; font-family:'Syne',sans-serif; }
  #name-gate .note{ margin-top:12px; font-size:.7em; color:var(--muted); }
  #name-gate .note.logged-in{ color:var(--green); }
  #joystick-zone{ position:absolute; left:20px; bottom:90px; width:120px; height:120px; z-index:6; display:none; touch-action:none; }
  #joystick-base{ width:100%; height:100%; border-radius:50%; background:rgba(255,255,255,.08); border:2px solid rgba(255,255,255,.25); }
  #joystick-stick{ position:absolute; top:35px; left:35px; width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg,#FE019A,#9b5de5); box-shadow:0 4px 14px rgba(0,0,0,.4); }

  /* ============ HEALTH HUD ============ */
  #health-pill{ pointer-events:auto; display:flex; align-items:center; gap:8px;
    padding:7px 12px; border-radius:20px; font-size:.8em; font-weight:700;
    background:var(--card); border:1px solid var(--border); box-shadow:0 6px 18px rgba(0,0,0,.4); }
  #health-pill i { color:#ff4d6d; }
  #health-pill.critical { animation:hpPulse 0.8s infinite; }
  @keyframes hpPulse { 50% { box-shadow:0 0 0 4px rgba(255,77,109,.35); } }
  #health-bar { width:78px; height:8px; background:rgba(0,0,0,.45); border-radius:4px; overflow:hidden; }
  #health-fill { height:100%; width:100%; background:linear-gradient(90deg,#00f593,#7ee787); transition:width .25s, background .25s; }
  #health-val { min-width:28px; text-align:right; color:#fff; }
  #ambulance-btn{ position:absolute; top:110px; left:50%; transform:translateX(-50%); z-index:7;
    display:none; padding:11px 20px; border:none; border-radius:22px; font-weight:800; font-size:.88em;
    color:#fff; background:linear-gradient(135deg,#d62828,#ff4d6d); cursor:pointer;
    box-shadow:0 8px 24px rgba(214,40,40,.5); animation:ambPulse 1s infinite; }
  @keyframes ambPulse { 50% { transform:translateX(-50%) scale(1.06); } }
  #hurt-flash{ position:absolute; inset:0; pointer-events:none; z-index:40;
    background:radial-gradient(circle,transparent 40%,rgba(200,0,30,.55) 100%);
    opacity:0; transition:opacity .15s; }
  #hurt-flash.on { opacity:1; }
  #death-overlay{ position:absolute; inset:0; z-index:45; display:none; align-items:center; justify-content:center;
    background:rgba(20,0,0,.75); backdrop-filter:blur(4px); }
  #death-overlay.open { display:flex; }
  .death-card { text-align:center; color:#fff; font-family:'Syne',sans-serif; }
  .death-skull { font-size:4.5em; animation:skullBounce 0.9s infinite alternate; }
  @keyframes skullBounce { from{transform:translateY(-8px);} to{transform:translateY(8px);} }
  .death-title { font-size:1.6em; font-weight:800; margin-top:8px; }
  .death-sub { color:#ffb3b3; margin-top:6px; font-size:.95em; }
  #hospital-overlay{ position:absolute; inset:0; z-index:46; display:none; align-items:center; justify-content:center;
    background:radial-gradient(circle at 20% 20%, rgba(120,200,220,.15), transparent 60%),
               radial-gradient(circle at 80% 80%, rgba(200,220,255,.15), transparent 60%),
               linear-gradient(180deg, #0d1b2a 0%, #071622 100%); }
  #hospital-overlay.open { display:flex; }
  .hosp-room { width:min(92vw, 460px); padding:26px 22px; border-radius:22px;
    background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    border:1px solid rgba(255,255,255,.14); box-shadow:0 25px 60px rgba(0,0,0,.6);
    text-align:center; color:#eaf3fb; position:relative; overflow:hidden; }
  .hosp-monitor { width:110px; height:70px; margin:0 auto 12px; border-radius:10px;
    background:linear-gradient(180deg,#03161a,#0a2b30); border:2px solid #1f4c55; position:relative; }
  .hosp-monitor::after { content:""; position:absolute; left:8px; right:8px; top:50%;
    height:2px; background:#00f593; box-shadow:0 0 8px #00f593; animation:ekg 1.4s linear infinite; }
  @keyframes ekg { 0%{transform:translateY(0) scaleX(1);opacity:.3;} 20%{transform:translateY(-8px);opacity:1;}
    30%{transform:translateY(6px);opacity:1;} 45%{transform:translateY(0) scaleX(.6);opacity:1;}
    100%{transform:translateY(0) scaleX(1);opacity:.4;} }
  .hosp-title { font-family:'Syne',sans-serif; font-weight:800; font-size:1.25em; color:#7fe3ff; }
  .hosp-sub { color:#a9c6d8; font-size:.86em; margin-top:4px; }
  .hosp-count { font-family:'Space Grotesk',monospace; font-size:3.4em; font-weight:800; margin:8px 0 4px;
    background:linear-gradient(135deg,#7fe3ff,#ff80d5); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
  .hosp-note { color:#9fb6c6; font-size:.82em; line-height:1.5; margin-top:6px; }
  .hosp-note b { color:#ffd166; }
  #hosp-discharge-early { width:100%; margin-top:14px; padding:12px; border:none; border-radius:12px;
    background:rgba(255,255,255,.08); color:#7d93a4; font-weight:700; font-size:.85em; cursor:not-allowed; font-family:inherit; }

  @media (max-width:700px){
    #chat-box{ display:none; width:calc(100% - 28px); }
    #chat-box.open{ display:flex; }
    #chat-toggle{ display:flex; }
    #controls-hint{ display:none; }
    #joystick-zone{ display:block; }
    #mic-btn{ top:64px; left:auto; right:14px; }
    #sound-btn{ top:64px; left:auto; right:66px; }
    #map-btn{ top:110px; left:auto; right:14px; }
    #stats-btn{ top:156px; left:auto; right:14px; }
    #shop-btn{ top:202px; left:auto; right:14px; }
    #inventory-btn{ top:248px; left:auto; right:14px; }
    #health-pill { padding:6px 10px; font-size:.72em; }
    #health-bar { width:60px; }
    #ambulance-btn { top:56px; font-size:.78em; padding:9px 16px; }
  }
</style>
</head>
<body>

<div id="name-gate" class="checking">
  <div class="card">
    <h3>Neyyappam City</h3>
    <p class="sub">Kerala edition — GTA-style visuals</p>
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
    <div class="note" id="save-status">Guest mode — balloons won't be saved.</div>
  </div>
</div>

<div id="canvas-wrap"></div>
<div id="vignette"></div>

<div id="topbar">
  <div id="brand">
    <img src="https://neyyappam.com/assets/logos/neyyappamicon.png" onerror="this.style.display='none'" />
    <span>Neyyappam City</span>
  </div>
  <div id="hud-right">
    <div id="status-pill"><span class="live-dot"></span> <span id="online-count">1</span> online</div>
    <div id="health-pill" title="Health">
      <i class="fa-solid fa-heart"></i>
      <div id="health-bar"><div id="health-fill"></div></div>
      <span id="health-val">100</span>
    </div>
    <div id="balloon-pill"><i class="fa-solid fa-circle" style="border-radius:50%;"></i> 🎈 <span id="balloon-val">0</span></div>
  </div>
</div>

<button id="mic-btn" title="Microphone (off)"><i class="fa-solid fa-microphone-slash"></i></button>
<button id="sound-btn" title="Sound (off)"><i class="fa-solid fa-volume-xmark"></i></button>
<button id="map-btn"><i class="fa-solid fa-map"></i> Map</button>
<button id="stats-btn"><i class="fa-solid fa-user"></i> Stats</button>
<button id="shop-btn"><i class="fa-solid fa-store"></i> Shop</button>
<button id="inventory-btn"><i class="fa-solid fa-bag-shopping"></i> Bag</button>
<button id="ambulance-btn"><i class="fa-solid fa-truck-medical"></i> Call Ambulance (25 🎈)</button>

<div id="toast-stack"></div>

<div id="death-overlay">
  <div class="death-card">
    <div class="death-skull">💀</div>
    <div class="death-title">You were knocked out!</div>
    <div class="death-sub">An ambulance is rushing to you…</div>
  </div>
</div>

<div id="hospital-overlay">
  <div class="hosp-room">
    <div class="hosp-monitor"></div>
    <div class="hosp-title">🏥 Neyyappam General Hospital</div>
    <div class="hosp-sub" id="hosp-sub">Doctors are treating you…</div>
    <div class="hosp-count" id="hosp-count">25</div>
    <div class="hosp-note">You'll be discharged with <b>40% health</b>.<br>
      Buy <b>💊 medicine</b> at the pharmacy to heal faster.</div>
    <button id="hosp-discharge-early" disabled>Discharge in 25s…</button>
  </div>
</div>

<div id="hurt-flash"></div>

<div id="job-banner"></div>
<div id="waypoint-readout" style="display:none;"></div>

<div id="map-overlay" style="display:none;">
  <div id="map-panel">
    <div id="map-header"><span>City Map — tap to set a waypoint</span>
      <button id="map-close"><i class="fa-solid fa-xmark"></i></button></div>
    <canvas id="map-canvas" width="360" height="360"></canvas>
  </div>
</div>

<div id="stats-overlay" style="display:none;">
  <div id="stats-panel">
    <div id="stats-header"><span>Player Stats</span><button id="stats-close"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="stat-row"><span>Name</span><span id="stat-name">—</span></div>
    <div class="stat-row"><span>Look</span><span id="stat-look">—</span></div>
    <div class="stat-row"><span>Health</span><span id="stat-health">100%</span></div>
    <div class="stat-row"><span>Balloons</span><span id="stat-balloons">0</span></div>
    <div class="stat-row"><span>Distance</span><span id="stat-distance">0 m</span></div>
  </div>
</div>

<div id="shop-overlay" style="display:none;">
  <div id="shop-panel">
    <div id="shop-header"><span>🛒 Shop</span><button id="shop-close"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="shop-balance" id="shop-balance">🎈 0 balloons to spend</div>
    <div id="shop-grid"></div>
  </div>
</div>

<div id="inventory-overlay" style="display:none;">
  <div id="inventory-panel">
    <div id="inventory-header"><span>🎒 Inventory</span><button id="inventory-close"><i class="fa-solid fa-xmark"></i></button></div>
    <div id="inventory-list"></div>
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
<button id="shop-prompt" style="display:none; position:absolute; left:50%; bottom:150px; transform:translateX(-50%); z-index:6; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer;">🛒 Press B or tap to shop</button>
<button id="vehicle-prompt" style="display:none; position:absolute; left:50%; bottom:100px; transform:translateX(-50%); z-index:6; background:rgba(20,15,10,.85); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer;">Press E to enter</button>

<div id="joystick-zone"><div id="joystick-base"></div><div id="joystick-stick"></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
/* ============================================================
   NEYYAPPAM CITY — GTA-style upgrade
   ============================================================ */
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(65, innerWidth/innerHeight, 0.1, 1500);
camera.position.set(0, 3, 6);
scene.fog = new THREE.Fog(0xbcd3e0, 60, 260);

let renderer;
try {
  renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;
  renderer.outputEncoding = THREE.sRGBEncoding;
  document.getElementById('canvas-wrap').appendChild(renderer.domElement);
} catch (e) { alert('3D failed: ' + e.message); }

/* ---------- Sky / atmosphere ---------- */
function makeSky() {
  const cv = document.createElement('canvas'); cv.width = 2; cv.height = 512;
  const ctx = cv.getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 512);
  grad.addColorStop(0, '#1e6fc4'); grad.addColorStop(0.42, '#7fb8dd');
  grad.addColorStop(0.72, '#c8dcea'); grad.addColorStop(0.88, '#f0e2c8');
  grad.addColorStop(1, '#d4c79a');
  ctx.fillStyle = grad; ctx.fillRect(0, 0, 2, 512);
  const tex = new THREE.CanvasTexture(cv);
  const sky = new THREE.Mesh(new THREE.SphereGeometry(600, 32, 24),
    new THREE.MeshBasicMaterial({ map: tex, side: THREE.BackSide, fog: false, toneMapped: false }));
  scene.add(sky);
}
makeSky();

/* ---------- Lights: warm sun + soft ambient ---------- */
scene.add(new THREE.HemisphereLight(0xbdd9f0, 0x7a6a52, 0.85));
const sun = new THREE.DirectionalLight(0xfff0d0, 1.6);
sun.position.set(60, 90, 40);
sun.castShadow = true;
sun.shadow.mapSize.set(2048, 2048);
Object.assign(sun.shadow.camera, { left: -60, right: 60, top: 60, bottom: -60, near: 1, far: 260 });
sun.shadow.bias = -0.0006; sun.shadow.normalBias = 0.025;
scene.add(sun); scene.add(sun.target);
// Rim fill light
const fill = new THREE.DirectionalLight(0x8fbcd8, 0.35);
fill.position.set(-40, 30, -60);
scene.add(fill);

/* ---------- Ground & roads ---------- */
const BLOCK = 20, GRID = 6, ROAD_W = 8, HALF = (GRID * BLOCK) / 2;

const ground = new THREE.Mesh(
  new THREE.PlaneGeometry(GRID*BLOCK + 120, GRID*BLOCK + 120),
  new THREE.MeshStandardMaterial({ color: 0x6d9c48, roughness: 0.95 })
);
ground.rotation.x = -Math.PI/2; scene.add(ground);

// Asphalt road material (reflective when wet later)
const roadMat = new THREE.MeshStandardMaterial({ color: 0x2d2d33, roughness: 0.72, metalness: 0.15 });
window.ROAD_MAT = roadMat;
for (let i = -GRID/2; i <= GRID/2; i++) {
  const rv = new THREE.Mesh(new THREE.BoxGeometry(ROAD_W, 0.05, GRID*BLOCK + ROAD_W), roadMat);
  rv.position.set(i*BLOCK, 0.025, 0); rv.receiveShadow = true; scene.add(rv);
  const rh = new THREE.Mesh(new THREE.BoxGeometry(GRID*BLOCK + ROAD_W, 0.05, ROAD_W), roadMat);
  rh.position.set(0, 0.025, i*BLOCK); rh.receiveShadow = true; scene.add(rh);
}
// Lane markings
const laneMat = new THREE.MeshStandardMaterial({ color: 0xf2efe4, roughness: 0.6 });
for (let gx = -GRID/2; gx <= GRID/2; gx++) {
  for (let d = -GRID*BLOCK/2 + 2; d < GRID*BLOCK/2; d += 6) {
    const dv = new THREE.Mesh(new THREE.BoxGeometry(0.18, 0.055, 2.4), laneMat);
    dv.position.set(gx*BLOCK, 0.055, d); scene.add(dv);
    const dh = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.055, 0.18), laneMat);
    dh.position.set(d, 0.055, gx*BLOCK); scene.add(dh);
  }
}
// Sidewalk curbs
const curbMat = new THREE.MeshStandardMaterial({ color: 0x8a8a8a, roughness: 0.9 });
const curbTopMat = new THREE.MeshStandardMaterial({ color: 0xa8a8a0, roughness: 0.9 });
for (let gx = -GRID/2; gx < GRID/2; gx++) {
  for (let gz = -GRID/2; gz < GRID/2; gz++) {
    const cx = gx*BLOCK + BLOCK/2, cz = gz*BLOCK + BLOCK/2;
    const footprint = BLOCK - ROAD_W;
    const sidewalk = new THREE.Mesh(new THREE.BoxGeometry(footprint, 0.16, footprint), curbTopMat);
    sidewalk.position.set(cx, 0.08, cz); sidewalk.receiveShadow = true; scene.add(sidewalk);
    const curb = new THREE.Mesh(new THREE.BoxGeometry(footprint + 0.3, 0.22, footprint + 0.3), curbMat);
    curb.position.set(cx, 0.05, cz); scene.add(curb);
  }
}

/* ============================================================
   GTA-STYLE CAR — proper body panels, wheel wells, chrome, glass
   ============================================================ */
const carColors = [
  0x1a1a1a, 0xe6e6e6, 0xc81e1e, 0x2a4d8f, 0x1b6a3f,
  0xd4a017, 0x5a2a7a, 0x8a8a8a, 0x2b2b2b, 0xf2f2f2,
  0x0d3d5c, 0xb03030, 0xe0e0e0
];
const cars = {}; let carIdCounter = 0;

function mat(c, rough, metal, extra) {
  return new THREE.MeshStandardMaterial(Object.assign({
    color: c, roughness: rough === undefined ? 0.7 : rough, metalness: metal || 0
  }, extra || {}));
}

function addParkedCar(x, z, rotY) {
  const group = new THREE.Group();
  const color = carColors[Math.floor(Math.random()*carColors.length)];
  const isSports = Math.random() < 0.25;

  const bodyMat  = mat(color, 0.28, 0.75);
  const glassMat = mat(0x0a1620, 0.05, 0.4, { transparent: true, opacity: 0.7 });
  const chromeMat= mat(0xdfe3e8, 0.15, 0.95);
  const darkMat  = mat(0x0a0a0a, 0.7, 0.15);
  const tireMat  = mat(0x0e0e0e, 0.95, 0);
  const hubMat   = mat(0xcccccc, 0.2, 0.95);
  const headMat  = mat(0xffffff, 0.15, 0.3, { emissive: 0xfff5cc, emissiveIntensity: 0.45 });
  const tailMat  = mat(0xd42020, 0.25, 0.3, { emissive: 0xd42020, emissiveIntensity: 0.7 });
  const plateMat = mat(0xf5f5f5, 0.6, 0);

  const L = isSports ? 4.3 : 4.6;
  const W = isSports ? 1.85 : 1.82;
  const wheelR = isSports ? 0.36 : 0.34;
  const wheelbase = L * 0.6;

  const add = (geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); group.add(o); return o; };

  // ---- Lower body ----
  const lower = add(new THREE.BoxGeometry(L, 0.5, W), bodyMat, 0, 0.55, 0);
  // Hood with slight slope
  const hood = add(new THREE.BoxGeometry(L * 0.32, 0.22, W * 0.94), bodyMat, L * 0.28, 0.85, 0);
  hood.rotation.z = 0.06;
  // Front bumper
  add(new THREE.BoxGeometry(0.35, 0.34, W * 0.98), bodyMat, L * 0.44, 0.42, 0);
  add(new THREE.BoxGeometry(0.4, 0.09, W * 0.95), chromeMat, L * 0.455, 0.62, 0);
  // Rear deck
  const deck = add(new THREE.BoxGeometry(L * 0.28, 0.22, W * 0.94), bodyMat, -L * 0.32, 0.85, 0);
  add(new THREE.BoxGeometry(0.32, 0.34, W * 0.98), bodyMat, -L * 0.44, 0.42, 0);
  // Rear spoiler (only on sports)
  if (isSports) {
    add(new THREE.BoxGeometry(0.06, 0.09, W * 0.9), bodyMat, -L * 0.45, 0.98, 0);
    [-1, 1].forEach(s => add(new THREE.BoxGeometry(0.05, 0.16, 0.06), darkMat, -L * 0.45, 0.89, s * W * 0.42));
  }

  // ---- Cabin / greenhouse ----
  const cabinY = 1.12;
  const cabinH = isSports ? 0.42 : 0.54;
  const cabinL = isSports ? L * 0.36 : L * 0.42;
  add(new THREE.BoxGeometry(cabinL, cabinH, W * 0.85), bodyMat, -L * 0.02, cabinY, 0);
  // Roof
  add(new THREE.BoxGeometry(cabinL * 0.94, 0.05, W * 0.78), bodyMat, -L * 0.02, cabinY + cabinH/2, 0);
  // Glass insets (front / side / rear)
  add(new THREE.BoxGeometry(cabinL * 0.98, cabinH * 0.9, W * 0.83), glassMat, -L * 0.02, cabinY, 0);
  // Windshield angle accent
  add(new THREE.BoxGeometry(0.06, cabinH * 0.85, W * 0.8), chromeMat, cabinL * 0.45 - L*0.02, cabinY, 0);
  add(new THREE.BoxGeometry(0.06, cabinH * 0.85, W * 0.8), chromeMat, -cabinL * 0.45 - L*0.02, cabinY, 0);
  // Pillars
  [-1, 1].forEach(s => {
    add(new THREE.BoxGeometry(0.05, cabinH, 0.02), darkMat, cabinL * 0.44 - L*0.02, cabinY, s * W * 0.42);
    add(new THREE.BoxGeometry(0.05, cabinH, 0.02), darkMat, -cabinL * 0.44 - L*0.02, cabinY, s * W * 0.42);
  });

  // ---- Wheel wells + wheels ----
  const wheelX = wheelbase * 0.5;
  const wheelZ = W * 0.5;
  [[wheelX, wheelZ], [wheelX, -wheelZ], [-wheelX, wheelZ], [-wheelX, -wheelZ]].forEach(([wx, wz]) => {
    // Arch cutout (darker trim)
    add(new THREE.CylinderGeometry(wheelR + 0.06, wheelR + 0.06, 0.06, 20, 1, false, 0, Math.PI), darkMat,
        wx, wheelR * 0.55, wz > 0 ? wz * 0.96 : wz * 0.96).rotation.set(Math.PI/2, 0, 0);
    // Tire
    const tire = add(new THREE.CylinderGeometry(wheelR, wheelR, 0.24, 24), tireMat, wx, wheelR * 0.55, wz);
    tire.rotation.z = Math.PI/2; tire.castShadow = true;
    // Rim
    const rim = add(new THREE.CylinderGeometry(wheelR * 0.62, wheelR * 0.62, 0.26, 20), hubMat, wx, wheelR * 0.55, wz);
    rim.rotation.z = Math.PI/2;
    // Spokes (5-spoke pattern)
    for (let k = 0; k < 5; k++) {
      const sp = add(new THREE.BoxGeometry(0.26, 0.03, wheelR * 1.05), darkMat, wx, wheelR * 0.55, wz);
      sp.rotation.x = k * (Math.PI * 2 / 5);
    }
  });

  // ---- Headlights (front) ----
  [-1, 1].forEach(s => {
    add(new THREE.BoxGeometry(0.05, 0.18, 0.35), headMat, L/2 - 0.02, 0.72, s * W * 0.32);
    add(new THREE.BoxGeometry(0.03, 0.06, 0.4), darkMat, L/2 + 0.01, 0.72, s * W * 0.32);
  });
  // Tail lights
  [-1, 1].forEach(s => {
    add(new THREE.BoxGeometry(0.05, 0.16, 0.4), tailMat, -L/2 + 0.02, 0.75, s * W * 0.32);
  });
  // Front grille
  add(new THREE.BoxGeometry(0.05, 0.14, W * 0.55), darkMat, L/2 - 0.01, 0.55, 0);
  // Chrome grille slats
  for (let i = 0; i < 5; i++) add(new THREE.BoxGeometry(0.02, 0.02, W * 0.52), chromeMat, L/2 + 0.005, 0.5 + i * 0.028, 0);
  // License plate
  add(new THREE.BoxGeometry(0.04, 0.1, 0.3), plateMat, L/2 + 0.03, 0.4, 0);
  add(new THREE.BoxGeometry(0.04, 0.1, 0.3), plateMat, -L/2 - 0.03, 0.4, 0);

  // Side mirrors
  [-1, 1].forEach(s => {
    add(new THREE.BoxGeometry(0.06, 0.09, 0.14), bodyMat, L * 0.12, cabinY - 0.05, s * W * 0.6);
  });

  // Wheel bolts / cap
  [[wheelX, wheelZ], [-wheelX, wheelZ], [wheelX, -wheelZ], [-wheelX, -wheelZ]].forEach(([wx, wz]) => {
    const cap = add(new THREE.CylinderGeometry(wheelR * 0.18, wheelR * 0.18, 0.28, 12), chromeMat, wx, wheelR * 0.55, wz);
    cap.rotation.z = Math.PI/2;
  });

  group.position.set(x, 0, z);
  group.rotation.y = rotY;
  group.traverse(o => { if (o.isMesh) o.castShadow = true; });
  scene.add(group);

  const id = 'car_' + (carIdCounter++);
  cars[id] = { id, group, occupiedBy: null };
  return id;
}

/* ============================================================
   KERALA-STYLE BUILDINGS — brick + tiled roofs + shop fronts
   ============================================================ */
const kMat = (c, o) => new THREE.MeshStandardMaterial(Object.assign({ color: c }, o || {}));
const houseColors = [0xf4ecd8, 0xf2d97a, 0x7fc8c2, 0xf0a6a0, 0xbcd8a0, 0xa9c8e8, 0xe8c0d0];
const roofMat  = kMat(0xb5482a, { roughness: 0.85 });
const roofMat2 = kMat(0x9c3b22, { roughness: 0.85 });
const roofGeo  = new THREE.ConeGeometry(1, 1, 4);
const doorMat  = kMat(0x4a2a12, { roughness: 0.75 });
const winFrameMat = kMat(0x3a2818, { roughness: 0.7 });
const winGlassMat = kMat(0x88b8d8, { roughness: 0.15, metalness: 0.35 });

function makeSignTex(text, bg, fg) {
  const cv = document.createElement('canvas'); cv.width = 512; cv.height = 128;
  const tex = new THREE.CanvasTexture(cv);
  const g = cv.getContext('2d');
  g.fillStyle = bg; g.fillRect(0, 0, 512, 128);
  g.strokeStyle = fg; g.lineWidth = 6; g.strokeRect(8, 8, 496, 112);
  g.fillStyle = fg; g.font = "700 56px 'Noto Sans Malayalam','Nirmala UI',sans-serif";
  g.textAlign = 'center'; g.textBaseline = 'middle';
  g.fillText(text, 256, 68, 470);
  tex.needsUpdate = true;
  return tex;
}
const SIGNS = [
  makeSignTex('പലചരക്ക്', '#7a2e1d', '#fff2cc'),
  makeSignTex('ബേക്കറി', '#f4c95d', '#5a1f0f'),
  makeSignTex('ഹോട്ടൽ', '#b33a3a', '#ffffff'),
  makeSignTex('മെഡിക്കൽ', '#0f7b6c', '#ffffff'),
  makeSignTex('മീൻ കട', '#1d6fa5', '#ffffff'),
  makeSignTex('ചായക്കട', '#1f5b3a', '#ffd166')
];

function addKeralaHouse(cx, cz, footprint) {
  const w = footprint * (0.6 + Math.random()*0.15);
  const h = 3.4 + Math.random()*2;
  const body = new THREE.Mesh(new THREE.BoxGeometry(w, h, w), kMat(houseColors[Math.floor(Math.random()*houseColors.length)], { roughness: 0.9 }));
  body.position.set(cx, 0.16 + h/2, cz);
  body.castShadow = body.receiveShadow = true;
  scene.add(body);

  const roof = new THREE.Mesh(roofGeo, Math.random() > 0.5 ? roofMat : roofMat2);
  const r = (w + 1.4) * 0.7071;
  roof.scale.set(r, 2.4, r);
  roof.rotation.y = Math.PI/4;
  roof.position.set(cx, 0.16 + h + 1.2, cz);
  roof.castShadow = true; scene.add(roof);

  const fz = cz + w/2 + 0.03;
  const sign = new THREE.Mesh(new THREE.PlaneGeometry(Math.min(4, w*0.75), 0.95),
    new THREE.MeshBasicMaterial({ map: SIGNS[Math.floor(Math.random()*SIGNS.length)] }));
  sign.position.set(cx, 3.05, fz + 0.02); scene.add(sign);

  const door = new THREE.Mesh(new THREE.PlaneGeometry(1.2, 2.1), doorMat);
  door.position.set(cx, 1.2, fz + 0.01); scene.add(door);

  [-1, 1].forEach(s => {
    const fr = new THREE.Mesh(new THREE.BoxGeometry(1.05, 1.15, 0.08), winFrameMat);
    fr.position.set(cx + s*w*0.3, 1.85, fz); scene.add(fr);
    const gl = new THREE.Mesh(new THREE.PlaneGeometry(0.8, 0.9), winGlassMat);
    gl.position.set(cx + s*w*0.3, 1.85, fz + 0.05); scene.add(gl);
  });
}

// Build city blocks
const balloonSpots = []; let balloonId = 0;
for (let gx = -GRID/2; gx < GRID/2; gx++) {
  for (let gz = -GRID/2; gz < GRID/2; gz++) {
    if (gx === 0 && gz === 0) continue;
    const cx = gx*BLOCK + BLOCK/2, cz = gz*BLOCK + BLOCK/2;
    const footprint = BLOCK - ROAD_W;

    addKeralaHouse(cx, cz, footprint);
    // Some parked cars
    if (Math.random() < 0.6) {
      addParkedCar(cx + (Math.random()-0.5)*footprint*0.5, cz + footprint/2 + 2.2, Math.random() > 0.5 ? 0 : Math.PI);
    }
    balloonSpots.push({ id: 'balloon_' + (balloonId++), x: cx + (Math.random()-0.5)*4, z: cz + (Math.random()-0.5)*4 });
  }
}

/* ============================================================
   GTA-STYLE REALISTIC HUMANS
   ============================================================ */
const genderColors = { male: 0x2a3950, female: 0x9b2c5a, other: 0x4a3a70 };
const _geo = {};
const G = (key, make) => _geo[key] || (_geo[key] = make());

// Skin texture generator
const _skinTx = {};
function skinTex(hex) {
  if (_skinTx[hex]) return _skinTx[hex];
  const c = document.createElement('canvas'); c.width = c.height = 128;
  const x = c.getContext('2d');
  const base = new THREE.Color(hex), css = '#' + base.getHexString();
  x.fillStyle = css; x.fillRect(0, 0, 128, 128);
  const gr = x.createLinearGradient(0, 0, 0, 128);
  gr.addColorStop(0, 'rgba(255,200,170,0.12)');
  gr.addColorStop(0.5, 'rgba(0,0,0,0)');
  gr.addColorStop(1, 'rgba(60,20,10,0.14)');
  x.fillStyle = gr; x.fillRect(0, 0, 128, 128);
  for (let i = 0; i < 1400; i++) {
    x.fillStyle = Math.random() < 0.6 ? 'rgba(90,50,30,' + (0.03 + Math.random()*0.06) + ')' : 'rgba(255,230,210,' + (0.03 + Math.random()*0.05) + ')';
    x.fillRect(Math.random()*128, Math.random()*128, 1 + Math.random()*1.5, 1 + Math.random()*1.5);
  }
  for (let i = 0; i < 20; i++) {
    x.fillStyle = 'rgba(110,60,30,0.15)';
    x.beginPath(); x.arc(Math.random()*128, Math.random()*128, 0.8 + Math.random(), 0, 7); x.fill();
  }
  return (_skinTx[hex] = new THREE.CanvasTexture(c));
}

const _shirtTx = {};
function shirtTex(color, type) {
  const key = color + '_' + type; if (_shirtTx[key]) return _shirtTx[key];
  const c = document.createElement('canvas'); c.width = c.height = 128;
  const x = c.getContext('2d');
  const baseCss = typeof color === 'number' ? '#' + color.toString(16).padStart(6, '0') : color;
  x.fillStyle = baseCss; x.fillRect(0, 0, 128, 128);
  // Fabric weave
  x.strokeStyle = 'rgba(255,255,255,0.08)'; x.lineWidth = 1;
  for (let i = 0; i < 128; i += 4) { x.beginPath(); x.moveTo(i,0); x.lineTo(i,128); x.stroke(); }
  // Noise
  for (let i = 0; i < 3000; i++) {
    x.fillStyle = Math.random() < 0.5 ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.06)';
    x.fillRect(Math.random()*128, Math.random()*128, 1, 1);
  }
  const t = new THREE.CanvasTexture(c);
  t.wrapS = t.wrapT = THREE.RepeatWrapping;
  t.repeat.set(2, 2);
  return (_shirtTx[key] = t);
}

function makeAvatarMesh(gender, outfitColor, hairStyle, role, look) {
  look = look || {};
  const isF = gender === 'female' || role === 'karavakkari';
  const mature = role === 'karavakkari';
  const color = outfitColor ? parseInt(String(outfitColor).replace('#', ''), 16) : (genderColors[gender] || genderColors.other);
  const style = hairStyle || 'short';

  const group = new THREE.Group();
  const body = new THREE.Group(); group.add(body);

  const skinHex = role === 'karavakkari' ? 0xb87850 : role === 'chayakkaran' ? 0x9d6a45 : (look.skin || 0xe8b88a);
  const M = (c, rough, metal, extra) => new THREE.MeshStandardMaterial(Object.assign({
    color: c, roughness: rough === undefined ? 0.75 : rough, metalness: metal || 0
  }, extra || {}));
  const skinMat = M(0xffffff, 0.55, 0, { map: skinTex(skinHex) });
  const hairMat = M(look.hair || 0x150c07, 0.42, 0);
  const gold = M(0xd4a53a, 0.3, 0.85);
  const steel = M(0xbfc4cc, 0.25, 0.9);
  const denim = M(0x1e2d4a, 0.88, 0);
  const shoeMat = role === 'chayakkaran' ? M(0x4a3520, 0.7, 0) : M(0x121212, 0.55, 0.05);

  const add = (parent, geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); parent.add(o); return o; };
  const sph = (r, ws, hs) => G('s'+r+'_'+(ws||18)+'_'+(hs||14), () => new THREE.SphereGeometry(r, ws||18, hs||14));
  const cyl = (r1, r2, h, s) => G('c'+r1+'_'+r2+'_'+h, () => new THREE.CylinderGeometry(r1, r2, h, s||16));
  const box = (w, h, d) => G('b'+w+'_'+h+'_'+d, () => new THREE.BoxGeometry(w, h, d));
  const torus = (r, t, seg) => G('t'+r+'_'+t, () => new THREE.TorusGeometry(r, t, seg||8, 24));

  // Proportions
  const female = isF && !mature;
  const shoulderW = female ? 0.19 : 0.22;
  const waistW    = female ? 0.16 : 0.17;
  const hipW      = female ? 0.185 : 0.17;
  const shY = 1.46, hipY = 0.95, headY = 1.68;

  // ---- Torso (lathe geometry, curved) ----
  const prof = [];
  const px = (r, y) => new THREE.Vector2(r, y);
  if (female) {
    prof.push(px(0.001, 0.86), px(hipW*0.95, 0.92), px(hipW, 0.98),
              px(waistW*0.9, 1.10), px(waistW, 1.22), px(shoulderW*0.9, 1.36),
              px(shoulderW, 1.46), px(shoulderW*0.8, 1.50), px(0.001, 1.52));
  } else {
    prof.push(px(0.001, 0.86), px(hipW, 0.92), px(hipW*0.98, 1.00),
              px(waistW, 1.14), px(waistW*1.02, 1.24), px(shoulderW*0.94, 1.38),
              px(shoulderW, 1.47), px(shoulderW*0.82, 1.50), px(0.001, 1.52));
  }
  const torsoGeo = new THREE.LatheGeometry(prof, 28);
  const torsoMat = role === 'chayakkaran' ? M(0xf5f0e0, 0.85, 0) :
                   role === 'karavakkari' ? M(0x8a1a24, 0.7, 0) :
                   M(0xffffff, 0.82, 0, { map: shirtTex(color, 'shirt') });
  const torso = add(body, torsoGeo, torsoMat, 0, 0, 0);
  torso.scale.z = 0.72;
  torso.castShadow = true;

  // Collar
  if (!role) {
    const collar = add(body, cyl(0.115, 0.12, 0.045, 18), M(0x1a1a1a, 0.7, 0), 0, 1.50, 0.005);
    collar.scale.set(1, 1, 0.75);
  }

  // ---- Neck ----
  add(body, cyl(0.05, 0.06, 0.13, 14), skinMat, 0, 1.555, 0.005);

  // ---- Head ----
  const head = new THREE.Group(); head.position.set(0, headY, 0); body.add(head);
  const skull = add(head, sph(0.115, 26, 22), skinMat, 0, 0.015, 0);
  skull.scale.set(0.94, 1.02, 0.98);
  skull.castShadow = true;
  const jaw = add(head, sph(0.082, 20, 16), skinMat, 0, -0.05, 0.008);
  jaw.scale.set(1, 0.76, 0.92);
  [-1, 1].forEach(s => add(head, sph(0.034, 12, 10), skinMat, s*0.055, -0.028, 0.055).scale.set(1, 0.85, 0.7));

  // Eyes
  const scleraMat = M(0xf2ece4, 0.3, 0);
  const irisMat = M(0x3d2417, 0.15, 0.05);
  const pupilMat = M(0x050505, 0.1, 0.15);
  const hlMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
  [-1, 1].forEach(s => {
    // eye socket
    add(head, sph(0.032, 12, 10), M(0xd8a37a, 0.6, 0), s*0.042, 0.018, 0.05).scale.set(1, 0.7, 0.5);
    // eyeball
    const eyeball = add(head, sph(0.017, 14, 12), scleraMat, s*0.042, 0.018, 0.084);
    eyeball.scale.set(1, 1, 0.6);
    // iris
    add(head, new THREE.CircleGeometry(0.0092, 18), irisMat, s*0.042, 0.018, 0.0975);
    // pupil
    add(head, new THREE.CircleGeometry(0.0048, 14), pupilMat, s*0.042, 0.018, 0.0998);
    // catch light
    add(head, new THREE.CircleGeometry(0.0022, 8), hlMat, s*0.042 + 0.0035, 0.0212, 0.1005);
    // upper lash line
    add(head, box(0.036, 0.0022, 0.006), M(0x0a0604, 0.35, 0), s*0.042, 0.031, 0.093);
    // eyelid crease
    add(head, box(0.036, 0.0045, 0.01), M(0x8a5a3a, 0.55, 0), s*0.042, 0.035, 0.089).rotation.z = -s*0.08;
    // brow
    add(head, box(0.042, 0.0055, 0.008), hairMat, s*0.042, 0.055, 0.081).rotation.z = -s*0.14;
  });

  // Nose
  const bridge = add(head, sph(0.012, 10, 8), skinMat, 0, 0.005, 0.098);
  bridge.scale.set(0.7, 1.6, 0.9);
  const tip = add(head, sph(0.016, 12, 10), skinMat, 0, -0.025, 0.107);
  tip.scale.set(1, 0.85, 1);
  [-1, 1].forEach(s => add(head, sph(0.005, 6, 5), M(0xb8794f, 0.6, 0), s*0.008, -0.03, 0.106).scale.set(0.9, 0.6, 0.7));

  // Lips
  const lipCol = role === 'karavakkari' ? 0x8a1c28 : isF ? 0xba4a58 : 0xa86554;
  const lipMat = M(lipCol, 0.5, 0);
  add(head, sph(0.018, 10, 8), lipMat, 0, -0.055, 0.098).scale.set(1.4, 0.5, 0.7);
  add(head, sph(0.019, 10, 8), lipMat, 0, -0.066, 0.096).scale.set(1.3, 0.55, 0.75);
  add(head, box(0.028, 0.0015, 0.003), M(0x4a1018, 0.5, 0), 0, -0.060, 0.102);

  // Ears
  [-1, 1].forEach(s => {
    const ear = add(head, sph(0.026, 10, 8), skinMat, s*0.098, 0.005, 0.002);
    ear.scale.set(0.5, 1.1, 0.85);
  });

  // ---- Hair ----
  const hairCap = add(head, new THREE.SphereGeometry(0.128, 24, 20, 0, Math.PI*2, 0, Math.PI * 0.58), hairMat, 0, 0.026, -0.006);
  hairCap.scale.set(0.97, 1.05, 1.02);
  hairCap.castShadow = true;
  const backHair = add(head, sph(0.118, 20, 16), hairMat, 0, 0.0, -0.028);
  backHair.scale.set(0.98, 0.95, 1.05);
  [-1, 1].forEach(s => add(head, sph(0.026, 10, 8), hairMat, s*0.100, 0.005, -0.015).scale.set(0.6, 1.5, 0.85));

  if (mature) {
    const grey = M(0x8a8a8a, 0.5, 0);
    for (let i = 0; i < 8; i++) add(body, sph(0.03 - i*0.001, 10, 8), grey, 0, 1.62 - i*0.072, -0.115 - i*0.01).scale.set(1.05, 1, 0.9);
    const bun = add(head, sph(0.072, 16, 14), grey, 0, 0.075, -0.125);
    bun.scale.set(1, 0.9, 0.85);
    add(head, sph(0.0065, 6, 6), M(0xb01822, 0.4, 0), 0, 0.058, 0.098);
  } else if (style === 'pony') {
    const pony = new THREE.Group(); pony.position.set(0, 0.035, -0.115); head.add(pony);
    add(pony, cyl(0.038, 0.055, 0.19, 12), hairMat, 0, -0.095, 0).rotation.x = 0.32;
    add(pony, cyl(0.028, 0.038, 0.22, 10), hairMat, 0, -0.29, 0.06).rotation.x = 0.16;
    add(pony, sph(0.055, 12, 10), hairMat, 0, -0.40, 0.115);
    add(pony, cyl(0.042, 0.042, 0.02, 12), M(0xd83c3c, 0.6, 0), 0, -0.005, 0);
  } else if (style === 'bandana') {
    const band = add(head, cyl(0.126, 0.126, 0.05, 20), M(0xd83c3c, 0.7, 0), 0, 0.055, 0);
    band.scale.set(1, 1, 0.98);
    add(head, sph(0.028, 8, 6), M(0xd83c3c, 0.7, 0), 0.105, 0.055, -0.06).scale.set(1, 0.7, 1);
  }

  // ---- Arms ----
  const arms = [], elbows = [];
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * shoulderW * 0.92, shY - 0.05, 0); pv.rotation.z = s * 0.07; body.add(pv);
    add(pv, sph(0.058, 14, 12), torsoMat, 0, 0.02, 0);
    add(pv, cyl(0.042, 0.034, 0.28, 12), skinMat, 0, -0.16, 0);
    add(pv, sph(0.034, 12, 10), skinMat, 0, -0.30, 0);
    const fa = new THREE.Group(); fa.position.set(0, -0.30, 0); fa.rotation.x = -0.18; pv.add(fa);
    add(fa, cyl(0.033, 0.026, 0.26, 12), skinMat, 0, -0.13, 0);
    add(fa, sph(0.026, 10, 8), skinMat, 0, -0.27, 0);
    // Palm
    const palm = add(fa, box(0.052, 0.07, 0.02), skinMat, 0, -0.315, 0.005);
    palm.rotation.x = 0.05;
    // Thumb
    add(fa, cyl(0.009, 0.008, 0.032, 8), skinMat, -s*0.03, -0.31, 0.005).rotation.z = s * 0.7;
    // Fingers
    for (let f = 0; f < 4; f++) {
      const fx = -0.019 + f * 0.0125;
      add(fa, box(0.010, 0.042, 0.012), skinMat, fx, -0.362, 0.010);
    }
    // Sleeve
    add(pv, cyl(0.048, 0.045, 0.13, 12), torsoMat, 0, -0.10, 0);
    if (role === 'karavakkari') [0, 1].forEach(k => {
      add(fa, torus(0.03, 0.005), gold, 0, -0.24 - k*0.016, 0).rotation.x = Math.PI/2;
    });
    pv.traverse(o => { if (o.isMesh) o.castShadow = true; });
    arms.push(pv); elbows.push(fa);
  });

  // ---- Legs ----
  const legs = [], knees = [];
  const legMat = (isF || role === 'chayakkaran') ? skinMat : denim;
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * hipW * 0.62, hipY, 0); body.add(pv);
    add(pv, sph(0.082, 14, 12), legMat, 0, 0.02, 0);
    add(pv, cyl(0.078, 0.058, 0.46, 14), legMat, 0, -0.24, 0);
    add(pv, sph(0.056, 12, 10), legMat, 0, -0.46, 0);
    const sk = new THREE.Group(); sk.position.set(0, -0.46, 0); pv.add(sk);
    add(sk, cyl(0.055, 0.034, 0.44, 12), legMat, 0, -0.22, 0);
    add(sk, sph(0.034, 10, 8), legMat, 0, -0.45, 0);
    // Shoe
    const foot = add(sk, box(0.078, 0.055, 0.23), shoeMat, 0, -0.48, 0.05);
    add(sk, box(0.084, 0.012, 0.24), M(0x000000, 0.9, 0), 0, -0.503, 0.05);
    add(sk, sph(0.04, 10, 8), shoeMat, 0, -0.472, 0.155).scale.set(0.9, 0.6, 0.7);
    pv.traverse(o => { if (o.isMesh) o.castShadow = true; });
    legs.push(pv); knees.push(sk);
  });

  // ---- Role: Chayakkaran ----
  if (role === 'chayakkaran') {
    add(body, cyl(0.20, 0.27, 0.66, 20), M(0xf7f3e8, 0.88, 0), 0, 0.62, 0);
    add(body, torus(0.27, 0.013), gold, 0, 0.30, 0).rotation.x = Math.PI/2;
  }
  // ---- Role: Karavakkari ----
  else if (role === 'karavakkari') {
    const sari = M(0x146b3a, 0.75, 0);
    add(body, cyl(0.20, 0.40, 0.86, 22), sari, 0, 0.50, 0).scale.z = 0.75;
    add(body, torus(0.40, 0.020), gold, 0, 0.08, 0).rotation.x = Math.PI/2;
    add(body, torus(0.32, 0.011), gold, 0, 0.28, 0).rotation.x = Math.PI/2;
    const pallu = add(body, box(0.17, 0.52, 0.055), M(0xc9a428, 0.65, 0), shoulderW*0.35, 1.28, 0.02);
    pallu.rotation.z = -0.15;
    add(head, sph(0.0075, 6, 6), M(0xb01822, 0.4, 0), 0, 0.05, 0.098).scale.set(1, 1.2, 0.5);
  }
  // Female top fit
  else if (isF) {
    add(body, cyl(0.14, 0.22, 0.42, 18), torsoMat, 0, 0.75, 0);
  }

  // Sunglasses
  if (look.shades) {
    const frameMat = M(0x090909, 0.35, 0.6);
    const lensMat = M(0x1a1a1a, 0.15, 0.3, { transparent: true, opacity: 0.85 });
    [-1, 1].forEach(s => {
      add(head, box(0.05, 0.03, 0.008), frameMat, s*0.042, 0.020, 0.100);
      add(head, box(0.046, 0.026, 0.006), lensMat, s*0.042, 0.020, 0.103);
    });
    add(head, box(0.028, 0.005, 0.008), frameMat, 0, 0.034, 0.100);
  }

  if (look.h) group.scale.setScalar(look.h);
  if (look.w) body.scale.x *= look.w;

  group.userData = { legs, arms, elbows, knees, body, head, emote: null };
  group.traverse(o => { if (o.isMesh) o.castShadow = true; });
  return group;
}

/* ---------- Animation ticker ---------- */
function tickAvatar(g, now) {
  const u = g && g.userData; if (!u || !u.legs) return;
  const p = g.position, sp = u.lx === undefined ? 0 : Math.hypot(p.x - u.lx, p.z - u.lz);
  u.lx = p.x; u.lz = p.z;
  u.ph = (u.ph || 0) + sp * 9;
  const amp = Math.min(0.8, sp * 7), la = Math.sin(u.ph) * amp, T = now / 1000;
  let a0x = -la * 0.9, a1x = la * 0.9, a0z = -0.07, a1z = 0.07;
  let bob = Math.abs(Math.sin(u.ph)) * 0.018 * amp + Math.sin(T * 2) * 0.003;
  let rx = 0, rz = 0, l0 = la, l1 = -la;

  const e = u.emote && now < u.emote.until ? u.emote : null;
  if (e) {
    const t6 = T * 6;
    if (e.type === 'dance') { a0x = -2.4 + Math.sin(t6) * 0.5; a1x = -2.4 - Math.sin(t6) * 0.5; a0z = -0.5; a1z = 0.5; rz = Math.sin(t6*0.5) * 0.12; bob = Math.abs(Math.sin(t6)) * 0.07; l0 = Math.sin(t6) * 0.5; l1 = -Math.sin(t6) * 0.5; }
    else if (e.type === 'laugh') { a0x = a1x = -0.9; bob = Math.abs(Math.sin(T*14)) * 0.04; rx = 0.12 + Math.sin(T*10) * 0.06; }
    else if (e.type === 'aiyyo') { a0x = a1x = -2.6; a0z = -0.9; a1z = 0.9; rz = Math.sin(T*30) * 0.05; bob = Math.abs(Math.sin(T*18)) * 0.03; }
    else if (e.type === 'punch') { a1x = -1.55 + Math.sin(T*24) * 0.25; a0x = -0.5; a0z = -0.2; rx = 0.12; }
  } else u.emote = null;

  u.legs[0].rotation.x = l0; u.legs[1].rotation.x = l1;
  u.arms[0].rotation.x = a0x; u.arms[1].rotation.x = a1x;
  u.arms[0].rotation.z = a0z; u.arms[1].rotation.z = a1z;
  u.body.position.y = bob;
  u.body.rotation.x = rx;
  u.body.rotation.z = rz;
  if (u.knees) {
    u.knees[0].rotation.x = Math.max(0, -Math.cos(u.ph)) * amp * 1.1;
    u.knees[1].rotation.x = Math.max(0, Math.cos(u.ph)) * amp * 1.1;
  }
}

/* ---------- Label ---------- */
function makeLabel(text) {
  const cv = document.createElement('canvas'); cv.width = 256; cv.height = 56;
  const ctx = cv.getContext('2d');
  ctx.fillStyle = 'rgba(20,10,30,.85)';
  ctx.beginPath();
  const r = 14;
  ctx.moveTo(r, 0); ctx.arcTo(256, 0, 256, 56, r); ctx.arcTo(256, 56, 0, 56, r);
  ctx.arcTo(0, 56, 0, 0, r); ctx.arcTo(0, 0, 256, 0, r);
  ctx.closePath(); ctx.fill();
  ctx.fillStyle = '#FE019A';
  ctx.font = 'bold 24px sans-serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.fillText(text.slice(0, 22), 128, 30);
  const tex = new THREE.CanvasTexture(cv);
  const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
  sp.scale.set(1.8, 0.4, 1);
  sp.position.y = 2.2;
  return sp;
}

/* ---------- Player avatar ---------- */
let myGender = 'other', myOutfitColor = '#9b5de5', myHairStyle = 'short', myRole = null;
let myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle);
scene.add(myAvatar);

const others = {};

/* ---------- Pedestrians (GTA-style NPCs on sidewalks) ---------- */
const PEDS = [];
(function spawnPeds() {
  const skins = [0xf1c9a5, 0xe0ac82, 0xc68a5d, 0xae7a50, 0x8d5a3b, 0x6b4429];
  const shirts = ['#ff6fb5', '#2ec4b6', '#ffd166', '#ff8a5b', '#f8f4e3', '#7bdff2', '#b388eb'];
  const pants = [0xf1ece0, 0xd9c9a3, 0x3d5a80, 0x2e2e38, 0xe8dcc8];
  const hairs = ['short', 'pony', 'bandana'];
  const R = a => a[Math.floor(Math.random() * a.length)];
  for (let i = 0; i < 14; i++) {
    const g = makeAvatarMesh(Math.random() < 0.5 ? 'female' : 'male', R(shirts), R(hairs), null, {
      skin: R(skins), shades: Math.random() < 0.4,
      hair: R([0x0e0a08, 0x2b1a10, 0x4a2c17, 0x7a4a22, 0x8a8a8a]),
      h: 0.95 + Math.random() * 0.1, w: 0.92 + Math.random() * 0.2
    });
    scene.add(g);
    PEDS.push({
      g, axis: Math.random() < 0.5 ? 0 : 1,
      line: Math.floor(Math.random() * (GRID + 1)) - GRID / 2,
      side: (Math.random() < 0.5 ? -1 : 1) * (ROAD_W / 2 + 1.5),
      dir: Math.random() < 0.5 ? -1 : 1,
      pos: (Math.random() * 2 - 1) * HALF,
      speed: 1.1 + Math.random() * 0.7, cool: 0
    });
  }
})();

function updatePeds(dt) {
  PEDS.forEach(p => {
    const prev = p.pos;
    p.pos += p.dir * p.speed * dt;
    p.cool -= dt;
    if (Math.abs(p.pos) > HALF + 3) { p.dir *= -1; p.pos = Math.sign(p.pos) * (HALF + 3); }
    const k0 = Math.floor(prev / BLOCK), k1 = Math.floor(p.pos / BLOCK);
    if (k0 !== k1 && p.cool <= 0 && Math.random() < 0.35) {
      const k = p.dir > 0 ? k1 : k0;
      if (Math.abs(k) <= GRID / 2) {
        const old = p.line;
        p.axis ^= 1; p.line = k; p.pos = old * BLOCK;
        p.dir = Math.random() < 0.5 ? -1 : 1;
        p.side = (Math.random() < 0.5 ? -1 : 1) * (ROAD_W / 2 + 1.5);
        p.cool = 2;
      }
    }
    const a = p.line * BLOCK + p.side;
    p.g.position.set(p.axis === 0 ? p.pos : a, 0, p.axis === 0 ? a : p.pos);
    p.g.rotation.y = Math.atan2(p.axis === 0 ? p.dir : 0, p.axis === 0 ? 0 : p.dir);
  });
}

/* ---------- Shop NPCs ---------- */
{
  const keeper = makeAvatarMesh('female', '#ffb703', 'pony');
  keeper.position.set(10, 0.16, 12.7);
  keeper.add(makeLabel('Shopkeeper'));
  scene.add(keeper);
  const chaya = makeAvatarMesh('male', '#ffffff', 'short', 'chayakkaran');
  chaya.position.set(-10, 0.16, 10.8); chaya.rotation.y = Math.PI;
  chaya.add(makeLabel('Chayakkaran'));
  scene.add(chaya);
  const milk = makeAvatarMesh('female', '#a61e24', 'short', 'karavakkari');
  milk.position.set(-6, 0.16, -5.5);
  milk.add(makeLabel('Karavakkari'));
  scene.add(milk);
}

/* ============================================================
   MOVEMENT / INPUT
   ============================================================ */
const keys = {};
const KEYALIAS = { arrowup: 'w', arrowdown: 's', arrowleft: 'a', arrowright: 'd' };
addEventListener('keydown', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = true; if (KEYALIAS[k] && !/INPUT|TEXTAREA/.test((e.target.tagName || ''))) e.preventDefault(); });
addEventListener('keyup', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = false; });

let camYaw = 0, camPitch = 0.18, dragging = false, lastX = 0, lastY = 0;
renderer.domElement.addEventListener('mousedown', e => { dragging = true; lastX = e.clientX; lastY = e.clientY; });
addEventListener('mouseup', () => dragging = false);
addEventListener('mousemove', e => {
  if (!dragging) return;
  camYaw -= (e.clientX - lastX) * 0.006;
  camPitch = Math.max(-0.85, Math.min(1.0, camPitch + (e.clientY - lastY) * 0.004));
  lastX = e.clientX; lastY = e.clientY;
});
renderer.domElement.addEventListener('touchstart', e => { if (e.touches.length) { dragging = true; lastX = e.touches[0].clientX; lastY = e.touches[0].clientY; } }, { passive: true });
renderer.domElement.addEventListener('touchmove', e => {
  if (!dragging || !e.touches.length) return;
  camYaw -= (e.touches[0].clientX - lastX) * 0.006;
  camPitch = Math.max(-0.85, Math.min(1.0, camPitch + (e.touches[0].clientY - lastY) * 0.004));
  lastX = e.touches[0].clientX; lastY = e.touches[0].clientY;
}, { passive: true });
renderer.domElement.addEventListener('touchend', () => dragging = false);

// Joystick
let joyVec = { x: 0, y: 0 };
const joyZone = document.getElementById('joystick-zone');
const joyStick = document.getElementById('joystick-stick');
let joyActive = false, joyStartX = 0, joyStartY = 0;
joyZone.addEventListener('touchstart', e => { joyActive = true; joyStartX = e.touches[0].clientX; joyStartY = e.touches[0].clientY; }, { passive: true });
joyZone.addEventListener('touchmove', e => {
  if (!joyActive) return;
  const t = e.touches[0];
  let dx = t.clientX - joyStartX, dy = t.clientY - joyStartY;
  const max = 40, len = Math.hypot(dx, dy);
  if (len > max) { dx = dx/len*max; dy = dy/len*max; }
  joyStick.style.transform = `translate(${dx}px, ${dy}px)`;
  joyVec.x = dx/max; joyVec.y = dy/max;
}, { passive: true });
joyZone.addEventListener('touchend', () => { joyActive = false; joyVec = { x:0, y:0 }; joyStick.style.transform = 'translate(0,0)'; });

/* ============================================================
   MOVEMENT / VEHICLES / CAMERA
   ============================================================ */
const speed = 0.14;
let drivingCarId = null, carSpeed = 0;
let sitPos = null, sitting = false;

function findNearbyCar() {
  let nearest = null, dist = Infinity;
  for (const id in cars) {
    const c = cars[id];
    const d = Math.hypot(c.group.position.x - myAvatar.position.x, c.group.position.z - myAvatar.position.z);
    if (d < 2.6 && d < dist) { nearest = c; dist = d; }
  }
  return nearest;
}

const vehiclePrompt = document.getElementById('vehicle-prompt');
vehiclePrompt.addEventListener('click', triggerVehicleAction);
addEventListener('keydown', e => {
  if (e.key.toLowerCase() !== 'e') return;
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  triggerVehicleAction();
});

function triggerVehicleAction() {
  if (document.getElementById('name-gate').style.display !== 'none') return;
  if (drivingCarId) { exitVehicle(); return; }
  const c = findNearbyCar();
  if (c && !c.occupiedBy) enterVehicle(c.id);
}

function enterVehicle(carId) {
  const c = cars[carId]; if (!c) return;
  drivingCarId = carId; carSpeed = 0;
  myAvatar.visible = false;
  vehiclePrompt.textContent = 'Press E to exit';
  vehiclePrompt.style.display = 'block';
}

function exitVehicle() {
  if (!drivingCarId) return;
  const c = cars[drivingCarId];
  const fwd = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
  myAvatar.position.set(c.group.position.x - fwd.z * 2.2, 0, c.group.position.z + fwd.x * 2.2);
  myAvatar.rotation.y = c.group.rotation.y;
  myAvatar.visible = true;
  drivingCarId = null; carSpeed = 0;
  vehiclePrompt.textContent = 'Press E to enter';
}

function updateMovement(dt) {
  if (drivingCarId) {
    const c = cars[drivingCarId]; if (!c) { drivingCarId = null; return; }
    let throttle = 0, steer = 0;
    if (keys['w']) throttle += 1;
    if (keys['s']) throttle -= 1;
    if (keys['a']) steer += 1;
    if (keys['d']) steer -= 1;
    throttle += -joyVec.y; steer += -joyVec.x;

    const accel = 0.012, maxSpeed = 0.36, friction = 0.985, turnRate = 0.05;
    carSpeed += throttle * accel;
    carSpeed *= friction;
    carSpeed = Math.max(-maxSpeed*0.6, Math.min(maxSpeed, carSpeed));
    if (Math.abs(carSpeed) > 0.005) c.group.rotation.y += steer * turnRate * (carSpeed > 0 ? 1 : -1);

    const fwd = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
    c.group.position.x += fwd.x * carSpeed;
    c.group.position.z += fwd.z * carSpeed;

    const camDist = 7.5;
    camera.position.x = c.group.position.x - fwd.x * camDist * Math.cos(camPitch);
    camera.position.z = c.group.position.z - fwd.z * camDist * Math.cos(camPitch);
    camera.position.y = 2.2 + camDist * Math.sin(camPitch);
    camera.lookAt(c.group.position.x, 1.1, c.group.position.z);
    return;
  }

  let dx = 0, dz = 0;
  if (keys['w']) dz -= 1;
  if (keys['s']) dz += 1;
  if (keys['a']) dx -= 1;
  if (keys['d']) dx += 1;
  dx += joyVec.x; dz += joyVec.y;

  if (dx || dz) {
    const len = Math.hypot(dx, dz) || 1; dx /= len; dz /= len;
    const mx = -dx * Math.cos(camYaw) - dz * Math.sin(camYaw);
    const mz =  dx * Math.sin(camYaw) - dz * Math.cos(camYaw);
    myAvatar.position.x += mx * speed;
    myAvatar.position.z += mz * speed;
    myAvatar.rotation.y = Math.atan2(mx, mz);
  }

  const camDist = 5.5;
  camera.position.x = myAvatar.position.x - Math.sin(camYaw) * camDist * Math.cos(camPitch);
  camera.position.z = myAvatar.position.z - Math.cos(camYaw) * camDist * Math.cos(camPitch);
  camera.position.y = myAvatar.position.y + 1.7 + camDist * Math.sin(camPitch);
  camera.lookAt(myAvatar.position.x, myAvatar.position.y + 1.3, myAvatar.position.z);

  const near = findNearbyCar();
  if (near && !near.occupiedBy) {
    vehiclePrompt.textContent = 'Press E to enter';
    vehiclePrompt.style.display = 'block';
  } else vehiclePrompt.style.display = 'none';
}

/* ---------- Fake socket (single-player mode) ---------- */
const socket = {
  id: 'local',
  connected: true,
  handlers: {},
  on(ev, fn) { (this.handlers[ev] = this.handlers[ev] || []).push(fn); },
  emit(ev, ...args) { /* no-op in solo demo */ }
};
function inGame() { return document.getElementById('name-gate').style.display === 'none'; }

/* ---------- Enter city ---------- */
document.getElementById('join-btn').onclick = enterCity;
document.getElementById('name-input').addEventListener('keydown', e => { if (e.key === 'Enter') enterCity(); });

function enterCity() {
  scene.remove(myAvatar);
  myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle, myRole);
  scene.add(myAvatar);
  document.getElementById('name-gate').style.display = 'none';
  setTimeout(() => showToast('🔇 Sound is OFF — click the sound icon to enable. WASD to move, E to enter a car.'), 500);
}

/* ---------- Health / Ambulance / Hospital ---------- */
const HOSPITAL_FEE = 25, HOSPITAL_STAY_MS = 25000, REGEN_SECONDS = 600;
let myHealth = 100, inHospital = false, hospitalEndAt = 0, hospitalTimer = null;
let ambulance = null, invulnUntil = 0, lastDamageAt = -1e9, regenAcc = 0, _lastHealthAt = performance.now();

const healthPill = document.getElementById('health-pill');
const healthFill = document.getElementById('health-fill');
const healthVal  = document.getElementById('health-val');
const ambBtn     = document.getElementById('ambulance-btn');

function paintHealth() {
  const pct = Math.max(0, Math.min(100, myHealth));
  healthFill.style.width = pct + '%';
  healthVal.textContent = Math.round(pct);
  healthFill.style.background =
    pct > 60 ? 'linear-gradient(90deg,#00f593,#7ee787)' :
    pct > 30 ? 'linear-gradient(90deg,#ffcc00,#ffa500)' :
               'linear-gradient(90deg,#ff4d6d,#d90034)';
  healthPill.classList.toggle('critical', pct < 30 && pct > 0);
  ambBtn.style.display = (pct < 30 && pct > 0 && !inHospital && !ambulance) ? 'block' : 'none';
}
function setHealth(v) {
  const before = myHealth;
  myHealth = Math.max(0, Math.min(100, v));
  paintHealth();
  if (myHealth <= 0 && before > 0 && !inHospital && !ambulance) callAmbulance(true);
}
function applyDamage(amount) {
  if (inHospital) return;
  const now = performance.now();
  if (now < invulnUntil) return;
  lastDamageAt = now;
  setHealth(myHealth - amount);
  document.getElementById('hurt-flash').classList.add('on');
  setTimeout(() => document.getElementById('hurt-flash').classList.remove('on'), 160);
  if (amount >= 8) showToast('💔 -' + Math.round(amount) + ' HP');
}
ambBtn.onclick = () => callAmbulance(false);

function makeAmbulance() {
  const g = new THREE.Group();
  const white = mat(0xf4f4f4, 0.5, 0);
  const red   = mat(0xd62828, 0.5, 0, { emissive: 0x400000, emissiveIntensity: 0.4 });
  const dark  = mat(0x1a1a1a, 0.7, 0.1);
  const glass = mat(0x1d2b38, 0.08, 0.35, { transparent: true, opacity: 0.75 });
  const add = (geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); g.add(o); return o; };
  add(new THREE.BoxGeometry(2.6, 0.8, 1.2), white, 0, 0.6, 0);
  add(new THREE.BoxGeometry(2.4, 1.0, 1.15), white, 0, 1.4, 0);
  add(new THREE.BoxGeometry(2.42, 0.22, 1.18), red, 0, 1.2, 0);
  // Red cross
  add(new THREE.BoxGeometry(0.55, 0.14, 0.14), red, -0.65, 1.7, 0.6);
  add(new THREE.BoxGeometry(0.14, 0.55, 0.14), red, -0.65, 1.7, 0.6);
  add(new THREE.BoxGeometry(0.55, 0.14, 0.14), red, -0.65, 1.7, -0.6);
  add(new THREE.BoxGeometry(0.14, 0.55, 0.14), red, -0.65, 1.7, -0.6);
  add(new THREE.BoxGeometry(2.3, 0.6, 1.0), glass, 0.25, 1.45, 0);
  const sR = add(new THREE.BoxGeometry(0.24, 0.18, 0.24), mat(0xff0000, 0.3, 0, { emissive: 0xff0000, emissiveIntensity: 1 }), 0.45, 2, 0);
  const sB = add(new THREE.BoxGeometry(0.24, 0.18, 0.24), mat(0x0066ff, 0.3, 0, { emissive: 0x0066ff, emissiveIntensity: 0.2 }), -0.45, 2, 0);
  const wg = new THREE.CylinderGeometry(0.32, 0.32, 0.22, 14);
  [[-0.85,-0.66],[-0.85,0.66],[0.85,-0.66],[0.85,0.66]].forEach(([x,z]) => {
    const w = add(wg, dark, x, 0.32, z); w.rotation.z = Math.PI/2;
  });
  g.traverse(o => { if (o.isMesh) o.castShadow = true; });
  g.userData = { sirenR: sR, sirenB: sB };
  return g;
}
let _sirenFlip = false, actx = null, master = null, soundOn = false;
function ensureAudio() {
  if (!actx) {
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return false;
    actx = new AC();
    master = actx.createGain(); master.gain.value = 0.9; master.connect(actx.destination);
  }
  if (actx.state === 'suspended') actx.resume();
  return true;
}
function sirenBeep(hi) {
  if (!soundOn || !actx || actx.state !== 'running') return;
  const t = actx.currentTime, o = actx.createOscillator(), gn = actx.createGain();
  o.type = 'sine';
  o.frequency.setValueAtTime(hi ? 1100 : 750, t);
  o.frequency.linearRampToValueAtTime(hi ? 750 : 1100, t + 0.4);
  gn.gain.setValueAtTime(0.0001, t);
  gn.gain.linearRampToValueAtTime(0.14, t + 0.05);
  gn.gain.linearRampToValueAtTime(0.0001, t + 0.5);
  o.connect(gn); gn.connect(master); o.start(t); o.stop(t + 0.55);
}
function callAmbulance(free) {
  if (ambulance || inHospital) return;
  if (!free) {
    const bal = parseInt(document.getElementById('balloon-val').textContent, 10) || 0;
    if (bal < HOSPITAL_FEE) { showToast(`🚑 Need ${HOSPITAL_FEE} 🎈 to call an ambulance`); return; }
    document.getElementById('balloon-val').textContent = bal - HOSPITAL_FEE;
  }
  showToast('🚑 Ambulance on the way!');
  const g = makeAmbulance();
  const a = Math.random() * Math.PI * 2;
  g.position.set(myAvatar.position.x + Math.cos(a) * 55, 0, myAvatar.position.z + Math.sin(a) * 55);
  scene.add(g);
  ambulance = { group: g, sirenAt: 0 };
}
function updateAmbulance(dt) {
  if (!ambulance) return;
  const g = ambulance.group, p = myAvatar.position;
  const dx = p.x - g.position.x, dz = p.z - g.position.z, d = Math.hypot(dx, dz);
  g.userData.sirenR.material.emissiveIntensity = 0.4 + 0.9 * Math.abs(Math.sin(performance.now()/180));
  g.userData.sirenB.material.emissiveIntensity = 0.4 + 0.9 * Math.abs(Math.cos(performance.now()/180));
  const now = performance.now();
  if (now > ambulance.sirenAt && soundOn) { ambulance.sirenAt = now + 550; _sirenFlip = !_sirenFlip; sirenBeep(_sirenFlip); }
  if (d > 3) {
    const sp = Math.min(9 * dt, d);
    g.position.x += dx/d * sp; g.position.z += dz/d * sp;
    g.rotation.y = Math.atan2(dx, dz);
  } else {
    scene.remove(g); ambulance = null;
    if (soundOn) { sirenBeep(true); setTimeout(() => sirenBeep(false), 300); }
    enterHospital();
  }
}
function enterHospital() {
  inHospital = true;
  if (drivingCarId) exitVehicle();
  myAvatar.visible = false;
  document.getElementById('death-overlay').classList.remove('open');
  document.getElementById('hospital-overlay').classList.add('open');
  hospitalEndAt = performance.now() + HOSPITAL_STAY_MS;
  const counter = document.getElementById('hosp-count');
  const btn = document.getElementById('hosp-discharge-early');
  clearInterval(hospitalTimer);
  hospitalTimer = setInterval(() => {
    const left = Math.max(0, Math.ceil((hospitalEndAt - performance.now()) / 1000));
    counter.textContent = left;
    btn.textContent = left > 0 ? `Discharge in ${left}s…` : 'Discharged!';
    if (left <= 0) { clearInterval(hospitalTimer); leaveHospital(); }
  }, 250);
}
function leaveHospital() {
  inHospital = false;
  document.getElementById('hospital-overlay').classList.remove('open');
  myHealth = 40; paintHealth();
  myAvatar.visible = true;
  myAvatar.position.set(30, 0, -25);
  invulnUntil = performance.now() + 8000;
  showToast('🏥 Discharged at 40% — buy 💊 medicine or wait to recover');
}

function healthTick() {
  const now = performance.now();
  const dt = Math.min(0.1, (now - _lastHealthAt) / 1000); _lastHealthAt = now;
  if (inHospital || myHealth >= 100) return;
  if (now - lastDamageAt < 6000) return;
  regenAcc += dt * (100 / REGEN_SECONDS);
  if (regenAcc >= 0.5) { const a = Math.floor(regenAcc); regenAcc -= a; setHealth(myHealth + a); }
}

/* ---------- Balloon pickups ---------- */
const balloonMeshes = {};
function makeBalloonSprite() {
  const cv = document.createElement('canvas'); cv.width = 128; cv.height = 128;
  const c = cv.getContext('2d');
  c.font = '92px serif'; c.textAlign = 'center'; c.textBaseline = 'middle';
  c.shadowColor = '#FE019A'; c.shadowBlur = 18;
  c.fillText('🎈', 64, 62);
  const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(cv), transparent: true }));
  sp.scale.set(1, 1, 1);
  return sp;
}
balloonSpots.forEach(s => {
  const spr = makeBalloonSprite();
  spr.position.set(s.x, 1.2, s.z);
  spr.userData.bob = Math.random() * Math.PI * 2;
  scene.add(spr);
  balloonMeshes[s.id] = spr;
});

/* ---------- Toast / misc UI ---------- */
function showToast(msg) {
  const stack = document.getElementById('toast-stack');
  const t = document.createElement('div');
  t.className = 'toast'; t.textContent = msg;
  stack.appendChild(t);
  setTimeout(() => t.remove(), 3600);
}

const shopPrompt = document.getElementById('shop-prompt');
shopPrompt.style.display = 'none';
document.getElementById('shop-btn').onclick = () => {
  document.getElementById('shop-overlay').style.display = 'flex';
  renderShop();
};
document.getElementById('shop-close').onclick = () => document.getElementById('shop-overlay').style.display = 'none';
document.getElementById('inventory-btn').onclick = () => {
  document.getElementById('inventory-overlay').style.display = 'flex';
  renderInventory();
};
document.getElementById('inventory-close').onclick = () => document.getElementById('inventory-overlay').style.display = 'none';
document.getElementById('map-btn').onclick = () => {
  document.getElementById('map-overlay').style.display = 'flex';
  drawMap();
};
document.getElementById('map-close').onclick = () => document.getElementById('map-overlay').style.display = 'none';
document.getElementById('stats-btn').onclick = () => {
  document.getElementById('stat-name').textContent = 'Player';
  document.getElementById('stat-look').textContent = `${myGender}, ${myHairStyle} hair`;
  document.getElementById('stat-health').textContent = Math.round(myHealth) + '%';
  document.getElementById('stat-balloons').textContent = document.getElementById('balloon-val').textContent;
  document.getElementById('stats-overlay').style.display = 'flex';
};
document.getElementById('stats-close').onclick = () => document.getElementById('stats-overlay').style.display = 'none';

const MED_ITEMS = [
  { id: 'bandage',      name: 'Bandage',       emoji: '🩹', price:  5, heal: 25 },
  { id: 'painkiller',   name: 'Painkiller',    emoji: '💊', price: 10, heal: 45 },
  { id: 'energy_drink', name: 'Energy Drink',  emoji: '🥤', price: 15, heal: 70 },
  { id: 'med_kit',      name: 'Medical Kit',   emoji: '🧰', price: 30, heal: 100 }
];

function renderShop() {
  const grid = document.getElementById('shop-grid');
  const balance = parseInt(document.getElementById('balloon-val').textContent, 10) || 0;
  document.getElementById('shop-balance').textContent = `🎈 ${balance} balloons to spend`;
  grid.innerHTML = '';
  const label = document.createElement('div');
  label.className = 'shop-cat-label'; label.textContent = '💊 Medicine & Drinks';
  grid.appendChild(label);
  const row = document.createElement('div'); row.className = 'shop-grid';
  MED_ITEMS.forEach(it => {
    const afford = balance >= it.price;
    const div = document.createElement('div'); div.className = 'shop-item';
    div.innerHTML = `<div class="emoji">${it.emoji}</div><div class="name">${it.name}</div><div class="price">🎈 ${it.price}</div><button ${afford ? '' : 'disabled'}>${afford ? 'Buy' : 'Need more'}</button>`;
    div.querySelector('button').onclick = () => {
      const bal = parseInt(document.getElementById('balloon-val').textContent, 10) || 0;
      if (bal < it.price) { showToast('Not enough balloons 🎈'); return; }
      document.getElementById('balloon-val').textContent = bal - it.price;
      setHealth(myHealth + it.heal);
      showToast(`${it.emoji} +${it.heal} HP`);
      renderShop();
    };
    row.appendChild(div);
  });
  grid.appendChild(row);
}

function renderInventory() {
  const list = document.getElementById('inventory-list');
  list.innerHTML = '<div class="inv-empty">No items yet — visit the Shop!</div>';
}

/* ---------- Map (simple dots) ---------- */
const mapCanvas = document.getElementById('map-canvas');
const mapCtx = mapCanvas.getContext('2d');
function drawMap() {
  const W = mapCanvas.width, H = mapCanvas.height;
  mapCtx.fillStyle = '#241a34'; mapCtx.fillRect(0, 0, W, H);
  const worldHalf = HALF + 20;
  const w2m = (x, z) => ({ px: ((x + worldHalf) / (worldHalf*2)) * W, py: ((z + worldHalf) / (worldHalf*2)) * H });
  // roads
  mapCtx.strokeStyle = 'rgba(255,255,255,.08)';
  for (let gx = -GRID/2; gx <= GRID/2; gx++) {
    const { px } = w2m(gx*BLOCK, 0);
    mapCtx.beginPath(); mapCtx.moveTo(px, 0); mapCtx.lineTo(px, H); mapCtx.stroke();
    const { py } = w2m(0, gx*BLOCK);
    mapCtx.beginPath(); mapCtx.moveTo(0, py); mapCtx.lineTo(W, py); mapCtx.stroke();
  }
  // peds
  mapCtx.fillStyle = '#8fc4e8';
  PEDS.forEach(p => {
    const { px, py } = w2m(p.g.position.x, p.g.position.z);
    mapCtx.beginPath(); mapCtx.arc(px, py, 3, 0, Math.PI*2); mapCtx.fill();
  });
  // player
  const me = w2m(myAvatar.position.x, myAvatar.position.z);
  mapCtx.fillStyle = '#FE019A';
  mapCtx.beginPath(); mapCtx.arc(me.px, me.py, 6, 0, Math.PI*2); mapCtx.fill();
  mapCtx.strokeStyle = '#fff'; mapCtx.lineWidth = 2; mapCtx.stroke();
}

/* ---------- Sound button ---------- */
document.getElementById('sound-btn').onclick = () => {
  soundOn = !soundOn;
  ensureAudio();
  document.getElementById('sound-btn').classList.toggle('on', soundOn);
  document.getElementById('sound-btn').innerHTML = soundOn ? '<i class="fa-solid fa-volume-high"></i>' : '<i class="fa-solid fa-volume-xmark"></i>';
};

/* ---------- Render loop ---------- */
applyShadows = function() {
  scene.traverse(o => {
    if (!o.isMesh) return;
    o.receiveShadow = true;
    if (!o.isInstancedMesh && !(o.material && o.material.transparent) && !['PlaneGeometry','ShapeGeometry','CircleGeometry'].includes(o.geometry.type)) {
      o.castShadow = true;
    }
  });
};
applyShadows();

const clock = new THREE.Clock();
let _lastT = 0;
function animate() {
  requestAnimationFrame(animate);
  const now = performance.now(), dt = Math.min(0.1, (now - _lastT) / 1000); _lastT = now;

  updateMovement(dt);
  updatePeds(dt);
  tickAvatar(myAvatar, now);
  PEDS.forEach(p => tickAvatar(p.g, now));

  // balloon bobbing + pickup
  const t = now / 1000;
  Object.values(balloonMeshes).forEach(m => {
    if (!m.visible) return;
    m.position.y = 1.2 + Math.sin(t*2 + m.userData.bob) * 0.25;
    if (m.position.distanceTo(myAvatar.position) < 1.2) {
      m.visible = false;
      const el = document.getElementById('balloon-val');
      el.textContent = (parseInt(el.textContent, 10) || 0) + 1;
      showToast('+1 🎈');
    }
  });

  healthTick();
  updateAmbulance(dt);
  if (myHealth <= 0 && !inHospital && !ambulance) document.getElementById('death-overlay').classList.add('open');

  // Sun follows player for shadows
  sun.position.set(myAvatar.position.x + 60, 90, myAvatar.position.z + 40);
  sun.target.position.set(myAvatar.position.x, 0, myAvatar.position.z);

  renderer.render(scene, camera);
}
animate();

addEventListener('resize', () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
});

/* ---------- Gate UI wiring ---------- */
document.querySelectorAll('.gender-pill[data-gender]').forEach(b => b.onclick = () => {
  document.querySelectorAll('.gender-pill[data-gender]').forEach(x => x.classList.remove('selected'));
  b.classList.add('selected'); myGender = b.dataset.gender;
});
document.querySelector('.gender-pill[data-gender="other"]').classList.add('selected');
document.querySelectorAll('.hair-pill').forEach(b => b.onclick = () => {
  document.querySelectorAll('.hair-pill').forEach(x => x.classList.remove('selected'));
  b.classList.add('selected'); myHairStyle = b.dataset.hair;
});
document.querySelector('.hair-pill[data-hair="short"]').classList.add('selected');
document.querySelectorAll('.swatch').forEach(b => b.onclick = () => {
  document.querySelectorAll('.swatch').forEach(x => x.classList.remove('selected'));
  b.classList.add('selected'); myOutfitColor = b.dataset.color;
});
document.querySelector('.swatch[data-color="#9b5de5"]').classList.add('selected');

paintHealth();
</script>
</body>
</html>
