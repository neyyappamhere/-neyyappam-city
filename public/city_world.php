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

  #mic-btn, #sound-btn{
    position:absolute; top:64px; z-index:5; width:44px; height:44px; padding:0;
    display:flex; align-items:center; justify-content:center; font-size:1.05em;
    background:var(--card); color:var(--muted); border:1px solid var(--border); border-radius:50%; cursor:pointer;
  }
  #mic-btn{ left:14px; } #sound-btn{ left:66px; }
  #mic-btn.on{ background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; border-color:transparent; }
  #sound-btn.on{ background:linear-gradient(135deg,#00b37a,#00f593); color:#05210f; border-color:transparent; }
  .rbtn{ background:#5b3416; color:#ffd166; border:1px solid #c98a3a; border-radius:20px; padding:10px 16px; font-weight:700; font-size:.82em; cursor:pointer; }

  #map-btn, #stats-btn, #shop-btn, #inventory-btn{
    position:absolute; top:64px; z-index:5;
    background:var(--card); color:var(--text); border:1px solid var(--border);
    border-radius:24px; padding:9px 16px; font-weight:700; font-size:.82em; cursor:pointer;
  }
  #map-btn{ left:118px; }
  #stats-btn{ left:204px; }
  #shop-btn{ left:290px; }
  #inventory-btn{ left:376px; }

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
  .shop-cat-label{ font-size:.72em; font-weight:700; color:var(--muted); margin:14px 0 8px; text-transform:uppercase; letter-spacing:.04em; }
  .shop-cat-label:first-of-type{ margin-top:0; }
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
    #mic-btn{ top:64px; left:auto; right:14px; }
    #sound-btn{ top:64px; left:auto; right:66px; }
    #map-btn{ top:110px; left:auto; right:14px; }
    #stats-btn{ top:156px; left:auto; right:14px; }
    #shop-btn{ top:202px; left:auto; right:14px; }
    #inventory-btn{ top:248px; left:auto; right:14px; }
    #job-banner{ top:64px; max-width:70vw; font-size:.72em; }
    #waypoint-readout{ top:100px; }
  }
</style>
</head>
<body>

<div id="name-gate">
  <div class="card">
    <h3>Neyyappam City</h3>
    <p class="sub">Kerala edition — sip chaya at the chayakkada, ride a kaalavandi, collect balloons</p>
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

<button id="mic-btn" title="Microphone (off)"><i class="fa-solid fa-microphone-slash"></i></button>
<button id="sound-btn" title="Sound (off)"><i class="fa-solid fa-volume-xmark"></i></button>
<button id="map-btn"><i class="fa-solid fa-map"></i> Map</button>
<button id="stats-btn"><i class="fa-solid fa-user"></i> Stats</button>
<button id="shop-btn"><i class="fa-solid fa-store"></i> Shop</button>
<button id="inventory-btn"><i class="fa-solid fa-bag-shopping"></i> Bag</button>

<div id="toast-stack"></div>

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

<div id="shop-overlay" style="display:none;">
  <div id="shop-panel">
    <div id="shop-header">
      <span>🛒 Shop</span>
      <button id="shop-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="shop-balance" id="shop-balance">🎈 0 balloons to spend</div>
    <div id="shop-grid"></div>
  </div>
</div>

<div id="inventory-overlay" style="display:none;">
  <div id="inventory-panel">
    <div id="inventory-header">
      <span>🎒 Inventory</span>
      <button id="inventory-close"><i class="fa-solid fa-xmark"></i></button>
    </div>
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
<div id="state-chip" style="display:none; position:absolute; top:108px; left:50%; transform:translateX(-50%); z-index:6; background:rgba(20,15,10,.8); color:#fff; padding:6px 14px; border-radius:16px; font-weight:700; font-size:.8em; pointer-events:none;"></div>
<button id="shop-prompt" style="display:none; position:absolute; left:50%; bottom:150px; transform:translateX(-50%); z-index:6; background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer;">🛒 Press B or tap to shop</button>
<div id="fly-btns" style="display:none; position:absolute; right:16px; bottom:150px; z-index:6; flex-direction:column; gap:10px;">
  <button id="fly-up" style="width:54px;height:54px;border-radius:50%;border:none;background:rgba(20,15,10,.85);color:#fff;font-size:1.2em;">▲</button>
  <button id="fly-down" style="width:54px;height:54px;border-radius:50%;border:none;background:rgba(20,15,10,.85);color:#fff;font-size:1.2em;">▼</button>
</div>
<button id="vehicle-prompt" style="display:none; position:absolute; left:50%; bottom:100px; transform:translateX(-50%); z-index:6; background:rgba(20,15,10,.85); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer;">Press E to enter</button>

<div id="radio-ui" style="display:none; position:absolute; left:50%; bottom:200px; transform:translateX(-50%); z-index:6; gap:6px;">
  <button id="radio-prev" class="rbtn">⏮ Prev (P)</button>
  <button id="radio-toggle" class="rbtn">📻 Radio</button>
  <button id="radio-next" class="rbtn">Next (N) ⏭</button>
</div>
<button id="cart-call" class="rbtn" style="display:none; position:absolute; right:16px; bottom:150px; z-index:6;">🐂 Moo (H)</button>
<div id="radio-chip" style="display:none; position:absolute; top:140px; left:50%; transform:translateX(-50%); z-index:6; background:rgba(60,35,12,.88); color:#ffd166; padding:6px 14px; border-radius:16px; font-weight:700; font-size:.78em; pointer-events:none;"></div>

<div id="joystick-zone">
  <div id="joystick-base"></div>
  <div id="joystick-stick"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="/socket.io/socket.io.js"></script>
<script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
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
scene.fog = new THREE.Fog(0xdcebd4, 45, 170);

const camera = new THREE.PerspectiveCamera(60, innerWidth/innerHeight, 0.1, 1000);
camera.position.set(0, 3, 6);

// --- WebGL availability check: a black screen with the HUD still floating on
// top (exactly what shows up if WebGL/Three.js fails) means the renderer
// never got created and the rest of this script threw silently. Surface it
// instead of leaving a blank canvas. ---
function webglAvailable() {
  try {
    const c = document.createElement('canvas');
    return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
  } catch (e) { return false; }
}

let renderer;
if (!window.THREE) {
  showFatalError('3D library failed to load (three.js). Check your internet connection or ad-blocker and reload.');
} else if (!webglAvailable()) {
  showFatalError('Your browser or device can\'t run 3D graphics (WebGL). Try a different browser, enable hardware acceleration, or switch device.');
} else {
  try {
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(innerWidth, innerHeight);
    renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
    document.getElementById('canvas-wrap').appendChild(renderer.domElement);
  } catch (e) {
    console.error('Renderer init failed:', e);
    showFatalError('Could not start the 3D renderer: ' + e.message);
  }
}

function showFatalError(msg) {
  document.getElementById('canvas-wrap').innerHTML =
    '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;' +
    'padding:24px;text-align:center;color:#fff;font-family:Space Grotesk,sans-serif;font-size:.95em;">' +
    '⚠️ ' + msg + '</div>';
  throw new Error(msg);
}

// Surface any other uncaught error the same way, instead of a silent black
// screen with nothing but the HUD showing.
window.addEventListener('error', (e) => {
  console.error('Neyyappam City error:', e.error || e.message);
});

// --- Daytime gradient sky: blue overhead fading to a warm haze at the horizon ---
function makeSky() {
  const canvas = document.createElement('canvas');
  canvas.width = 2; canvas.height = 256;
  const ctx = canvas.getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 256);
  grad.addColorStop(0, '#1f7fd1');
  grad.addColorStop(0.55, '#9bd8ea');
  grad.addColorStop(0.8, '#f4e6b8');
  grad.addColorStop(1, '#dcd9a8');
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
  new THREE.MeshStandardMaterial({ color: 0x79ad4a })   // tropical green ground
);
ground.rotation.x = -Math.PI/2;
scene.add(ground);
{ // asphalt roads laid over the green (a road on every multiple of BLOCK, ROAD_W wide)
  const roadMat = new THREE.MeshStandardMaterial({ color: 0x55565c });
  for (let i = -GRID/2; i <= GRID/2; i++) {
    const rv = new THREE.Mesh(new THREE.BoxGeometry(ROAD_W, 0.02, GRID*BLOCK + ROAD_W), roadMat); rv.position.set(i*BLOCK, 0.01, 0); scene.add(rv);
    const rh = new THREE.Mesh(new THREE.BoxGeometry(GRID*BLOCK + ROAD_W, 0.02, ROAD_W), roadMat); rh.position.set(0, 0.01, i*BLOCK); scene.add(rh);
  }
}

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

/* =========================================================
   KERALA helpers — Malayalam signboards, tile-roofed houses, tree spots
   ========================================================= */
const kMat = (c, o) => new THREE.MeshStandardMaterial(Object.assign({ color: c }, o || {}));

// Canvas signboard in Malayalam script; redraws once the web font has loaded.
function makeSignTex(text, bg, fg) {
  const cv = document.createElement('canvas'); cv.width = 512; cv.height = 128;
  const tex = new THREE.CanvasTexture(cv);
  const draw = () => {
    const g = cv.getContext('2d');
    g.fillStyle = bg; g.fillRect(0, 0, 512, 128);
    g.strokeStyle = fg; g.lineWidth = 6; g.strokeRect(8, 8, 496, 112);
    g.fillStyle = fg; g.font = "700 56px 'Noto Sans Malayalam','Nirmala UI',sans-serif";
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.fillText(text, 256, 68, 470);
    tex.needsUpdate = true;
  };
  draw();
  if (document.fonts && document.fonts.load) document.fonts.load("700 56px 'Noto Sans Malayalam'", text).then(draw).catch(() => {});
  return tex;
}
const SIGNS = [
  ['പലചരക്ക് കട', '#7a2e1d', '#fff2cc'], ['ബേക്കറി', '#f4c95d', '#5a1f0f'], ['തുണിക്കട', '#2b4c7e', '#ffe9b0'],
  ['ഹോട്ടൽ', '#b33a3a', '#ffffff'], ['മെഡിക്കൽ സ്റ്റോർ', '#0f7b6c', '#ffffff'], ['മീൻ കട', '#1d6fa5', '#ffffff'],
].map(([t, b, f]) => makeSignTex(t, b, f));

const houseColors = [0xf4ecd8, 0xf2d97a, 0x7fc8c2, 0xf0a6a0, 0xbcd8a0, 0xa9c8e8];
const roofMat = kMat(0xb5482a), roofMat2 = kMat(0x9c3b22);
const roofGeo = new THREE.ConeGeometry(1, 1, 4);
const doorMat = kMat(0x5a3a1a), winFrameMat = kMat(0x6b4423), winGlassMat = kMat(0x7fa9c9);

const palmSpots = [], broadSpots = [], bananaSpots = [];
function addPalm(x, z)   { palmSpots.push({ x, z, h: 5.2 + Math.random()*3 }); }
function addBroad(x, z)  { broadSpots.push({ x, z, h: 2.2 + Math.random() }); }
function addBanana(x, z) { bananaSpots.push({ x, z }); }

// Low-rise Kerala shop-house: pastel walls, terracotta pyramid roof, Malayalam signboard.
function addKeralaHouse(cx, cz, footprint) {
  const w = footprint * (0.55 + Math.random()*0.2), h = 3.6 + Math.random()*2.2;
  const body = new THREE.Mesh(new THREE.BoxGeometry(w, h, w), kMat(houseColors[Math.floor(Math.random()*houseColors.length)]));
  body.position.set(cx, 0.25 + h/2, cz); scene.add(body);
  const roof = new THREE.Mesh(roofGeo, Math.random() > 0.5 ? roofMat : roofMat2);
  const r = (w + 1.2) * 0.7071;
  roof.scale.set(r, 2.6, r); roof.rotation.y = Math.PI/4; roof.position.set(cx, 0.25 + h + 1.3, cz); scene.add(roof);
  const fz = cz + w/2 + 0.03;
  const sign = new THREE.Mesh(new THREE.PlaneGeometry(Math.min(4.4, w*0.8), 1.05),
    new THREE.MeshBasicMaterial({ map: SIGNS[Math.floor(Math.random()*SIGNS.length)] }));
  sign.position.set(cx, 3.15, fz + 0.02); scene.add(sign);
  const door = new THREE.Mesh(new THREE.PlaneGeometry(1.3, 2.2), doorMat); door.position.set(cx, 1.35, fz); scene.add(door);
  [-1, 1].forEach(s => {
    const fr = new THREE.Mesh(new THREE.BoxGeometry(1.15, 1.25, 0.1), winFrameMat); fr.position.set(cx + s*w*0.32, 1.9, fz); scene.add(fr);
    const gl = new THREE.Mesh(new THREE.PlaneGeometry(0.85, 0.95), winGlassMat); gl.position.set(cx + s*w*0.32, 1.9, fz + 0.06); scene.add(gl);
  });
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
      new THREE.MeshStandardMaterial({ color: 0x86b856 })
    );
    sidewalk.position.set(cx, 0.12, cz);
    scene.add(sidewalk);
    const curb = new THREE.Mesh(
      new THREE.BoxGeometry(footprint + 0.3, 0.32, footprint + 0.3),
      new THREE.MeshStandardMaterial({ color: 0xa5603a })
    );
    curb.position.set(cx, 0.06, cz);
    scene.add(curb);

    // Block (-1,0) is the chayakkada, built separately below; every other block gets a Kerala house.
    if (!(gx === -1 && (gz === 0 || gz === -1))) addKeralaHouse(cx, cz, footprint);
    // coconut palms on two random corners, plus a mango-type tree or banana clump on a third
    {
      const e = footprint/2 - 1.3, cs = [[-1,-1],[1,-1],[-1,1],[1,1]].sort(() => Math.random() - 0.5);
      addPalm(cx + cs[0][0]*e, cz + cs[0][1]*e);
      addPalm(cx + cs[1][0]*e, cz + cs[1][1]*e);
      if (Math.random() > 0.4) addBroad(cx + cs[2][0]*e, cz + cs[2][1]*e);
      else addBanana(cx + cs[2][0]*e, cz + cs[2][1]*e);
    }

    // Streetlight at one corner, alternating which side it faces the road
    cornerToggle = !cornerToggle;
    addStreetlight(cx - footprint/2 - 1, cz - footprint/2 - 1, cornerToggle);
    // 1-2 parked cars along the curb, engine-first toward the road
    addParkedCar(cx + (Math.random()-0.5)*footprint*0.6, cz + footprint/2 + 1.7, Math.random() > 0.5 ? 0 : Math.PI);

    const id = 'balloon_' + (idCounter++);
    balloonSpots.push({ id, x: cx + (Math.random()-0.5)*4, z: cz + (Math.random()-0.5)*4 });
  }
}


/* =========================================================
   1b) WORLD EXTRAS — river (swimmable), the Shop, airfield + plane
   ========================================================= */
function emojiSprite(ch, scale) {
  const cv = document.createElement('canvas'); cv.width = cv.height = 128;
  const g = cv.getContext('2d');
  g.font = '96px serif'; g.textAlign = 'center'; g.textBaseline = 'middle';
  g.fillText(ch, 64, 70);
  const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(cv), transparent: true }));
  sp.scale.set(scale, scale, 1);
  return sp;
}

// ---- River: runs north–south just east of the city ----
const RIVER = { x1: 66, x2: 80 };
function isInWater(x, z) { return x > RIVER.x1 && x < RIVER.x2 && Math.abs(z) < 88; }

const waterCanvas = document.createElement('canvas'); waterCanvas.width = waterCanvas.height = 128;
{
  const g = waterCanvas.getContext('2d');
  g.fillStyle = '#2f9bd8'; g.fillRect(0, 0, 128, 128);
  g.strokeStyle = 'rgba(255,255,255,0.38)'; g.lineWidth = 3;
  for (let i = 0; i < 4; i++) {
    g.beginPath();
    for (let x = 0; x <= 128; x += 4) {
      const y = 16 + i*32 + Math.sin(x/128*Math.PI*2 + i)*6;
      x === 0 ? g.moveTo(x, y) : g.lineTo(x, y);
    }
    g.stroke();
  }
}
const waterTex = new THREE.CanvasTexture(waterCanvas);
waterTex.wrapS = waterTex.wrapT = THREE.RepeatWrapping;
waterTex.repeat.set(3, 28);
{
  const water = new THREE.Mesh(
    new THREE.PlaneGeometry(RIVER.x2 - RIVER.x1, 176),
    new THREE.MeshStandardMaterial({ map: waterTex, roughness: 0.25 })
  );
  water.rotation.x = -Math.PI/2;
  water.position.set((RIVER.x1 + RIVER.x2)/2, 0.07, 0);
  scene.add(water);
  const sandMat = new THREE.MeshStandardMaterial({ color: 0xe6d3a3 });
  [RIVER.x1 - 2, RIVER.x2 + 2].forEach(x => {
    const bank = new THREE.Mesh(new THREE.BoxGeometry(4, 0.1, 176), sandMat);
    bank.position.set(x, 0.05, 0);
    scene.add(bank);
  });
  // wooden dock to jump in from
  const dock = new THREE.Mesh(new THREE.BoxGeometry(9, 0.2, 2.6), new THREE.MeshStandardMaterial({ color: 0x8b5a2b }));
  dock.position.set(66, 0.2, 10);
  scene.add(dock);
  const swimSign = emojiSprite('🏊', 2.2); swimSign.position.set(63, 2.2, 10); scene.add(swimSign);
}

// ---- The Shop: walk up to the counter (plaza block at the city centre) ----
const SHOP_POS = { x: 10, z: 15.5 }, SHOP_RANGE = 7;
function nearShop() {
  return !drivingCarId && Math.hypot(myAvatar.position.x - SHOP_POS.x, myAvatar.position.z - SHOP_POS.z) < SHOP_RANGE;
}
{
  const cx = 10, cz = 9, w = 12, d = 8, h = 5;
  const plaza = new THREE.Mesh(new THREE.BoxGeometry(14, 0.25, 14), new THREE.MeshStandardMaterial({ color: 0xd8d6cc }));
  plaza.position.set(10, 0.12, 10); scene.add(plaza);

  const body = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), new THREE.MeshStandardMaterial({ color: 0x1f8a8a }));
  body.position.set(cx, h/2 + 0.25, cz); scene.add(body);
  const roof = new THREE.Mesh(new THREE.BoxGeometry(w + 0.6, 0.3, d + 0.6), new THREE.MeshStandardMaterial({ color: 0x2b2b33 }));
  roof.position.set(cx, h + 0.4, cz); scene.add(roof);

  // striped awning
  for (let i = 0; i < 6; i++) {
    const stripe = new THREE.Mesh(new THREE.BoxGeometry(w/6, 0.12, 2.4),
      new THREE.MeshStandardMaterial({ color: i % 2 ? 0xffffff : 0xe63946 }));
    stripe.position.set(cx - w/2 + w/12 + i*(w/6), 3.6, cz + d/2 + 1.1);
    stripe.rotation.x = 0.22;
    scene.add(stripe);
  }
  // window + door
  const win = new THREE.Mesh(new THREE.PlaneGeometry(4, 1.8), new THREE.MeshStandardMaterial({ color: 0x9fd6f0 }));
  win.position.set(cx - 3, 2.3, cz + d/2 + 0.03); scene.add(win);
  const door = new THREE.Mesh(new THREE.PlaneGeometry(1.6, 2.4), new THREE.MeshStandardMaterial({ color: 0x5a3a1a }));
  door.position.set(cx + 3, 1.45, cz + d/2 + 0.03); scene.add(door);

  // sign
  const sc = document.createElement('canvas'); sc.width = 512; sc.height = 128;
  const sg = sc.getContext('2d');
  sg.fillStyle = '#1a1a22'; sg.fillRect(0, 0, 512, 128);
  sg.fillStyle = '#FFD700'; sg.font = 'bold 54px sans-serif'; sg.textAlign = 'center'; sg.textBaseline = 'middle';
  sg.fillText('🛒 SHOP 🌹🔫', 256, 68);
  const sign = new THREE.Mesh(new THREE.PlaneGeometry(7, 1.75), new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(sc) }));
  sign.position.set(cx, 4.35, cz + d/2 + 0.05); scene.add(sign);

  // counter
  const counter = new THREE.Mesh(new THREE.BoxGeometry(6, 1, 0.9), new THREE.MeshStandardMaterial({ color: 0x8b5a2b }));
  counter.position.set(cx, 0.75, 13.9); scene.add(counter);

  // flower buckets with roses
  for (let i = 0; i < 4; i++) {
    const bucket = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.24, 0.5, 10), new THREE.MeshStandardMaterial({ color: 0x555566 }));
    bucket.position.set(cx - 5 + i*0.85, 0.5, 14.9); scene.add(bucket);
    const rose = emojiSprite('🌹', 0.9); rose.position.set(cx - 5 + i*0.85, 1.1, 14.9); scene.add(rose);
  }
  // gun rack on the wall
  const rack = new THREE.Mesh(new THREE.BoxGeometry(2.6, 0.12, 0.1), new THREE.MeshStandardMaterial({ color: 0x2b2b33 }));
  rack.position.set(cx + 4.6, 2.5, cz + d/2 + 0.08); scene.add(rack);
  [-0.8, 0, 0.8].forEach(dx => {
    const gun = emojiSprite('🔫', 0.75); gun.position.set(cx + 4.6 + dx, 2.9, cz + d/2 + 0.2); scene.add(gun);
  });
  const pin = emojiSprite('🛒', 2.4); pin.position.set(cx, h + 2.6, cz); scene.add(pin);
}

// ---- Airfield + plane (south-west… north edge of the map) ----
{
  const runway = new THREE.Mesh(new THREE.BoxGeometry(90, 0.06, 12), new THREE.MeshStandardMaterial({ color: 0x2d2d33 }));
  runway.position.set(0, 0.03, -76); scene.add(runway);
  const dashMat = new THREE.MeshStandardMaterial({ color: 0xf5f5f0 });
  for (let x = -40; x <= 40; x += 8) {
    const dash = new THREE.Mesh(new THREE.BoxGeometry(4, 0.02, 0.4), dashMat);
    dash.position.set(x, 0.08, -76); scene.add(dash);
  }
  const tag = emojiSprite('✈️', 3); tag.position.set(-35, 6, -76); scene.add(tag);
}
function addPlane(x, z, rotY) {
  const g = new THREE.Group();
  const white = new THREE.MeshStandardMaterial({ color: 0xf2f2f2 });
  const red = new THREE.MeshStandardMaterial({ color: 0xe63946 });
  const dark = new THREE.MeshStandardMaterial({ color: 0x1a1a1a });
  const glass = new THREE.MeshStandardMaterial({ color: 0x9fc4e8 });
  const add = (mesh, px, py, pz) => { mesh.position.set(px, py, pz); g.add(mesh); return mesh; };

  const fus = add(new THREE.Mesh(new THREE.CylinderGeometry(0.45, 0.3, 4.2, 12), white), 0, 1.15, 0);
  fus.rotation.z = -Math.PI/2;                                    // nose points +x (car forward convention)
  const spinner = add(new THREE.Mesh(new THREE.ConeGeometry(0.22, 0.4, 10), red), 2.3, 1.15, 0);
  spinner.rotation.z = -Math.PI/2;
  const prop = add(new THREE.Mesh(new THREE.BoxGeometry(0.04, 2.0, 0.14), dark), 2.55, 1.15, 0);
  add(new THREE.Mesh(new THREE.BoxGeometry(1.1, 0.1, 7.2), white), 0.2, 1.45, 0);   // wing
  add(new THREE.Mesh(new THREE.BoxGeometry(1.12, 0.11, 0.6), red), 0.2, 1.45, 3.3);
  add(new THREE.Mesh(new THREE.BoxGeometry(1.12, 0.11, 0.6), red), 0.2, 1.45, -3.3);
  add(new THREE.Mesh(new THREE.BoxGeometry(0.9, 1.1, 0.1), red), -1.9, 1.75, 0);    // tail fin
  add(new THREE.Mesh(new THREE.BoxGeometry(0.7, 0.08, 2.4), white), -1.9, 1.3, 0);  // stabilizer
  const cockpit = add(new THREE.Mesh(new THREE.SphereGeometry(0.4, 10, 8), glass), 0.7, 1.6, 0);
  cockpit.scale.set(1.3, 0.8, 0.9);
  [-0.9, 0.9].forEach(zz => {
    add(new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.4, 0.06), dark), 0.7, 0.55, zz);
    const wheel = add(new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, 0.12, 12), dark), 0.7, 0.2, zz);
    wheel.rotation.x = Math.PI/2;
  });
  const tail = add(new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.1, 0.1, 8), dark), -1.8, 0.15, 0);
  tail.rotation.x = Math.PI/2;

  g.position.set(x, 0, z);
  g.rotation.y = rotY;
  scene.add(g);
  cars['plane_0'] = { id: 'plane_0', group: g, occupiedBy: null, isPlane: true, prop };
}
addPlane(-35, -76, 0);

/* =========================================================
   1c) KERALA WORLD — chayakkada (samavar + old radio), kaalavandi bullock carts, palms
   ========================================================= */
const TEA_POS = { x: -10, z: 8 }, TEA_RANGE = 6;       // customer spot at the chayakkada counter
const RADIO_POS = { x: -12.8, z: 9.7 };                 // the old valve radio on the counter
const RADIO_CTRL_RANGE = 6, RADIO_HEAR_RANGE = 26;
const teaFx = { steam: [], dial: null, note: null };

function steamTexture() {
  const c = document.createElement('canvas'); c.width = c.height = 64;
  const g = c.getContext('2d'), gr = g.createRadialGradient(32, 32, 2, 32, 32, 30);
  gr.addColorStop(0, 'rgba(255,255,255,0.9)'); gr.addColorStop(1, 'rgba(255,255,255,0)');
  g.fillStyle = gr; g.fillRect(0, 0, 64, 64);
  return new THREE.CanvasTexture(c);
}

function buildChayakkada(cx, cz) {
  // Local frame: shop body at the back (z<0), open veranda + yard in front (z>0). Rotated so the front faces the spawn road.
  const T = new THREE.Group(); T.position.set(cx, 0, cz); T.rotation.y = Math.PI; scene.add(T);
  const add = (mesh, x, y, z) => { mesh.position.set(x, y, z); T.add(mesh); return mesh; };
  const box = (w, h, d, c, x, y, z) => add(new THREE.Mesh(new THREE.BoxGeometry(w, h, d), kMat(c)), x, y, z);
  const wood = 0x8b5a2b, dark = 0x5a3a1a;

  const yard = add(new THREE.Mesh(new THREE.PlaneGeometry(13, 9), kMat(0xc98a5e)), 0, 0.27, 2.2); yard.rotation.x = -Math.PI/2;

  // walls, gable ends, terracotta tile roof
  box(9, 4, 5, 0xf0e6cf, 0, 2.25, -4);
  [-4.5, 4.5].forEach(x => {
    const tri = new THREE.Mesh(new THREE.ShapeGeometry(new THREE.Shape([new THREE.Vector2(-6.5, 4.25), new THREE.Vector2(-1.5, 4.25), new THREE.Vector2(-4, 5.05)])),
      kMat(0xf0e6cf, { side: THREE.DoubleSide }));
    tri.rotation.y = -Math.PI/2; add(tri, x, 0, 0);
  });
  const tile = kMat(0xb5482a);
  add(new THREE.Mesh(new THREE.BoxGeometry(10.4, 0.16, 5.43), tile), 0, 4.27, -1.4).rotation.x = 0.2915;
  add(new THREE.Mesh(new THREE.BoxGeometry(10.4, 0.16, 2.92), tile), 0, 4.63, -5.4).rotation.x = -0.2915;
  box(10.5, 0.14, 0.3, 0x8a2f18, 0, 5.1, -4);
  [-4.3, 4.3].forEach(x => box(0.14, 3.3, 0.14, dark, x, 1.9, 1.1));   // veranda posts

  // Malayalam signboard: ചായക്കട
  box(6.6, 1.25, 0.12, 0x3a2413, 0, 3.0, 1.3);
  add(new THREE.Mesh(new THREE.PlaneGeometry(6.2, 1.05), new THREE.MeshBasicMaterial({ map: makeSignTex('ചായക്കട', '#1f5b3a', '#ffd166') })), 0, 3.0, 1.37);

  // counter
  box(8.4, 1.05, 0.9, wood, 0, 0.78, 0.3);
  box(8.6, 0.08, 1.05, 0xa9743a, 0, 1.34, 0.3);

  // shelf of snack jars on the back wall
  box(8, 0.06, 0.3, dark, 0, 2.3, -1.35);
  for (let i = 0; i < 10; i++) {
    add(new THREE.Mesh(new THREE.CylinderGeometry(0.17, 0.17, 0.36, 8), kMat(i % 2 ? 0xf2c14e : 0xc98a3a)), -3.6 + i*0.8, 2.51, -1.35);
    add(new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.18, 0.05, 8), kMat(0xf2f2f2)), -3.6 + i*0.8, 2.72, -1.35);
  }

  // hanging banana bunches (pazham) — the classic chayakkada look
  box(8.4, 0.04, 0.04, dark, 0, 3.15, 0.9);
  [-3.6, -1.0, 1.0, 3.6].forEach(x => {
    for (let k = 0; k < 7; k++) {
      const a = k * 0.9, b = add(new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.03, 0.34, 5), kMat(0xf2d13b)), x + Math.cos(a)*0.09, 2.95, 0.9 + Math.sin(a)*0.09);
      b.rotation.x = Math.sin(a)*0.3; b.rotation.z = -Math.cos(a)*0.3;
    }
  });

  // SAMAVAR — brass tea urn with tap, handles and rising steam
  const sam = new THREE.Group(); sam.position.set(-2.8, 1.38, 0.3); T.add(sam);
  const brass = kMat(0xd9a93c, { metalness: 0.3, roughness: 0.35, emissive: 0x3a2a08 });
  const sadd = (g, x, y, z) => { const m = new THREE.Mesh(g, brass); m.position.set(x, y, z); sam.add(m); return m; };
  sadd(new THREE.CylinderGeometry(0.28, 0.32, 0.14, 14), 0, 0.07, 0);
  sadd(new THREE.CylinderGeometry(0.34, 0.38, 0.78, 16), 0, 0.53, 0);
  sadd(new THREE.SphereGeometry(0.34, 14, 8, 0, Math.PI*2, 0, Math.PI/2), 0, 0.92, 0).scale.y = 0.6;
  sadd(new THREE.CylinderGeometry(0.1, 0.13, 0.34, 10), 0, 1.2, 0);
  sadd(new THREE.ConeGeometry(0.14, 0.2, 10), 0, 1.47, 0);
  [-1, 1].forEach(s => sadd(new THREE.TorusGeometry(0.1, 0.025, 6, 10), s*0.4, 0.75, 0));
  sadd(new THREE.CylinderGeometry(0.035, 0.035, 0.3, 8), 0.5, 0.28, 0).rotation.z = Math.PI/2;
  sadd(new THREE.SphereGeometry(0.06, 8, 8), 0.68, 0.28, 0);
  const stTex = steamTexture();
  for (let i = 0; i < 4; i++) {
    const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: stTex, transparent: true, depthWrite: false }));
    sp.position.set(0, 1.7, 0); sam.add(sp); teaFx.steam.push({ sp, phase: i / 4 });
  }
  // tea glasses
  [-1.7, -1.4, -1.1].forEach(x => add(new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.055, 0.15, 8), kMat(0xfff1c4, { transparent: true, opacity: 0.7 })), x, 1.46, 0.5));

  // OLD VALVE RADIO — wooden cabinet, speaker cloth, glowing dial, knobs
  const rad = new THREE.Group(); rad.position.set(2.8, 1.38, 0.3); T.add(rad);
  const radAdd = (mesh, x, y, z) => { mesh.position.set(x, y, z); rad.add(mesh); return mesh; };
  radAdd(new THREE.Mesh(new THREE.BoxGeometry(0.84, 0.52, 0.32), kMat(0x5b3416)), 0, 0.26, 0);
  const gc = document.createElement('canvas'); gc.width = gc.height = 64;
  { const g = gc.getContext('2d'); g.fillStyle = '#d8c08a'; g.fillRect(0, 0, 64, 64); g.strokeStyle = '#8a6a34'; g.lineWidth = 3;
    for (let i = 4; i < 64; i += 8) { g.beginPath(); g.moveTo(i, 0); g.lineTo(i, 64); g.stroke(); } }
  radAdd(new THREE.Mesh(new THREE.PlaneGeometry(0.34, 0.36), new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(gc) })), -0.2, 0.26, 0.165);
  teaFx.dial = kMat(0x6b4a10, { emissive: 0xffb347, emissiveIntensity: 0 });
  radAdd(new THREE.Mesh(new THREE.PlaneGeometry(0.3, 0.16), teaFx.dial), 0.2, 0.36, 0.165);
  [0.12, 0.28].forEach(x => { radAdd(new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.035, 0.05, 8), kMat(0xe8d9a8)), x, 0.15, 0.175).rotation.x = Math.PI/2; });
  teaFx.note = emojiSprite('🎵', 0.6); teaFx.note.position.set(0, 1.0, 0.1); rad.add(teaFx.note);

  // benches in the yard
  [-2.7, 2.7].forEach(x => {
    box(2.3, 0.1, 0.55, wood, x, 0.8, 3.4);
    [-1, 1].forEach(s => box(0.1, 0.55, 0.45, dark, x + s*1.0, 0.52, 3.4));
  });
}
buildChayakkada(-10, 10);

// ---- Kaalavandi: bullock cart with arched thatch canopy, spoked wheels and two oxen (drive with E) ----
const oxLegGeo = new THREE.CylinderGeometry(0.065, 0.05, 0.65, 6); oxLegGeo.translate(0, -0.325, 0);
let cartCounter = 0;
function addKaalavandi(x, z, rotY) {
  const g = new THREE.Group(), legs = [], wheels = [];
  const wood = kMat(0x8b5a2b), dark = kMat(0x5a3a1a), thatch = kMat(0xc2a060, { side: THREE.DoubleSide });
  const add = (geo, m, px, py, pz) => { const o = new THREE.Mesh(geo, m); o.position.set(px, py, pz); g.add(o); return o; };
  add(new THREE.BoxGeometry(2.4, 0.12, 1.3), wood, -0.3, 0.85, 0);
  [-1, 1].forEach(s => add(new THREE.BoxGeometry(2.4, 0.25, 0.06), dark, -0.3, 1.0, s*0.65));
  add(new THREE.CylinderGeometry(0.66, 0.66, 2.2, 12, 1, true, 0, Math.PI), thatch, -0.3, 0.92, 0).rotation.z = Math.PI/2;   // koodaram canopy
  [-1, 1].forEach(s => {
    const w = new THREE.Group(); w.position.set(-0.3, 0.57, s*0.74); g.add(w); wheels.push(w);
    w.add(new THREE.Mesh(new THREE.TorusGeometry(0.52, 0.05, 6, 16), dark));
    w.add(new THREE.Mesh(new THREE.SphereGeometry(0.1, 8, 8), dark));
    for (let k = 0; k < 3; k++) { const sp = new THREE.Mesh(new THREE.BoxGeometry(1.04, 0.06, 0.06), wood); sp.rotation.z = k*Math.PI/3; w.add(sp); }
  });
  add(new THREE.BoxGeometry(2.6, 0.08, 0.08), dark, 2.2, 1.05, 0);     // pole
  add(new THREE.BoxGeometry(0.12, 0.1, 1.25), dark, 3.45, 1.45, 0);    // yoke
  [[-0.42, 0xf3ebdd], [0.42, 0x8a5a3a]].forEach(([oz, col]) => {
    const m = kMat(col);
    add(new THREE.BoxGeometry(1.3, 0.6, 0.5), m, 3.0, 1.0, oz);
    add(new THREE.SphereGeometry(0.2, 8, 8), m, 2.6, 1.4, oz);
    add(new THREE.BoxGeometry(0.45, 0.32, 0.3), m, 3.8, 1.1, oz);
    [-1, 1].forEach(s => add(new THREE.ConeGeometry(0.04, 0.35, 5), kMat(0xeeeeee), 3.8, 1.42, oz + s*0.14).rotation.x = s*0.6);
    add(new THREE.CylinderGeometry(0.02, 0.02, 0.5, 4), dark, 2.3, 0.95, oz).rotation.z = 0.3;
    [2.55, 3.45].forEach(lx => [-0.17, 0.17].forEach(lz => legs.push(add(oxLegGeo, m, lx, 0.7, oz + lz))));
  });
  g.position.set(x, 0, z); g.rotation.y = rotY; g.userData.legs = legs; g.userData.wheels = wheels;
  scene.add(g);
  const id = 'cart_' + (cartCounter++);
  cars[id] = { id, group: g, occupiedBy: null, isCart: true };
}
addKaalavandi(8, -1.2, 0);
addKaalavandi(-13, 4.5, Math.PI);
addKaalavandi(-21, -18, Math.PI/2);

// ---- Trees: coconut palms, mango-type broad trees and banana clumps, drawn as a few instanced meshes ----
for (let z = -80; z <= 80; z += 7) { addPalm(60.5 + Math.random()*1.5, z + Math.random()*2); addPalm(84 + Math.random()*1.5, z + Math.random()*2); }
function buildPalms() {
  const d = new THREE.Object3D(); d.rotation.order = 'YXZ';
  const nP = palmSpots.length, nB = broadSpots.length, nBan = bananaSpots.length;
  const fg = new THREE.ConeGeometry(0.3, 3, 4); fg.scale(1, 1, 0.22); fg.translate(0, 1.5, 0);
  const trunks  = new THREE.InstancedMesh(new THREE.CylinderGeometry(0.15, 0.26, 1, 6), kMat(0x8d6e4a), nP + nB + nBan);
  const fronds  = new THREE.InstancedMesh(fg, kMat(0x2e8b3d, { side: THREE.DoubleSide }), nP*7 + nBan*5);
  const nuts    = new THREE.InstancedMesh(new THREE.SphereGeometry(0.17, 6, 6), kMat(0x6f8f2f), nP*3);
  const canopy  = new THREE.InstancedMesh(new THREE.SphereGeometry(1.5, 8, 6), kMat(0x3f8f3a), Math.max(1, nB*3));
  let ti = 0, fi = 0, ni = 0, ci = 0;
  const put = (mesh, i, px, py, pz, rx, ry, rz, sx, sy, sz) => { d.position.set(px, py, pz); d.rotation.set(rx, ry, rz); d.scale.set(sx, sy, sz); d.updateMatrix(); mesh.setMatrixAt(i, d.matrix); };
  palmSpots.forEach(p => {
    const lean = (Math.random() - 0.5) * 0.22, tx = p.x - Math.sin(lean)*p.h, ty = Math.cos(lean)*p.h;
    put(trunks, ti++, (p.x + tx)/2, ty/2, p.z, 0, 0, lean, 1, p.h, 1);
    for (let k = 0; k < 7; k++) put(fronds, fi++, tx, ty, p.z, 1.05 + Math.random()*0.35, k/7*Math.PI*2 + Math.random()*0.4, 0, 1, 1, 1);
    for (let k = 0; k < 3; k++) { const a = k*2.1; put(nuts, ni++, tx + Math.cos(a)*0.25, ty - 0.15, p.z + Math.sin(a)*0.25, 0, 0, 0, 1, 1, 1); }
  });
  broadSpots.forEach(b => {
    put(trunks, ti++, b.x, b.h/2, b.z, 0, 0, 0, 1.2, b.h, 1.2);
    put(canopy, ci++, b.x, b.h + 1.0, b.z, 0, 0, 0, 1, 1, 1);
    put(canopy, ci++, b.x + 0.9, b.h + 0.6, b.z + 0.4, 0, 0, 0, 0.8, 0.8, 0.8);
    put(canopy, ci++, b.x - 0.8, b.h + 0.7, b.z - 0.5, 0, 0, 0, 0.85, 0.85, 0.85);
  });
  bananaSpots.forEach(b => {
    put(trunks, ti++, b.x, 0.6, b.z, 0, 0, 0, 0.9, 1.2, 0.9);
    for (let k = 0; k < 5; k++) put(fronds, fi++, b.x, 1.1, b.z, 0.9, k*1.26, 0, 1.7, 0.7, 1);
  });
  [trunks, fronds, nuts, canopy].forEach(m => { m.instanceMatrix.needsUpdate = true; m.frustumCulled = false; scene.add(m); });
}
buildPalms();

/* =========================================================
   COWSHED (പശുത്തൊഴുത്ത്) + milk stall, SOUND ENGINE, ANIMALS, shared RADIO
   ========================================================= */
const MILK_POS = { x: -6, z: -5 };   // Karavakkari chechi's milk stall
const SHOPS = {
  tea:  { x: TEA_POS.x,  z: TEA_POS.z,  range: TEA_RANGE, title: '🍵 ചായക്കട — Chayakkada', prompt: '🍵 Press B for chaya & palaharam' },
  milk: { x: MILK_POS.x, z: MILK_POS.z, range: 6,         title: '🥛 പാൽ — Milk stall',      prompt: '🥛 Press B for palu, thairu & nei' },
  main: { x: SHOP_POS.x, z: SHOP_POS.z, range: 7,         title: '🛒 Shop',                 prompt: '🛒 Press B or tap to shop' },
};
let milkmaid = null;

(function buildCowshed() {
  const cx = -10, y0 = 0.25, wood = 0x7a4c22, thatch = kMat(0xc2a060, { side: THREE.DoubleSide });
  const box = (w, h, d, c, x, y, z) => { const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), kMat(c)); m.position.set(x, y, z); scene.add(m); return m; };
  [[-14, -15], [-6, -15], [-14, -11], [-6, -11]].forEach(([x, z]) => box(0.22, 3, 0.22, wood, x, y0 + 1.5, z));
  box(8.2, 2.6, 0.15, 0x9a6a3a, cx, y0 + 1.3, -15);                       // back wall
  const r1 = box(9.6, 0.14, 2.45, 0xc2a060, cx, y0 + 3.45, -12.0); r1.rotation.x = 0.42;
  const r2 = box(9.6, 0.14, 2.45, 0xc2a060, cx, y0 + 3.45, -14.0); r2.rotation.x = -0.42;
  box(9.6, 0.12, 0.18, 0x8a6a30, cx, y0 + 3.92, -13.0);                    // ridge
  box(8.4, 0.15, 0.15, wood, cx, y0 + 2.9, -11);                           // front beam
  const sg = new THREE.Mesh(new THREE.PlaneGeometry(3.8, 0.85), new THREE.MeshBasicMaterial({ map: makeSignTex('പശുത്തൊഴുത്ത്', '#3a5a2a', '#fff2cc') }));
  sg.position.set(cx, y0 + 2.4, -10.9); scene.add(sg);
  box(2.2, 0.7, 1.1, 0xe3c25a, -12, y0 + 0.35, -13.6);                     // hay
  box(2.6, 0.35, 0.6, wood, -8, y0 + 0.3, -13.7);                          // trough
  // milk stall: table, steel pots, signboard
  box(2.6, 0.9, 0.8, wood, MILK_POS.x, y0 + 0.45, -4.4);
  box(2.8, 0.08, 0.95, 0xa9743a, MILK_POS.x, y0 + 0.94, -4.4);
  const steel = kMat(0xc9ced6, { metalness: 0.6, roughness: 0.3 });
  [-0.8, 0, 0.8].forEach(dx => { const m = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.17, 0.34, 12), steel); m.position.set(MILK_POS.x + dx, y0 + 1.15, -4.4); scene.add(m); });
  [-1.2, 1.2].forEach(dx => box(0.1, 2.6, 0.1, wood, MILK_POS.x + dx, y0 + 1.3, -4.0));
  const ms = new THREE.Mesh(new THREE.PlaneGeometry(2.4, 0.6), new THREE.MeshBasicMaterial({ map: makeSignTex('പാൽ · തൈര് · മോര്', '#ffffff', '#1d4f8a') }));
  ms.position.set(MILK_POS.x, y0 + 2.35, -3.93); scene.add(ms);
})();

/* ---- Sound engine (WebAudio, synthesised — no audio files). Everything stays silent until the Sound button is on. ---- */
let soundOn = false, actx = null, master = null;
function ensureAudio() {
  if (!actx) {
    const AC = window.AudioContext || window.webkitAudioContext; if (!AC) return false;
    actx = new AC(); master = actx.createGain(); master.gain.value = 0.9; master.connect(actx.destination);
  }
  if (actx.state === 'suspended') actx.resume();
  return true;
}
function envGain(t0, attack, hold, release, peak) {
  const g = actx.createGain(), pk = Math.max(0.0002, peak);
  g.gain.setValueAtTime(0.0001, t0);
  g.gain.exponentialRampToValueAtTime(pk, t0 + attack);
  g.gain.setValueAtTime(pk, t0 + attack + hold);
  g.gain.exponentialRampToValueAtTime(0.0001, t0 + attack + hold + release);
  return g;
}
// One synthesised "voice": a pitch glide through f[], shaped by band-pass formants [centre, Q, gain, sweepTo?].
function vox({ type = 'sawtooth', f = [200, 200], dur = 1, vol = 0.3, formants = [[500, 5]], vib = [0, 0], at = 0.06, t0 = 0 }) {
  if (!actx || !soundOn || vol < 0.003) return;
  const t = actx.currentTime + t0, o = actx.createOscillator(); o.type = type;
  o.frequency.setValueAtTime(f[0], t);
  for (let i = 1; i < f.length; i++) o.frequency.linearRampToValueAtTime(f[i], t + dur * i / (f.length - 1));
  if (vib[0]) { const l = actx.createOscillator(), lg = actx.createGain(); l.frequency.value = vib[0]; lg.gain.value = vib[1]; l.connect(lg); lg.connect(o.frequency); l.start(t); l.stop(t + dur + 0.2); }
  const out = envGain(t, at, Math.max(0.01, dur - at - 0.15), 0.15, vol);
  formants.forEach(([fc, q, gain = 1, sweep]) => {
    const b = actx.createBiquadFilter(), gg = actx.createGain();
    b.type = 'bandpass'; b.frequency.setValueAtTime(fc, t); b.Q.value = q; gg.gain.value = gain;
    if (sweep) { b.frequency.linearRampToValueAtTime(sweep, t + dur * 0.5); b.frequency.linearRampToValueAtTime(fc, t + dur); }
    o.connect(b); b.connect(gg); gg.connect(out);
  });
  out.connect(master); o.start(t); o.stop(t + dur + 0.25);
}
const SND = {
  moo(v, p = 1)  { vox({ f: [105*p, 150*p, 135*p, 80*p], dur: 1.7, vol: 0.6*v, formants: [[300, 4, 1, 650], [800, 6, 0.6, 1100]], vib: [5, 4], at: 0.12 }); },
  ox(v)          { SND.moo(v, 0.72); },                       // the kaalas: deeper than a cow
  goat(v)        { vox({ f: [430, 480, 400], dur: 0.85, vol: 0.45*v, formants: [[1200, 4], [2200, 6, 0.6]], vib: [26, 40], at: 0.03 }); },
  hen(v)         { for (let i = 0; i < 4; i++) vox({ f: [340, 250], dur: 0.11, vol: 0.28*v, formants: [[900, 3], [1800, 4, 0.5]], at: 0.01, t0: i*0.15 + Math.random()*0.03 }); },
  rooster(v)     { [[0, 520, 0.16], [0.2, 520, 0.16], [0.4, 680, 0.22]].forEach(([d, fr, du]) => vox({ f: [fr, fr*1.08], dur: du, vol: 0.4*v, formants: [[1400, 4]], at: 0.02, t0: d }));
                   vox({ f: [800, 950, 560], dur: 1.0, vol: 0.4*v, formants: [[1300, 4], [2400, 5, 0.5]], vib: [9, 25], at: 0.04, t0: 0.65 }); },
  bell(v)        { if (!actx || !soundOn || v < 0.01) return; const t = actx.currentTime;
                   [1150, 1725].forEach((fr, i) => { const o = actx.createOscillator(), g = envGain(t, 0.005, 0.01, 0.5, 0.14*v/(i + 1)); o.type = 'sine'; o.frequency.value = fr; o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.6); }); },
};
function listenerPos() { return (drivingCarId && cars[drivingCarId]) ? cars[drivingCarId].group.position : myAvatar.position; }
function earVol(x, z, range = 24) { const q = listenerPos(), d = Math.hypot(q.x - x, q.z - z); return Math.pow(Math.max(0, 1 - d / range), 1.5); }
function oxCall(c) { const p = c.group.position, v = earVol(p.x, p.z, 34); if (v > 0.01) { SND.ox(v); setTimeout(() => SND.ox(v * 0.8), 450); } }

/* ---- Animals: pashu (cow), aadu (goat), kozhi (hen/rooster). Positions are a pure function of the clock,
   so every player sees them in the same place without any network traffic. ---- */
const animals = [];
const part = (g) => (geo, mat, x, y, z) => { const o = new THREE.Mesh(geo, mat); o.position.set(x, y, z); g.add(o); return o; };
const cowLeg = new THREE.CylinderGeometry(0.08, 0.06, 0.6, 6); cowLeg.translate(0, -0.3, 0);
const goatLeg = new THREE.CylinderGeometry(0.04, 0.03, 0.38, 5); goatLeg.translate(0, -0.19, 0);
const henLeg = new THREE.CylinderGeometry(0.012, 0.012, 0.16, 4); henLeg.translate(0, -0.08, 0);

function makeCow(coat, patch) {
  const g = new THREE.Group(), legs = [], a = part(g);
  const m = kMat(coat), pm = kMat(patch), dk = kMat(0x2b1d14), pk = kMat(0xe9a8a0), hn = kMat(0xeeeeee);
  a(new THREE.BoxGeometry(1.5, 0.72, 0.62), m, 0, 0.95, 0);
  a(new THREE.BoxGeometry(0.55, 0.74, 0.64), pm, -0.3, 0.96, 0);
  a(new THREE.SphereGeometry(0.25, 8, 6), m, 0.42, 1.35, 0);                    // hump
  a(new THREE.BoxGeometry(0.46, 0.4, 0.4), m, 0.98, 1.1, 0);                    // head
  a(new THREE.BoxGeometry(0.2, 0.26, 0.34), pk, 1.25, 1.02, 0);                 // muzzle
  [-1, 1].forEach(s => {
    a(new THREE.ConeGeometry(0.05, 0.3, 5), hn, 0.92, 1.38, s*0.2).rotation.x = s*0.5;   // horns
    a(new THREE.BoxGeometry(0.18, 0.06, 0.12), pm, 0.88, 1.24, s*0.27);                  // ears
    a(new THREE.SphereGeometry(0.03, 5, 5), dk, 1.17, 1.18, s*0.14);                     // eyes
  });
  a(new THREE.CylinderGeometry(0.025, 0.02, 0.75, 5), m, -0.78, 0.95, 0).rotation.z = 0.25;
  a(new THREE.SphereGeometry(0.1, 6, 6), dk, -0.85, 0.55, 0);
  a(new THREE.SphereGeometry(0.17, 8, 6), pk, -0.45, 0.58, 0);                  // udder
  [[0.5, 0.22], [0.5, -0.22], [-0.5, 0.22], [-0.5, -0.22]].forEach(([x, z]) => legs.push(a(cowLeg, m, x, 0.6, z)));
  g.userData = { legs, kind: 'cow' }; return g;
}
function makeGoat(coat) {
  const g = new THREE.Group(), legs = [], a = part(g);
  const m = kMat(coat), dk = kMat(0x2b1d14), hn = kMat(0xd9d2b8);
  a(new THREE.BoxGeometry(0.85, 0.36, 0.3), m, 0, 0.58, 0);
  a(new THREE.BoxGeometry(0.22, 0.22, 0.2), m, 0.52, 0.8, 0);
  a(new THREE.BoxGeometry(0.12, 0.14, 0.14), dk, 0.64, 0.74, 0);
  a(new THREE.ConeGeometry(0.03, 0.14, 5), m, 0.62, 0.62, 0).rotation.z = Math.PI;      // beard
  a(new THREE.CylinderGeometry(0.1, 0.1, 0.1, 6), m, 0.3, 0.68, 0).rotation.z = 0.9;     // neck
  [-1, 1].forEach(s => {
    a(new THREE.ConeGeometry(0.025, 0.22, 5), hn, 0.46, 0.95, s*0.06).rotation.z = 0.7;
    a(new THREE.BoxGeometry(0.12, 0.04, 0.12), m, 0.45, 0.82, s*0.15);
    a(new THREE.SphereGeometry(0.02, 5, 5), dk, 0.62, 0.84, s*0.08);
  });
  a(new THREE.ConeGeometry(0.04, 0.2, 5), m, -0.44, 0.74, 0).rotation.z = 0.5;
  [[0.3, 0.1], [0.3, -0.1], [-0.3, 0.1], [-0.3, -0.1]].forEach(([x, z]) => legs.push(a(goatLeg, m, x, 0.4, z)));
  g.userData = { legs, kind: 'goat' }; return g;
}
function makeHen(body, tailC, big) {
  const g = new THREE.Group(), up = new THREE.Group(), legs = [], a = part(up), b = part(g);
  up.position.y = 0.28; g.add(up);
  const m = kMat(body), red = kMat(0xd62828), tl = kMat(tailC), yl = kMat(0xe9b23a);
  a(new THREE.SphereGeometry(0.2, 8, 6), m, 0, 0, 0).scale.set(1.15, 0.85, 0.8);
  a(new THREE.SphereGeometry(0.075, 6, 6), m, 0.2, 0.17, 0);
  a(new THREE.BoxGeometry(0.03, big ? 0.09 : 0.06, 0.02), red, 0.2, 0.26, 0);              // comb
  a(new THREE.SphereGeometry(0.025, 5, 5), red, 0.26, 0.1, 0);                              // wattle
  a(new THREE.ConeGeometry(0.025, 0.08, 5), yl, 0.29, 0.16, 0).rotation.z = -Math.PI/2;    // beak
  a(new THREE.ConeGeometry(0.07, 0.32, 5), tl, -0.24, 0.14, 0).rotation.z = 0.9;           // tail
  if (big) a(new THREE.ConeGeometry(0.05, 0.4, 5), kMat(0x1f6b3a), -0.28, 0.2, 0).rotation.z = 0.6;
  [-1, 1].forEach(s => { a(new THREE.SphereGeometry(0.1, 6, 5), m, -0.02, 0.02, s*0.14).scale.set(1.2, 0.6, 0.3); legs.push(b(henLeg, yl, 0, 0.17, s*0.06)); });
  if (big) g.scale.setScalar(1.35);
  g.userData = { legs, up, kind: big ? 'rooster' : 'hen' }; return g;
}
function addAnimal(g, hx, hz, ax, az, w) {
  const seed = animals.length + 1;
  g.position.set(hx, 0.26, hz); scene.add(g);
  animals.push({ g, seed, hx, hz, ax, az, w, p1: seed*1.7, p2: seed*2.9, p3: seed*0.7, p4: seed*4.1, kind: g.userData.kind, lastIdx: null });
}
// pashu — cows around the cowshed; aadu — goats; kozhi — hens in the yard + cowshed block, one rooster
addAnimal(makeCow(0xf3ebdd, 0x8a5a3a), -10, -8.4, 2.4, 1.2, 0.07);
addAnimal(makeCow(0x8a5a3a, 0xf3ebdd), -11, -8.0, 2.2, 1.1, 0.06);
addAnimal(makeGoat(0xf3ebdd), -12, -5.6, 1.8, 1.0, 0.12);
addAnimal(makeGoat(0x6b4a2e), -8.5, -6.4, 1.6, 1.0, 0.13);
addAnimal(makeGoat(0x222222), -10, -7.0, 1.8, 0.9, 0.11);
addAnimal(makeGoat(0xf3ebdd), 62.8, -20, 0.9, 6.0, 0.11);
addAnimal(makeGoat(0x8a5a3a), 62.8, -14, 0.9, 5.0, 0.12);
[0xc0392b, 0xf0e6d0, 0x8a5a3a, 0x2b2b2b].forEach((c, i) => addAnimal(makeHen(c, 0x3a2a1a, false), -10 + i - 1.5, 6, 3.2, 1.4, 0.2 + i*0.02));
[0xe6d8c0, 0xb5651d, 0x333333].forEach((c, i) => addAnimal(makeHen(c, 0x3a2a1a, false), -9 + i*2, -6.2, 3.5, 1.4, 0.21 + i*0.02));
addAnimal(makeHen(0xa8451d, 0x1f6b3a, true), -13, -6, 1.5, 1.2, 0.2);       // rooster

const ANIMAL_SND = { cow: [26, 55, SND.moo], goat: [19, 55, SND.goat], hen: [11, 50, SND.hen], rooster: [47, 60, SND.rooster] };
function animalPos(a, t) {
  return { x: a.hx + a.ax*Math.sin(a.w*t + a.p1) + 0.4*a.ax*Math.sin(2.7*a.w*t + a.p2),
           z: a.hz + a.az*Math.sin(1.3*a.w*t + a.p3) + 0.4*a.az*Math.cos(2.1*a.w*t + a.p4) };
}
function animalsTick() {
  const now = Date.now() / 1000;
  animals.forEach(a => {
    const p = animalPos(a, now), q = animalPos(a, now + 0.25);
    a.g.position.x = p.x; a.g.position.z = p.z;
    const vx = q.x - p.x, vz = q.z - p.z, sp = Math.hypot(vx, vz) / 0.25;
    if (sp > 0.03) a.g.rotation.y = Math.atan2(-vz, vx);
    const amp = Math.min(0.5, sp * 1.6), ph = now * (3 + sp * 5);
    a.g.userData.legs.forEach((l, i) => { l.rotation.z = Math.sin(ph + (i % 2 ? Math.PI : 0)) * amp; });
    if (a.g.userData.up) a.g.userData.up.rotation.z = -Math.max(0, Math.sin(now * 3 + a.seed)) * 0.55 * (sp < 0.5 ? 1 : 0.3);   // pecking
    const cfg = ANIMAL_SND[a.kind], idx = Math.floor((now + a.seed * 3.7) / cfg[0]);
    if (a.lastIdx === null) a.lastIdx = idx;
    else if (idx !== a.lastIdx) {
      a.lastIdx = idx;
      if (soundOn && ((idx * 2654435761 + a.seed * 97) >>> 0) % 100 < cfg[1]) { const v = earVol(p.x, p.z, 26); if (v > 0.02) cfg[2](v); }
    }
  });
}

/* ---- Shared old radio: the server relays one live stream per station, so everyone hears the same broadcast,
   louder the closer they are to the chayakkada, and only while Sound is on. ---- */
let radioState = { on: true, station: 0, stations: [] };
const radioAudio = new Audio(); radioAudio.preload = 'none';
let radioLoaded = -1, radioStatus = 'idle', radioRetryAt = 0, radioErrs = [], radioPlaying = false;
radioAudio.addEventListener('playing', () => { radioStatus = 'live'; });
radioAudio.addEventListener('waiting', () => { if (radioLoaded !== -1) radioStatus = 'connecting'; });
radioAudio.addEventListener('error', () => {
  if (radioLoaded === -1) return;
  const bad = radioLoaded, now = Date.now();
  radioStatus = 'nosignal'; radioRetryAt = now + 5000;
  radioErrs = radioErrs.filter(t => now - t < 30000); radioErrs.push(now);
  if (radioErrs.length >= 3) { socket.emit('radioFail', { station: bad }); radioErrs = []; }
  radioAudio.removeAttribute('src'); radioAudio.load(); radioLoaded = -1;
});
document.addEventListener('click', () => { if (soundOn && radioLoaded !== -1 && radioAudio.paused) radioAudio.play().catch(() => {}); });

function radioDistance() {
  const p = (drivingCarId && cars[drivingCarId]) ? cars[drivingCarId].group.position : myAvatar.position;
  return Math.hypot(p.x - RADIO_POS.x, p.z - RADIO_POS.z);
}
function radioNear() { return document.getElementById('name-gate').style.display === 'none' && !drivingCarId && radioDistance() < RADIO_CTRL_RANGE; }
function radioToggle() { if (radioNear()) socket.emit('radioSet', { on: !radioState.on, station: radioState.station }); }
function radioStep(dir) {
  const n = (radioState.stations || []).length; if (!radioNear() || n < 2) return;
  socket.emit('radioSet', { on: true, station: (radioState.station + dir + n) % n });
}
document.getElementById('radio-toggle').onclick = radioToggle;
document.getElementById('radio-next').onclick = () => radioStep(1);
document.getElementById('radio-prev').onclick = () => radioStep(-1);
function cartCall() { if (drivingCarId && cars[drivingCarId] && cars[drivingCarId].isCart) socket.emit('cartCall', { carId: drivingCarId }); }
document.getElementById('cart-call').onclick = cartCall;
addEventListener('keydown', e => {
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  const k = e.key.toLowerCase();
  if (k === 'r') radioToggle(); else if (k === 'n') radioStep(1); else if (k === 'p') radioStep(-1); else if (k === 'h') cartCall();
});

function keralaTick(t) {
  const nowMs = Date.now(), nowS = nowMs / 1000;
  // samavar steam
  teaFx.steam.forEach(s => {
    const p = (t*0.25 + s.phase) % 1;
    s.sp.position.set(Math.sin(p*6 + s.phase*5)*0.12, 1.7 + p*1.6, 0);
    s.sp.scale.setScalar(0.35 + p*0.7); s.sp.material.opacity = (1 - p) * 0.55;
  });
  // bullock carts: oxen walk, wheels turn, bell rings and the kaalas moo while moving
  Object.values(cars).forEach(c => {
    if (!c.isCart) return;
    const p = c.group.position, sp = typeof c.lx === 'number' ? Math.hypot(p.x - c.lx, p.z - c.lz) : 0;
    c.lx = p.x; c.lz = p.z; c.ph = (c.ph || 0) + sp*7;
    const amp = Math.min(0.5, sp*10);
    c.group.userData.legs.forEach((l, i) => { l.rotation.z = Math.sin(c.ph + (i % 2 ? Math.PI : 0)) * amp; });
    c.group.userData.wheels.forEach(w => { w.rotation.z -= sp/0.55; });
    if (soundOn && sp > 0.015) {
      const v = earVol(p.x, p.z, 30);
      if (v > 0.02) {
        if (nowMs > (c.nextBell || 0)) { SND.bell(v); c.nextBell = nowMs + 650; }
        if (nowMs > (c.nextMoo || 0))  { oxCall(c); c.nextMoo = nowMs + 8000 + Math.random()*7000; }
      }
    }
  });
  animalsTick();
  if (milkmaid) { milkmaid.rotation.z = Math.sin(t*1.2)*0.03; milkmaid.rotation.x = Math.sin(t*3)*0.03; }
  // radio dial + note
  teaFx.dial.emissiveIntensity = radioState.on ? 0.7 + 0.25*Math.sin(t*5) : 0;
  teaFx.note.visible = radioState.on; teaFx.note.position.y = 1.0 + Math.sin(t*2.5)*0.08;
  // radio audio
  const gate = document.getElementById('name-gate').style.display !== 'none';
  const d = radioDistance(), sts = radioState.stations || [];
  const idx = sts.length ? radioState.station % sts.length : -1;
  const prox = Math.max(0, Math.min(1, 1 - (d - 3) / (RADIO_HEAR_RANGE - 3)));
  const vol = (soundOn && !gate && radioState.on && idx >= 0 && !sts[idx].down) ? prox : 0;
  if (vol > 0.03) {
    if (radioLoaded !== idx && nowMs > radioRetryAt) { radioAudio.src = '/radio/stream/' + sts[idx].id + '?s=' + nowMs; radioLoaded = idx; radioStatus = 'connecting'; }
    if (radioLoaded === idx) {
      radioAudio.volume = Math.min(1, vol * 0.9);
      if (radioAudio.paused && !radioPlaying) { radioPlaying = true; radioAudio.play().catch(() => {}).then(() => setTimeout(() => { radioPlaying = false; }, 1500)); }
    }
  } else if (radioLoaded !== -1) {
    radioAudio.pause(); radioAudio.removeAttribute('src'); radioAudio.load(); radioLoaded = -1; radioStatus = 'idle';
  }
  const chip = document.getElementById('radio-chip');
  if (!gate && radioState.on && d < RADIO_HEAR_RANGE) {
    const nm = idx >= 0 ? sts[idx].name : '';
    chip.textContent = idx < 0 ? '📻 No stations yet…'
      : sts[idx].down ? '📻 ' + nm + ' — off air, skipping…'
      : !soundOn ? '📻 ' + nm + ' — 🔇 turn Sound on to listen'
      : '📻 ' + nm + ' • ' + ({ live: 'live', connecting: 'connecting…', nosignal: 'no signal, retrying', idle: 'tuning…' }[radioStatus]);
    chip.style.display = 'block';
  } else chip.style.display = 'none';
  const near = !gate && !drivingCarId && d < RADIO_CTRL_RANGE;
  document.getElementById('radio-ui').style.display = near ? 'flex' : 'none';
  if (near) {
    document.getElementById('radio-toggle').textContent = radioState.on ? '📻 Turn off (R)' : '📻 Turn on (R)';
    document.getElementById('radio-next').style.display = document.getElementById('radio-prev').style.display = sts.length > 1 ? '' : 'none';
  }
  document.getElementById('cart-call').style.display = (!gate && drivingCarId && cars[drivingCarId] && cars[drivingCarId].isCart) ? 'block' : 'none';
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

// --- Treasure chests: scattered around the city, positions come straight
// from the server so every player sees the same ones in the same spots. ---
function makeChestSprite() {
  const canvas = document.createElement('canvas');
  canvas.width = 128; canvas.height = 128;
  const ctx = canvas.getContext('2d');
  ctx.font = '84px serif';
  ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
  ctx.shadowColor = '#FFD700'; ctx.shadowBlur = 16;
  ctx.fillText('🧰', 64, 62);
  const tex = new THREE.CanvasTexture(canvas);
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
  sprite.scale.set(0.9, 0.9, 0.9);
  return sprite;
}
const chestMeshes = {};
function rebuildChests(spots) {
  Object.values(chestMeshes).forEach(m => scene.remove(m));
  for (const k in chestMeshes) delete chestMeshes[k];
  spots.forEach(spot => {
    const s = makeChestSprite();
    s.position.set(spot.x, 0.9, spot.z);
    s.userData.bobOffset = Math.random()*Math.PI*2;
    s.userData.chestId = spot.id;
    s.userData.wx = spot.x; s.userData.wz = spot.z;
    scene.add(s);
    chestMeshes[spot.id] = s;
  });
}

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
{
  const keeper = makeAvatarMesh('female', '#ffb703', 'pony');
  keeper.position.set(10, 0.25, 12.7);
  keeper.add(makeLabel('Shopkeeper'));
  scene.add(keeper);
  const chaya = makeAvatarMesh('male', '#ffffff', 'short');          // the chayakkaran, behind the counter
  chaya.position.set(-10, 0.27, 10.8); chaya.rotation.y = Math.PI;
  chaya.add(makeLabel('Chayakkaran'));
  scene.add(chaya);
  milkmaid = buildKaravakkari(); milkmaid.position.set(-6, 0.27, -5.5); scene.add(milkmaid);
}

// Karavakkari chechi (കറവക്കാരി, the milkmaid): kasavu-style blouse, long green pavada, davani, jasmine in her braid.
function buildKaravakkari() {
  const g = makeAvatarMesh('female', '#b3261e', 'short');
  g.children.forEach(ch => { if (ch.geometry && ch.geometry.type === 'CylinderGeometry' && ch.geometry.parameters.radiusBottom === 0.36) ch.visible = false; });  // swap the short skirt for a pavada
  const add = (geo, mat, x, y, z) => { const o = new THREE.Mesh(geo, mat); o.position.set(x, y, z); g.add(o); return o; };
  const green = kMat(0x1e7a4a), gold = kMat(0xf2b632, { metalness: 0.4, roughness: 0.4 }), red = kMat(0xb3261e);
  const white = kMat(0xffffff), yellow = kMat(0xf2c230), hair = kMat(0x1a1010), steel = kMat(0xc9ced6, { metalness: 0.6, roughness: 0.3 });
  add(new THREE.CylinderGeometry(0.22, 0.46, 0.88, 16), green, 0, 0.46, 0);                              // pavada
  add(new THREE.TorusGeometry(0.46, 0.03, 6, 20), gold, 0, 0.06, 0).rotation.x = Math.PI/2;              // gold hem
  add(new THREE.TorusGeometry(0.4, 0.02, 6, 20), gold, 0, 0.2, 0).rotation.x = Math.PI/2;
  [-1, 1].forEach(s => {
    add(new THREE.CylinderGeometry(0.088, 0.088, 0.2, 8), red, s*0.27, 1.3, 0);                          // blouse sleeves
    add(new THREE.TorusGeometry(0.07, 0.014, 6, 12), gold, s*0.285, 0.97, 0).rotation.x = Math.PI/2;     // bangles
    add(new THREE.TorusGeometry(0.07, 0.014, 6, 12), gold, s*0.285, 1.0, 0).rotation.x = Math.PI/2;
    add(new THREE.SphereGeometry(0.03, 6, 6), gold, s*0.265, 1.76, 0);                                    // earrings
  });
  add(new THREE.BoxGeometry(0.13, 0.9, 0.34), yellow, 0, 1.08, 0).rotation.z = 0.7;                       // davani across the shoulder
  add(new THREE.SphereGeometry(0.025, 6, 6), red, 0, 1.83, 0.262);                                         // pottu
  for (let i = 0; i < 7; i++) {                                                                           // long braid with jasmine
    add(new THREE.SphereGeometry(0.055 - i*0.002, 6, 6), hair, 0, 1.72 - i*0.1, -0.27 - i*0.012);
    add(new THREE.SphereGeometry(0.03, 6, 6), white, (i % 2 ? 0.045 : -0.045), 1.72 - i*0.1, -0.31 - i*0.012);
  }
  add(new THREE.SphereGeometry(0.04, 6, 6), red, 0, 1.0, -0.36);
  for (let i = 0; i < 9; i++) { const a = -1.1 + i*0.275; add(new THREE.SphereGeometry(0.032, 6, 6), white, Math.sin(a)*0.265, 1.82, -Math.cos(a)*0.265); }
  add(new THREE.CylinderGeometry(0.16, 0.13, 0.3, 12), steel, 0.55, 0.17, 0.35);                          // milk pail
  add(new THREE.CylinderGeometry(0.15, 0.15, 0.01, 12), white, 0.55, 0.32, 0.35);
  add(new THREE.CylinderGeometry(0.2, 0.2, 0.3, 8), kMat(0x7a4c22), -0.6, 0.15, 0.3);                     // stool
  g.add(makeLabel('Karavakkari 🥛'));
  return g;
}
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
  let nearest = null, nearestDist = Infinity;
  for (const id in cars) {
    const c = cars[id];
    const d = Math.hypot(c.group.position.x - myAvatar.position.x, c.group.position.z - myAvatar.position.z);
    const reach = c.isPlane ? 4 : c.isCart ? 3.4 : 2.2; // planes and carts are bigger
    if (d < reach && d < nearestDist) { nearest = c; nearestDist = d; }
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
  if (c.isPlane) {
    planeSpeed = 0; planeVy = 0;
    vehiclePrompt.textContent = 'Press E to exit (on the ground)';
    document.getElementById('controls-hint').textContent = 'W/S throttle • A/D turn • Space climb • F dive • E exit';
    document.getElementById('fly-btns').style.display = 'flex';
  } else {
    document.getElementById('controls-hint').textContent = 'WASD to drive • E to exit';
  }
  stats.carsDriven++;
}

function exitVehicle() {
  if (!drivingCarId) return;
  const c = cars[drivingCarId];
  if (c.isPlane && c.group.position.y > 0.6) { showToast('Land the plane first ✈️'); return; }
  const off = c.isPlane ? 3.6 : c.isCart ? 2.2 : 1.6;
  c.group.rotation.z = 0;
  document.getElementById('fly-btns').style.display = 'none';
  setChip(null);
  const forward = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
  // Step out to the left side of the car rather than through it
  myAvatar.position.set(
    c.group.position.x - forward.z * off,
    0,
    c.group.position.z + forward.x * off
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
  if (c.isPlane) { updateFlying(c); return; }

  let throttle = 0, steer = 0;
  if (keys['w']) throttle += 1;
  if (keys['s']) throttle -= 1;
  if (keys['a']) steer += 1;
  if (keys['d']) steer -= 1;
  throttle += -joyVec.y; steer += -joyVec.x;

  const cart = !!c.isCart;   // kaalavandi: slow and steady
  const accel = cart ? 0.006 : 0.012, maxSpeed = cart ? 0.13 : 0.32, friction = 0.985, turnRate = cart ? 0.03 : 0.045;
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
  if (c.group.position.x > RIVER.x1 - 2.5) { c.group.position.x = RIVER.x1 - 2.5; carSpeed = 0; } // cars stop at the riverbank

  // Chase camera behind the car — now with vertical pitch for a full 360° view
  const camDist = 7;
  camera.position.x = c.group.position.x - forward.x * camDist * Math.cos(camPitch);
  camera.position.z = c.group.position.z - forward.z * camDist * Math.cos(camPitch);
  camera.position.y = 1.5 + camDist * Math.sin(camPitch) + 1.5;
  camera.lookAt(c.group.position.x, 1, c.group.position.z);

  socket.emit('driveCar', { carId: drivingCarId, x: c.group.position.x, y: 0, z: c.group.position.z, rotY: c.group.rotation.y });
}

let planeSpeed = 0, planeVy = 0, flyUpHeld = false, flyDownHeld = false;
const stateChip = document.getElementById('state-chip');
function setChip(txt) {
  if (txt) { stateChip.textContent = txt; stateChip.style.display = 'block'; }
  else stateChip.style.display = 'none';
}
[['fly-up', v => flyUpHeld = v], ['fly-down', v => flyDownHeld = v]].forEach(([id, set]) => {
  const el = document.getElementById(id);
  el.addEventListener('pointerdown', e => { e.preventDefault(); set(true); });
  ['pointerup', 'pointerleave', 'pointercancel'].forEach(ev => el.addEventListener(ev, () => set(false)));
});

function updateFlying(c) {
  let throttle = 0, steer = 0;
  if (keys['w']) throttle += 1;
  if (keys['s']) throttle -= 1;
  if (keys['a']) steer += 1;
  if (keys['d']) steer -= 1;
  throttle += -joyVec.y; steer += -joyVec.x;
  const up = keys[' '] || keys['q'] || flyUpHeld;
  const down = keys['f'] || keys['shift'] || flyDownHeld;

  const maxSpeed = 0.75, takeoffSpeed = 0.3;
  planeSpeed += throttle * 0.008;
  planeSpeed *= 0.996;
  planeSpeed = Math.max(0, Math.min(maxSpeed, planeSpeed));

  const p = c.group.position;
  const airborne = p.y > 0.05;
  const canLift = planeSpeed >= takeoffSpeed;
  if (up && canLift) planeVy += 0.006;
  else if (down) planeVy -= 0.006;
  else planeVy *= 0.96;
  if (!canLift && airborne) planeVy -= 0.01;          // too slow: stall and sink
  planeVy = Math.max(-0.25, Math.min(0.25, planeVy));
  p.y += planeVy;
  if (p.y <= 0) { p.y = 0; planeVy = 0; if (!throttle) planeSpeed *= 0.97; }   // wheels down, rolling friction
  if (p.y > 110) { p.y = 110; planeVy = Math.min(planeVy, 0); }

  if (planeSpeed > 0.02) c.group.rotation.y += steer * 0.028 * (airborne ? 1 : 0.6);
  const targetPitch = Math.max(-0.45, Math.min(0.45, planeVy * 3));
  c.group.rotation.z += (targetPitch - c.group.rotation.z) * 0.1;

  const forward = { x: Math.cos(c.group.rotation.y), z: -Math.sin(c.group.rotation.y) };
  p.x = Math.max(-150, Math.min(150, p.x + forward.x * planeSpeed));
  p.z = Math.max(-150, Math.min(150, p.z + forward.z * planeSpeed));
  c.prop.rotation.x += 0.35 + planeSpeed * 1.5;
  stats.distanceTraveled += planeSpeed;

  const camDist = 12;
  camera.position.x = p.x - forward.x * camDist * Math.cos(camPitch);
  camera.position.z = p.z - forward.z * camDist * Math.cos(camPitch);
  camera.position.y = p.y + 2.5 + camDist * Math.sin(camPitch);
  camera.lookAt(p.x, p.y + 1.2, p.z);

  setChip(`✈️ ${Math.round(p.y)} m  •  ${Math.round(planeSpeed * 100)} kts` + (!airborne && canLift ? '  •  hold Space to take off' : ''));
  socket.emit('driveCar', { carId: c.id, x: p.x, y: p.y, z: p.z, rotY: c.group.rotation.y });
}

function updateMovement() {
  if (drivingCarId) { updateDriving(); return; }

  let dx=0, dz=0;
  if (keys['w']) dz -= 1;
  if (keys['s']) dz += 1;
  if (keys['a']) dx -= 1;
  if (keys['d']) dx += 1;
  dx += joyVec.x; dz += joyVec.y;
  const swimming = isInWater(myAvatar.position.x, myAvatar.position.z);
  if (dx || dz) {
    const len = Math.hypot(dx,dz) || 1;
    dx/=len; dz/=len;
    const moveX = dx*Math.cos(camYaw) - dz*Math.sin(camYaw);
    const moveZ = dx*Math.sin(camYaw) + dz*Math.cos(camYaw);
    const sp = swimming ? speed * 0.55 : speed;   // swimming is slower
    myAvatar.position.x += moveX*sp;
    myAvatar.position.z += moveZ*sp;
    myAvatar.rotation.y = Math.atan2(moveX, moveZ);
    stats.distanceTraveled += speed;
    checkBalloonPickup();
    checkDeliveryProximity();
    checkTreasureProximity();
  }
  // Sink into the water while swimming, with a gentle bob
  const targetY = swimming ? -0.65 + Math.sin(performance.now()/350) * 0.06 : 0;
  myAvatar.position.y += (targetY - myAvatar.position.y) * 0.25;
  setChip(swimming ? '🏊 Swimming' : null);
  const camDist = 6;
  camera.position.x = myAvatar.position.x - Math.sin(camYaw)*camDist*Math.cos(camPitch);
  camera.position.z = myAvatar.position.z - Math.cos(camYaw)*camDist*Math.cos(camPitch);
  camera.position.y = myAvatar.position.y + 1.2 + camDist*Math.sin(camPitch);
  camera.lookAt(myAvatar.position.x, myAvatar.position.y+1, myAvatar.position.z);

  // Show/hide the "Press E to enter" prompt based on proximity to a free car
  const nearby = findNearbyCar();
  if (nearby && !nearby.occupiedBy) {
    vehiclePrompt.textContent = nearby.isPlane ? 'Press E to fly ✈️' : nearby.isCart ? 'Press E to ride the kaalavandi 🐂' : 'Press E to enter';
    vehiclePrompt.style.display = 'block';
  } else vehiclePrompt.style.display = 'none';

  const here = nearShopMode();
  shopPrompt.textContent = here ? SHOPS[here].prompt : '';
  shopPrompt.style.display = here ? 'block' : 'none';
  if (document.getElementById('shop-overlay').style.display !== 'none' && shopMode !== here) {
    document.getElementById('shop-overlay').style.display = 'none'; // walked away from the counter
  }
}

const shopPrompt = document.getElementById('shop-prompt');
let shopMode = 'main';
function nearShopMode() {
  if (drivingCarId) return null;
  const p = myAvatar.position;
  for (const m of Object.keys(SHOPS)) if (Math.hypot(p.x - SHOPS[m].x, p.z - SHOPS[m].z) < SHOPS[m].range) return m;
  return null;
}
function openShop() {
  if (document.getElementById('name-gate').style.display !== 'none') return;
  const here = nearShopMode();
  if (!here) { showToast('🛒 Walk up to the Shop, the chayakkada or the milk stall to buy things'); return; }
  shopMode = here;
  renderShop();
  document.getElementById('shop-overlay').style.display = 'flex';
}
shopPrompt.addEventListener('click', openShop);
addEventListener('keydown', e => {
  if (e.key.toLowerCase() === 'b' && !/INPUT|TEXTAREA/.test((e.target.tagName || ''))) openShop();
});

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

function checkTreasureProximity() {
  for (const id in chestMeshes) {
    const mesh = chestMeshes[id];
    if (!mesh.visible) continue;
    if (Math.hypot(mesh.userData.wx - myAvatar.position.x, mesh.userData.wz - myAvatar.position.z) < 2.2) {
      socket.emit('claimTreasure', id);
    }
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
  setTimeout(() => showToast('🔇 Sound and 🎤 mic are OFF — tap the round icons at top-left to turn them on'), 800);
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
  refreshOnlineCount();
});
socket.on('balloonCollected', ({ pickupId, by, balloons }) => {
  const mesh = balloonMeshes[pickupId];
  if (mesh) mesh.visible = false;
  if (by === socket.id) { balloonEl.textContent = balloons; refreshAffordability(); }
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
socket.on('carFreed', ({ carId }) => {
  const c = cars[carId];
  if (!c) return;
  c.occupiedBy = null;
  if (c.isPlane) { c.group.position.y = 0; c.group.rotation.z = 0; c.target = null; } // pilot left mid-air: park it
});
socket.on('carDenied', () => { /* someone else got there first — no action needed */ });

// --- Shared chayakkada radio + cart calls ---
socket.on('radioState', (s) => {
  radioState = { on: !!s.on, station: s.station | 0, stations: Array.isArray(s.stations) ? s.stations : [] };
  const n = radioState.stations[radioState.station];
  if (s.by) showToast(s.on ? `📻 ${s.by} tuned ${n ? n.name : 'the radio'}` : `📻 ${s.by} switched the radio off`);
});
socket.on('cartCall', ({ carId }) => { const c = cars[carId]; if (c && c.isCart) oxCall(c); });

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

// --- Treasure hunt ---
socket.on('treasureSpots', (spots) => { rebuildChests(spots); });
socket.on('treasureOpened', ({ chestId }) => { const m = chestMeshes[chestId]; if (m) m.visible = false; });
socket.on('treasureReward', ({ reward, balloons }) => {
  balloonEl.textContent = balloons;
  refreshAffordability();
  if (reward.type === 'balloons') showToast(`Chest opened! +${reward.amount} 🎈`);
  else if (reward.type === 'item') showToast(`Chest opened! Found ${itemLabel(reward.itemId)}`);
  else showToast(`Chest opened! Found a ${reward.name} ✨`);
});

// --- Shop / money economy ---
let shopCatalog = [];
let myInventory = {};
const CATEGORY_LABELS = {
  weapon: '🔫 Guns & Weapons', flower: '🌹 Flowers', food: '🍔 Food', drink: '🥤 Drinks',
  chayakkada: '🍵 Chaya & Palaharam', dairy: '🥛 Palu, Thairu & Nei', party: '🎉 Party', accessory: '🕶️ Accessories', clothing: '🧥 Clothing'
};
function itemLabel(itemId) {
  const item = shopCatalog.find(i => i.id === itemId);
  return item ? `${item.emoji} ${item.name}` : itemId;
}
socket.on('shopCatalog', (items) => { shopCatalog = items; renderShop(); });
socket.on('purchaseOk', ({ itemId, balloons, inventory }) => {
  balloonEl.textContent = balloons;
  myInventory = inventory;
  showToast(`Bought ${itemLabel(itemId)}!`);
  renderShop();
  renderInventory();
});
socket.on('purchaseDenied', ({ reason }) => {
  showToast(reason === 'insufficient' ? "Not enough balloons 🎈 for that" : reason === 'far' ? "Step up to the shop counter first 🛒" : "Purchase failed");
});
socket.on('inventoryUpdated', (inv) => { myInventory = inv; renderInventory(); });

function showToast(msg) {
  const stack = document.getElementById('toast-stack');
  const t = document.createElement('div');
  t.className = 'toast';
  t.textContent = msg;
  stack.appendChild(t);
  setTimeout(() => t.remove(), 3600);
}

function refreshAffordability() {
  if (document.getElementById('shop-overlay').style.display !== 'none') renderShop();
}

function renderShop() {
  const grid = document.getElementById('shop-grid');
  if (!grid) return;
  const balance = parseInt(balloonEl.textContent, 10) || 0;
  document.getElementById('shop-balance').textContent = `🎈 ${balance} balloons to spend`;
  grid.innerHTML = '';
  const byCategory = {};
  document.querySelector('#shop-header span').textContent = SHOPS[shopMode].title;
  shopCatalog.forEach(item => { if ((item.shop || 'main') !== shopMode) return; (byCategory[item.category] = byCategory[item.category] || []).push(item); });
  Object.entries(byCategory).forEach(([cat, items]) => {
    const label = document.createElement('div');
    label.className = 'shop-cat-label';
    label.textContent = CATEGORY_LABELS[cat] || cat;
    grid.appendChild(label);
    const row = document.createElement('div');
    row.className = 'shop-grid';
    items.forEach(item => {
      const afford = balance >= item.price;
      const div = document.createElement('div');
      div.className = 'shop-item';
      div.innerHTML = `
        <div class="emoji">${item.emoji}</div>
        <div class="name">${item.name}</div>
        <div class="price">🎈 ${item.price}</div>
        <button ${afford ? '' : 'disabled'}>${afford ? 'Buy' : 'Need more'}</button>
      `;
      div.querySelector('button').onclick = () => socket.emit('buyItem', item.id);
      row.appendChild(div);
    });
    grid.appendChild(row);
  });
}

function renderInventory() {
  const list = document.getElementById('inventory-list');
  if (!list) return;
  const entries = Object.entries(myInventory).filter(([, n]) => n > 0);
  if (!entries.length) { list.innerHTML = '<div class="inv-empty">No items yet — visit the Shop!</div>'; return; }
  list.innerHTML = '';
  entries.forEach(([itemId, count]) => {
    const item = shopCatalog.find(i => i.id === itemId) || { name: itemId, emoji: '📦' };
    const row = document.createElement('div');
    row.className = 'inv-row';
    row.innerHTML = `<div class="inv-left"><span>${item.emoji}</span><span>${item.name} × ${count}</span></div>`;
    list.appendChild(row);
  });
}

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
   7) VOICE — via LiveKit (audio is relayed through LiveKit's own
   servers rather than connecting players' devices directly to each
   other, which is what made voice fail on some mobile carrier networks)
   ========================================================= */
let lkRoom = null, micOn = false, voiceChain = Promise.resolve();
const micBtn = document.getElementById('mic-btn'), soundBtn = document.getElementById('sound-btn');

function paintVoiceBtns() {
  micBtn.classList.toggle('on', micOn); soundBtn.classList.toggle('on', soundOn);
  micBtn.innerHTML = micOn ? '<i class="fa-solid fa-microphone"></i>' : '<i class="fa-solid fa-microphone-slash"></i>';
  soundBtn.innerHTML = soundOn ? '<i class="fa-solid fa-volume-high"></i>' : '<i class="fa-solid fa-volume-xmark"></i>';
  micBtn.title = 'Microphone (' + (micOn ? 'on — others can hear you' : 'off') + ')';
  soundBtn.title = 'Sound (' + (soundOn ? 'on' : 'off') + ')';
}
function applyRemoteAudio() {
  document.querySelectorAll('audio.lk-voice-audio').forEach(el => { el.muted = !soundOn; if (soundOn && el.paused) el.play().catch(() => {}); });
}
async function connectVoice() {
  const res = await fetch(`/livekit-token?identity=${encodeURIComponent(socket.id)}`);
  let data; try { data = await res.json(); } catch (e) { throw new Error('the voice service is not reachable on this server'); }
  if (!res.ok || !data.token) throw new Error(data.error || 'no voice token');
  const room = new LivekitClient.Room({ adaptiveStream: true, dynacast: true });
  room.on(LivekitClient.RoomEvent.TrackSubscribed, (track) => {
    if (track.kind !== 'audio') return;
    const el = track.attach(); el.className = 'lk-voice-audio'; el.style.display = 'none'; el.muted = !soundOn;
    document.body.appendChild(el); if (soundOn) el.play().catch(() => {});
  });
  room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track) => { track.detach().forEach(el => el.remove()); });
  room.on(LivekitClient.RoomEvent.Disconnected, () => { if (lkRoom === room) lkRoom = null; });
  await room.connect(data.url, data.token);
  lkRoom = room;
  try { await room.startAudio(); } catch (e) {}
}
// Mic and Sound are independent: you can listen to others with your mic off. The voice room is joined only when either is on.
function syncVoice() {
  voiceChain = voiceChain.then(async () => {
    const want = micOn || soundOn;
    try {
      if (want && !lkRoom) await connectVoice();
      if (!want && lkRoom) { const r = lkRoom; lkRoom = null; await r.disconnect(); document.querySelectorAll('audio.lk-voice-audio').forEach(el => el.remove()); }
      if (lkRoom) { await lkRoom.localParticipant.setMicrophoneEnabled(micOn); applyRemoteAudio(); }
    } catch (e) {
      if (micOn) { micOn = false; if (lkRoom) { try { await lkRoom.localParticipant.setMicrophoneEnabled(false); } catch (e2) {} } }
      showToast('🎤 Voice chat unavailable: ' + (e.message || e));
      paintVoiceBtns();
    }
  });
}
micBtn.onclick = () => { micOn = !micOn; paintVoiceBtns(); syncVoice(); };
soundBtn.onclick = () => {
  soundOn = !soundOn;
  if (soundOn) { ensureAudio(); if (radioLoaded !== -1) radioAudio.play().catch(() => {}); }
  else if (actx) actx.suspend();
  paintVoiceBtns(); applyRemoteAudio(); syncVoice();
};
paintVoiceBtns();

/* =========================================================
   8) MAP + STATS + SHOP + INVENTORY PANELS
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

  mapCtx.fillStyle = '#FFD700';
  Object.values(chestMeshes).forEach(m => {
    if (!m.visible) return;
    const { px, py } = worldToMap(m.userData.wx, m.userData.wz);
    mapCtx.fillRect(px-4, py-4, 8, 8);
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

  mapCtx.font = '15px serif'; mapCtx.textAlign = 'center'; mapCtx.textBaseline = 'middle';
  { const t = worldToMap(TEA_POS.x, TEA_POS.z); mapCtx.fillText('🍵', t.px, t.py);
    const s = worldToMap(SHOP_POS.x, SHOP_POS.z); mapCtx.fillText('🛒', s.px, s.py);
    const m = worldToMap(MILK_POS.x, MILK_POS.z); mapCtx.fillText('🥛', m.px, m.py);
    Object.values(cars).forEach(c => { if (c.isCart) { const q = worldToMap(c.group.position.x, c.group.position.z); mapCtx.fillText('🐂', q.px, q.py); } }); }
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

document.getElementById('shop-btn').onclick = openShop;
document.getElementById('shop-close').onclick = () => { document.getElementById('shop-overlay').style.display = 'none'; };
document.getElementById('inventory-btn').onclick = () => { renderInventory(); document.getElementById('inventory-overlay').style.display = 'flex'; };
document.getElementById('inventory-close').onclick = () => { document.getElementById('inventory-overlay').style.display = 'none'; };

/* =========================================================
   9) RENDER LOOP
   ========================================================= */
const clock = new THREE.Clock();
function animate() {
  requestAnimationFrame(animate);
  const t = clock.getElapsedTime();
  waterTex.offset.y = t * 0.05;
  Object.values(cars).forEach(c => { if (c.prop && c.occupiedBy && c.occupiedBy !== socket.id) c.prop.rotation.x += 0.6; });
  updateMovement();
  keralaTick(t);
  updateWaypointReadout();
  if (waypointBeacon.visible) waypointBeacon.position.y = 1.2 + Math.sin(t*3)*0.15;
  if (mapOpen) drawMap();
  Object.values(balloonMeshes).forEach(m => { if (m.visible) m.position.y = 1 + Math.sin(t*2 + m.userData.bobOffset)*0.2; });
  Object.values(chestMeshes).forEach(m => { if (m.visible) m.position.y = 0.9 + Math.sin(t*1.6 + m.userData.bobOffset)*0.12; });
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
