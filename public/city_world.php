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
  .role-pill{ font-size:.7em; padding:8px 4px; } .role-pill:disabled{ opacity:.4; cursor:not-allowed; }
  /* 80s film look: warm sepia grade + vignette (press V to toggle) */
  #vignette{ position:absolute; inset:0; pointer-events:none; z-index:2; background:radial-gradient(ellipse at center, rgba(0,0,0,0) 55%, rgba(60,30,0,.38) 100%); }
  #canvas-wrap canvas{ filter:sepia(.2) saturate(1.1) contrast(1.05); }
  body.novintage #canvas-wrap canvas{ filter:none; } body.novintage #vignette{ display:none; }

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
    /* ==== HEALTH mobile tweaks ==== */
    #health-pill { padding:6px 10px; font-size:.72em; }
    #health-bar { width:60px; }
    #ambulance-btn { top:56px; font-size:.78em; padding:9px 16px; }
  }

  /* ================= ACCOUNT / LOGIN POPUP ================= */
  #account-pill{ pointer-events:auto; display:flex; align-items:center; gap:8px; padding:6px 6px 6px 12px; border-radius:20px; font-size:.8em; font-weight:700;
    background:var(--card); border:1px solid var(--border); box-shadow:0 6px 18px rgba(0,0,0,.4); color:var(--text); max-width:170px; }
  #account-name{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  #account-pill button{ border:none; border-radius:14px; padding:6px 12px; font-weight:700; font-size:.95em; cursor:pointer; color:#fff; font-family:inherit;
    background:linear-gradient(135deg,#FE019A,#9b5de5); }
  #account-pill button.ghost{ background:var(--surface); color:var(--muted); border:1px solid var(--border); }
  #login-modal{ position:absolute; inset:0; z-index:40; display:none; align-items:center; justify-content:center; padding:16px;
    background:rgba(5,2,10,.78); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); overflow-y:auto; }
  #login-modal.open{ display:flex; }
  #login-card{ position:relative; background:#fff; color:#333; width:min(100%,400px); border-radius:18px; padding:26px 26px 22px;
    box-shadow:0 20px 60px rgba(254,1,154,.35); font-family:'Poppins','Space Grotesk',sans-serif; margin:auto; }
  #login-close{ position:absolute; top:10px; right:12px; width:36px; height:36px; border:none; background:none; font-size:1.1em; color:#999; cursor:pointer; border-radius:50%; }
  #login-close:hover{ background:#f5f5f5; }
  #login-head{ display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:4px; }
  #login-head img{ width:42px; height:42px; object-fit:contain; }
  #login-head h2{ margin:0; color:#FE019A; font-size:1.45em; font-weight:700; font-family:'Poppins','Space Grotesk',sans-serif; }
  #login-why{ text-align:center; font-size:.86em; color:#666; margin:2px 0 16px; line-height:1.4; }
  #login-error{ display:none; background:#ffebeb; color:#cc0000; border:1px solid #ffcccc; padding:10px 12px; border-radius:10px; font-size:.85em; text-align:center; margin-bottom:12px; }
  #login-card input[type=email], #login-card input[type=password], #login-card input[type=text]{
    width:100%; padding:13px 14px; border:1px solid #FFD9EF; border-radius:12px; font-size:16px; color:#333; background:#fff; font-family:inherit; outline:none; margin:5px 0; }
  #login-card input:focus{ border-color:#FE019A; box-shadow:0 0 0 3px rgba(254,1,154,.18); }
  .lg-pw{ position:relative; } .lg-pw input{ padding-right:48px !important; }
  #lg-eye{ position:absolute; right:4px; top:50%; transform:translateY(-50%); width:42px; height:42px; border:none; background:none; font-size:1.15em; cursor:pointer; }
  .lg-remember{ display:flex; align-items:center; gap:8px; margin:10px 2px 2px; font-size:.86em; color:#333; cursor:pointer; user-select:none; }
  .lg-remember input{ width:18px; height:18px; accent-color:#FE019A; }
  #lg-submit{ width:100%; margin-top:16px; padding:14px; min-height:48px; border:none; border-radius:12px; background:#FE019A; color:#fff; font-weight:700; font-size:16px; cursor:pointer; font-family:inherit; }
  #lg-submit:hover{ background:#D90085; } #lg-submit:disabled{ opacity:.6; cursor:wait; }
  .lg-links{ text-align:center; font-size:.85em; margin-top:14px; color:#444; line-height:1.9; }
  .lg-links a{ color:#FE019A; font-weight:700; text-decoration:none; } .lg-links a:hover{ text-decoration:underline; }
  .lg-guest{ display:block; width:100%; margin-top:6px; padding:10px; background:none; border:none; color:#999; font-size:.82em; cursor:pointer; font-family:inherit; }
  #chat-input[readonly]{ cursor:pointer; }
  @media (max-width:420px){ #login-card{ padding:22px 18px 18px; } #account-pill{ max-width:130px; } }

  /* ===== Phase 2: signed-in gate + character settings ===== */
  #name-gate.checking .card{ display:none; }
  #gate-loading{ display:none; color:var(--muted); font-size:.9em; font-weight:700; text-align:center; }
  #name-gate.checking #gate-loading{ display:block; }
  #gate-account{ display:none; background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:10px 14px; margin:0 0 14px; text-align:center; }
  #gate-account b{ display:block; font-size:1.05em; } #gate-account span{ color:var(--muted); font-size:.8em; }
  #name-gate.member #gate-account{ display:block; }
  #name-gate.member #name-input{ display:none; }
  #name-gate.member .role-block{ display:none; }
  .set-sec{ margin-top:12px; padding-top:10px; border-top:1px solid var(--border); }
  .set-sec h4{ margin:0 0 8px; font-size:.8em; color:var(--muted); font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
  .set-row{ display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
  .set-pill{ flex:1 1 auto; padding:9px 10px; min-height:40px; border-radius:12px; border:2px solid var(--border); background:var(--surface); color:var(--text); font-weight:700; font-size:.8em; cursor:pointer; font-family:inherit; }
  .set-pill.selected{ border-color:var(--pk); background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  .set-swatch{ width:36px; height:36px; border-radius:50%; border:3px solid transparent; cursor:pointer; }
  .set-swatch.selected{ border-color:#fff; box-shadow:0 0 0 2px var(--pk); }
  .set-note{ font-size:.72em; color:var(--muted); margin:2px 0 0; }
  #stats-panel{ max-height:88vh; overflow-y:auto; }

  /* ===== Phase 3: private voice rooms (room ID + passcode) ===== */
  #private-btn{ position:absolute; top:64px; left:462px; z-index:5; background:var(--card); color:var(--text); border:1px solid var(--border);
    border-radius:24px; padding:9px 16px; font-weight:700; font-size:.82em; cursor:pointer; }
  #private-overlay{ position:absolute; inset:0; z-index:22; display:none; align-items:center; justify-content:center; padding:12px; background:rgba(5,2,10,.78); }
  #private-overlay.open{ display:flex; }
  #private-panel{ background:var(--card); border:1px solid var(--border); border-radius:18px; padding:16px; width:min(94vw,440px); max-height:92vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,.6); }
  #private-head{ display:flex; justify-content:space-between; align-items:center; font-weight:800; margin-bottom:8px; }
  #private-head button{ background:none; border:none; color:var(--muted); font-size:1.1em; cursor:pointer; width:36px; height:36px; }
  .priv-tabs{ display:flex; gap:6px; margin-bottom:6px; }
  .priv-tabs button{ flex:1; padding:10px; min-height:42px; border-radius:12px; border:2px solid var(--border); background:var(--surface); color:var(--text); font-weight:800; cursor:pointer; font-family:inherit; font-size:.85em; }
  .priv-tabs button.on{ border-color:var(--pk); background:linear-gradient(135deg,rgba(254,1,154,.35),rgba(155,93,229,.35)); }
  .priv-lbl{ font-size:.74em; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin:12px 0 6px; }
  #priv-places{ display:grid; grid-template-columns:1fr 1fr; gap:8px; }
  .priv-place{ border:2px solid var(--border); background:var(--surface); color:var(--text); border-radius:14px; padding:12px 8px; font-weight:700; cursor:pointer; font-family:inherit; font-size:.85em; min-height:64px; }
  .priv-place .em{ display:block; font-size:1.6em; margin-bottom:2px; }
  .priv-place.selected{ border-color:var(--pk); background:linear-gradient(135deg,rgba(254,1,154,.35),rgba(155,93,229,.35)); }
  #priv-size{ display:flex; gap:6px; }
  #priv-size button{ flex:1; min-height:42px; border-radius:12px; border:2px solid var(--border); background:var(--surface); color:var(--text); font-weight:800; cursor:pointer; font-family:inherit; }
  #priv-size button.selected{ border-color:var(--pk); background:linear-gradient(135deg,#FE019A,#9b5de5); color:#fff; }
  .priv-note{ font-size:.72em; color:var(--muted); margin:10px 0 0; line-height:1.45; }
  .priv-main{ width:100%; margin-top:12px; padding:13px; min-height:46px; border:none; border-radius:12px; color:#fff; font-weight:800; font-size:.95em; cursor:pointer; font-family:inherit; background:linear-gradient(135deg,#FE019A,#9b5de5); }
  .priv-main:disabled{ opacity:.45; cursor:not-allowed; }
  .priv-code{ background:var(--surface); border:2px dashed var(--pk); border-radius:14px; padding:10px 14px; margin-top:8px; text-align:center; }
  .priv-code small{ display:block; color:var(--muted); font-size:.7em; text-transform:uppercase; letter-spacing:.05em; }
  .priv-code b{ font-size:1.7em; letter-spacing:.18em; font-family:'Space Grotesk',monospace; }
  .priv-row2{ display:flex; gap:8px; margin-top:10px; } .priv-row2 .priv-main{ margin-top:0; font-size:.82em; }
  .priv-link{ word-break:break-all; font-size:.74em; color:var(--muted); margin-top:8px; text-align:center; }
  #pv-join input{ width:100%; padding:13px 14px; border:2px solid var(--border); border-radius:12px; background:var(--surface); color:var(--text); font-size:16px; font-family:inherit; outline:none; margin-top:6px; text-transform:uppercase; letter-spacing:.12em; font-weight:700; }
  #pv-join input#pj-pass{ letter-spacing:.3em; } #pv-join input:focus{ border-color:var(--pk); }
  #pj-found{ margin-top:8px; font-size:.86em; font-weight:700; text-align:center; min-height:1.2em; }
  #private-bar{ position:absolute; z-index:15; top:112px; left:50%; transform:translateX(-50%); display:none; align-items:center; gap:8px; flex-wrap:wrap; justify-content:center;
    max-width:min(94vw,520px); padding:8px 10px 8px 14px; border-radius:22px; background:linear-gradient(135deg,rgba(254,1,154,.92),rgba(155,93,229,.92)); color:#fff; font-size:.8em; font-weight:700; box-shadow:0 8px 24px rgba(0,0,0,.45); }
  #private-bar.open{ display:flex; }
  #private-bar button{ border:none; border-radius:16px; padding:8px 12px; min-height:36px; font-weight:800; cursor:pointer; font-family:inherit; font-size:.95em; background:rgba(255,255,255,.2); color:#fff; }
  #private-bar button.leave{ background:#fff; color:#d90085; }
  @media (max-width:700px){ #private-btn{ top:294px; left:auto; right:14px; } #private-bar{ top:104px; } }

  /* ===== Fun & social: slap / hold hands / sit (consent prompts reuse the kiss-prompt look) ===== */
  #hand-prompt{ display:none; position:absolute; left:50%; top:90px; transform:translateX(-50%); z-index:9; background:rgba(60,15,40,.92);
    border:2px solid #FE019A; border-radius:16px; padding:12px 16px; color:#fff; text-align:center; max-width:90vw; }
  #hand-prompt p{ margin:0 0 8px; font-weight:800; } #hand-prompt .row{ display:flex; gap:8px; justify-content:center; }
  #hand-prompt button{ border:none; border-radius:10px; padding:9px 14px; min-height:40px; font-weight:800; cursor:pointer; font-family:inherit; }
  #hp-yes{ background:#FE019A; color:#fff; } #hp-no{ background:#444; color:#fff; }
  #sit-prompt{ display:none; position:absolute; left:50%; bottom:190px; transform:translateX(-50%); z-index:6; background:rgba(20,15,10,.85); color:#fff; padding:10px 20px; border:none; border-radius:20px; font-weight:700; font-size:.85em; cursor:pointer; }
  #forced-mute-note{ display:none; position:absolute; top:10px; left:50%; transform:translateX(-50%); z-index:30; background:#c0392b; color:#fff;
    font-weight:800; padding:8px 16px; border-radius:20px; font-size:.82em; }

  /* ===== Register + Verification popups (same look as the login popup) ===== */
  .auth-modal{ position:absolute; inset:0; z-index:40; display:none; align-items:center; justify-content:center; padding:16px;
    background:rgba(5,2,10,.78); backdrop-filter:blur(3px); -webkit-backdrop-filter:blur(3px); overflow-y:auto; }
  .auth-modal.open{ display:flex; }
  .auth-card{ position:relative; background:#fff; color:#333; width:min(100%,420px); border-radius:18px; padding:24px 24px 20px;
    box-shadow:0 20px 60px rgba(254,1,154,.35); font-family:'Poppins','Space Grotesk',sans-serif; margin:auto; }
  .auth-card .ac-close{ position:absolute; top:10px; right:12px; width:36px; height:36px; border:none; background:none; font-size:1.1em; color:#999; cursor:pointer; border-radius:50%; }
  .auth-card .ac-close:hover{ background:#f5f5f5; }
  .ac-head{ display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:4px; }
  .ac-head img{ width:40px; height:40px; object-fit:contain; }
  .ac-head h2{ margin:0; color:#FE019A; font-size:1.35em; font-weight:700; }
  .ac-why{ text-align:center; font-size:.86em; color:#666; margin:2px 0 14px; line-height:1.4; }
  .ac-error{ display:none; background:#ffebeb; color:#cc0000; border:1px solid #ffcccc; padding:10px 12px; border-radius:10px; font-size:.85em; text-align:center; margin-bottom:10px; }
  .ac-ok{ display:none; background:#e6ffe6; color:#0a7a2f; border:1px solid #ccffcc; padding:10px 12px; border-radius:10px; font-size:.85em; text-align:center; margin-bottom:10px; }
  .auth-card input[type=text], .auth-card input[type=email], .auth-card input[type=password], .auth-card input[type=date], .auth-card select{
    width:100%; padding:12px 14px; border:1px solid #FFD9EF; border-radius:12px; font-size:16px; color:#333; background:#fff; font-family:inherit; outline:none; margin:4px 0; box-sizing:border-box; }
  .auth-card input:focus, .auth-card select:focus{ border-color:#FE019A; box-shadow:0 0 0 3px rgba(254,1,154,.18); }
  .ac-row{ display:flex; gap:8px; } .ac-row > *{ flex:1; min-width:0; }
  .ac-lbl{ font-size:.78em; color:#666; margin:8px 2px 0; font-weight:600; }
  .ac-fb{ font-size:.78em; min-height:1.1em; margin:0 2px 4px; } .ac-fb.good{ color:#059669; } .ac-fb.bad{ color:#dc2626; } .ac-fb.neu{ color:#777; }
  .ac-btn{ width:100%; margin-top:14px; padding:14px; min-height:48px; border:none; border-radius:12px; background:#FE019A; color:#fff; font-weight:700; font-size:16px; cursor:pointer; font-family:inherit; }
  .ac-btn:hover{ background:#D90085; } .ac-btn:disabled{ opacity:.6; cursor:wait; }
  .ac-btn.ghost{ background:#fff; color:#FE019A; border:2px solid #FE019A; margin-top:8px; } .ac-btn.ghost:disabled{ cursor:not-allowed; }
  .ac-terms{ display:flex; align-items:flex-start; gap:8px; margin:12px 2px 0; font-size:.78em; color:#555; line-height:1.4; }
  .ac-terms input{ width:18px; height:18px; margin-top:2px; accent-color:#FE019A; flex-shrink:0; }
  .ac-terms a{ color:#FE019A; font-weight:600; text-decoration:none; }
  #vf-code{ text-align:center; font-size:28px !important; letter-spacing:.45em; font-weight:700; padding:14px 8px !important; }
  .ac-mail{ text-align:center; font-size:2.2em; margin-bottom:2px; }

  /* ================= HEALTH / AMBULANCE / HOSPITAL ================= */
  #health-pill {
    pointer-events:auto; display:flex; align-items:center; gap:8px;
    padding:7px 12px; border-radius:20px; font-size:.8em; font-weight:700;
    background:var(--card); border:1px solid var(--border); box-shadow:0 6px 18px rgba(0,0,0,.4);
  }
  #health-pill i { color:#ff4d6d; }
  #health-pill.critical { animation:hpPulse 0.8s infinite; }
  @keyframes hpPulse { 50% { box-shadow:0 0 0 4px rgba(255,77,109,.35); } }
  #health-bar { width:78px; height:8px; background:rgba(0,0,0,.45); border-radius:4px; overflow:hidden; }
  #health-fill { height:100%; width:100%; background:linear-gradient(90deg,#00f593,#7ee787); transition:width .25s, background .25s; }
  #health-val { min-width:28px; text-align:right; color:#fff; }

  #ambulance-btn {
    position:absolute; top:110px; left:50%; transform:translateX(-50%); z-index:7;
    display:none; padding:11px 20px; border:none; border-radius:22px; font-weight:800; font-size:.88em;
    color:#fff; background:linear-gradient(135deg,#d62828,#ff4d6d); cursor:pointer;
    box-shadow:0 8px 24px rgba(214,40,40,.5); animation:ambPulse 1s infinite;
  }
  @keyframes ambPulse { 50% { transform:translateX(-50%) scale(1.06); } }

  #hurt-flash {
    position:absolute; inset:0; pointer-events:none; z-index:40;
    background:radial-gradient(circle,transparent 40%,rgba(200,0,30,.55) 100%);
    opacity:0; transition:opacity .15s;
  }
  #hurt-flash.on { opacity:1; }

  #death-overlay {
    position:absolute; inset:0; z-index:45; display:none; align-items:center; justify-content:center;
    background:rgba(20,0,0,.75); backdrop-filter:blur(4px);
  }
  #death-overlay.open { display:flex; }
  .death-card { text-align:center; color:#fff; font-family:'Syne',sans-serif; }
  .death-skull { font-size:4.5em; animation:skullBounce 0.9s infinite alternate; }
  @keyframes skullBounce { from{transform:translateY(-8px);} to{transform:translateY(8px);} }
  .death-title { font-size:1.6em; font-weight:800; margin-top:8px; }
  .death-sub { color:#ffb3b3; margin-top:6px; font-size:.95em; }

  #hospital-overlay {
    position:absolute; inset:0; z-index:46; display:none; align-items:center; justify-content:center;
    background:
      radial-gradient(circle at 20% 20%, rgba(120,200,220,.15), transparent 60%),
      radial-gradient(circle at 80% 80%, rgba(200,220,255,.15), transparent 60%),
      linear-gradient(180deg, #0d1b2a 0%, #071622 100%);
  }
  #hospital-overlay.open { display:flex; }
  .hosp-room {
    width:min(92vw, 460px); padding:26px 22px; border-radius:22px;
    background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    border:1px solid rgba(255,255,255,.14); box-shadow:0 25px 60px rgba(0,0,0,.6);
    text-align:center; color:#eaf3fb; position:relative; overflow:hidden;
  }
  .hosp-room::before {
    content:""; position:absolute; inset:0;
    background:repeating-linear-gradient(45deg, rgba(255,255,255,.03) 0 12px, transparent 12px 24px);
    pointer-events:none;
  }
  .hosp-monitor {
    width:110px; height:70px; margin:0 auto 12px; border-radius:10px;
    background:linear-gradient(180deg,#03161a,#0a2b30); border:2px solid #1f4c55;
    position:relative;
  }
  .hosp-monitor::after {
    content:""; position:absolute; left:8px; right:8px; top:50%;
    height:2px; background:#00f593; box-shadow:0 0 8px #00f593;
    animation:ekg 1.4s linear infinite;
  }
  @keyframes ekg {
    0%   { transform:translateY(0) scaleX(1);   opacity:.3; }
    20%  { transform:translateY(-8px);          opacity:1; }
    30%  { transform:translateY(6px);           opacity:1; }
    45%  { transform:translateY(0) scaleX(.6);  opacity:1; }
    100% { transform:translateY(0) scaleX(1);   opacity:.4; }
  }
  .hosp-title { font-family:'Syne',sans-serif; font-weight:800; font-size:1.25em; color:#7fe3ff; }
  .hosp-sub { color:#a9c6d8; font-size:.86em; margin-top:4px; }
  .hosp-count {
    font-family:'Space Grotesk',monospace; font-size:3.4em; font-weight:800; margin:8px 0 4px;
    background:linear-gradient(135deg,#7fe3ff,#ff80d5); -webkit-background-clip:text; -webkit-text-fill-color:transparent;
  }
  .hosp-note { color:#9fb6c6; font-size:.82em; line-height:1.5; margin-top:6px; }
  .hosp-note b { color:#ffd166; }
  #hosp-discharge-early {
    width:100%; margin-top:14px; padding:12px; border:none; border-radius:12px;
    background:rgba(255,255,255,.08); color:#7d93a4; font-weight:700; font-size:.85em; cursor:not-allowed;
    font-family:inherit;
  }
</style>
</head>
<body>

<div id="name-gate" class="checking">
  <div id="gate-loading">Signing you in…</div>
  <div class="card">
    <h3>Neyyappam City</h3>
    <p class="sub">Kerala edition — sip chaya at the chayakkada, ride a kaalavandi, collect balloons</p>
    <div id="gate-account"><b id="gate-disp">—</b><span id="gate-user"></span><br><span>Your city name comes from your account</span></div>
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
    <div class="role-block"><div class="picker-label">Play as</div></div>
    <div class="gender-row role-block">
      <button type="button" class="gender-pill role-pill selected" data-role="visitor">Visitor</button>
      <button type="button" class="gender-pill role-pill" data-role="chayakkaran">🍵 Chayakkadakkaran</button>
      <button type="button" class="gender-pill role-pill" data-role="karavakkari">🥛 Karavakkari chechi</button>
    </div>
    <div class="note role-block">Run the tea shop or the milk stall as a real player — customers tip you half of every sale.</div>
    <button class="enter" id="join-btn">Enter City</button>
    <div class="note" id="save-status">Guest mode — balloons won't be saved to an account.</div>
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
    <div id="account-pill"><span id="account-name">Guest</span><button id="account-action" type="button">Log in</button></div>
    <div id="balloon-pill"><i class="fa-solid fa-circle" style="border-radius:50%;"></i> 🎈 <span id="balloon-val">0</span></div>
    <div id="cash-pill" style="background:#12301a;color:#fff;border-radius:20px;padding:6px 12px;font-weight:700;margin-left:6px">💵 <span id="cash-val">0</span></div>
    <button onclick="document.getElementById('car-shop').style.display='block';renderCarShop()" style="margin-left:6px;border:0;border-radius:20px;padding:6px 12px;background:#e91e8c;color:#fff;font-weight:700;cursor:pointer">🚗 Car Shop</button>
    <div id="car-shop" style="display:none;position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);width:min(380px,92vw);background:#1b1030;color:#fff;border-radius:16px;padding:16px;z-index:60"><div style="display:flex;justify-content:space-between"><b>🚗 Car Shop</b><span onclick="document.getElementById('car-shop').style.display='none'" style="cursor:pointer">✖</span></div><div id="car-shop-body"></div></div>
  </div>
</div>

<button id="mic-btn" title="Microphone (off)"><i class="fa-solid fa-microphone-slash"></i></button>
<button id="sound-btn" title="Sound (off)"><i class="fa-solid fa-volume-xmark"></i></button>
<button id="map-btn"><i class="fa-solid fa-map"></i> Map</button>
<button id="stats-btn"><i class="fa-solid fa-user"></i> Stats</button>
<button id="shop-btn"><i class="fa-solid fa-store"></i> Shop</button>
<button id="inventory-btn"><i class="fa-solid fa-bag-shopping"></i> Bag</button>
<button id="private-btn"><i class="fa-solid fa-lock"></i> Private</button>

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
    <div class="hosp-info">
      <div class="hosp-title">🏥 Neyyappam General Hospital</div>
      <div class="hosp-sub" id="hosp-sub">Doctors are treating you…</div>
      <div class="hosp-count" id="hosp-count">25</div>
      <div class="hosp-note">
        You'll be discharged with <b>40% health</b>.<br>
        Buy <b>💊 medicine</b> at the pharmacy to heal faster, or wait — health slowly recovers over 10 min.
      </div>
      <button id="hosp-discharge-early" disabled>Discharge in 25s…</button>
    </div>
  </div>
</div>

<div id="hurt-flash"></div>

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
    <div class="stat-row"><span>Health</span><span id="stat-health">100%</span></div>
    <div class="stat-row"><span>Balloons</span><span id="stat-balloons">0</span></div>
    <div class="stat-row"><span>Distance traveled</span><span id="stat-distance">0 m</span></div>
    <div class="stat-row"><span>Deliveries completed</span><span id="stat-deliveries">0</span></div>
    <div class="stat-row"><span>Cars driven</span><span id="stat-cars">0</span></div>
    <div class="set-sec" id="set-sec">
      <h4>Character settings</h4>
      <div class="set-row" id="set-gender">
        <button type="button" class="set-pill" data-sg="male">Male</button>
        <button type="button" class="set-pill" data-sg="female">Female</button>
        <button type="button" class="set-pill" data-sg="other">Other</button>
      </div>
      <div class="set-row" id="set-color">
        <button type="button" class="set-swatch" data-sc="#FE019A" style="background:#FE019A" aria-label="Pink"></button>
        <button type="button" class="set-swatch" data-sc="#3b82f6" style="background:#3b82f6" aria-label="Blue"></button>
        <button type="button" class="set-swatch" data-sc="#3fae55" style="background:#3fae55" aria-label="Green"></button>
        <button type="button" class="set-swatch" data-sc="#9b5de5" style="background:#9b5de5" aria-label="Purple"></button>
        <button type="button" class="set-swatch" data-sc="#f2c230" style="background:#f2c230" aria-label="Yellow"></button>
        <button type="button" class="set-swatch" data-sc="#d83c3c" style="background:#d83c3c" aria-label="Red"></button>
      </div>
      <div class="set-row" id="set-hair">
        <button type="button" class="set-pill" data-sh="short">Short</button>
        <button type="button" class="set-pill" data-sh="pony">Pony</button>
        <button type="button" class="set-pill" data-sh="bandana">Bandana</button>
      </div>
      <div class="set-row" id="set-role">
        <button type="button" class="set-pill" data-sr="visitor">Visitor</button>
        <button type="button" class="set-pill" data-sr="chayakkaran">🍵 Chaya</button>
        <button type="button" class="set-pill" data-sr="karavakkari">🥛 Karavakkari</button>
      </div>
      <p class="set-note" id="set-note">Your name comes from your account and can't be changed here. Changing "Play as" reloads the city.</p>
    </div>
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
<div id="social-ui" style="display:none; position:absolute; left:50%; bottom:250px; transform:translateX(-50%); z-index:6; gap:6px;">
  <button id="kiss-btn" class="rbtn">💋 Kiss (K)</button>
  <button id="slap-btn" class="rbtn">🖐️ Slap</button>
</div>
<div id="emote-bar" style="display:none; position:absolute; right:16px; bottom:220px; z-index:6; flex-direction:column; gap:6px;">
  <button id="emote-dance" class="rbtn" title="Dance (X)">💃</button>
  <button id="emote-laugh" class="rbtn" title="Laugh (L)">😂</button>
  <button id="emote-aiyyo" class="rbtn" title="Aiyyo! (Z)">😱</button>
  <button id="hand-btn" class="rbtn" title="Hold hands (J)">🤝</button>
</div>
<div id="forced-mute-note">🔇 A moderator has muted your mic in this room</div>
<div id="sit-prompt" style="display:none">Press E to sit</div>
<div id="hand-prompt"><p id="hp-text">—</p><div class="row"><button id="hp-no">Not now</button><button id="hp-yes">🤝 Hold hands</button></div></div>
<div id="kiss-prompt" style="display:none; position:absolute; left:50%; top:90px; transform:translateX(-50%); z-index:9; background:rgba(60,15,40,.94); color:#fff; padding:12px 16px; border-radius:16px; text-align:center; font-weight:700; font-size:.85em;">
  <div id="kiss-text">💋</div>
  <div style="display:flex; gap:8px; margin-top:8px; justify-content:center; flex-wrap:wrap;">
    <button id="kiss-back" class="rbtn">💋 Kiss back</button><button id="kiss-ignore" class="rbtn">Ignore</button><button id="kiss-block" class="rbtn">🚫 No kisses</button>
  </div>
</div>
<div id="radio-chip" style="display:none; position:absolute; top:140px; left:50%; transform:translateX(-50%); z-index:6; background:rgba(60,35,12,.88); color:#ffd166; padding:6px 14px; border-radius:16px; font-weight:700; font-size:.78em; pointer-events:none;"></div>

<div id="joystick-zone">
  <div id="joystick-base"></div>
  <div id="joystick-stick"></div>
</div>


<div id="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
  <div id="login-card">
    <button id="login-close" type="button" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    <div id="login-head">
      <img src="https://neyyappam.com/assets/logos/neyyappamicon.png" alt="" onerror="this.style.display='none'">
      <h2 id="login-title">Neyyappam.com</h2>
    </div>
    <p id="login-why">Log in to use the mic and chat in Neyyappam City.</p>
    <div id="login-error" role="alert"></div>
    <form id="login-form" autocomplete="on">
      <input type="email" id="lg-email" name="email" placeholder="Email Address" autocomplete="username" autocapitalize="off" required>
      <div class="lg-pw">
        <input type="password" id="lg-pass" name="password" placeholder="Password" autocomplete="current-password" required>
        <button type="button" id="lg-eye" aria-label="Show password">👁️</button>
      </div>
      <label class="lg-remember"><input type="checkbox" id="lg-remember" checked> Keep me signed in</label>
      <button type="submit" id="lg-submit">Login</button>
    </form>
    <div class="lg-links">
      <a id="lg-forgot" href="#" target="_blank" rel="noopener">Forgot Password?</a><br>
      New here? <a id="lg-register" href="#" target="_blank" rel="noopener">Create an account</a>
    </div>
    <button type="button" class="lg-guest" id="lg-guest">Continue as guest (no mic or chat)</button>
  </div>
</div>

<!-- REGISTER popup: replaces the login popup when "Create an account" is clicked -->
<div id="reg-modal" class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="reg-title">
  <div class="auth-card">
    <button class="ac-close" type="button" id="reg-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    <div class="ac-head"><img src="https://neyyappam.com/assets/logos/neyyappamicon.png" alt="" onerror="this.style.display='none'"><h2 id="reg-title">Join Neyyappam</h2></div>
    <p class="ac-why">Create your account here — it works on neyyappam.com too.</p>
    <div class="ac-error" id="reg-error" role="alert"></div>
    <form id="reg-form" autocomplete="on">
      <div class="ac-row">
        <input type="text" name="first_name" placeholder="First Name" maxlength="30" required autocomplete="given-name">
        <input type="text" name="last_name" placeholder="Last Name" maxlength="30" required autocomplete="family-name">
      </div>
      <input type="text" name="username" id="rg-username" placeholder="Username (a-z, 0-9, _, .)" maxlength="30" required autocomplete="username" autocapitalize="off">
      <div class="ac-fb neu" id="rg-user-fb"></div>
      <div class="ac-lbl">Date of birth</div>
      <input type="date" name="dob" id="rg-dob" required>
      <select name="gender" required>
        <option value="" disabled selected hidden>Select Gender</option>
        <option value="male">Male</option><option value="female">Female</option><option value="other">Other</option>
      </select>
      <input type="text" name="location" placeholder="Location" maxlength="30" required autocomplete="address-level2">
      <input type="email" name="email" placeholder="Email Address" required autocomplete="email" autocapitalize="off">
      <div class="lg-pw"><input type="password" name="password" id="rg-pass" placeholder="Password (min 8 characters)" minlength="8" required autocomplete="new-password"><button type="button" id="rg-eye" aria-label="Show password" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);width:42px;height:42px;border:none;background:none;font-size:1.15em;cursor:pointer;">👁️</button></div>
      <input type="password" name="confirm_password" id="rg-pass2" placeholder="Confirm Password" minlength="8" required autocomplete="new-password">
      <div class="ac-fb neu" id="rg-pass-fb"></div>
      <label class="ac-terms"><input type="checkbox" name="agree_terms" required>
        <span>By clicking Register, you agree to our <a id="rg-terms" href="#" target="_blank" rel="noopener">Terms</a>, <a id="rg-privacy" href="#" target="_blank" rel="noopener">Privacy Policy</a> and <a id="rg-cookies" href="#" target="_blank" rel="noopener">Cookies Policy</a>. You may receive email notifications from us.</span></label>
      <button type="submit" class="ac-btn" id="rg-submit">Register</button>
    </form>
    <div class="lg-links">Already have an account? <a href="#" id="rg-to-login">Log in</a></div>
  </div>
</div>

<!-- VERIFICATION popup: shown after register (or when an unverified member tries to log in) -->
<div id="ver-modal" class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="ver-title">
  <div class="auth-card">
    <button class="ac-close" type="button" id="ver-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
    <div class="ac-mail">✉️</div>
    <div class="ac-head"><h2 id="ver-title">Verify your email</h2></div>
    <p class="ac-why" id="vf-note">Enter the 6-digit code we emailed you.</p>
    <div class="ac-error" id="vf-error" role="alert"></div>
    <div class="ac-ok" id="vf-ok" role="status"></div>
    <form id="vf-form" autocomplete="off">
      <input type="text" id="vf-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="••••••" autocomplete="one-time-code" required>
      <button type="submit" class="ac-btn" id="vf-submit">Verify &amp; enter the city</button>
    </form>
    <button type="button" class="ac-btn ghost" id="vf-resend">Resend code</button>
    <div class="lg-links">The code is valid for 30 minutes. Check your spam folder too.<br><a href="#" id="vf-to-login">Back to login</a></div>
  </div>
</div>


<div id="private-bar">
  <span id="priv-title">🔒 Private</span>
  <button type="button" id="priv-info"><i class="fa-solid fa-share-nodes"></i> Invite</button>
  <button type="button" id="priv-mute"><i class="fa-solid fa-microphone"></i> Mute</button>
  <button type="button" class="leave" id="priv-leave"><i class="fa-solid fa-phone-slash"></i> Leave</button>
</div>

<div id="private-overlay">
  <div id="private-panel">
    <div id="private-head"><span>🔒 Private voice room</span><button type="button" id="private-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="priv-tabs"><button type="button" id="ptab-create" class="on">Create room</button><button type="button" id="ptab-join">Join room</button></div>

    <div id="pv-create">
      <div class="priv-lbl">1 · Pick a place</div>
      <div id="priv-places"></div>
      <div class="priv-lbl">2 · How many people (including you)?</div>
      <div id="priv-size"></div>
      <button type="button" class="priv-main" id="priv-start" disabled>Create private room</button>
    </div>

    <div id="pv-ready" style="display:none">
      <div class="priv-lbl" id="pr-title">Your room is ready</div>
      <div class="priv-code"><small>Room ID</small><b id="pr-id">—</b></div>
      <div class="priv-code"><small>Passcode</small><b id="pr-pass">—</b></div>
      <div class="priv-link" id="pr-link"></div>
      <div class="priv-row2">
        <button type="button" class="priv-main" id="pr-copy-invite">Share invite</button>
        <button type="button" class="priv-main" id="pr-copy-link">Copy link</button>
      </div>
      <p class="priv-note">Send the link (or Room ID) and the passcode to your partners. They need the passcode to get in.</p>
    </div>

    <div id="pv-join" style="display:none">
      <div class="priv-lbl">Room ID</div>
      <div class="priv-row2" style="margin-top:0"><input id="pj-id" placeholder="e.g. K7M2QP" maxlength="8" autocomplete="off" autocapitalize="characters"><button type="button" class="priv-main" id="pj-find" style="max-width:110px">Search</button></div>
      <div id="pj-found"></div>
      <div class="priv-lbl">Passcode</div>
      <input id="pj-pass" placeholder="6-digit passcode" maxlength="6" inputmode="numeric" autocomplete="off">
      <button type="button" class="priv-main" id="pj-join" disabled>Join room</button>
    </div>

    <p class="priv-note">You and your people move into your own private copy of the place — nobody else can see you, hear you or walk in. Voice is never recorded.</p>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
<script src="/socket.io/socket.io.js"></script>
<script src="https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js"></script>
<script>
/* =========================================================
   0) OPTIONAL LOGGED-IN HANDOFF FROM PHP
   ========================================================= */
const params = new URLSearchParams(location.search);
const handoffName = params.get('name');
const handoffBalloons = 0;   // balances now come only from the signed login token (see AUTH below)

/* =========================================================
   0b) AUTH — site login shared with login.php (same PHP session + "keep me signed in" cookie).
   ========================================================= */
const ON_PHP_SITE = /^(www\.)?neyyappam\.(com|net|in)$/.test(location.hostname);
const AUTH_URL = params.get('auth') || window.CITY_AUTH_URL || (ON_PHP_SITE ? '/ajax/city_auth.php' : 'https://neyyappam.com/ajax/city_auth.php');
const SITE_URL = (params.get('site') || window.CITY_SITE_URL || (AUTH_URL.startsWith('http') ? new URL(AUTH_URL).origin : location.origin)).replace(/\/$/, '');
const auth = { loggedIn: false, name: null, display: null, uid: null, token: null, expires: 0, checked: false, profile: null };
let pendingAfterLogin = null;

async function authCall(action, body) {
  const opt = { credentials: 'include', cache: 'no-store' };
  let url = AUTH_URL + '?action=' + encodeURIComponent(action);
  if (body) { opt.method = 'POST'; opt.body = new URLSearchParams(Object.assign({ action }, body)); url = AUTH_URL; }
  const host = new URL(url, location.href).host;
  let res;
  try { res = await fetch(url, opt); }
  catch (e) { console.error('[auth] request blocked or offline:', url, e); throw new Error("Can't reach " + host + " (blocked by CORS, cookies or network)"); }
  let data = null; try { data = await res.json(); } catch (e) {}
  if (!data) { console.error('[auth] not JSON. status', res.status, 'url', url); throw new Error('Login service not reachable: ' + host + ' answered HTTP ' + res.status + ' (is ajax/city_auth.php uploaded?)'); }
  return data;
}
function applyAuth(d) {
  auth.checked = true;
  if (d && d.logged_in && d.city_token) {
    auth.loggedIn = true; auth.token = d.city_token; auth.expires = (d.city_token_expires || 0) * 1000;
    auth.uid = d.user && d.user.id; auth.name = d.user && d.user.name; auth.display = (d.user && d.user.display) || auth.name;
    auth.profile = d.profile || null;
  } else { auth.loggedIn = false; auth.token = null; auth.uid = null; auth.name = null; auth.display = null; auth.profile = null; auth.expires = 0; }
  paintAuthUi();
  routeGate();
}
function paintAuthUi() {
  const nm = document.getElementById('account-name'), btn = document.getElementById('account-action');
  if (!nm) return;
  nm.textContent = auth.loggedIn ? auth.display : 'Guest';
  btn.textContent = auth.loggedIn ? 'Log out' : 'Log in';
  btn.className = auth.loggedIn ? 'ghost' : '';
  const ci = document.getElementById('chat-input');
  if (ci) { ci.readOnly = !auth.loggedIn; ci.placeholder = auth.loggedIn ? 'Say something...' : '🔒 Log in to chat'; }
  const ni = document.getElementById('name-input');
  if (ni) { if (auth.loggedIn) { ni.value = auth.display; ni.readOnly = true; } else { ni.readOnly = false; } }
  const ss = document.getElementById('save-status');
  if (ss) {
    ss.textContent = auth.loggedIn ? 'Signed in as ' + auth.display + ' (@' + auth.name + ') — balloons are saved, mic & chat unlocked.'
                                   : 'Guest mode — you can explore, but mic, chat and saving balloons need a login.';
    ss.classList.toggle('logged-in', auth.loggedIn);
  }
}
async function refreshAuth() {
  try { applyAuth(await authCall('status')); } catch (e) { if (!auth.checked) { auth.checked = true; paintAuthUi(); routeGate(); } }
  return auth.loggedIn;
}
async function keepTokenFresh() {
  if (!auth.loggedIn) return;
  if (auth.expires - Date.now() < 20 * 60 * 1000) {
    const wasIn = auth.loggedIn; await refreshAuth();
    if (auth.loggedIn && typeof socket !== 'undefined' && socket.connected && inGame()) socket.emit('auth', { token: auth.token });
    if (wasIn && !auth.loggedIn) showToast('🔒 You were signed out — please log in again');
  }
}
setInterval(keepTokenFresh, 5 * 60 * 1000);
document.addEventListener('visibilitychange', () => { if (!document.hidden) keepTokenFresh(); });

// ---- name screen routing: signed-in members skip it ----
const gateEl = document.getElementById('name-gate');
let gateRouted = false;
function routeGate() {
  if (gateEl.style.display === 'none') return;                 // already in the city
  gateEl.classList.remove('checking');
  if (!auth.loggedIn) {
    gateEl.classList.remove('member');
    let r = null; try { r = sessionStorage.getItem('cityRole'); } catch (e) {}
    const rb = r && document.querySelector('.role-pill[data-role="' + r + '"]');
    if (rb && !gateRouted) { gateRouted = true; rb.click(); }     // keep the "Play as" choice made in settings after the reload
    return;
  }
  document.getElementById('gate-disp').textContent = auth.display;
  document.getElementById('gate-user').textContent = '@' + auth.name;
  if (auth.profile) {                                           // character already set up -> straight into the city
    if (gateRouted) return;
    gateRouted = true;
    myGender = auth.profile.gender; myOutfitColor = auth.profile.outfit; myHairStyle = auth.profile.hair;
    let r = null; try { r = sessionStorage.getItem('cityRole'); } catch (e) {}
    myRole = (r === 'chayakkaran' || r === 'karavakkari') ? r : null;
    enterCity();
  } else {                                                      // first time: pick a look once
    gateEl.classList.add('member');
    document.getElementById('join-btn').textContent = 'Save & Enter City';
  }
}
setTimeout(() => { gateEl.classList.remove('checking'); }, 3500);   // never leave the screen blank if the login check is slow

async function saveProfile() {
  if (!auth.loggedIn) return;
  try { await authCall('save_profile', { gender: myGender, outfit: myOutfitColor, hair: myHairStyle }); auth.profile = { gender: myGender, outfit: myOutfitColor, hair: myHairStyle }; } catch (e) { console.error('[profile] save failed', e); }
}

// ---- login popup ----
const loginModal = document.getElementById('login-modal');
function openLogin(why, after) {
  pendingAfterLogin = after || null;
  document.getElementById('login-why').textContent = why || 'Log in to use the mic and chat in Neyyappam City.';
  const e = document.getElementById('login-error'); e.style.display = 'none';
  document.getElementById('lg-forgot').href = SITE_URL + '/forgot_password.php';
  document.getElementById('lg-register').href = SITE_URL + '/register.php';
  loginModal.classList.add('open');
  setTimeout(() => document.getElementById('lg-email').focus(), 60);
}
function closeLogin() { loginModal.classList.remove('open'); pendingAfterLogin = null; }
function requireLogin(feature, after) {
  if (auth.loggedIn) { after && after(); return true; }
  openLogin(feature === 'mic' ? '🎤 Log in to turn on your microphone.' : feature === 'chat' ? '💬 Log in to chat with other players.' : feature === 'private' ? '🔒 Log in to start a private voice room.' : 'Log in to continue.', after);
  return false;
}
document.getElementById('login-close').onclick = closeLogin;
document.getElementById('lg-guest').onclick = closeLogin;

/* ---- Register + Verification popups: one popup visible at a time (login -> register -> verify), all inside the city ---- */
const regModal = document.getElementById('reg-modal'), verModal = document.getElementById('ver-modal');
let verEmail = '', resendTimer = null;
function showAuthModal(which) {
  loginModal.classList.toggle('open', which === 'login');
  regModal.classList.toggle('open', which === 'reg');
  verModal.classList.toggle('open', which === 'ver');
}
function closeAuthModals() { showAuthModal(null); pendingAfterLogin = null; clearInterval(resendTimer); }
function acMsg(id, text) { const el = document.getElementById(id); el.textContent = text || ''; el.style.display = text ? 'block' : 'none'; }
function backToLogin() {
  const after = pendingAfterLogin; clearInterval(resendTimer);
  showAuthModal(null); openLogin(null, after);
}
function openRegister() {
  document.getElementById('rg-terms').href = SITE_URL + '/terms.php';
  document.getElementById('rg-privacy').href = SITE_URL + '/privacy.php';
  document.getElementById('rg-cookies').href = SITE_URL + '/cookies.php';
  const dob = document.getElementById('rg-dob'), d = new Date(); d.setFullYear(d.getFullYear() - 13); dob.max = d.toISOString().slice(0, 10);
  acMsg('reg-error', '');
  showAuthModal('reg');
}
function openVerify(email, note, cooldown) {
  verEmail = email;
  document.getElementById('vf-note').textContent = note || ('We sent a 6-digit code to ' + email + '. Enter it below.');
  document.getElementById('vf-code').value = '';
  acMsg('vf-error', ''); acMsg('vf-ok', '');
  showAuthModal('ver');
  startResendTimer(cooldown === undefined ? 60 : cooldown);
  setTimeout(() => document.getElementById('vf-code').focus(), 80);
}
function startResendTimer(sec) {
  clearInterval(resendTimer);
  const b = document.getElementById('vf-resend'); let left = Math.max(0, Math.ceil(sec));
  const paint = () => { b.disabled = left > 0; b.textContent = left > 0 ? 'Resend code in ' + left + 's' : 'Resend code'; };
  paint();
  if (left > 0) resendTimer = setInterval(() => { left--; paint(); if (left <= 0) clearInterval(resendTimer); }, 1000);
}
document.getElementById('lg-register').onclick = (e) => { e.preventDefault(); openRegister(); };     // hides login, shows register
document.getElementById('rg-to-login').onclick = (e) => { e.preventDefault(); backToLogin(); };
document.getElementById('vf-to-login').onclick = (e) => { e.preventDefault(); backToLogin(); };
document.getElementById('reg-close').onclick = closeAuthModals;
document.getElementById('ver-close').onclick = closeAuthModals;
[regModal, verModal].forEach(m => m.addEventListener('mousedown', e => { if (e.target === m) closeAuthModals(); }));
addEventListener('keydown', e => { if (e.key === 'Escape' && (regModal.classList.contains('open') || verModal.classList.contains('open'))) closeAuthModals(); });
document.getElementById('rg-eye').onclick = function () {
  const a = document.getElementById('rg-pass'), b = document.getElementById('rg-pass2'), show = a.type === 'password';
  a.type = b.type = show ? 'text' : 'password'; this.textContent = show ? '🕶️' : '👁️';
};

// live username check (same rules as register.php)
let userChkT = null;
document.getElementById('rg-username').addEventListener('input', (e) => {
  clearTimeout(userChkT);
  const fb = document.getElementById('rg-user-fb'), u = e.target.value.trim();
  const set = (t, c) => { fb.textContent = t; fb.className = 'ac-fb ' + c; };
  if (!u) { set('', 'neu'); return; }
  if (!/^[a-zA-Z0-9_.]+$/.test(u) || u.length > 30) { set('Only letters, numbers, underscores and dots (max 30).', 'bad'); return; }
  set('Checking availability…', 'neu');
  userChkT = setTimeout(async () => {
    try {
      const r = await fetch(AUTH_URL + '?action=username_check&username=' + encodeURIComponent(u), { credentials: 'include', cache: 'no-store' });
      const d = await r.json();
      if (document.getElementById('rg-username').value.trim() !== u) return;      // typed something newer meanwhile
      set(d.available ? '✓ This username is available!' : '✗ ' + d.message, d.available ? 'good' : 'bad');
    } catch (x) { set('Could not check right now.', 'bad'); }
  }, 350);
});
// password rules feedback
function regPassFb() {
  const p = document.getElementById('rg-pass').value, c = document.getElementById('rg-pass2').value, fb = document.getElementById('rg-pass-fb');
  const set = (t, k) => { fb.textContent = t; fb.className = 'ac-fb ' + k; };
  if (!p && !c) set('', 'neu');
  else if (p.length < 8) set('Password must be at least 8 characters.', 'bad');
  else if (!c) set('Please confirm your password.', 'neu');
  else if (p === c) set('✓ Passwords match.', 'good');
  else set('✗ Passwords do not match.', 'bad');
}
document.getElementById('rg-pass').addEventListener('input', regPassFb);
document.getElementById('rg-pass2').addEventListener('input', regPassFb);

// submit register -> verification popup
document.getElementById('reg-form').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const f = ev.target, btn = document.getElementById('rg-submit');
  if (f.elements.password.value !== f.elements.confirm_password.value) { acMsg('reg-error', 'Passwords do not match.'); return; }
  acMsg('reg-error', ''); btn.disabled = true; btn.textContent = 'Creating your account…';
  const body = {};
  ['first_name', 'last_name', 'username', 'dob', 'gender', 'location', 'email', 'password', 'confirm_password'].forEach(n => body[n] = f.elements[n].value);
  body.agree_terms = f.elements.agree_terms.checked ? '1' : '';
  try {
    const d = await authCall('register', body);
    if (!d.ok) acMsg('reg-error', d.error || 'Could not register');
    else {
      f.elements.password.value = ''; f.elements.confirm_password.value = '';
      openVerify(d.email, d.mail_error ? d.mail_error : ('We sent a 6-digit code to ' + d.email + '. Enter it below.'), d.mail_error ? 0 : 60);
    }
  } catch (e) { acMsg('reg-error', e.message || 'Could not reach the server'); }
  btn.disabled = false; btn.textContent = 'Register';
});

// submit the code -> logged in, popup closes, player stays in the city
document.getElementById('vf-code').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6); });
document.getElementById('vf-form').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const btn = document.getElementById('vf-submit'), code = document.getElementById('vf-code').value;
  if (code.length !== 6) { acMsg('vf-error', 'Enter all 6 digits.'); return; }
  acMsg('vf-error', ''); acMsg('vf-ok', ''); btn.disabled = true; btn.textContent = 'Verifying…';
  try {
    const d = await authCall('verify', { email: verEmail, code });
    if (d.ok && d.logged_in) {
      const after = pendingAfterLogin;
      applyAuth(d); closeAuthModals();
      showToast('✅ Email verified — welcome, ' + auth.display + '!');
      if (typeof socket !== 'undefined' && socket.connected && inGame()) { socket.emit('auth', { token: auth.token }); afterLoginInGame(); }
      if (after) after();
    } else if (d.already_verified) { backToLogin(); showToast('This email is already verified — please log in'); }
    else acMsg('vf-error', d.error || 'Verification failed');
  } catch (e) { acMsg('vf-error', e.message || 'Could not reach the server'); }
  btn.disabled = false; btn.textContent = 'Verify & enter the city';
});
document.getElementById('vf-resend').onclick = async () => {
  acMsg('vf-error', ''); acMsg('vf-ok', '');
  const b = document.getElementById('vf-resend'); b.disabled = true; b.textContent = 'Sending…';
  try {
    const d = await authCall('resend', { email: verEmail });
    if (d.ok) { acMsg('vf-ok', 'A new code is on its way to ' + verEmail + '.'); startResendTimer(d.wait || 60); }
    else if (d.already_verified) { backToLogin(); showToast('This email is already verified — please log in'); }
    else { acMsg('vf-error', d.error || 'Could not resend'); startResendTimer(d.wait || 0); }
  } catch (e) { acMsg('vf-error', e.message || 'Could not reach the server'); startResendTimer(0); }
};
loginModal.addEventListener('mousedown', e => { if (e.target === loginModal) closeLogin(); });
addEventListener('keydown', e => { if (e.key === 'Escape' && loginModal.classList.contains('open')) closeLogin(); });
document.getElementById('lg-eye').onclick = function () {
  const f = document.getElementById('lg-pass'), show = f.type === 'password';
  f.type = show ? 'text' : 'password'; this.textContent = show ? '🕶️' : '👁️'; this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
};
document.getElementById('login-form').addEventListener('submit', async (ev) => {
  ev.preventDefault();
  const btn = document.getElementById('lg-submit'), err = document.getElementById('login-error');
  btn.disabled = true; btn.textContent = 'Logging in…'; err.style.display = 'none';
  try {
    const d = await authCall('login', { email: document.getElementById('lg-email').value.trim(), password: document.getElementById('lg-pass').value, remember: document.getElementById('lg-remember').checked ? '1' : '0' });
    if (!d.ok || !d.logged_in) {
      if (d.needs_verify) {                        // account exists but the email is not verified yet: open the verification popup
        openVerify(document.getElementById('lg-email').value.trim(), 'Please verify your email first. Enter the code we sent you, or tap Resend.', 0);
        btn.disabled = false; btn.textContent = 'Login'; return;
      }
      err.innerHTML = '';
      err.textContent = d.error || 'Login failed';
      if (d.verify_url) { const a = document.createElement('a'); a.href = SITE_URL + '/' + d.verify_url; a.target = '_blank'; a.textContent = ' Verify now'; a.style.color = '#FE019A'; err.appendChild(a); }
      err.style.display = 'block';
    } else {
      document.getElementById('lg-pass').value = '';
      applyAuth(d);
      const after = pendingAfterLogin; closeLogin();
      showToast('✅ Welcome, ' + auth.display + '!');
      if (typeof socket !== 'undefined' && socket.connected && inGame()) { socket.emit('auth', { token: auth.token }); afterLoginInGame(); }
      if (after) after();
    }
  } catch (e) { err.textContent = e.message || 'Could not reach the login service'; err.style.display = 'block'; }
  btn.disabled = false; btn.textContent = 'Login';
});
document.getElementById('account-action').onclick = async () => {
  if (!auth.loggedIn) { openLogin('Log in to save your balloons and use mic & chat.'); return; }
  if (!confirm('Log out of Neyyappam?')) return;
  try { await authCall('logout', {}); } catch (e) {}
  applyAuth(null);
  if (typeof socket !== 'undefined' && socket.connected && inGame()) socket.emit('deauth');
  if (privRoom) cleanupPrivate();
  if (micOn) { micOn = false; paintVoiceBtns(); syncVoice(); }
  document.getElementById('balloon-val').textContent = '0';
  showToast('👋 Logged out');
};
refreshAuth();

/* =========================================================
   1) CITY GENERATION — bright low-poly daytime style (blue sky, brick buildings, toon cars)
   ========================================================= */
const scene = new THREE.Scene();

// Declared up here (not near where they're used later) because the plaza benches below are built
// immediately at startup, before later `const`/`let` declarations in this file would run.
const BENCHES = [];
let sitting = false, mySitPos = null, nearBench = null;
let handWith = null, handPending = null;     // holdWith: id of the player we're linked with
const handLinks = {};                         // pairKey -> THREE.Group (the visual link between two linked avatars)
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
    renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap;
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
// sky is built in the Atmosphere block below

scene.add(new THREE.HemisphereLight(0xffffff, 0x8f8464, 1.15));
const sun = new THREE.DirectionalLight(0xfff3d6, 1.0);
sun.position.set(10, 25, 8);
sun.castShadow = true;
{ const big = !(typeof matchMedia !== 'undefined' && matchMedia('(pointer:coarse)').matches), sc = sun.shadow.camera;
  sun.shadow.mapSize.set(big ? 2048 : 1024, big ? 2048 : 1024); sc.left = -32; sc.right = 32; sc.top = 32; sc.bottom = -32; sc.near = 1; sc.far = 110;
  sun.shadow.bias = -0.0006; sun.shadow.normalBias = 0.02; }
scene.add(sun); scene.add(sun.target);

/* =========================================================
   PHASE 1 — Atmosphere: shared day/night cycle, sunrise/sunset, stars,
   rain + wet roads, reflections (PMREM sky), ACES filmic tone mapping.
   Everyone sees the same time/weather (derived from the wall clock).
   Tweak ATMO below. Test with ?time=0.5 (noon) ?time=0.8 (dusk) ?time=0 (night) ?rain=1
   ========================================================= */
const ATMO = { dayMs: 20 * 60 * 1000, exposure: 1.0, rainBlockMs: 5 * 60 * 1000, rainChance: 0.3, envEveryMs: 12000 };
const _aq = new URLSearchParams(location.search);
const SKY_KEYS = [
  { t: 0,    top: '#02030a', mid: '#0a1024', hor: '#1a1b33', fog: '#0b0e1c', sun: '#8fa4d8', si: 0.12, hi: 0.30 },
  { t: 0.23, top: '#16224a', mid: '#6a4f78', hor: '#ff9a6a', fog: '#7a6070', sun: '#ffa070', si: 0.25, hi: 0.45 },
  { t: 0.28, top: '#2a78c8', mid: '#a8d0e4', hor: '#ffd9a0', fog: '#e8d2b0', sun: '#ffcf94', si: 0.80, hi: 0.85 },
  { t: 0.36, top: '#1f7fd1', mid: '#9bd8ea', hor: '#f4e6b8', fog: '#dcebd4', sun: '#fff3d6', si: 1.15, hi: 1.00 },
  { t: 0.64, top: '#1f7fd1', mid: '#9bd8ea', hor: '#f4e6b8', fog: '#dcebd4', sun: '#fff3d6', si: 1.15, hi: 1.00 },
  { t: 0.72, top: '#2a78c8', mid: '#b0d0dc', hor: '#ffcf8a', fog: '#ecd0a0', sun: '#ffc27a', si: 0.85, hi: 0.85 },
  { t: 0.77, top: '#3a2a6a', mid: '#c4607a', hor: '#ff7a3a', fog: '#b8705e', sun: '#ff8a45', si: 0.35, hi: 0.50 },
  { t: 0.83, top: '#070b22', mid: '#1c2146', hor: '#4a3050', fog: '#1c1a30', sun: '#8fa4d8', si: 0.14, hi: 0.32 },
  { t: 1,    top: '#02030a', mid: '#0a1024', hor: '#1a1b33', fog: '#0b0e1c', sun: '#8fa4d8', si: 0.12, hi: 0.30 }
];
function skySample(t) {
  let i = 0; while (i < SKY_KEYS.length - 2 && t >= SKY_KEYS[i + 1].t) i++;
  const a = SKY_KEYS[i], b = SKY_KEYS[i + 1], k = Math.min(1, Math.max(0, (t - a.t) / (b.t - a.t)));
  const col = p => new THREE.Color(a[p]).lerp(new THREE.Color(b[p]), k);
  return { top: col('top'), mid: col('mid'), hor: col('hor'), fog: col('fog'), sun: col('sun'), si: a.si + (b.si - a.si) * k, hi: a.hi + (b.hi - a.hi) * k };
}
function cityClock() {
  if (_aq.has('time')) return (((+_aq.get('time')) % 1) + 1) % 1;
  // ~85% of each cycle is daytime (with sunrise/sunset), ~15% is night
  const u = (Date.now() % ATMO.dayMs) / ATMO.dayMs;
  return u < 0.85 ? 0.23 + (u / 0.85) * 0.60 : (0.83 + ((u - 0.85) / 0.15) * 0.40) % 1;
}
function rainTarget() {
  if (_aq.has('rain')) return +_aq.get('rain') ? 1 : 0;
  const r = Math.sin(Math.floor(Date.now() / ATMO.rainBlockMs) * 12.9898) * 43758.5453;
  return (r - Math.floor(r)) < ATMO.rainChance ? 1 : 0;
}
renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = ATMO.exposure;

const skyCanvas = document.createElement('canvas'); skyCanvas.width = 2; skyCanvas.height = 256;
const skyCtx = skyCanvas.getContext('2d'), skyTex = new THREE.CanvasTexture(skyCanvas);
const skyMat = new THREE.MeshBasicMaterial({ map: skyTex, side: THREE.BackSide, fog: false, toneMapped: false });
scene.add(new THREE.Mesh(new THREE.SphereGeometry(400, 24, 24), skyMat));
const discMat = new THREE.MeshBasicMaterial({ color: 0xffffff, fog: false, toneMapped: false, transparent: true });
const disc = new THREE.Mesh(new THREE.SphereGeometry(11, 16, 16), discMat); scene.add(disc);
const stars = (() => {
  const n = 700, p = new Float32Array(n * 3);
  for (let i = 0; i < n; i++) { const u = Math.random() * Math.PI * 2, v = Math.acos(Math.random() * 0.95), r = 380; p[i*3] = r*Math.sin(v)*Math.cos(u); p[i*3+1] = r*Math.cos(v); p[i*3+2] = r*Math.sin(v)*Math.sin(u); }
  const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.BufferAttribute(p, 3));
  const m = new THREE.Points(g, new THREE.PointsMaterial({ color: 0xffffff, size: 1.8, sizeAttenuation: false, fog: false, transparent: true, opacity: 0, depthWrite: false, toneMapped: false }));
  m.frustumCulled = false; scene.add(m); return m;
})();
const hemi = scene.children.find(o => o.isHemisphereLight);

// Sky-driven reflections (wet roads, glass, car paint) — a tiny scene sharing the sky material
const envScene = new THREE.Scene(), envDisc = new THREE.Mesh(new THREE.SphereGeometry(11 * 0.25, 8, 8), discMat);
envScene.add(new THREE.Mesh(new THREE.SphereGeometry(100, 16, 16), skyMat)); envScene.add(envDisc);
const pmrem = new THREE.PMREMGenerator(renderer); let envRT = null, _envAt = -1e9, _skyAt = -1e9, _lastAt = performance.now(), rainAmt = 0;

// Rain streaks around the player
const RAIN_N = 900, RAIN_B = 36, rainD = new Float32Array(RAIN_N * 3), rainP = new Float32Array(RAIN_N * 6);
for (let i = 0; i < RAIN_N; i++) { rainD[i*3] = (Math.random() - 0.5) * 400; rainD[i*3+1] = Math.random() * 22; rainD[i*3+2] = (Math.random() - 0.5) * 400; }
const rainGeo = new THREE.BufferGeometry(); rainGeo.setAttribute('position', new THREE.BufferAttribute(rainP, 3));
const rainMesh = new THREE.LineSegments(rainGeo, new THREE.LineBasicMaterial({ color: 0xaec6e0, transparent: true, opacity: 0, fog: false, depthWrite: false }));
rainMesh.frustumCulled = false; rainMesh.visible = false; scene.add(rainMesh);
const REFLECT = [];
function reflective(m, rough, metal, inten) { m.roughness = rough; m.metalness = metal; m.envMapIntensity = inten; REFLECT.push(m); if (envRT) m.envMap = envRT.texture; return m; }
const ROAD_BASE = new THREE.Color(0x55565c), GREY = new THREE.Color(0x8d959c);

function updateAtmosphere() {
  const now = performance.now(), dt = Math.min(0.1, (now - _lastAt) / 1000); _lastAt = now;
  const inPriv = !!privScene, T = inPriv ? 0.5 : cityClock();
  rainAmt += (rainTarget() - rainAmt) * Math.min(1, dt * 0.25);
  const R = inPriv ? 0 : rainAmt, s = skySample(T);
  const lum = 0.35 + 0.65 * Math.min(1, s.hi), grey = GREY.clone().multiplyScalar(lum);
  s.fog.lerp(grey, R * 0.7); s.top.lerp(grey, R * 0.75); s.mid.lerp(grey, R * 0.8); s.hor.lerp(grey, R * 0.7);
  const night = Math.min(1, Math.max(0, (0.6 - s.hi) / 0.3));
  // sun / moon
  const a = (T - 0.25) * Math.PI * 2, ca = Math.cos(a), sa = Math.sin(a), sg = sa >= 0 ? 1 : -1;
  const ax = myAvatar.position.x, az = myAvatar.position.z;
  sun.position.set(ax + ca * 45, Math.abs(sa) * 45 + 8, az + 20); sun.target.position.set(ax, 0, az);
  sun.color.copy(s.sun); sun.intensity = s.si * (1 - 0.7 * R);
  hemi.color.copy(s.mid).lerp(new THREE.Color(0xffffff), 0.5); hemi.groundColor.set(0x8f8464).multiplyScalar(lum);
  hemi.intensity = s.hi * 1.0 * (1 - 0.25 * R);
  disc.position.set(sg * ca * 350, sg * sa * 350, 100); disc.visible = R < 0.9; discMat.opacity = 1 - R;
  discMat.color.copy(s.sun).lerp(new THREE.Color(0xffffff), night > 0.5 ? 0.6 : 0.4);
  envDisc.position.copy(disc.position).multiplyScalar(0.25);
  stars.material.opacity = night * (1 - R);
  if (!inPriv) { scene.fog.color.copy(s.fog); scene.fog.near = 45 - 25 * R - 15 * night; scene.fog.far = 170 - 80 * R - 40 * night; }
  // sky gradient texture (twice a second is plenty)
  if (now - _skyAt > 500) {
    _skyAt = now;
    const g = skyCtx.createLinearGradient(0, 0, 0, 256);
    g.addColorStop(0, '#' + s.top.getHexString()); g.addColorStop(0.55, '#' + s.mid.getHexString());
    g.addColorStop(0.8, '#' + s.hor.getHexString()); g.addColorStop(1, '#' + s.hor.getHexString());
    skyCtx.fillStyle = g; skyCtx.fillRect(0, 0, 2, 256); skyTex.needsUpdate = true;
  }
  if (now - _envAt > ATMO.envEveryMs) {
    _envAt = now;
    const rt = pmrem.fromScene(envScene, 0, 0.1, 1000);
    if (window.ROAD_MAT && !REFLECT.includes(ROAD_MAT)) REFLECT.push(ROAD_MAT); REFLECT.forEach(m => { if (!m.envMap) m.needsUpdate = true; m.envMap = rt.texture; }); if (envRT) envRT.dispose(); envRT = rt;
  }
  if (window.cloudMat) { cloudMat.color.copy(s.hor).lerp(new THREE.Color(0xffffff), 0.55).lerp(grey, R * 0.8).multiplyScalar(0.2 + 0.8 * Math.min(1, s.hi)); cloudMat.opacity = 0.9 - 0.2 * night + 0.1 * R; }
  if (window.BIRDS) BIRDS.visible = night < 0.6 && R < 0.8;
  // wet roads
  if (window.ROAD_MAT) { ROAD_MAT.roughness = 0.95 - 0.7 * R; ROAD_MAT.color.copy(ROAD_BASE).multiplyScalar(1 - 0.45 * R); ROAD_MAT.envMapIntensity = 1.4 * R; }
  // rain
  rainMesh.visible = R > 0.02;
  if (rainMesh.visible) {
    rainMesh.material.opacity = 0.35 * R;
    for (let i = 0; i < RAIN_N; i++) {
      let y = rainD[i*3+1] - 28 * dt; if (y < 0) y += 22; rainD[i*3+1] = y;
      const x = ax + ((((rainD[i*3] - ax) % RAIN_B) + RAIN_B) % RAIN_B) - RAIN_B / 2, z = az + ((((rainD[i*3+2] - az) % RAIN_B) + RAIN_B) % RAIN_B) - RAIN_B / 2;
      rainP[i*6] = x; rainP[i*6+1] = y; rainP[i*6+2] = z; rainP[i*6+3] = x + 0.04; rainP[i*6+4] = y + 0.7; rainP[i*6+5] = z;
    }
    rainGeo.attributes.position.needsUpdate = true;
  }
}


const BLOCK = 20, GRID = 6, ROAD_W = 6;

// --- Flat gray asphalt (clean, not gritty — matches the toy-city reference) ---
const ground = new THREE.Mesh(
  new THREE.PlaneGeometry(GRID*BLOCK + 60, GRID*BLOCK + 60),
  new THREE.MeshStandardMaterial({ color: 0x79ad4a })   // tropical green ground
);
ground.rotation.x = -Math.PI/2;
scene.add(ground);
{ // asphalt roads laid over the green (a road on every multiple of BLOCK, ROAD_W wide)
  const roadMat = new THREE.MeshStandardMaterial({ color: 0x55565c }); window.ROAD_MAT = roadMat;
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
const carColors = [0x2f5fd0, 0xd83c3c, 0xf2c230, 0x3fae55, 0x555a63, 0x8b1e3f, 0x1f2a44, 0xe07a1f];
const cars = {}; // carId -> { id, group, occupiedBy }
let carIdCounter = 0;

/* ---- Phase 3: realistic retro cars + bikes (all procedural: extruded body profiles, clearcoat paint, real tyres/rims) ---- */
function vmat(m, rough) { m.roughness = rough; m.metalness = 0; m.envMapIntensity = 0; return m; }   // flat lit colour: no sky-reflection, so paint never washes out white
const _cg = {}; const CG = (k, f) => _cg[k] || (_cg[k] = f());
function _extrude(pts, depth, bev) {
  const s = new THREE.Shape(); s.moveTo(pts[0][0], pts[0][1]);
  for (let i = 1; i < pts.length; i++) pts[i].length === 4 ? s.quadraticCurveTo(pts[i][0], pts[i][1], pts[i][2], pts[i][3]) : s.lineTo(pts[i][0], pts[i][1]);
  const g = new THREE.ExtrudeGeometry(s, { depth, bevelEnabled: true, bevelSize: bev, bevelThickness: bev, bevelSegments: 4, curveSegments: 14 });
  g.translate(0, 0, -depth / 2); g.computeVertexNormals(); return g;
}
function _wheel(R, tr, rimMat, tyreMat, nSpoke) {
  const w = new THREE.Group();
  w.add(new THREE.Mesh(CG('ty' + R + tr, () => new THREE.TorusGeometry(R - tr, tr, 12, 28)), tyreMat));
  const rim = new THREE.Mesh(CG('rm' + R, () => new THREE.CylinderGeometry(R - tr * 1.1, R - tr * 1.1, tr * 2.1, 24)), rimMat); rim.rotation.x = Math.PI / 2; w.add(rim);
  for (let i = 0; i < nSpoke; i++) { const sp = new THREE.Mesh(CG('sp' + R, () => new THREE.BoxGeometry(R * 1.5, 0.03, 0.012)), tyreMat); sp.rotation.z = i / nSpoke * Math.PI; sp.position.z = tr * 1.1; w.add(sp); }
  const hub = new THREE.Mesh(CG('hb' + R, () => new THREE.CylinderGeometry(R * 0.2, R * 0.2, tr * 2.4, 12)), rimMat); hub.rotation.x = Math.PI / 2; w.add(hub);
  return w;
}
function buildRetroCar(group, color) {
  const paint = vmat(new THREE.MeshPhysicalMaterial({ color, clearcoat: 0.3, clearcoatRoughness: 0.25 }), 0.42, 0.25, 0.4);
  const glass = vmat(new THREE.MeshStandardMaterial({ color: 0x1c2c3a, transparent: true, opacity: 0.6, side: THREE.DoubleSide }), 0.08, 0.3, 1.2);
  const hatch = Math.random() < 0.4;
  const chrome = vmat(new THREE.MeshStandardMaterial({ color: 0xd8dade, emissive: 0x3a3c40 }), 0.2, 0.7, 1.3);
  const rubber = new THREE.MeshStandardMaterial({ color: 0x151515, roughness: 0.9 }), dark = new THREE.MeshStandardMaterial({ color: 0x0b0b0b, roughness: 0.7 });
  const add = (geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); group.add(m); return m; };
  const box = (w, h, d) => CG('b' + [w, h, d], () => new THREE.BoxGeometry(w, h, d));
  add(CG('tub', () => _extrude([[-1.05, 0.28], [1.05, 0.28], [1.08, 0.5], [1.0, 0.66], [0.8, 0.74, 0.5, 0.76], [0.28, 0.78], [-0.7, 0.78], [-1.0, 0.76, -1.08, 0.62], [-1.08, 0.4]], 0.88, 0.06)), paint, 0, 0, 0);
  add(hatch ? CG('gh2', () => _extrude([[0.3, 0.76], [0.1, 1.15], [-0.78, 1.17], [-1.0, 0.78]], 0.78, 0.025)) : CG('gh', () => _extrude([[0.3, 0.76], [0.08, 1.17], [-0.6, 1.19], [-0.88, 0.76]], 0.78, 0.025)), glass, 0, 0, 0);
  add(box(hatch ? 0.9 : 0.74, 0.045, 0.86), paint, hatch ? -0.34 : -0.26, hatch ? 1.18 : 1.2, 0);                                          // roof
  [-1, 1].forEach(s => {
    add(box(0.07, 0.42, 0.05), paint, hatch ? -0.89 : -0.74, 0.98, s * 0.4).rotation.z = -0.5;                   // C-pillars
    add(box(0.05, 0.4, 0.04), paint, 0.19, 0.97, s * 0.4).rotation.z = 0.5;                    // A-pillars
    const hl = add(CG('hl', () => new THREE.SphereGeometry(0.09, 14, 10)), new THREE.MeshStandardMaterial({ color: 0xfff4cf, emissive: 0xffe9a8, emissiveIntensity: 0.8 }), 1.06, 0.56, s * 0.33); hl.scale.set(0.5, 1, 1);
    const ring = add(CG('hr', () => new THREE.TorusGeometry(0.1, 0.018, 8, 18)), chrome, 1.08, 0.56, s * 0.33); ring.rotation.y = Math.PI / 2;
    add(box(0.05, 0.1, 0.22), new THREE.MeshStandardMaterial({ color: 0xc1121f, emissive: 0xff1a1a, emissiveIntensity: 0.55 }), -1.1, 0.6, s * 0.34);   // tail lamps
    add(box(0.12, 0.08, 0.05), paint, 0.42, 0.9, s * 0.52);                                    // mirrors
    add(box(0.3, 0.012, 0.012), dark, -0.2, 0.55, s * 0.485);                                  // door shut-line
    add(box(0.07, 0.015, 0.02), chrome, -0.02, 0.68, s * 0.5);                                 // door handle
  });
  add(box(0.06, 0.12, 0.84), chrome, 1.12, 0.36, 0); add(box(0.06, 0.12, 0.84), chrome, -1.12, 0.36, 0);   // bumpers
  add(box(0.04, 0.14, 0.36), dark, 1.1, 0.55, 0);                                              // grille
  add(box(0.02, 0.1, 0.28), new THREE.MeshStandardMaterial({ color: 0xf2f2f2 }), 1.14, 0.38, 0);      // plate
  add(box(1.9, 0.08, 0.8), dark, 0, 0.26, 0);                                                  // underbody
  const seatM = new THREE.MeshStandardMaterial({ color: 0x4a3a2c, roughness: 0.8 });                // interior: seats, dash, steering wheel
  [0.2, -0.2].forEach(z => { add(box(0.34, 0.14, 0.3), seatM, -0.18, 0.88, z); add(box(0.08, 0.34, 0.3), seatM, -0.36, 1.0, z); });
  add(box(0.5, 0.2, 0.34), seatM, -0.62, 0.9, 0); add(box(0.2, 0.12, 0.8), dark, 0.2, 0.86, 0);
  add(CG('sw', () => new THREE.TorusGeometry(0.1, 0.012, 6, 16)), dark, 0.1, 0.98, 0.2).rotation.y = Math.PI / 2 - 0.35;
  [1, -1].forEach(sx => [1, -1].forEach(sz => { const ar = add(CG('ar', () => new THREE.CylinderGeometry(0.33, 0.33, 0.03, 20)), dark, sx * 0.68, 0.285, sz * 0.502); ar.rotation.x = Math.PI / 2; }));
  const W = [], rimMat = chrome;
  [[0.68, 0.46], [0.68, -0.46], [-0.68, 0.46], [-0.68, -0.46]].forEach(([x, z]) => { const w = _wheel(0.285, 0.085, rimMat, rubber, 5); w.position.set(x, 0.285, z); group.add(w); W.push({ m: w, R: 0.285 }); });
  return W;
}
function buildRetroBike(group, color) {
  const paint = vmat(new THREE.MeshPhysicalMaterial({ color, clearcoat: 0.3, clearcoatRoughness: 0.25 }), 0.4, 0.3, 0.45);
  const chrome = vmat(new THREE.MeshStandardMaterial({ color: 0xd8dade, emissive: 0x3a3c40 }), 0.2, 0.7, 1.3);
  const steel = new THREE.MeshStandardMaterial({ color: 0x2a2a2e, metalness: 0.6, roughness: 0.45 }), rubber = new THREE.MeshStandardMaterial({ color: 0x141414, roughness: 0.9 });
  const seatM = new THREE.MeshStandardMaterial({ color: 0x3b2417, roughness: 0.6 });
  const add = (geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); group.add(m); return m; };
  const tube = (a, b, r, m) => { const A = new THREE.Vector3(...a), B = new THREE.Vector3(...b), d = B.clone().sub(A); const t = new THREE.Mesh(new THREE.CylinderGeometry(r, r, d.length(), 8), m); t.position.copy(A).add(B).multiplyScalar(0.5); t.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), d.normalize()); group.add(t); return t; };
  const W = []; [0.7, -0.7].forEach(x => { const w = _wheel(0.34, 0.06, chrome, rubber, 8); w.position.set(x, 0.34, 0); group.add(w); W.push({ m: w, R: 0.34 }); });
  [-1, 1].forEach(s => tube([0.7, 0.34, s * 0.07], [0.52, 0.98, s * 0.09], 0.02, chrome));       // front forks
  tube([0.52, 0.98, -0.12], [0.52, 0.98, 0.12], 0.016, steel);
  add(new THREE.CylinderGeometry(0.016, 0.016, 0.7, 8), steel, 0.5, 1.03, 0).rotation.x = Math.PI / 2;     // handlebar
  [-1, 1].forEach(s => { add(new THREE.CylinderGeometry(0.022, 0.022, 0.12, 8), rubber, 0.5, 1.03, s * 0.35).rotation.x = Math.PI / 2; add(new THREE.SphereGeometry(0.06, 10, 8), chrome, 0.58, 1.0, s * 0.3).scale.set(0.8, 1, 0.6); });
  const lamp = add(new THREE.SphereGeometry(0.11, 14, 10), chrome, 0.62, 0.95, 0); lamp.scale.set(0.8, 1, 1);
  add(new THREE.SphereGeometry(0.075, 12, 8), new THREE.MeshStandardMaterial({ color: 0xfff4cf, emissive: 0xffe9a8, emissiveIntensity: 0.9 }), 0.7, 0.95, 0);
  tube([0.45, 0.82, 0], [-0.1, 0.78, 0], 0.03, steel); tube([0.4, 0.8, 0], [0.15, 0.35, 0], 0.03, steel); tube([-0.1, 0.78, 0], [-0.7, 0.34, 0.07], 0.02, steel); tube([-0.1, 0.78, 0], [-0.7, 0.34, -0.07], 0.02, steel);
  const tank = add(new THREE.SphereGeometry(0.2, 18, 12), paint, 0.2, 0.92, 0); tank.scale.set(1.5, 0.8, 0.85);
  const seat = add(new THREE.SphereGeometry(0.2, 14, 10), seatM, -0.22, 0.88, 0); seat.scale.set(1.5, 0.35, 0.95);
  const eng = add(new THREE.BoxGeometry(0.34, 0.3, 0.26), steel, 0.12, 0.45, 0);
  for (let i = 0; i < 4; i++) add(new THREE.BoxGeometry(0.34, 0.015, 0.34), steel, 0.12, 0.58 + i * 0.035, 0);   // cooling fins
  add(new THREE.CylinderGeometry(0.05, 0.065, 0.95, 12), chrome, -0.45, 0.28, 0.17).rotation.z = Math.PI / 2 - 0.08;   // exhaust
  [0.7, -0.7].forEach(x => { const fd = add(new THREE.TorusGeometry(0.4, 0.025, 6, 18, 2.2), paint, x, 0.34, 0); fd.rotation.z = Math.PI / 2 - 1.1; });
  add(new THREE.BoxGeometry(0.06, 0.05, 0.1), new THREE.MeshStandardMaterial({ color: 0xc1121f, emissive: 0xff1a1a, emissiveIntensity: 0.6 }), -0.82, 0.66, 0);
  tube([0.12, 0.2, 0.2], [0.12, 0.2, -0.2], 0.012, steel);                                      // foot-peg bar
  return W;
}

function _tubeFn(group) { return (a, b, r, m) => { const A = new THREE.Vector3(...a), B = new THREE.Vector3(...b), d = B.clone().sub(A); const t = new THREE.Mesh(new THREE.CylinderGeometry(r, r, d.length(), 8), m); t.position.copy(A).add(B).multiplyScalar(0.5); t.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), d.normalize()); group.add(t); return t; }; }
function buildCycle(group, color) {   // 80s Hercules-style roadster with carrier, bell and lamp
  const paint = vmat(new THREE.MeshPhysicalMaterial({ color, clearcoat: 0.3, clearcoatRoughness: 0.25 }), 0.4, 0.3, 0.45);
  const chrome = vmat(new THREE.MeshStandardMaterial({ color: 0xd8dade, emissive: 0x3a3c40 }), 0.2, 0.7, 1.3);
  const rubber = new THREE.MeshStandardMaterial({ color: 0x141414, roughness: 0.9 }), blk = new THREE.MeshStandardMaterial({ color: 0x1a1512, roughness: 0.6 });
  const add = (geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); group.add(m); return m; }, tube = _tubeFn(group), W = [];
  [0.56, -0.56].forEach(x => { const w = _wheel(0.34, 0.03, chrome, rubber, 14); w.position.set(x, 0.34, 0); group.add(w); W.push({ m: w, R: 0.34 });
    const fd = add(new THREE.TorusGeometry(0.375, 0.012, 6, 18, 2.2), paint, x, 0.34, 0); fd.rotation.z = Math.PI / 2 - 1.1; });
  const BB = [0, 0.3, 0], ST = [-0.13, 0.92, 0], HT = [0.44, 0.88, 0], HB = [0.47, 0.72, 0];
  tube(BB, ST, 0.02, paint); tube(ST, HT, 0.02, paint); tube(BB, HB, 0.024, paint); tube(HT, HB, 0.028, paint);
  [-1, 1].forEach(s => { tube(ST, [-0.56, 0.34, s * 0.06], 0.014, paint); tube(BB, [-0.56, 0.34, s * 0.06], 0.014, paint); tube(HB, [0.56, 0.34, s * 0.05], 0.016, chrome);
    add(new THREE.BoxGeometry(0.09, 0.02, 0.05), blk, s * 0.1 * 0 + 0.0, 0.3 + s * 0.1, s * 0.12);                           // pedals
    add(new THREE.CylinderGeometry(0.02, 0.022, 0.12, 8), rubber, 0.4, 1.03, s * 0.3).rotation.x = Math.PI / 2; });          // grips
  tube(HT, [0.4, 1.0, 0], 0.018, chrome); add(new THREE.CylinderGeometry(0.014, 0.014, 0.62, 8), chrome, 0.4, 1.02, 0).rotation.x = Math.PI / 2;
  add(new THREE.SphereGeometry(0.035, 8, 6), chrome, 0.42, 1.08, 0.16);                                                       // bell
  add(new THREE.SphereGeometry(0.05, 10, 8), new THREE.MeshStandardMaterial({ color: 0xfff4cf, emissive: 0xffe9a8, emissiveIntensity: 0.8 }), 0.52, 0.92, 0);
  tube(ST, [-0.14, 1.0, 0], 0.016, chrome); add(new THREE.SphereGeometry(0.17, 12, 8), blk, -0.16, 1.02, 0).scale.set(1.4, 0.3, 0.8);   // saddle
  add(new THREE.TorusGeometry(0.09, 0.012, 6, 18), chrome, 0, 0.3, 0.05);                                                      // chainring
  add(new THREE.BoxGeometry(0.34, 0.02, 0.17), paint, -0.66, 0.72, 0);                                                          // carrier
  [-1, 1].forEach(s => tube([-0.56, 0.34, s * 0.06], [-0.66, 0.72, s * 0.07], 0.01, chrome));
  return W;
}
/* ---- Real 3D vehicle models (GTA-style): drop .glb files in public/models/ ; falls back to the code-built vehicles if a file is missing ----
   len = length in game units (avatar is ~1.7 tall). rotY = extra turn if a model faces the wrong way (try Math.PI or ±Math.PI/2). seat = [x, y] rider seat for bikes/cycles. */
const VEHICLE_MODELS = {
  car:   [{ url: 'models/car1.glb', len: 3.6 }, { url: 'models/car2.glb', len: 3.6 }, { url: 'models/car3.glb', len: 3.6 }],
  bike:  [{ url: 'models/bike1.glb', len: 2.0, seat: [-0.2, 0.95] }],
  cycle: [{ url: 'models/cycle1.glb', len: 1.8, seat: [-0.14, 1.0] }],
};
const _mdlCache = {};
function loadModel(url) {
  if (!THREE.GLTFLoader) return Promise.resolve(null);
  return _mdlCache[url] || (_mdlCache[url] = new Promise(res => new THREE.GLTFLoader().load(url, g => res(g.scene), undefined, () => res(null))));
}
function upgradeToModel(c, kind, entry) {
  const list = VEHICLE_MODELS[kind]; if (!entry && (!list || !list.length)) return;
  const e = entry || list[Math.floor(Math.random() * list.length)];
  loadModel(e.url).then(src => {
    if (!src) return;
    const m = src.clone(true), holder = new THREE.Group(); holder.add(m);
    let b = new THREE.Box3().setFromObject(holder), sz = b.getSize(new THREE.Vector3());
    holder.rotation.y = (sz.z > sz.x ? Math.PI / 2 : 0) + (e.rotY || 0); holder.updateMatrixWorld(true);
    b = new THREE.Box3().setFromObject(holder); sz = b.getSize(new THREE.Vector3());
    const k = e.len / Math.max(sz.x, sz.z), ctr = b.getCenter(new THREE.Vector3());
    holder.scale.setScalar(k); holder.updateMatrixWorld(true);
    b = new THREE.Box3().setFromObject(holder); ctr.copy(b.getCenter(new THREE.Vector3()));
    holder.position.set(-ctr.x, -b.min.y, -ctr.z);
    m.traverse(o => { if (o.isMesh) { o.castShadow = o.receiveShadow = true; } });
    c.group.children.slice().forEach(ch => { ch.visible = false; });    // hide the code-built version
    c.group.add(holder); c.wheels = null; c.seat = e.seat || null;
  });
}
/* ---- Phase 4: cash (game-only money) collected inside buildings + car shop (buy / rent). Cash lives in MySQL via server.js -> ajax/city_cash.php ---- */
const DOORS = [], CASH_ROOM = { x: 8000, z: 8000 }, PILE_OFFS = [[-4, -4], [4, -4], [0, 0], [-4, 4], [4, 4], [0, -5]];
const PREMIUM = { sport_coupe: { url: 'models/premium/sport-coupe.glb', len: 3.8 }, luxury_suv: { url: 'models/premium/luxury-suv.glb', len: 4.0 }, exec_sedan: { url: 'models/premium/exec-sedan.glb', len: 4.0 } };
let inCashRoom = false, cashBack = null, myOwned = [], carCatalog = {}, nearDoor = null; const cashPileMeshes = [];
(function buildCashRoom() {
  const g = new THREE.Group(), M = c => new THREE.MeshStandardMaterial({ color: c, roughness: 0.8 }), add = (geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); g.add(m); return m; };
  g.position.set(CASH_ROOM.x, 0, CASH_ROOM.z); add(new THREE.BoxGeometry(18, 0.2, 18), M(0x8a6a48), 0, -0.1, 0);
  [[0, -9, 18, 0.3], [0, 9, 18, 0.3], [-9, 0, 0.3, 18], [9, 0, 0.3, 18]].forEach(([x, z, w, d]) => add(new THREE.BoxGeometry(w, 4, d), M(0xe8dcc0), x, 2, z));
  add(new THREE.CylinderGeometry(1.1, 1.1, 0.05, 20), M(0x2e7d32), 0, 0.03, 7);                       // exit pad
  const L = new THREE.PointLight(0xfff0d0, 1.2, 40); L.position.set(0, 3.5, 0); g.add(L);
  PILE_OFFS.forEach(([x, z]) => { const p = new THREE.Group(); p.add(new THREE.Mesh(new THREE.BoxGeometry(0.7, 0.14, 0.34), M(0x2e8b3a))); p.add(new THREE.Mesh(new THREE.BoxGeometry(0.14, 0.16, 0.36), M(0xf4f1de))); p.position.set(x, 0.1, z); g.add(p); cashPileMeshes.push(p); });
  scene.add(g);
})();
const _cp = document.createElement('div'); _cp.style.cssText = 'position:fixed;left:50%;bottom:70px;transform:translateX(-50%);background:#222;color:#fff;padding:8px 16px;border-radius:20px;font:600 14px sans-serif;display:none;z-index:50';
document.body.appendChild(_cp);
setInterval(() => {
  if (typeof myAvatar === 'undefined' || !myAvatar || (typeof drivingCarId !== 'undefined' && drivingCarId)) { _cp.style.display = 'none'; return; }
  const p = myAvatar.position; let hint = '';
  if (inCashRoom) {
    p.x = Math.max(CASH_ROOM.x - 8.4, Math.min(CASH_ROOM.x + 8.4, p.x)); p.z = Math.max(CASH_ROOM.z - 8.4, Math.min(CASH_ROOM.z + 8.4, p.z));
    cashPileMeshes.forEach((m, i) => { if (m.visible && Math.hypot(p.x - (CASH_ROOM.x + m.position.x), p.z - (CASH_ROOM.z + m.position.z)) < 1.3) { m.visible = false; socket.emit('collectCash', i); } });
    if (Math.hypot(p.x - CASH_ROOM.x, p.z - (CASH_ROOM.z + 7)) < 2) hint = 'Press E to leave';
  } else { nearDoor = DOORS.find(d => Math.hypot(p.x - d.x, p.z - d.z) < 2.2) || null; if (nearDoor) hint = 'Press E to go inside'; }
  _cp.textContent = hint; _cp.style.display = hint ? 'block' : 'none';
}, 150);
document.addEventListener('keydown', e => {
  if (e.code !== 'KeyE' || /INPUT|TEXTAREA/.test(document.activeElement.tagName) || (typeof drivingCarId !== 'undefined' && drivingCarId)) return;
  const p = myAvatar.position;
  if (inCashRoom) { if (Math.hypot(p.x - CASH_ROOM.x, p.z - (CASH_ROOM.z + 7)) < 2) { inCashRoom = false; p.set(cashBack.x, 0, cashBack.z + 1.5); } }
  else if (nearDoor && !(typeof findNearbyCar === 'function' && findNearbyCar())) { if (!window._loggedIn) { showToast('Log in to collect cash'); return; } cashBack = { x: p.x, z: p.z }; inCashRoom = true; p.set(CASH_ROOM.x, 0, CASH_ROOM.z + 6); }
});
function spawnOwnedCar(carId) {
  const o = myOwned.find(o => o.car_id === carId && (!o.until_ts || o.until_ts * 1000 > Date.now())); if (!o) { showToast('Rental expired or not owned'); return; }
  if (window._ownCar && cars[window._ownCar] && !cars[window._ownCar].occupiedBy) { scene.remove(cars[window._ownCar].group); delete cars[window._ownCar]; }
  const g = new THREE.Group(), W = buildRetroCar(g, 0x1f2a44), r = myAvatar.rotation.y, p = myAvatar.position;
  g.position.set(p.x + Math.sin(r) * 3, 0, p.z + Math.cos(r) * 3); g.rotation.y = r - Math.PI / 2; scene.add(g);
  const id = window._ownCar = 'own_' + carId + '_' + (carIdCounter++); cars[id] = { id, group: g, occupiedBy: null, kind: 'car', wheels: W, rider: false };
  upgradeToModel(cars[id], 'car', PREMIUM[carId]); document.getElementById('car-shop').style.display = 'none'; showToast('🚗 Your car is parked next to you - press E');
}
function buyCar(id, mode) { if (!window._loggedIn) { showToast('Log in first'); return; } socket.emit('buyCar', { carId: id, mode }); }
function renderCarShop() {
  document.getElementById('car-shop-body').innerHTML = Object.entries(carCatalog).map(([id, c]) => {
    const mine = myOwned.filter(o => o.car_id === id), own = mine.some(o => !o.until_ts), rent = mine.find(o => o.until_ts && o.until_ts * 1000 > Date.now());
    const btn = (t, f) => `<button onclick="${f}" style="margin:3px;padding:6px 10px;border:0;border-radius:8px;background:#e91e8c;color:#fff;cursor:pointer">${t}</button>`;
    return `<div style="padding:10px 0;border-bottom:1px solid #444"><b>${c.name}</b><br>${own ? btn('Drive (owned)', `spawnOwnedCar('${id}')`) : btn('Buy 💵' + c.price.toLocaleString(), `buyCar('${id}','buy')`)}${rent ? btn('Drive (rented ' + Math.ceil((rent.until_ts * 1000 - Date.now()) / 60000) + ' min left)', `spawnOwnedCar('${id}')`) : (own ? '' : btn('Rent 30 min 💵' + c.rent.toLocaleString(), `buyCar('${id}','rent')`))}</div>`;
  }).join('') || 'Loading…';
}
window.addEventListener('load', () => {
  socket.on('cashState', s => {
    if (s.cash !== undefined) document.getElementById('cash-val').textContent = Number(s.cash).toLocaleString();
    if (s.cars) myOwned = s.cars; window._loggedIn = !s.guest;
    if (s.gained) showToast('💵 +' + s.gained);
    if (s.error) showToast(s.error === 'funds' ? 'Not enough cash' : s.error === 'owned' ? 'You already own this car' : 'Something went wrong');
    if (s.purchased) showToast('🚗 ' + (carCatalog[s.purchased] || {}).name + ' purchased!'); renderCarShop();
  });
  socket.on('carCatalog', c => { carCatalog = c; renderCarShop(); });
  socket.on('cashPiles', a => a.forEach((v, i) => { if (cashPileMeshes[i]) cashPileMeshes[i].visible = !!v; }));
});
function addParkedCar(x, z, rotY) {
  const group = new THREE.Group();
  const color = carColors[Math.floor(Math.random()*carColors.length)];
  const roll = Math.random(), kind = roll < 0.2 ? 'bike' : roll < 0.4 ? 'cycle' : 'car';
  const wheels = kind === 'bike' ? buildRetroBike(group, color) : kind === 'cycle' ? buildCycle(group, color) : buildRetroCar(group, color);

  group.position.set(x, 0, z);
  group.rotation.y = rotY;
  scene.add(group);

  const id = 'car_' + (carIdCounter++);
  cars[id] = { id, group, occupiedBy: null, kind, wheels, rider: kind !== 'car' };
  upgradeToModel(cars[id], kind);
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
  const door = new THREE.Mesh(new THREE.PlaneGeometry(1.3, 2.2), doorMat); door.position.set(cx, 1.35, fz); scene.add(door); DOORS.push({ x: cx, z: fz });
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
function isInWater(x, z) { return x > RIVER.x1 && x < RIVER.x2 && Math.abs(z) < 88 && !onBridge(x, z); }

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

  // A few public benches to sit on — pbench()/BENCHES are defined further down (function declarations are hoisted).
  [[16, 10], [4, 16], [10, 4], [-14, -6], [14, -40]].forEach(b => { pbench(scene, b[0], b[1]); BENCHES.push({ x: b[0], z: b[1] }); });

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
/* =========================================================
   80s KERALA RIVERSIDE — footbridge, river house (laterite + tiled roof), well, tulasi thara, jetty + vallam,
   Chinese fishing net, paddy field with a scarecrow, petti kada, old electric poles with sagging wires
   ========================================================= */
const BRIDGE = { z: -33.5, x1: 61, x2: 85, w: 2.2, y: 0.5 };
function onBridge(x, z) { return x > BRIDGE.x1 && x < BRIDGE.x2 && Math.abs(z - BRIDGE.z) < BRIDGE.w / 2; }
let riverBoat = null, riverNet = null, riverNetHang = null;
(function buildRiverside() {
  const grp = (x, y, z, ry) => { const g = new THREE.Group(); g.position.set(x, y, z); g.rotation.y = ry || 0; scene.add(g); return g; };
  const bx = (p, w, h, d, c, x, y, z, o) => { const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), kMat(c, o)); m.position.set(x, y, z); p.add(m); return m; };
  const cy = (p, r1, r2, h, c, x, y, z, seg, o) => { const m = new THREE.Mesh(new THREE.CylinderGeometry(r1, r2, h, seg || 10), kMat(c, o)); m.position.set(x, y, z); p.add(m); return m; };
  const wood = 0x7a4c22, dark = 0x4a2f16;

  // east bank land
  const east = new THREE.Mesh(new THREE.PlaneGeometry(100, 200), kMat(0x79ad4a)); east.rotation.x = -Math.PI / 2; east.position.set(134, -0.01, 0); scene.add(east);

  // ---- footbridge across the river ----
  const bc = document.createElement('canvas'); bc.width = bc.height = 64; { const g = bc.getContext('2d'); g.fillStyle = '#8b5a2b'; g.fillRect(0, 0, 64, 64); g.fillStyle = '#5a3a1a'; for (let i = 0; i < 64; i += 16) g.fillRect(i, 0, 2, 64); }
  const bt = new THREE.CanvasTexture(bc); bt.wrapS = bt.wrapT = THREE.RepeatWrapping; bt.repeat.set(24, 1);
  const bridge = grp((BRIDGE.x1 + BRIDGE.x2) / 2, 0, BRIDGE.z);
  const L = BRIDGE.x2 - BRIDGE.x1;
  const deck = new THREE.Mesh(new THREE.BoxGeometry(L, 0.12, BRIDGE.w), new THREE.MeshStandardMaterial({ map: bt, roughness: 0.9 })); deck.position.y = BRIDGE.y - 0.07; bridge.add(deck);
  [-1, 1].forEach(s => {
    bx(bridge, L, 0.07, 0.07, wood, 0, BRIDGE.y + 1.0, s * (BRIDGE.w / 2 - 0.05));
    bx(bridge, L, 0.07, 0.07, wood, 0, BRIDGE.y + 0.5, s * (BRIDGE.w / 2 - 0.05));
    for (let x = -L / 2 + 1; x < L / 2; x += 3) bx(bridge, 0.09, 1.05, 0.09, wood, x, BRIDGE.y + 0.5, s * (BRIDGE.w / 2 - 0.05));
  });
  for (let x = -L / 2 + 3; x < L / 2; x += 6) [-1, 1].forEach(s => cy(bridge, 0.14, 0.14, 2.2, dark, x, -0.55, s * 0.9));

  // ---- river house (faces the river, i.e. west) ----
  const H = grp(101, 0, -30, -Math.PI / 2);
  bx(H, 7.4, 0.5, 5.6, 0x9c5b3a, 0, 0.25, 0);                               // laterite plinth
  bx(H, 7, 3.0, 5.2, 0xf1e3c2, 0, 2.0, 0);                                  // lime-washed walls
  const tile = kMat(0xb5482a), tile2 = kMat(0x9c3b22);
  const rf = new THREE.Mesh(new THREE.BoxGeometry(8.8, 0.14, 3.55), tile); rf.position.set(0, 4.2, 1.6); rf.rotation.x = 0.44; H.add(rf);
  const rb = new THREE.Mesh(new THREE.BoxGeometry(8.8, 0.14, 3.55), tile2); rb.position.set(0, 4.2, -1.6); rb.rotation.x = -0.44; H.add(rb);
  bx(H, 8.9, 0.12, 0.2, 0x8a2f18, 0, 4.93, 0);
  [-3.5, 3.5].forEach(x => { const tri = new THREE.Mesh(new THREE.ShapeGeometry(new THREE.Shape([new THREE.Vector2(-2.6, 3.5), new THREE.Vector2(2.6, 3.5), new THREE.Vector2(0, 4.9)])), kMat(0xf1e3c2, { side: THREE.DoubleSide })); tri.rotation.y = -Math.PI / 2; tri.position.x = x; H.add(tri); });
  bx(H, 7.4, 0.3, 2.4, 0x9c5b3a, 0, 0.35, 3.8);                             // veranda floor
  [-3, -1, 1, 3].forEach(x => bx(H, 0.18, 3.0, 0.18, wood, x, 1.9, 4.8));  // veranda pillars
  bx(H, 7.6, 0.14, 0.2, wood, 0, 3.4, 4.8);
  const vr = new THREE.Mesh(new THREE.BoxGeometry(8.8, 0.12, 1.9), tile); vr.position.set(0, 3.75, 3.95); vr.rotation.x = 0.2; H.add(vr);
  bx(H, 1.3, 2.3, 0.12, dark, 0, 1.65, 2.62);                               // door
  [-2.3, 2.3].forEach(x => { bx(H, 1.2, 1.2, 0.1, wood, x, 2.1, 2.62); bx(H, 1.0, 1.0, 0.06, 0x1b1b1b, x, 2.1, 2.66); for (let k = -2; k <= 2; k++) bx(H, 0.04, 1.0, 0.05, 0x9a9a9a, x + k * 0.2, 2.1, 2.7); });
  const ts = new THREE.Mesh(new THREE.PlaneGeometry(1.4, 0.4), new THREE.MeshBasicMaterial({ map: makeSignTex('ഓം', '#f1e3c2', '#7a2e1d') })); ts.position.set(0, 3.15, 2.64); H.add(ts);
  [0.25, 0.1].forEach((y, i) => bx(H, 2 - i*0.2, 0.15, 0.5, 0x9c5b3a, 0, y, 5.3 + i*0.25));  // steps
  // adukkala (kitchen lean-to), firewood
  bx(H, 3, 2.2, 3, 0xe8d7b0, -5.2, 1.4, -0.8);
  const lt = new THREE.Mesh(new THREE.BoxGeometry(3.6, 0.12, 3.6), kMat(0xb59a58)); lt.position.set(-5.2, 2.75, -0.8); lt.rotation.z = 0.28; H.add(lt);
  for (let r = 0; r < 3; r++) for (let k = 0; k < 5 - r; k++) { const lg = cy(H, 0.09, 0.09, 1.4, 0x6b4a2a, -6.6 + r*0.1, 0.2 + r*0.17, 1.2 + k*0.19 + r*0.09, 6); lg.rotation.z = Math.PI / 2; }
  // TV antenna (Doordarshan era)
  cy(H, 0.03, 0.03, 2.0, 0x888888, 2, 5.8, -0.5, 6);
  [0.0, 0.4, 0.8].forEach(y => bx(H, 0.03, 0.03, 1.2 - y*0.6, 0x888888, 2, 5.2 + y*1.1, -0.5));
  // tulasi thara
  bx(H, 0.9, 0.7, 0.9, 0xf4efe0, 0.3, 0.35, 8.2); bx(H, 0.5, 0.2, 0.5, 0xf4efe0, 0.3, 0.8, 8.2);
  const tu = new THREE.Mesh(new THREE.SphereGeometry(0.25, 8, 6), kMat(0x2e8b3d)); tu.position.set(0.3, 1.1, 8.2); H.add(tu);
  // well (kinar) with pulley
  const ring = new THREE.Mesh(new THREE.CylinderGeometry(0.9, 0.9, 0.7, 16, 1, true), kMat(0x9c5b3a, { side: THREE.DoubleSide })); ring.position.set(-3.6, 0.35, 7.6); H.add(ring);
  cy(H, 0.82, 0.82, 0.02, 0x143a52, -3.6, 0.2, 7.6, 14);
  [-1, 1].forEach(s => bx(H, 0.1, 2.0, 0.1, wood, -3.6 + s * 0.85, 1.35, 7.6));
  bx(H, 1.9, 0.1, 0.1, wood, -3.6, 2.35, 7.6);
  const pul = new THREE.Mesh(new THREE.TorusGeometry(0.12, 0.03, 6, 12), kMat(0x333333)); pul.position.set(-3.6, 2.2, 7.6); H.add(pul);
  cy(H, 0.14, 0.11, 0.22, 0x6a6f78, -3.6, 1.0, 7.6, 8);
  // oil lantern
  cy(H, 0.07, 0.07, 0.2, 0xffd27a, 1.2, 3.1, 4.7, 8, { emissive: 0xffa233, emissiveIntensity: 0.9 });
  // 80s bicycle (Hercules)
  const bic = new THREE.Group(); bic.position.set(-3.2, 0, 5.8); bic.rotation.y = 0.5; H.add(bic);
  [-0.55, 0.55].forEach(x => { const w = new THREE.Mesh(new THREE.TorusGeometry(0.34, 0.02, 6, 20), kMat(0x222222)); w.position.set(x, 0.38, 0); bic.add(w); });
  const fr = cy(bic, 0.015, 0.015, 1.1, 0x1c1c1c, 0, 0.62, 0, 6); fr.rotation.z = Math.PI / 2;
  const hb = cy(bic, 0.015, 0.015, 0.45, 0x1c1c1c, 0.55, 0.85, 0, 6); hb.rotation.x = Math.PI / 2;
  bx(bic, 0.22, 0.05, 0.1, 0x1c1c1c, -0.3, 0.82, 0);
  // yard hens' water pot & clothesline
  [-1, 1].forEach(s => cy(H, 0.04, 0.04, 2.3, wood, 4.2, 1.15, 6 + s * 1.5, 6));
  bx(H, 0.02, 0.02, 3, 0xdddddd, 4.2, 2.2, 6);
  [0xffffff, 0xc0392b, 0xf2c230].forEach((c, i) => bx(H, 0.04, 0.8, 0.5, c, 4.2, 1.8, 5.2 + i * 0.8));

  // ---- jetty + vallam (country boat) ----
  bx(scene, 7.5, 0.12, 1.6, wood, 79.5, 0.38, -24);
  [[76.2, -24.7], [76.2, -23.3], [79, -24.7], [79, -23.3], [82, -24.7], [82, -23.3]].forEach(([x, z]) => cy(scene, 0.09, 0.09, 1.4, dark, x, 0.1, z, 6));
  riverBoat = new THREE.Group(); riverBoat.position.set(77, 0.2, -21.6); riverBoat.rotation.y = 0.08; scene.add(riverBoat);
  const hull = new THREE.Mesh(new THREE.SphereGeometry(1, 18, 8, 0, Math.PI * 2, Math.PI / 2, Math.PI / 2), kMat(0x7a4a24, { side: THREE.DoubleSide, roughness: 0.8 })); hull.scale.set(2.9, 0.55, 0.85); hull.position.y = 0.45; riverBoat.add(hull);
  bx(riverBoat, 4.2, 0.05, 0.9, 0xb98a52, 0, 0.1, 0);
  [-1, 1].forEach(s => { const c = new THREE.Mesh(new THREE.ConeGeometry(0.2, 1.0, 6), kMat(0x7a4a24)); c.position.set(s * 2.85, 0.75, 0); c.rotation.z = -s * 1.1; riverBoat.add(c); });
  [-0.8, 0.8].forEach(x => bx(riverBoat, 0.1, 0.06, 1.6, 0x5a3a1a, x, 0.5, 0));
  cy(riverBoat, 0.04, 0.04, 3.6, 0xc9a96a, 0.2, 1.0, 0.7, 6).rotation.z = 1.2;     // punting pole

  // ---- Chinese fishing net (cheena vala) ----
  const net = grp(82.4, 0, -44);
  [-1, 1].forEach(s => { const m = cy(net, 0.12, 0.14, 5.2, wood, 0.4, 2.5, s * 1.3, 8); m.rotation.z = -0.1; });
  bx(net, 0.2, 0.2, 3.0, wood, 0.0, 5.0, 0);
  riverNet = new THREE.Group(); riverNet.position.set(0, 5.0, 0); net.add(riverNet);
  const boom = cy(riverNet, 0.1, 0.07, 9.5, wood, -4.75, 0, 0, 8); boom.rotation.z = Math.PI / 2;
  riverNetHang = new THREE.Group(); riverNetHang.position.set(-9.4, 0, 0); riverNet.add(riverNetHang);
  const nc = document.createElement('canvas'); nc.width = nc.height = 64; { const g = nc.getContext('2d'); g.strokeStyle = 'rgba(235,235,225,0.9)'; g.lineWidth = 2; for (let i = 0; i <= 64; i += 8) { g.beginPath(); g.moveTo(i, 0); g.lineTo(i, 64); g.moveTo(0, i); g.lineTo(64, i); g.stroke(); } }
  const nt = new THREE.CanvasTexture(nc);
  const netMesh = new THREE.Mesh(new THREE.PlaneGeometry(4.6, 4.6), new THREE.MeshBasicMaterial({ map: nt, transparent: true, side: THREE.DoubleSide })); netMesh.rotation.x = -Math.PI / 2; netMesh.position.y = -4.5; riverNetHang.add(netMesh);
  [[-2.3, -2.3], [2.3, -2.3], [-2.3, 2.3], [2.3, 2.3]].forEach(([x, z]) => { const l = cy(riverNetHang, 0.01, 0.01, 4.6, 0xdddddd, x * 0.5, -2.3, z * 0.5, 4); });
  net.scale.setScalar(0.9);

  // ---- paddy field + scarecrow (kolam) ----
  const pc = document.createElement('canvas'); pc.width = pc.height = 128; { const g = pc.getContext('2d'); g.fillStyle = '#7fc243'; g.fillRect(0, 0, 128, 128); g.strokeStyle = '#4f9a2a'; g.lineWidth = 3; for (let i = 6; i < 128; i += 12) { g.beginPath(); g.moveTo(i, 0); g.lineTo(i, 128); g.stroke(); } g.fillStyle = 'rgba(120,190,230,0.25)'; for (let i = 0; i < 128; i += 24) g.fillRect(0, i, 128, 4); }
  const pt = new THREE.CanvasTexture(pc); pt.wrapS = pt.wrapT = THREE.RepeatWrapping; pt.repeat.set(6, 5);
  const paddy = new THREE.Mesh(new THREE.PlaneGeometry(26, 20), new THREE.MeshStandardMaterial({ map: pt, roughness: 0.9 })); paddy.rotation.x = -Math.PI / 2; paddy.position.set(113, 0.03, -52); scene.add(paddy);
  [[113, -42, 26, 0.5], [113, -62, 26, 0.5]].forEach(([x, z, w, d]) => bx(scene, w, 0.22, d, 0x8a6a3a, x, 0.1, z));
  [[100, -52], [126, -52]].forEach(([x, z]) => bx(scene, 0.5, 0.22, 20, 0x8a6a3a, x, 0.1, z));
  const sc = grp(113, 0, -52);
  cy(sc, 0.04, 0.04, 2.0, wood, 0, 1.0, 0, 6); const arm = cy(sc, 0.03, 0.03, 1.6, wood, 0, 1.5, 0, 6); arm.rotation.z = Math.PI / 2;
  const pot = new THREE.Mesh(new THREE.SphereGeometry(0.22, 10, 8), kMat(0xb5482a)); pot.position.y = 2.15; sc.add(pot);
  bx(sc, 0.5, 0.6, 0.12, 0xf4efe0, 0, 1.45, 0.02);
  const hat = new THREE.Mesh(new THREE.ConeGeometry(0.3, 0.2, 8), kMat(0xc2a060)); hat.position.y = 2.4; sc.add(hat);

  // ---- petti kada (80s roadside kiosk) near the bridge ----
  const K = grp(63.6, 0.1, -27, -Math.PI / 2);
  bx(K, 2.4, 2.2, 1.8, 0x2f8f83, 0, 1.1, 0);
  bx(K, 2.2, 0.1, 1.2, 0x2f8f83, 0, 1.3, 1.5);
  const aw = new THREE.Mesh(new THREE.BoxGeometry(2.8, 0.08, 1.4), kMat(0xd83c3c)); aw.position.set(0, 2.1, 1.1); aw.rotation.x = 0.25; K.add(aw);
  [-1, 1].forEach(s => cy(K, 0.04, 0.04, 2.0, wood, s * 1.3, 1.0, 1.7, 6));
  const ks = new THREE.Mesh(new THREE.PlaneGeometry(2.2, 0.5), new THREE.MeshBasicMaterial({ map: makeSignTex('പെട്ടിക്കട', '#f4c95d', '#5a1f0f') })); ks.position.set(0, 2.5, 0.92); K.add(ks);
  [0xe63946, 0xf2c230, 0x3a86ff, 0x2a9d8f, 0xf4a261].forEach((c, i) => bx(K, 0.28, 0.4, 0.04, c, -0.9 + i * 0.45, 1.75, 1.05));   // hanging snack packets
  [-0.6, 0.2, 0.8].forEach(x => cy(K, 0.1, 0.1, 0.24, 0xf2d13b, x, 1.45, 1.5, 8));                                                       // banana/glass jars

  // ---- old wooden electric poles with sagging wires along the main road ----
  const polesX = [-50, -30, -10, 10, 30, 50], wireMat = new THREE.LineBasicMaterial({ color: 0x222222 });
  polesX.forEach(x => { cy(scene, 0.12, 0.16, 7.0, 0x6b4a2a, x, 3.5, 3.6, 8); bx(scene, 0.12, 0.12, 2.0, 0x5a3a1a, x, 6.7, 3.6); [-0.8, 0, 0.8].forEach(dz => cy(scene, 0.05, 0.05, 0.14, 0xdfe6e9, x, 6.85, 3.6 + dz, 6)); });
  for (let i = 0; i < polesX.length - 1; i++) [-0.8, 0, 0.8].forEach(dz => {
    const pts = []; for (let k = 0; k <= 8; k++) { const t = k / 8; pts.push(new THREE.Vector3(polesX[i] + (polesX[i + 1] - polesX[i]) * t, 6.9 - Math.sin(t * Math.PI) * 0.8, 3.6 + dz)); }
    scene.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(pts), wireMat));
  });

  // ---- coconut palms + banana clumps around the house (kept clear of house, paddy and bridge) ----
  const clear = (x, z) => !(x > 91 && x < 111 && z > -40 && z < -20) && !(x > 98 && x < 128 && z > -64 && z < -40) && !(Math.abs(z - BRIDGE.z) < 3 && x < 96);
  for (let k = 0; k < 40; k++) { const x = 88 + Math.random() * 60, z = -80 + Math.random() * 130; if (clear(x, z)) addPalm(x, z); }
  [[96, -37], [97, -23], [108, -36], [109, -23]].forEach(([x, z]) => addBanana(x, z));
})();

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
let milkmaid = null, chayaNpc = null;

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

/* ---- Sound engine (WebAudio). Silent until the Sound button is on. ---- */
let soundOn = false, actx = null, master = null, glottal = null, noiseBuf = null;
const SAMPLES = {}, SAMPLE_NAMES = ['cow', 'ox', 'goat', 'hen', 'rooster', 'crow', 'koel', 'kiss', 'laugh', 'aiyyo', 'bell'];
async function loadSamples() {
  for (const n of SAMPLE_NAMES) {
    for (const ext of ['mp3', 'ogg']) {
      try {
        const r = await fetch(`/sounds/${n}.${ext}`);
        if (!r.ok || /html/i.test(r.headers.get('content-type') || '')) continue;
        SAMPLES[n] = await actx.decodeAudioData(await r.arrayBuffer()); break;
      } catch (e) { /* not provided: use the synth */ }
    }
  }
}
function playSample(name, vol, rate = 1) {
  const b = SAMPLES[name]; if (!b || !actx || !soundOn || vol < 0.01) return false;
  const s = actx.createBufferSource(), g = actx.createGain();
  s.buffer = b; s.playbackRate.value = rate * (0.95 + Math.random() * 0.1); g.gain.value = Math.min(1, vol);
  s.connect(g); g.connect(master); s.start(); return true;
}
function ensureAudio() {
  if (!actx) {
    const AC = window.AudioContext || window.webkitAudioContext; if (!AC) return false;
    actx = new AC(); master = actx.createGain(); master.gain.value = 0.9; master.connect(actx.destination);
    loadSamples();
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
function glottalWave() {
  if (!glottal) { const n = 40, re = new Float32Array(n), im = new Float32Array(n); for (let k = 1; k < n; k++) im[k] = 1 / Math.pow(k, 1.25); glottal = actx.createPeriodicWave(re, im); }
  return glottal;
}
function noiseBuffer() {
  if (!noiseBuf) { noiseBuf = actx.createBuffer(1, actx.sampleRate, actx.sampleRate); const d = noiseBuf.getChannelData(0); for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1; }
  return noiseBuf;
}
function vox({ type = 'glottal', f = [200, 200], dur = 1, vol = 0.3, formants = [[500, 5]], vib = [0, 0], am = null, noise = 0, at = 0.06, t0 = 0 }) {
  if (!actx || !soundOn || vol < 0.003) return;
  const t = actx.currentTime + t0, o = actx.createOscillator();
  if (type === 'glottal') o.setPeriodicWave(glottalWave()); else o.type = type;
  o.frequency.setValueAtTime(f[0], t);
  for (let i = 1; i < f.length; i++) o.frequency.linearRampToValueAtTime(f[i], t + dur * i / (f.length - 1));
  if (vib[0]) { const l = actx.createOscillator(), lg = actx.createGain(); l.frequency.value = vib[0]; lg.gain.value = vib[1]; l.connect(lg); lg.connect(o.frequency); l.start(t); l.stop(t + dur + 0.2); }
  const out = envGain(t, at, Math.max(0.01, dur - at - 0.15), 0.15, vol), filters = [];
  formants.forEach(([fc, q, gain = 1, sweep]) => {
    const b = actx.createBiquadFilter(), gg = actx.createGain();
    b.type = 'bandpass'; b.frequency.setValueAtTime(fc, t); b.Q.value = q; gg.gain.value = gain;
    if (sweep) { b.frequency.linearRampToValueAtTime(sweep, t + dur * 0.5); b.frequency.linearRampToValueAtTime(fc, t + dur); }
    o.connect(b); b.connect(gg); gg.connect(out); filters.push(b);
  });
  if (noise > 0) {
    const ns = actx.createBufferSource(), ng = actx.createGain(); ns.buffer = noiseBuffer(); ns.loop = true; ng.gain.value = noise;
    ns.connect(ng); filters.forEach(b => ng.connect(b)); ns.start(t); ns.stop(t + dur + 0.2);
  }
  if (am) {
    const tr = actx.createGain(), l = actx.createOscillator(), lg = actx.createGain();
    tr.gain.value = 1 - am[1]; l.frequency.value = am[0]; lg.gain.value = am[1]; l.connect(lg); lg.connect(tr.gain); l.start(t); l.stop(t + dur + 0.2);
    out.connect(tr); tr.connect(master);
  } else out.connect(master);
  o.start(t); o.stop(t + dur + 0.25);
}
const S = (name, v, rate) => playSample(name, v * 1.2, rate);
const SND = {
  moo(v, p = 1)  { if (S('cow', v, p)) return; vox({ f: [98*p, 128*p, 150*p, 118*p, 78*p], dur: 2.0, vol: 0.65*v, formants: [[280, 5, 1, 520], [700, 7, 0.7, 950], [2400, 12, 0.12]], vib: [4.5, 3], noise: 0.07, at: 0.18 }); },
  ox(v)          { if (S('ox', v)) return; SND.moo(v, 0.72); },
  goat(v)        { if (S('goat', v)) return; vox({ f: [380, 430, 470, 410], dur: 1.0, vol: 0.5*v, formants: [[900, 5], [1700, 8, 0.8], [2600, 10, 0.3]], vib: [28, 35], am: [28, 0.55], noise: 0.12, at: 0.03 }); },
  hen(v)         { if (S('hen', v)) return;
                   for (let i = 0; i < 3; i++) vox({ f: [520, 380], dur: 0.1, vol: 0.3*v, formants: [[850, 4], [1700, 5, 0.6]], noise: 0.2, at: 0.008, t0: i*0.17 + Math.random()*0.02 });
                   vox({ f: [480, 620, 430], dur: 0.45, vol: 0.3*v, formants: [[900, 4], [1900, 6, 0.6]], am: [38, 0.4], noise: 0.15, at: 0.02, t0: 0.55 }); },
  rooster(v)     { if (S('rooster', v)) return;
                   [[0, 520, 0.16], [0.2, 520, 0.16], [0.4, 700, 0.22]].forEach(([d, fr, du]) => vox({ f: [fr, fr*1.1], dur: du, vol: 0.4*v, formants: [[1300, 5], [2400, 7, 0.5]], noise: 0.06, at: 0.02, t0: d }));
                   vox({ f: [700, 1100, 1300, 1000, 600], dur: 1.3, vol: 0.42*v, formants: [[1300, 6], [2600, 8, 0.7]], vib: [7, 30], noise: 0.08, at: 0.04, t0: 0.65 }); },
  crow(v)        { if (S('crow', v)) return; for (let i = 0; i < 3; i++) vox({ f: [430, 360], dur: 0.3, vol: 0.35*v, formants: [[1100, 3], [2200, 4, 0.6]], noise: 0.45, at: 0.02, t0: i*0.46 }); },
  koel(v)        { if (S('koel', v)) return; for (let i = 0; i < 5; i++) vox({ type: 'sine', f: [600 + i*110, 950 + i*120, 1250 + i*140], dur: 0.38, vol: 0.28*v, formants: [[1400, 0.7]], vib: [6, 14], at: 0.03, t0: i*0.52 }); },
  laugh(v)       { if (S('laugh', v)) return; for (let i = 0; i < 5; i++) vox({ f: [240 - i*8, 205 - i*8], dur: 0.12, vol: (0.36 - i*0.04)*v, formants: [[700, 5], [1300, 6, 0.7]], noise: 0.3, at: 0.01, t0: i*0.17 }); },
  aiyyo(v)       { if (S('aiyyo', v)) return;
                   vox({ f: [230, 280], dur: 0.38, vol: 0.45*v, formants: [[700, 5, 1, 350], [1200, 6, 0.7, 2300]], noise: 0.05, at: 0.03 });
                   vox({ f: [270, 190], dur: 0.5, vol: 0.45*v, formants: [[450, 5], [800, 6, 0.6]], vib: [6, 8], at: 0.04, t0: 0.3 }); },
  kiss(v)        { if (S('kiss', v) || !actx || !soundOn || v < 0.01) return;
                   const t = actx.currentTime, o = actx.createOscillator(), g = envGain(t, 0.005, 0.02, 0.06, 0.3*v);
                   o.type = 'sine'; o.frequency.setValueAtTime(380, t); o.frequency.exponentialRampToValueAtTime(1100, t + 0.07); o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.12);
                   const ns = actx.createBufferSource(), bp = actx.createBiquadFilter(), ng = envGain(t + 0.06, 0.002, 0.012, 0.05, 0.35*v);
                   ns.buffer = noiseBuffer(); bp.type = 'bandpass'; bp.frequency.value = 2600; bp.Q.value = 1.2; ns.connect(bp); bp.connect(ng); ng.connect(master); ns.start(t + 0.06); ns.stop(t + 0.2); },
  bell(v)        { if (S('bell', v) || !actx || !soundOn || v < 0.01) return; const t = actx.currentTime;
                   [1150, 1725].forEach((fr, i) => { const o = actx.createOscillator(), g = envGain(t, 0.005, 0.01, 0.5, 0.14*v/(i + 1)); o.type = 'sine'; o.frequency.value = fr; o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.6); }); },
};
function listenerPos() { return (drivingCarId && cars[drivingCarId]) ? cars[drivingCarId].group.position : myAvatar.position; }
function earVol(x, z, range = 24) { const q = listenerPos(), d = Math.hypot(q.x - x, q.z - z); return Math.pow(Math.max(0, 1 - d / range), 1.5); }
function oxCall(c) { const p = c.group.position, v = earVol(p.x, p.z, 34); if (v > 0.01) { SND.ox(v); setTimeout(() => SND.ox(v * 0.8), 450); } }

/* ---- Animals: pashu (cow), aadu (goat), kozhi (hen/rooster). ---- */
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
[0xc0392b, 0xf0e6d0, 0x2b2b2b].forEach((c, i) => addAnimal(makeHen(c, 0x3a2a1a, false), 94.5 + i*0.7, -30 + i, 2.2, 2.4, 0.19 + i*0.02));
addAnimal(makeGoat(0xf3ebdd), 93, -26, 1.6, 1.3, 0.12);
addAnimal(makeCow(0xf3ebdd, 0x8a5a3a), 94, -35, 2.0, 1.2, 0.06);

const ANIMAL_SND = { cow: [26, 55, SND.moo], goat: [19, 55, SND.goat], hen: [11, 50, SND.hen], rooster: [47, 60, SND.rooster] };
function animalPos(a, t) {
  return { x: a.hx + a.ax*Math.sin(a.w*t + a.p1) + 0.4*a.ax*Math.sin(2.7*a.w*t + a.p2),
           z: a.hz + a.az*Math.sin(1.3*a.w*t + a.p3) + 0.4*a.az*Math.cos(2.1*a.w*t + a.p4) };
}
function animalsTick() {
  const now = Date.now() / 1000, nowMs = Date.now(), inG = document.getElementById('name-gate').style.display === 'none';
  animals.forEach(a => {
    const p = animalPos(a, now), q = animalPos(a, now + 0.25);
    let px = p.x, pz = p.z;
    if (inG) {
      const mp = myAvatar.position, ddx = mp.x - p.x, ddz = mp.z - p.z, dd = Math.hypot(ddx, ddz);
      if (a.kind === 'hen' || a.kind === 'rooster') {
        const want = dd < 2.4 && !drivingCarId ? (2.4 - dd) * 1.1 : 0;
        a.fx = (a.fx || 0) + ((-ddx / (dd || 1)) * want - (a.fx || 0)) * 0.12;
        a.fz = (a.fz || 0) + ((-ddz / (dd || 1)) * want - (a.fz || 0)) * 0.12;
        if (want > 0.8 && !a.scared) { a.scared = true; if (soundOn && Math.random() < 0.6) { const v = earVol(p.x, p.z, 18); if (v > 0.02) SND.hen(v); } }
        if (want === 0) a.scared = false;
        px += a.fx; pz += a.fz;
      } else if (a.kind === 'goat' && !drivingCarId && dd < 1.25 && nowMs > goatCool) headbutt(a, ddx, ddz, dd);
      else if (a.kind === 'cow' && dd < 3 && nowMs > (a.mooAt || 0)) { a.mooAt = nowMs + 14000; if (soundOn) SND.moo(Math.max(0.5, earVol(p.x, p.z, 26))); }
    }
    a.g.position.x = px; a.g.position.z = pz;
    const vx = q.x - p.x, vz = q.z - p.z, sp = Math.hypot(vx, vz) / 0.25;
    if (sp > 0.03) a.g.rotation.y = Math.atan2(-vz, vx);
    const amp = Math.min(0.5, sp * 1.6), ph = now * (3 + sp * 5);
    a.g.userData.legs.forEach((l, i) => { l.rotation.z = Math.sin(ph + (i % 2 ? Math.PI : 0)) * amp; });
    if (a.g.userData.up) a.g.userData.up.rotation.z = -Math.max(0, Math.sin(now * 3 + a.seed)) * 0.55 * (sp < 0.5 ? 1 : 0.3);
    const cfg = ANIMAL_SND[a.kind], idx = Math.floor((now + a.seed * 3.7) / cfg[0]);
    if (a.lastIdx === null) a.lastIdx = idx;
    else if (idx !== a.lastIdx) {
      a.lastIdx = idx;
      if (soundOn && ((idx * 2654435761 + a.seed * 97) >>> 0) % 100 < cfg[1]) { const v = earVol(p.x, p.z, 26); if (v > 0.02) cfg[2](v); }
    }
  });
}

/* ---- Shared old radio ---- */
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

/* =========================================================
   FUN & SOCIAL — emotes, consensual kisses, goat headbutts, hens that run from you, role picker, 80s film look
   ========================================================= */
let myRole = null, knock = null, goatCool = 0, nextAmbient = 0, kissFrom = null, kissHide = null;
const emoteSprites = [], heartFx = [];
const EMOJI = { dance: '💃', laugh: '😂', aiyyo: '😱', kiss: '😘', blush: '😳' };
function avatarOf(id) { return id === socket.id ? myAvatar : (others[id] && others[id].group); }
function showEmote(id, type) {
  const g = avatarOf(id); if (!g || !g.userData) return;
  const now = performance.now();
  g.userData.emote = { type, until: now + (type === 'kiss' ? 1800 : 2800) };
  const sp = emojiSprite(EMOJI[type] || '✨', 0.9); sp.position.set(0, 2.45, 0); g.add(sp);
  emoteSprites.push({ sp, g, until: now + 2600 });
  const v = earVol(g.position.x, g.position.z, 30);
  if (type === 'laugh') SND.laugh(v); else if (type === 'aiyyo') SND.aiyyo(v);
}
let kissFreeze = 0;
function kissFx(fromId, toId) {
  const a = avatarOf(fromId), b = avatarOf(toId); if (!a || !b) return;
  showEmote(fromId, 'kiss'); showEmote(toId, 'blush');
  const yaw = Math.atan2(b.position.x - a.position.x, b.position.z - a.position.z);
  a.rotation.y = yaw; b.rotation.y = yaw + Math.PI;
  if (fromId === socket.id || toId === socket.id) kissFreeze = performance.now() + 900;
  const now = performance.now();
  for (let i = 0; i < 6; i++) { const sp = emojiSprite('❤️', 0.55); sp.visible = false; scene.add(sp); heartFx.push({ sp, a, b, t0: now + i * 170, dur: 1300, j: (Math.random() - 0.5) * 0.6 }); }
  const mid = earVol((a.position.x + b.position.x) / 2, (a.position.z + b.position.z) / 2, 30);
  setTimeout(() => SND.kiss(mid), 250);
}
function nearestOther() {
  let best = null, bd = 3.6;
  Object.keys(others).forEach(id => { const o = others[id]; if (!o.group.visible) return; const d = Math.hypot(o.group.position.x - myAvatar.position.x, o.group.position.z - myAvatar.position.z); if (d < bd) { bd = d; best = id; } });
  return best;
}
function inGame() { return document.getElementById('name-gate').style.display === 'none'; }
function doKiss() { if (!inGame()) return; const id = nearestOther(); if (!id) { showToast('💋 Nobody close enough — walk up to someone first'); return; } socket.emit('kiss', { targetId: id }); }
function doEmote(type) { if (inGame()) socket.emit('emote', { type }); }
document.getElementById('kiss-btn').onclick = doKiss;
document.getElementById('emote-dance').onclick = () => doEmote('dance');
document.getElementById('emote-laugh').onclick = () => doEmote('laugh');
document.getElementById('emote-aiyyo').onclick = () => doEmote('aiyyo');
function hideKissPrompt() { document.getElementById('kiss-prompt').style.display = 'none'; }
document.getElementById('kiss-back').onclick = () => { if (kissFrom) socket.emit('kiss', { targetId: kissFrom }); hideKissPrompt(); };
document.getElementById('kiss-ignore').onclick = hideKissPrompt;
document.getElementById('kiss-block').onclick = () => { socket.emit('setNoKiss', true); hideKissPrompt(); showToast('🚫 Kisses are off — others can no longer kiss you'); };
addEventListener('keydown', e => {
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  const k = e.key.toLowerCase();
  if (k === 'k') doKiss(); else if (k === 'x') doEmote('dance'); else if (k === 'l') doEmote('laugh'); else if (k === 'z') doEmote('aiyyo'); else if (k === 'j') doHoldHands();
  else if (k === 'v') { document.body.classList.toggle('novintage'); showToast(document.body.classList.contains('novintage') ? '🎞️ 80s film look off' : '🎞️ 80s film look on'); }
});

function headbutt(a, ddx, ddz, dd) {
  goatCool = Date.now() + 9000; const l = dd || 1;
  knock = { vx: ddx / l * 0.3, vz: ddz / l * 0.3, n: 16 };
  showToast('🐐 Aiyyo! The aadu headbutted you!'); socket.emit('emote', { type: 'aiyyo' });
  SND.goat(Math.max(0.7, earVol(a.g.position.x, a.g.position.z, 20)));
}
function funTick(now) {
  if (knock && knock.n-- > 0) { myAvatar.position.x += knock.vx; myAvatar.position.z += knock.vz; knock.vx *= 0.9; knock.vz *= 0.9; } else knock = null;
  for (let i = emoteSprites.length - 1; i >= 0; i--) { const e = emoteSprites[i]; e.sp.position.y = 2.45 + (1 - (e.until - now) / 2600) * 0.3; if (now > e.until) { e.g.remove(e.sp); emoteSprites.splice(i, 1); } }
  for (let i = heartFx.length - 1; i >= 0; i--) {
    const h = heartFx[i], t = (now - h.t0) / h.dur;
    if (t < 0) continue;
    if (t >= 1) { scene.remove(h.sp); heartFx.splice(i, 1); continue; }
    h.sp.visible = true;
    h.sp.position.set(h.a.position.x + (h.b.position.x - h.a.position.x) * t + h.j * Math.sin(t * 3), 1.7 + Math.sin(t * Math.PI) * 0.7 + t * 0.2, h.a.position.z + (h.b.position.z - h.a.position.z) * t);
    h.sp.material.opacity = 1 - t * t;
  }
  if (soundOn && now > nextAmbient) { if (nextAmbient) { const kind = ['koel', 'crow', 'koel'][Math.floor(Math.random() * 3)]; SND[kind](0.3); } nextAmbient = now + 14000 + Math.random() * 22000; }
  if (riverBoat) { riverBoat.position.y = 0.2 + Math.sin(now / 760) * 0.03; riverBoat.rotation.z = Math.sin(now / 1100) * 0.03; }
  if (riverNet) { riverNet.rotation.z = 0.32 + Math.sin(now / 2800) * 0.07; riverNetHang.rotation.z = -riverNet.rotation.z; }
}
function nearestBench() {
  if (drivingCarId) return null;
  let best = null, bd = 2.6;
  BENCHES.forEach(b => { const d = Math.hypot(b.x - myAvatar.position.x, b.z - myAvatar.position.z); if (d < bd) { bd = d; best = b; } });
  return best;
}
function toggleSit() {
  if (sitting) { sitting = false; mySitPos = null; myAvatar.position.y = 0; socket.emit('sitState', { sitting: false }); return; }
  const b = nearestBench(); if (!b) return;
  sitting = true; mySitPos = b;
  myAvatar.position.x = b.x; myAvatar.position.z = b.z; myAvatar.position.y = -0.35;
  myAvatar.rotation.y = Math.atan2(-b.x, -b.z);
  socket.emit('sitState', { sitting: true, x: b.x, y: 0, z: b.z, rotY: myAvatar.rotation.y });
}
document.getElementById('sit-prompt').onclick = toggleSit;
function pairKey(a, b) { return [a, b].sort().join('|'); }
function handLinkMesh() {
  const g = new THREE.Group();
  const bar = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 1, 6), new THREE.MeshStandardMaterial({ color: 0xffd1e6 }));
  bar.rotation.z = Math.PI / 2; g.add(bar);
  return g;
}
function doHoldHands() {
  if (!inGame() || sitting) return;
  if (handWith) { socket.emit('holdHandsRelease'); return; }
  const id = nearestOther(); if (!id) { showToast('🤝 Nobody close enough'); return; }
  socket.emit('holdHandsRequest', { targetId: id });
  showToast('🤝 Invite sent — waiting for ' + (others[id].name || 'them'));
}
document.getElementById('hand-btn').onclick = doHoldHands;
document.getElementById('hp-yes').onclick = () => { if (handPending) socket.emit('holdHandsRespond', { fromId: handPending, accept: true }); hideHandPrompt(); };
document.getElementById('hp-no').onclick = () => { if (handPending) socket.emit('holdHandsRespond', { fromId: handPending, accept: false }); hideHandPrompt(); };
function hideHandPrompt() { document.getElementById('hand-prompt').style.display = 'none'; handPending = null; }
function updateHandLinks() {
  Object.values(handLinks).forEach(m => {
    const ga = avatarOf(m._a), gb = avatarOf(m._b); if (!ga || !gb) return;
    const mid = ga.position.clone().add(gb.position).multiplyScalar(0.5); mid.y += 1.05;
    m.position.copy(mid);
    const dx = gb.position.x - ga.position.x, dz = gb.position.z - ga.position.z;
    m.scale.x = Math.hypot(dx, dz); m.rotation.y = Math.atan2(dx, dz) + Math.PI / 2;
  });
}
function doSlap() { if (!inGame()) return; const id = nearestOther(); if (!id) { showToast('🖐️ Nobody close enough'); return; } socket.emit('slap', { targetId: id }); }
document.getElementById('slap-btn').onclick = doSlap;
function slapFx(fromId, toId) {
  const a = avatarOf(fromId), b = avatarOf(toId); if (!a || !b) return;
  showEmote(toId, 'aiyyo');
  if (fromId === socket.id) a.rotation.y = Math.atan2(b.position.x - a.position.x, b.position.z - a.position.z);
  const sp = emojiSprite('✋', 0.7); sp.position.set(b.position.x, 2.1, b.position.z); scene.add(sp);
  const t0 = performance.now(); const tick = () => { const t = (performance.now() - t0) / 350; if (t >= 1) { scene.remove(sp); return; } sp.position.y = 2.1 + Math.sin(t * Math.PI) * 0.5; sp.material.opacity = 1 - t; requestAnimationFrame(tick); };
  tick();
  SND.aiyyo(earVol((a.position.x + b.position.x) / 2, (a.position.z + b.position.z) / 2, 30));
}
document.getElementById('slap-block') && (document.getElementById('slap-block').onclick = () => { socket.emit('setNoSlap', true); showToast('🚫 Slaps are off — others can no longer slap you'); });

function socialTick() {
  const gate = !inGame(), near = (!gate && !drivingCarId && !sitting) ? nearestOther() : null;
  document.getElementById('social-ui').style.display = near ? 'flex' : 'none';
  if (near) document.getElementById('kiss-btn').textContent = '💋 Kiss ' + (others[near].name || '') + ' (K)';
  document.getElementById('hand-btn').style.display = (near && !handWith) ? '' : (handWith ? '' : 'none');
  document.getElementById('hand-btn').textContent = handWith ? '🤝 Let go' : '🤝 Hold hands';
  document.getElementById('slap-btn').style.display = near ? '' : 'none';
  nearBench = (!gate && !sitting) ? nearestBench() : null;
  document.getElementById('sit-prompt').style.display = nearBench ? 'block' : 'none';
  document.getElementById('emote-bar').style.display = gate ? 'none' : 'flex';
}
document.querySelectorAll('.role-pill').forEach(b => b.onclick = () => {
  if (b.disabled) return;
  document.querySelectorAll('.role-pill').forEach(x => x.classList.remove('selected')); b.classList.add('selected');
  myRole = b.dataset.role === 'visitor' ? null : b.dataset.role;
});

function keralaTick(t) {
  const nowMs = Date.now(), nowS = nowMs / 1000;
  teaFx.steam.forEach(s => {
    const p = (t*0.25 + s.phase) % 1;
    s.sp.position.set(Math.sin(p*6 + s.phase*5)*0.12, 1.7 + p*1.6, 0);
    s.sp.scale.setScalar(0.35 + p*0.7); s.sp.material.opacity = (1 - p) * 0.55;
  });
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
  animalsTick(); funTick(performance.now()); socialTick();
  if (milkmaid) { milkmaid.rotation.z = Math.sin(t*1.2)*0.03; milkmaid.rotation.x = Math.sin(t*3)*0.03; }
  teaFx.dial.emissiveIntensity = radioState.on ? 0.7 + 0.25*Math.sin(t*5) : 0;
  teaFx.note.visible = radioState.on; teaFx.note.position.y = 1.0 + Math.sin(t*2.5)*0.08;
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

// Floating balloon pickups
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

// --- Treasure chests ---
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

// --- Delivery job markers ---
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

// --- Waypoint ---
const waypointBeacon = new THREE.Mesh(
  new THREE.ConeGeometry(0.4, 1.2, 6),
  new THREE.MeshStandardMaterial({ color: 0xFFD700, emissive: 0xFFD700, emissiveIntensity: 0.6, transparent: true, opacity: 0.9 })
);
waypointBeacon.visible = false;
scene.add(waypointBeacon);
let myWaypoint = null;

/* =========================================================
   2) AVATARS — distinct male / female / other silhouettes, not just color
   ========================================================= */
const genderColors = { male: 0x3b82f6, female: 0xFE019A, other: 0x9b5de5 };
const skinTone = 0xffe0c2;

const _geo = {};
const G = (key, make) => _geo[key] || (_geo[key] = make());
const shoeMatShared = new THREE.MeshStandardMaterial({ color: 0x1b1512, roughness: 0.6 });

/* ---- Phase 2: realistic skin + 80s Vice City shirts (cached canvas textures) ---- */
const _skinTx = {}, _shirtTx = {};
function skinTex(hex) {
  if (_skinTx[hex]) return _skinTx[hex];
  const c = document.createElement('canvas'); c.width = c.height = 128; const x = c.getContext('2d');
  const base = new THREE.Color(hex), css = '#' + base.getHexString();
  x.fillStyle = css; x.fillRect(0, 0, 128, 128);
  const gr = x.createLinearGradient(0, 0, 0, 128); gr.addColorStop(0, 'rgba(255,200,170,0.10)'); gr.addColorStop(0.5, 'rgba(0,0,0,0)'); gr.addColorStop(1, 'rgba(60,20,10,0.12)');
  x.fillStyle = gr; x.fillRect(0, 0, 128, 128);
  for (let i = 0; i < 900; i++) { x.fillStyle = Math.random() < 0.6 ? 'rgba(90,50,30,' + (0.03 + Math.random() * 0.07) + ')' : 'rgba(255,230,210,' + (0.03 + Math.random() * 0.06) + ')'; x.fillRect(Math.random() * 128, Math.random() * 128, 1 + Math.random() * 1.5, 1 + Math.random() * 1.5); }
  for (let i = 0; i < 14; i++) { x.fillStyle = 'rgba(110,60,30,0.16)'; x.beginPath(); x.arc(Math.random() * 128, Math.random() * 128, 0.8 + Math.random(), 0, 7); x.fill(); }
  return (_skinTx[hex] = new THREE.CanvasTexture(c));
}
function shirtTex(color, type) {
  const key = color + type; if (_shirtTx[key]) return _shirtTx[key];
  const c = document.createElement('canvas'); c.width = c.height = 64; const x = c.getContext('2d');
  x.fillStyle = typeof color === 'number' ? '#' + color.toString(16).padStart(6, '0') : color; x.fillRect(0, 0, 64, 64);
  x.strokeStyle = 'rgba(255,255,255,0.28)'; x.lineWidth = 3; for (let i = -64; i < 128; i += 14) { x.beginPath(); x.moveTo(i, 0); x.lineTo(i + 64, 64); x.stroke(); }
  x.fillStyle = 'rgba(255,255,255,0.45)'; [[12, 14], [44, 36], [26, 54], [56, 8]].forEach(p => { for (let k = 0; k < 5; k++) { x.beginPath(); x.ellipse(p[0] + Math.cos(k * 1.26) * 4, p[1] + Math.sin(k * 1.26) * 4, 3, 1.6, k * 1.26, 0, 7); x.fill(); } });
  x.fillStyle = 'rgba(0,0,0,0.10)'; for (let i = 0; i < 160; i++) x.fillRect(Math.random() * 64, Math.random() * 64, 1, 1);
  const t = new THREE.CanvasTexture(c); t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(3, 2);
  return (_shirtTx[key] = t);
}

function makeAvatarMesh(gender, outfitColor, hairStyle, role, look) {
  look = look || {};
  const isF = gender === 'female' || role === 'karavakkari';
  const mature = role === 'karavakkari';
  const color = outfitColor ? parseInt(String(outfitColor).replace('#', ''), 16) : (genderColors[gender] || genderColors.other);
  const style = hairStyle || 'short';
  const group = new THREE.Group(), body = new THREE.Group(); group.add(body);
  const skinHex = role === 'karavakkari' ? 0xc68a5d : role === 'chayakkaran' ? 0xae7a50 : 0xefc6a2;
  const M = (c, o) => new THREE.MeshStandardMaterial(Object.assign({ color: c, roughness: 0.75 }, o || {}));
  const skin = M(0xffffff, { roughness: 0.55, map: skinTex(look.skin || skinHex) });
  const hairHex = look.hair || (mature ? 0x120c0a : 0x1d1411), hairMat = M(hairHex, { roughness: 0.4 });
  const gold = M(0xf2b632, { metalness: 0.55, roughness: 0.35 });
  const add = (parent, geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); parent.add(m); return m; };
  const sph = (r, ws = 14, hs = 10) => G('s' + [r, ws, hs].join(), () => new THREE.SphereGeometry(r, ws, hs));
  const cyl = (r1, r2, h, s = 12) => G('c' + [r1, r2, h, s].join(), () => new THREE.CylinderGeometry(r1, r2, h, s));
  const sh = isF ? 0.18 : 0.215, hipX = isF ? 0.095 : 0.09, hipY = 0.9, shY = 1.45, headY = 1.67;
  body.scale.setScalar(isF ? (mature ? 0.935 : 0.94) : 1);

  const prof = isF
    ? (mature ? [[0.001,0.84],[0.158,0.86],[0.195,0.94],[0.165,1.02],[0.122,1.12],[0.14,1.22],[0.158,1.30],[0.145,1.38],[0.12,1.45],[0.05,1.49],[0.001,1.5]]
              : [[0.001,0.84],[0.15,0.86],[0.18,0.94],[0.15,1.02],[0.115,1.12],[0.13,1.22],[0.148,1.30],[0.14,1.38],[0.12,1.45],[0.05,1.49],[0.001,1.5]])
    : [[0.001,0.86],[0.16,0.88],[0.172,0.98],[0.158,1.12],[0.178,1.28],[0.19,1.40],[0.15,1.47],[0.06,1.51],[0.001,1.52]];
  const torsoMat = role === 'chayakkaran' ? M(0xf4f1e8) : role === 'karavakkari' ? M(0xa61e24, { roughness: 0.6 }) : M(0xffffff, { roughness: 0.8, map: shirtTex(color, 'vice') });
  const torso = add(body, G('torso' + isF + mature, () => new THREE.LatheGeometry(prof.map(p => new THREE.Vector2(p[0], p[1])), 22)), torsoMat, 0, 0, 0);
  torso.scale.z = 0.7;
  [-1, 1].forEach(s => add(body, sph(0.058, 10, 8), torsoMat, s * sh, shY - 0.035, 0));
  add(body, cyl(0.048, 0.054, 0.11, 12), skin, 0, 1.535, 0);
  if (!role) [-1, 1].forEach(s => { const c = add(body, new THREE.BoxGeometry(0.075, 0.012, 0.055), torsoMat, s * 0.045, 1.5, 0.095); c.rotation.set(0.55, 0, s * 0.55); });

  const head = new THREE.Group(); head.position.set(0, headY, 0); body.add(head);
  add(head, sph(0.105, 22, 16), skin, 0, 0, 0).scale.set(0.9, 1.1, 0.97);
  add(head, sph(0.07, 14, 10), skin, 0, -0.065, 0.025).scale.set(0.95, 0.85, 0.9);
  const eyeWhite = M(0xf6f2ec, { roughness: 0.3 }), irisMat = M(0x2a1a10, { roughness: 0.2 });
  [-1, 1].forEach(s => {
    add(head, sph(0.02, 8, 8), skin, s * 0.093, -0.005, 0).scale.set(0.5, 1, 0.8);
    add(head, sph(0.0155, 10, 8), eyeWhite, s * 0.04, 0.022, 0.088).scale.set(1, 0.72, 0.55);
    add(head, new THREE.CircleGeometry(0.0085, 12), irisMat, s * 0.04, 0.022, 0.0978);
    add(head, new THREE.CircleGeometry(0.0033, 8), new THREE.MeshBasicMaterial({ color: 0xffffff }), s * 0.04 + 0.003, 0.025, 0.0985);
    add(head, new THREE.BoxGeometry(0.034, 0.0065, 0.008), hairMat, s * 0.04, 0.047, 0.0835).rotation.z = -s * 0.12;
    if (isF) add(head, new THREE.BoxGeometry(0.03, 0.003, 0.006), M(0x0d0806), s * 0.04, 0.034, 0.09);
  });
  if (look.shades) { const sm = M(0x0b0b10, { roughness: 0.1, metalness: 0.6 }); [-1, 1].forEach(s => add(head, new THREE.BoxGeometry(0.052, 0.034, 0.008), sm, s * 0.043, 0.022, 0.1)); add(head, new THREE.BoxGeometry(0.03, 0.006, 0.008), sm, 0, 0.032, 0.1); }
  add(head, sph(0.016, 8, 8), skin, 0, -0.012, 0.098).scale.set(0.9, 1.1, 1.2);
  const lipMat = M(role === 'karavakkari' ? 0xa8323f : isF ? 0xc9686a : 0xb87a68, { roughness: 0.4 });
  add(head, sph(0.0165, 8, 6), lipMat, 0, -0.049, 0.089).scale.set(1.4, 0.42, 0.55);
  add(head, sph(0.0165, 8, 6), lipMat, 0, -0.058, 0.088).scale.set(1.3, 0.45, 0.55);
  if (role === 'chayakkaran') [-1, 0, 1].forEach(s => add(head, sph(0.017, 8, 6), hairMat, s * 0.016, -0.038, 0.095).scale.set(1.7, 0.55, 0.8));

  const cap = add(head, new THREE.SphereGeometry(0.113, 22, 16, 0, Math.PI * 2, 0, Math.PI * 0.42), hairMat, 0, 0.012, -0.004);
  cap.scale.set(0.94, 1.12, 1.0); cap.rotation.x = -0.32;
  add(head, sph(0.1, 14, 10), hairMat, 0, mature || style === 'pony' ? -0.02 : 0.0, -0.035).scale.set(0.9, mature ? 1.0 : 0.8, 0.9);
  if (mature) {
    for (let i = 0; i < 9; i++) {
      add(body, sph(0.03 - i * 0.0012, 8, 6), hairMat, 0, 1.6 - i * 0.075, -0.115 - i * 0.012).scale.set(1.1, 1.0, 0.9);
      add(body, sph(0.011, 6, 6), M(0xffffff), i % 2 ? 0.026 : -0.026, 1.6 - i * 0.075, -0.13 - i * 0.012);
    }
    add(body, sph(0.02, 6, 6), M(0xa61e24), 0, 0.93, -0.215);
    for (let i = 0; i < 11; i++) { const a = -1.3 + i * 0.26; add(body, sph(0.012, 6, 6), M(0xffffff), Math.sin(a) * 0.105, headY + 0.04, -Math.cos(a) * 0.105); }
    add(head, sph(0.007, 6, 6), M(0xb01822), 0, 0.062, 0.088);
    [-1, 1].forEach(s => { add(head, sph(0.011, 6, 6), gold, s * 0.095, -0.03, 0.005); add(head, sph(0.007, 6, 6), gold, s * 0.095, -0.047, 0.005); });
  } else if (style === 'pony') {
    add(body, cyl(0.03, 0.045, 0.2, 8), hairMat, 0, 1.6, -0.15).rotation.x = 0.5;
    add(body, cyl(0.02, 0.03, 0.2, 8), hairMat, 0, 1.46, -0.21).rotation.x = 0.25;
  } else if (style === 'bandana') {
    const b = add(head, new THREE.TorusGeometry(0.108, 0.012, 8, 22), M(0xffd700), 0, 0.06, 0); b.rotation.x = Math.PI / 2; b.scale.set(0.94, 1, 1.0);
  }

  if (isF && !mature && !role) { if (style !== 'pony') add(head, sph(0.1, 14, 10), hairMat, 0, -0.14, -0.05).scale.set(0.95, 2.1, 0.75); [-1, 1].forEach(s => add(head, sph(0.008, 6, 6), gold, s * 0.097, -0.03, 0.005)); }
  const arms = [], elbows = [], knees = [], sleeveMat = torsoMat;
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * sh, shY - 0.03, 0); pv.rotation.z = s * 0.07; body.add(pv);
    add(pv, cyl(0.04, 0.033, 0.29, 10), skin, 0, -0.145, 0);
    add(pv, sph(0.034, 10, 8), skin, 0, -0.29, 0);
    const fa = new THREE.Group(); fa.position.set(0, -0.29, 0); fa.rotation.x = -0.22; pv.add(fa);
    add(fa, cyl(0.033, 0.024, 0.26, 10), skin, 0, -0.13, 0);
    add(fa, sph(0.03, 10, 8), skin, 0, -0.275, 0).scale.set(0.95, 1.3, 0.55);
    add(fa, cyl(0.008, 0.007, 0.05, 5), skin, -s * 0.026, -0.27, 0.014).rotation.z = s * 0.6;
    add(pv, cyl(0.05, 0.046, 0.17, 10), sleeveMat, 0, -0.085, 0);
    if (mature) [0, 1].forEach(k => { add(fa, new THREE.TorusGeometry(0.03, 0.006, 6, 12), gold, 0, -0.232 - k * 0.018, 0).rotation.x = Math.PI / 2; });
    arms.push(pv); elbows.push(fa);
  });

  const legs = [], legMat = (isF || role === 'chayakkaran') ? skin : M(look.pants !== undefined ? look.pants : 0x2e2e38);
  const footMat = role === 'chayakkaran' ? M(0x6b4a2a) : shoeMatShared;
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * hipX, hipY, 0); body.add(pv);
    add(pv, cyl(0.078, 0.058, 0.44, 12), legMat, 0, -0.22, 0);
    add(pv, sph(0.058, 10, 8), legMat, 0, -0.44, 0);
    const sk = new THREE.Group(); sk.position.set(0, -0.44, 0); pv.add(sk);
    add(sk, cyl(0.056, 0.036, 0.42, 10), legMat, 0, -0.21, 0);
    add(sk, new THREE.BoxGeometry(0.075, 0.055, 0.21), footMat, 0, -0.435, 0.045);
    legs.push(pv); knees.push(sk);
  });

  if (role === 'chayakkaran') {
    add(body, cyl(0.19, 0.27, 0.62, 18), M(0xf6f3ea), 0, 0.64, 0);
    add(body, new THREE.TorusGeometry(0.27, 0.014, 6, 24), gold, 0, 0.345, 0).rotation.x = Math.PI / 2;
    add(body, new THREE.TorusGeometry(0.195, 0.02, 6, 20), M(0xe3ddcc), 0, 0.93, 0).rotation.x = Math.PI / 2;
    const cv = document.createElement('canvas'); cv.width = cv.height = 32; const g = cv.getContext('2d');
    g.fillStyle = '#ffffff'; g.fillRect(0, 0, 32, 32); g.fillStyle = '#c0392b'; for (let i = 0; i < 32; i += 8) { g.fillRect(i, 0, 4, 32); g.fillRect(0, i, 32, 4); }
    const tx = new THREE.CanvasTexture(cv); tx.wrapS = tx.wrapT = THREE.RepeatWrapping; tx.repeat.set(2, 6);
    const tw = add(body, new THREE.BoxGeometry(0.1, 0.5, 0.035), new THREE.MeshStandardMaterial({ map: tx, roughness: 0.9 }), -sh + 0.01, 1.27, 0.12); tw.rotation.z = 0.12;
  } else if (role === 'karavakkari') {
    add(body, cyl(0.2, 0.42, 0.86, 22), M(0x1e7a4a, { roughness: 0.65 }), 0, 0.5, 0);
    add(body, new THREE.TorusGeometry(0.42, 0.022, 6, 28), gold, 0, 0.1, 0).rotation.x = Math.PI / 2;
    add(body, new THREE.TorusGeometry(0.33, 0.012, 6, 28), gold, 0, 0.28, 0).rotation.x = Math.PI / 2;
    const sash = new THREE.Group(); sash.position.set(0, 1.22, 0); sash.scale.set(1, 1, 0.7); sash.rotation.z = 0.8; body.add(sash);
    add(sash, new THREE.TorusGeometry(0.175, 0.02, 8, 24), M(0xf2c230), 0, 0, 0).rotation.x = Math.PI / 2;
  } else if (isF) {
    add(body, cyl(0.17, 0.27, 0.46, 18), M(0xffffff, { roughness: 0.8, map: shirtTex(color, 'vice') }), 0, 0.7, 0);
  }
  if (look.h) group.scale.setScalar(look.h); if (look.w) body.scale.x *= look.w;
  group.userData = { legs, arms, elbows, knees, body, head, emote: null };
  group.traverse(o => { if (o.isMesh) o.castShadow = true; });
  return group;
}

function tickAvatar(g, now) {
  const u = g && g.userData; if (!u || !u.legs) return;
  const p = g.position, sp = u.lx === undefined ? 0 : Math.hypot(p.x - u.lx, p.z - u.lz);
  u.lx = p.x; u.lz = p.z; u.sp = (u.sp || 0) * 0.75 + sp * 0.25; u.ph = (u.ph || 0) + (u.walkAmp === undefined ? sp * 9 : 0);
  if (u.walkAmp !== undefined) { u.ph += Math.min(0.1, (now - (u.pn || now)) / 1000) * 7; u.pn = now; }
  const amp = u.walkAmp !== undefined ? u.walkAmp : Math.min(0.75, u.sp * 6), la = Math.sin(u.ph) * amp, T = now / 1000;
  let a0x = -la * 0.85, a1x = la * 0.85, a0z = -0.07, a1z = 0.07, bob = Math.abs(Math.sin(u.ph)) * 0.02 * amp * 4 + Math.sin(T * 2) * 0.003;
  let rx = 0, rz = 0, l0 = la, l1 = -la;
  const e = u.emote && now < u.emote.until ? u.emote : null;
  if (e) {
    const t6 = T * 6;
    if (e.type === 'dance') { a0x = -2.4 + Math.sin(t6) * 0.5; a1x = -2.4 - Math.sin(t6) * 0.5; a0z = -0.5; a1z = 0.5; rz = Math.sin(t6 * 0.5) * 0.12; bob = Math.abs(Math.sin(t6)) * 0.07; l0 = Math.sin(t6) * 0.5; l1 = -Math.sin(t6) * 0.5; }
    else if (e.type === 'laugh') { a0x = a1x = -0.9; bob = Math.abs(Math.sin(T * 14)) * 0.04; rx = 0.12 + Math.sin(T * 10) * 0.06; }
    else if (e.type === 'aiyyo') { a0x = a1x = -2.6; a0z = -0.9; a1z = 0.9; rz = Math.sin(T * 30) * 0.05; bob = Math.abs(Math.sin(T * 18)) * 0.03; }
    else if (e.type === 'kiss') { a1x = -2.0 + Math.sin(T * 6) * 0.15; a1z = 0.4; rx = -0.05; }
    else if (e.type === 'punch') { a1x = -1.55 + Math.sin(T * 24) * 0.25; a0x = -0.5; a0z = -0.2; rx = 0.12; }
  } else u.emote = null;
  u.legs[0].rotation.x = l0; u.legs[1].rotation.x = l1;
  u.arms[0].rotation.x = a0x; u.arms[1].rotation.x = a1x; u.arms[0].rotation.z = a0z; u.arms[1].rotation.z = a1z;
  u.body.position.y = bob; u.body.rotation.x = rx; u.body.rotation.z = rz;
  u.knees[0].rotation.x = Math.max(0, -Math.cos(u.ph)) * amp * 1.1; u.knees[1].rotation.x = Math.max(0, Math.cos(u.ph)) * amp * 1.1;
  u.elbows.forEach(f => f.rotation.x = -0.22 - (e ? 0 : amp * 0.6)); u.body.rotation.y = e ? 0 : Math.sin(u.ph) * amp * 0.14;
}

function makeLabel(text, sub) {
  const canvas = document.createElement('canvas');
  canvas.width = 256; canvas.height = 64;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#180d2a';
  const r = 16;
  ctx.beginPath();
  ctx.moveTo(r,0); ctx.arcTo(256,0,256,64,r); ctx.arcTo(256,64,0,64,r);
  ctx.arcTo(0,64,0,0,r); ctx.arcTo(0,0,256,0,r); ctx.closePath(); ctx.fill();
  ctx.textAlign = 'center';
  const fit = (t, px, weight) => { let size = px; ctx.font = weight + ' ' + size + 'px sans-serif'; while (ctx.measureText(t).width > 232 && size > 12) { size -= 1; ctx.font = weight + ' ' + size + 'px sans-serif'; } return t; };
  ctx.fillStyle = '#FE019A';
  if (sub) {
    ctx.fillText(fit(text, 26, 'bold'), 128, 30);
    ctx.fillStyle = '#c9b8e8';
    ctx.fillText(fit(sub, 18, 'normal'), 128, 53);
  } else {
    ctx.fillText(fit(text, 28, 'bold'), 128, 42);
  }
  const tex = new THREE.CanvasTexture(canvas);
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true }));
  sprite.scale.set(2, 0.5, 1);
  sprite.position.y = 2.2;
  return sprite;
}


/* =========================================================
   PHASE 3 — Clouds, flying birds, fighting the bots (F key / Fight button) with Manglish dialogue
   ========================================================= */
const FIGHT_LINES = {
  start: ['Nee enthinada enne adikkunne? 😡', 'Ninakk pranthaano? 🤪', 'Da da, kalikalle! ☝️😤', 'Nee eethada? 🧐', 'Vattaano ninakk? 🤨', 'Ente mukhathu nokkiyal adi kittum! 👊', 'Ayyo! Enthonnadey ithu? 😲', 'Njan aarenn ariyo ninakk? 😎', 'Pettannu kali maatti pokkolu! 🙄', 'Ninakk veettil aarum illeda? 😠'],
  hit: ['Ayyo ente nadum! 😭', 'Aah! Veedhanikkunnu da! 🤕', 'Enthuvaado ee kaanikkunne? 😵', 'Nee ente kayyil ninnu vaangum! 😠', 'Amme! 😫', 'Ayyayyo, pallu poyi! 😬', 'Kalikkaan aano udheshikkunne? 😤', 'Ente kannil iruttu! 🌟😵', 'Poda poda! 😤', 'Nee ente pani kaanum! 🔥', 'Nee valiya aalaanennano vicharam? 🤣', 'Ithinu nee anubhavikkum! 😡'],
  attack: ['Edaa, ee adi vaangikko! 👊😠', 'Ithu ninakku ullathaanu! 💥', 'Kandille ente kai? 😤👊', 'Nilkkada avide! 🏃😡', 'Njan oru nalla adi tharum! 😈', 'Ninte kali ivide venda! 🚫', 'Pedichu poyo? 😏'],
  down: ['Mathi mathi, njan thott! 🏳️😩', 'Ayyo, maappu tharanam chetta! 🙏😭', 'Ini ninte vazhikku varilla! 😰', 'Ente kaalu pidikkaam, vidu! 🥺'],
  quit: ['Pinne kaanam da! 😒', 'Ninne njan pinne kaanikkunnund! 😤', 'Ee naadu nannaavilla! 🙄']
};
const _pick = a => a[Math.floor(Math.random() * a.length)];
function makeBubble() {
  const c = document.createElement('canvas'); c.width = 512; c.height = 128;
  const t = new THREE.CanvasTexture(c), sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: t, transparent: true, depthTest: false }));
  sp.scale.set(3.4, 0.85, 1); sp.renderOrder = 20; sp.visible = false; sp.userData = { c, t }; scene.add(sp); return sp;
}
function say(p, kind) {
  const txt = _pick(FIGHT_LINES[kind]); if (!p.bubble) p.bubble = makeBubble();
  const { c, t } = p.bubble.userData, x = c.getContext('2d'); x.clearRect(0, 0, 512, 128);
  x.fillStyle = 'rgba(255,255,255,0.96)'; x.beginPath(); x.moveTo(24, 6); x.arcTo(508, 6, 508, 100, 24); x.arcTo(508, 100, 4, 100, 24); x.arcTo(4, 100, 4, 6, 24); x.arcTo(4, 6, 508, 6, 24); x.fill();
  x.beginPath(); x.moveTo(236, 98); x.lineTo(256, 124); x.lineTo(276, 98); x.fill();
  let size = 30; x.font = 'bold ' + size + 'px sans-serif'; while (x.measureText(txt).width > 470 && size > 16) { size -= 1; x.font = 'bold ' + size + 'px sans-serif'; }
  x.fillStyle = '#1a1020'; x.textAlign = 'center'; x.fillText(txt, 256, 62 + size / 3); t.needsUpdate = true; p.sayUntil = performance.now() + 2600;
}
function nearestPed(maxD) {
  let best = null, bd = maxD;
  PEDS.forEach(p => { if (p.state === 'down' || !p.g.visible) return; const d = Math.hypot(p.g.position.x - myAvatar.position.x, p.g.position.z - myAvatar.position.z); if (d < bd) { bd = d; best = p; } });
  return best;
}
let _punchAt = 0;
function doPunch() {
  const now = performance.now(); if (now < _punchAt || privScene) return; _punchAt = now + 450;
  myAvatar.userData.emote = { type: 'punch', until: now + 350 };
  const p = nearestPed(3.2); if (!p) return;
  if (p.hp === undefined) p.hp = 100;
  const dx = p.g.position.x - myAvatar.position.x, dz = p.g.position.z - myAvatar.position.z, d = Math.hypot(dx, dz) || 1;
  myAvatar.rotation.y = Math.atan2(dx, dz);
  if (p.state !== 'fight') { p.state = 'fight'; p.nextAtk = now + 1200; p.g.userData.walkAmp = 0; say(p, 'start'); }
  else if (Math.random() < 0.8) say(p, 'hit');
  p.hp -= 25; p.g.position.x += dx / d * 0.6; p.g.position.z += dz / d * 0.6;
  if (p.hp <= 0) { p.state = 'down'; p.downUntil = now + 4500; say(p, 'down'); p.g.rotation.order = 'YXZ'; p.g.rotation.x = -Math.PI / 2; p.g.position.y = 0.15; }
}
function resumePath(p) {
  const x = p.g.position.x, z = p.g.position.z, H = GRID / 2, lx = Math.max(-H, Math.min(H, Math.round(x / BLOCK))), lz = Math.max(-H, Math.min(H, Math.round(z / BLOCK)));
  const dz = z - lz * BLOCK, dx = x - lx * BLOCK, off = ROAD_W / 2 + 1.3;
  if (Math.abs(dz) <= Math.abs(dx)) { p.axis = 0; p.line = lz; p.pos = x; p.side = (dz >= 0 ? 1 : -1) * off; } else { p.axis = 1; p.line = lx; p.pos = z; p.side = (dx >= 0 ? 1 : -1) * off; }
  p.hp = 100; p.state = 'walk'; p.cool = 1; p.g.userData.walkAmp = 0.5; p.g.rotation.x = 0; p.g.rotation.order = 'XYZ'; p.g.position.y = 0;
}
const fightBtn = document.createElement('button'); fightBtn.id = 'fight-btn'; fightBtn.textContent = '👊 Fight (F)';
fightBtn.style.cssText = 'position:fixed;left:50%;bottom:150px;transform:translateX(-50%);z-index:7;padding:12px 22px;border:none;border-radius:24px;font-weight:700;font-size:.9em;cursor:pointer;color:#fff;background:linear-gradient(135deg,#FE019A,#9b5de5);display:none;box-shadow:0 8px 22px rgba(0,0,0,.5)';
document.body.appendChild(fightBtn); fightBtn.onclick = doPunch;
addEventListener('keydown', e => { if ((e.key === 'f' || e.key === 'F') && !/INPUT|TEXTAREA/.test(document.activeElement && document.activeElement.tagName)) doPunch(); });
let _fAt = performance.now();
function updateFight() {
  const now = performance.now(), dt = Math.min(0.1, (now - _fAt) / 1000); _fAt = now; const me = myAvatar.position;
  PEDS.forEach(p => {
    if (p.hp === undefined) p.hp = 100;
    const g = p.g;
    if (p.bubble) { p.bubble.visible = now < p.sayUntil && !privScene; p.bubble.position.set(g.position.x, g.position.y + (p.state === 'down' ? 1.1 : 2.4), g.position.z); }
    if (p.state === 'fight') {
      const dx = me.x - g.position.x, dz = me.z - g.position.z, d = Math.hypot(dx, dz) || 1; g.rotation.y = Math.atan2(dx, dz);
      if (d > 2.0) { g.position.x += dx / d * 2.6 * dt; g.position.z += dz / d * 2.6 * dt; g.userData.walkAmp = 0.7; }
      else { g.userData.walkAmp = 0; if (now > p.nextAtk) { p.nextAtk = now + 1500 + Math.random() * 600; g.userData.emote = { type: 'punch', until: now + 350 }; if (Math.random() < 0.5) say(p, 'attack'); if (Math.random() < 0.4) { showToast(_pick(['Ouch! 💥', 'Aah! 🤕', 'He hit you! 👊'])); applyDamage(12, 'punch'); } } }
      if (d > 16) { say(p, 'quit'); resumePath(p); }
    } else if (p.state === 'down' && now > p.downUntil) { say(p, 'quit'); resumePath(p); }
  });
  const near = nearestPed(3.2); fightBtn.style.display = (near && !privScene) ? 'block' : 'none';
}

// --- Clouds (drifting sprites tinted by the sky) and flocks of birds ---
const cloudTex = (() => { const c = document.createElement('canvas'); c.width = 256; c.height = 128; const x = c.getContext('2d');
  [[70, 70, 46], [120, 56, 54], [175, 68, 48], [100, 82, 40], [150, 84, 42]].forEach(p => { const g = x.createRadialGradient(p[0], p[1], 2, p[0], p[1], p[2]); g.addColorStop(0, 'rgba(255,255,255,0.95)'); g.addColorStop(1, 'rgba(255,255,255,0)'); x.fillStyle = g; x.fillRect(0, 0, 256, 128); });
  return new THREE.CanvasTexture(c); })();
window.cloudMat = new THREE.SpriteMaterial({ map: cloudTex, transparent: true, opacity: 0.9, depthWrite: false, fog: false });
const cloudGroup = new THREE.Group(); scene.add(cloudGroup);
for (let i = 0; i < 18; i++) { const s = new THREE.Sprite(cloudMat), a = Math.random() * 6.283, r = 170 + Math.random() * 190; s.position.set(Math.cos(a) * r, 70 + Math.random() * 60, Math.sin(a) * r); s.scale.set(110 + Math.random() * 70, 42 + Math.random() * 20, 1); cloudGroup.add(s); }
const BIRDS = new THREE.Group(); scene.add(BIRDS); window.BIRDS = BIRDS;
const _flocks = [], _wingG = (() => { const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.BufferAttribute(new Float32Array([0, 0, 0.16, 0, 0, -0.16, 0.85, 0.04, -0.05]), 3)); g.computeVertexNormals(); return g; })();
(function () {
  const coarse = typeof matchMedia !== 'undefined' && matchMedia('(pointer:coarse)').matches, F = coarse ? 4 : 6, N = coarse ? 4 : 5, bodyG = new THREE.SphereGeometry(0.15, 8, 6);
  for (let f = 0; f < F; f++) {
    const fl = { r: 30 + Math.random() * 80, a0: Math.random() * 6.283, w: (0.07 + Math.random() * 0.06) * (Math.random() < 0.5 ? -1 : 1), h: 16 + Math.random() * 14, birds: [] }, col = Math.random() < 0.5 ? 0xf4f4f0 : 0x1e1e22;
    const m = new THREE.MeshStandardMaterial({ color: col, roughness: 0.9, side: THREE.DoubleSide });
    for (let i = 0; i < N; i++) {
      const b = new THREE.Group(), body = new THREE.Mesh(bodyG, m); body.scale.set(0.7, 0.7, 1.6); b.add(body);
      const wr = new THREE.Mesh(_wingG, m), wl = new THREE.Mesh(_wingG, m); wl.scale.x = -1; b.add(wr); b.add(wl);
      b.userData = { wr, wl, ox: (Math.random() - 0.5) * 7, oy: (Math.random() - 0.5) * 3, oz: (Math.random() - 0.5) * 7, ph: Math.random() * 6 }; BIRDS.add(b); fl.birds.push(b);
    }
    _flocks.push(fl);
  }
})();
let _skAt = performance.now();
function updateSky() {
  const now = performance.now(), dt = Math.min(0.1, (now - _skAt) / 1000), t = now / 1000; _skAt = now; cloudGroup.rotation.y += dt * 0.004;
  _flocks.forEach(fl => fl.birds.forEach(b => {
    const a = fl.a0 + t * fl.w, u = b.userData, sg = Math.sign(fl.w);
    b.position.set(Math.cos(a) * fl.r + u.ox, fl.h + u.oy + Math.sin(t * 1.3 + u.ph) * 0.4, Math.sin(a) * fl.r + u.oz);
    b.rotation.y = Math.atan2(-Math.sin(a) * sg, Math.cos(a) * sg); const fl2 = Math.sin(t * 9 + u.ph) * 0.7; u.wr.rotation.z = fl2; u.wl.rotation.z = -fl2;
  }));
}

let myGender = 'other';
let myOutfitColor = '#9b5de5';
let myHairStyle = 'short';
{
  const keeper = makeAvatarMesh('female', '#ffb703', 'pony');
  keeper.position.set(10, 0.25, 12.7);
  keeper.add(makeLabel('Shopkeeper'));
  scene.add(keeper);
  chayaNpc = makeAvatarMesh('male', '#ffffff', 'short', 'chayakkaran');
  chayaNpc.position.set(-10, 0.27, 10.8); chayaNpc.rotation.y = Math.PI;
  chayaNpc.add(makeLabel('Chayakkaran 🤖')); scene.add(chayaNpc);
  milkmaid = makeAvatarMesh('female', '#a61e24', 'short', 'karavakkari');
  milkmaid.position.set(-6, 0.27, -5.5); milkmaid.add(makeLabel('Karavakkari 🤖')); milkProps(milkmaid); scene.add(milkmaid);
}

function milkProps(g) {
  const steel = kMat(0xc9ced6, { metalness: 0.6, roughness: 0.3 });
  const add = (geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); g.add(o); return o; };
  add(new THREE.CylinderGeometry(0.16, 0.13, 0.3, 12), steel, 0.55, 0.17, 0.35);
  add(new THREE.CylinderGeometry(0.15, 0.15, 0.01, 12), kMat(0xffffff), 0.55, 0.32, 0.35);
  add(new THREE.CylinderGeometry(0.2, 0.2, 0.3, 8), kMat(0x7a4c22), -0.6, 0.15, 0.3);
}
let myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle);
scene.add(myAvatar);
/* ---- Phase 2: walking pedestrians on the sidewalks (tune PED_COUNT) ---- */
const PEDS = []; let _pedAt = performance.now();
(function spawnPeds() {
  const coarse = typeof matchMedia !== 'undefined' && matchMedia('(pointer:coarse)').matches, PED_COUNT = coarse ? 8 : 14, half = GRID * BLOCK / 2;
  const skins = [0xf1c9a5, 0xe0ac82, 0xc68a5d, 0xae7a50, 0x8d5a3b, 0x6b4429], shirts = ['#ff6fb5', '#2ec4b6', '#ffd166', '#ff8a5b', '#f8f4e3', '#7bdff2', '#b388eb'];
  const pants = [0xf1ece0, 0xd9c9a3, 0x3d5a80, 0x2e2e38, 0xe8dcc8], hairs = ['short', 'pony', 'bandana'], R = a => a[Math.floor(Math.random() * a.length)];
  for (let i = 0; i < PED_COUNT; i++) {
    const g = makeAvatarMesh(Math.random() < 0.5 ? 'female' : 'male', R(shirts), R(hairs), null, { skin: R(skins), pants: R(pants), shades: Math.random() < 0.4, hair: R([0x0e0a08, 0x2b1a10, 0x4a2c17, 0x7a4a22, 0x8a8a8a]), h: 0.95 + Math.random() * 0.1, w: 0.92 + Math.random() * 0.2 });
    g.userData.walkAmp = 0.5; scene.add(g);
    PEDS.push({ g, axis: Math.random() < 0.5 ? 0 : 1, line: Math.floor(Math.random() * (GRID + 1)) - GRID / 2, side: (Math.random() < 0.5 ? -1 : 1) * (ROAD_W / 2 + 1.3), dir: Math.random() < 0.5 ? -1 : 1, pos: (Math.random() * 2 - 1) * half, speed: 1.1 + Math.random() * 0.7, cool: 0 });
  }
})();
function updatePeds() {
  const now = performance.now(), dt = Math.min(0.1, (now - _pedAt) / 1000); _pedAt = now; const half = GRID * BLOCK / 2;
  PEDS.forEach(p => {
    p.g.visible = !privScene; if (privScene) return; if (p.state && p.state !== 'walk') return;
    const prev = p.pos; p.pos += p.dir * p.speed * dt; p.cool -= dt;
    if (Math.abs(p.pos) > half + 3) { p.dir *= -1; p.pos = Math.sign(p.pos) * (half + 3); }
    const k0 = Math.floor(prev / BLOCK), k1 = Math.floor(p.pos / BLOCK);
    if (k0 !== k1 && p.cool <= 0 && Math.random() < 0.35) {
      const k = p.dir > 0 ? k1 : k0;
      if (Math.abs(k) <= GRID / 2) { const old = p.line; p.axis ^= 1; p.line = k; p.pos = old * BLOCK; p.dir = Math.random() < 0.5 ? -1 : 1; p.side = (Math.random() < 0.5 ? -1 : 1) * (ROAD_W / 2 + 1.3); p.cool = 2; }
    }
    const a = p.line * BLOCK + p.side;
    p.g.position.set(p.axis === 0 ? p.pos : a, 0, p.axis === 0 ? a : p.pos);
    p.g.rotation.y = Math.atan2(p.axis === 0 ? p.dir : 0, p.axis === 0 ? 0 : p.dir);
  });
}

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

if (handoffName && !auth.loggedIn) document.getElementById('name-input').value = handoffName;

/* =========================================================
   4) MOVEMENT
   ========================================================= */
const keys = {};
const KEYALIAS = { arrowup: 'w', arrowdown: 's', arrowleft: 'a', arrowright: 'd' };
addEventListener('keydown', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = true; if (KEYALIAS[k] && !/INPUT|TEXTAREA/.test((e.target.tagName || ''))) e.preventDefault(); });
addEventListener('keyup', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = false; });

let camYaw = 0, camPitch = 0.15, dragging = false, lastX = 0, lastY = 0;
function startDrag(x,y){ dragging = true; lastX = x; lastY = y; }
function moveDrag(x,y){
  if(!dragging) return;
  camYaw -= (x-lastX)*0.006;
  camPitch += (y-lastY)*0.004;
  camPitch = Math.max(-0.85, Math.min(1.0, camPitch));
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
let drivingCarId = null;
let carSpeed = 0;
const vehiclePrompt = document.getElementById('vehicle-prompt');

const stats = { distanceTraveled: 0, deliveriesCompleted: 0, carsDriven: 0 };

function findNearbyCar() {
  let nearest = null, nearestDist = Infinity;
  for (const id in cars) {
    const c = cars[id];
    const d = Math.hypot(c.group.position.x - myAvatar.position.x, c.group.position.z - myAvatar.position.z);
    const reach = c.isPlane ? 4 : c.isCart ? 3.4 : 2.2;
    if (d < reach && d < nearestDist) { nearest = c; nearestDist = d; }
  }
  return nearest;
}

addEventListener('keydown', (e) => {
  if (e.key.toLowerCase() !== 'e') return;
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  if (sitting || (nearBench && !drivingCarId)) toggleSit(); else triggerVehicleAction();
});
vehiclePrompt.addEventListener('click', triggerVehicleAction);

function triggerVehicleAction() {
  if (document.getElementById('name-gate').style.display !== 'none') return;
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
  myAvatar.visible = !!c.rider;
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

let _ridePh = 0;
function rideTick() {   // seat the player's avatar on the bike / cycle (seated pose, pedalling legs)
  const c = drivingCarId && cars[drivingCarId]; if (!c || !c.rider) return;
  const u = myAvatar.userData; if (!u || !u.legs) return;
  const cy = c.kind === 'cycle', sx = c.seat ? c.seat[0] : cy ? -0.14 : -0.22, sy = c.seat ? c.seat[1] : cy ? 1.0 : 0.95, r = c.group.rotation.y; _ridePh += carSpeed * 9;
  myAvatar.position.set(c.group.position.x + Math.cos(r) * sx, sy - 0.88, c.group.position.z - Math.sin(r) * sx);
  myAvatar.rotation.y = r + Math.PI / 2;
  const pd = cy ? Math.sin(_ridePh) * 0.5 : 0;
  u.legs[0].rotation.x = -1.15 + pd; u.legs[1].rotation.x = -1.15 - pd; u.knees[0].rotation.x = 1.2 - pd * 0.8; u.knees[1].rotation.x = 1.2 + pd * 0.8;
  u.arms[0].rotation.x = u.arms[1].rotation.x = -1.15; u.arms[0].rotation.z = -0.15; u.arms[1].rotation.z = 0.15; u.body.rotation.x = 0.18;
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

  const cart = !!c.isCart;
  const cyc = c.kind === 'cycle', bk = c.kind === 'bike';
  const accel = cart || cyc ? 0.006 : bk ? 0.014 : 0.012, maxSpeed = cart ? 0.13 : cyc ? 0.15 : bk ? 0.38 : 0.32, friction = 0.985, turnRate = cart ? 0.03 : (cyc || bk) ? 0.05 : 0.045;
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
  if (c.group.position.x > RIVER.x1 - 2.5) { c.group.position.x = RIVER.x1 - 2.5; carSpeed = 0; }

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
  if (!canLift && airborne) planeVy -= 0.01;
  planeVy = Math.max(-0.25, Math.min(0.25, planeVy));
  p.y += planeVy;
  if (p.y <= 0) { p.y = 0; planeVy = 0; if (!throttle) planeSpeed *= 0.97; }
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
    const moveX = -dx*Math.cos(camYaw) - dz*Math.sin(camYaw);
    const moveZ =  dx*Math.sin(camYaw) - dz*Math.cos(camYaw);
    const sp = swimming ? speed * 0.55 : speed;
    myAvatar.position.x += moveX*sp;
    myAvatar.position.z += moveZ*sp;
    myAvatar.rotation.y = Math.atan2(moveX, moveZ);
    stats.distanceTraveled += speed;
    checkBalloonPickup();
    checkDeliveryProximity();
    checkTreasureProximity();
  }
  if (privScene) clampPrivate();
  if (performance.now() < kissFreeze) { renderer.render(scene, camera); return; }
  const targetY = swimming ? -0.65 + Math.sin(performance.now()/350) * 0.06 : (onBridge(myAvatar.position.x, myAvatar.position.z) ? BRIDGE.y : 0);
  myAvatar.position.y += (targetY - myAvatar.position.y) * 0.25;
  setChip(swimming ? '🏊 Swimming' : null);
  const camDist = 6;
  camera.position.x = myAvatar.position.x - Math.sin(camYaw)*camDist*Math.cos(camPitch);
  camera.position.z = myAvatar.position.z - Math.cos(camYaw)*camDist*Math.cos(camPitch);
  camera.position.y = myAvatar.position.y + 1.2 + camDist*Math.sin(camPitch);
  camera.lookAt(myAvatar.position.x, myAvatar.position.y+1, myAvatar.position.z);

  const nearby = findNearbyCar();
  if (nearby && !nearby.occupiedBy) {
    vehiclePrompt.textContent = nearby.isPlane ? 'Press E to fly ✈️' : nearby.isCart ? 'Press E to ride the kaalavandi 🐂' : 'Press E to enter';
    vehiclePrompt.style.display = 'block';
  } else vehiclePrompt.style.display = 'none';

  const here = nearShopMode();
  shopPrompt.textContent = here ? SHOPS[here].prompt : '';
  shopPrompt.style.display = here ? 'block' : 'none';
  if (document.getElementById('shop-overlay').style.display !== 'none' && shopMode !== here) {
    document.getElementById('shop-overlay').style.display = 'none';
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
  if (d < 2) {
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
  myName = auth.loggedIn ? auth.display : (document.getElementById('name-input').value.trim() || 'Guest');
  const firstTime = auth.loggedIn && !auth.profile;
  scene.remove(myAvatar);
  if (myRole === 'chayakkaran') myGender = 'male'; else if (myRole === 'karavakkari') myGender = 'female';
  myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle, myRole);
  if (myRole === 'chayakkaran') { myAvatar.position.set(-10, 0.27, 10.8); myAvatar.rotation.y = Math.PI; camYaw = Math.PI; }
  else if (myRole === 'karavakkari') { myAvatar.position.set(-6, 0.27, -5.5); camYaw = 0; }
  scene.add(myAvatar);
  document.getElementById('name-gate').style.display = 'none';
  setTimeout(() => showToast(auth.loggedIn ? '🔇 Sound and 🎤 mic are OFF — tap the round icons to turn them on' : '🔇 Sound is OFF. Log in to use 🎤 mic and 💬 chat'), 800);
  socket.emit('join', { name: myName, gender: myGender, outfitColor: myOutfitColor, hairStyle: myHairStyle, role: myRole, token: auth.loggedIn ? auth.token : null });
  if (firstTime) saveProfile();
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
    others[driverId].group.visible = false;
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
  if (c.isPlane) { c.group.position.y = 0; c.group.rotation.z = 0; c.target = null; }
});
socket.on('carDenied', () => { });

socket.on('radioState', (s) => {
  radioState = { on: !!s.on, station: s.station | 0, stations: Array.isArray(s.stations) ? s.stations : [] };
  const n = radioState.stations[radioState.station];
  if (s.by) showToast(s.on ? `📻 ${s.by} tuned ${n ? n.name : 'the radio'}` : `📻 ${s.by} switched the radio off`);
});
socket.on('cartCall', ({ carId }) => { const c = cars[carId]; if (c && c.isCart) oxCall(c); });

socket.on('emote', ({ id, type }) => showEmote(id, type));
socket.on('kissFx', ({ fromId, toId }) => kissFx(fromId, toId));
socket.on('kissReceived', ({ from, fromId }) => {
  kissFrom = fromId;
  document.getElementById('kiss-text').textContent = '💋 ' + from + ' blew you a kiss!';
  document.getElementById('kiss-prompt').style.display = 'block';
  clearTimeout(kissHide); kissHide = setTimeout(hideKissPrompt, 12000);
});
socket.on('kissDenied', ({ reason }) => showToast(reason === 'range' ? '💋 Too far — walk closer first' : reason === 'blocked' ? '🙅 They prefer no kisses' : reason === 'cooldown' ? '💋 Easy there, romeo — wait a few seconds' : 'Kiss failed'));

socket.on('holdHandsPrompt', ({ fromId, from }) => {
  if (handWith) { socket.emit('holdHandsRespond', { fromId, accept: false }); return; }
  handPending = fromId; document.getElementById('hp-text').textContent = '🤝 ' + from + ' wants to hold hands';
  document.getElementById('hand-prompt').style.display = 'block';
});
socket.on('holdHandsDenied', ({ reason }) => showToast(reason === 'range' ? '🤝 Walk closer first' : reason === 'declined' ? '🤝 They said not now' : "🤝 Couldn't connect"));
socket.on('holdHandsState', ({ a, b, holding }) => {
  const mine = a === socket.id || b === socket.id;
  const otherId = a === socket.id ? b : a;
  if (mine) { handWith = holding ? otherId : null; document.getElementById('hand-btn').classList.toggle('selected', !!handWith); }
  const key = pairKey(a, b);
  if (!holding && handLinks[key]) { scene.remove(handLinks[key]); delete handLinks[key]; }
  if (holding && !handLinks[key]) { const m = handLinkMesh(); scene.add(m); handLinks[key] = m; handLinks[key]._a = a; handLinks[key]._b = b; }
});
socket.on('slapFx', ({ fromId, toId }) => slapFx(fromId, toId));
socket.on('slapReceived', ({ from }) => { showToast('🖐️ ' + from + ' slapped you!'); applyDamage(8, 'slap'); });
socket.on('slapDenied', ({ reason }) => showToast(reason === 'range' ? '🖐️ Too far — walk closer first' : reason === 'blocked' ? "🖐️ They've turned off slaps" : '🖐️ Wait a moment before slapping again'));
socket.on('privateForceMute', ({ muted }) => {
  document.getElementById('forced-mute-note').style.display = muted ? 'block' : 'none';
  if (muted) { privMuted = true; if (lkPriv) lkPriv.localParticipant.setMicrophoneEnabled(false).catch(() => {}); showToast('🔇 A moderator has muted your mic'); }
  paintPrivBar();
});
socket.on('privateKicked', () => { cleanupPrivate(); showToast('🔒 A moderator removed you from the private room'); });
socket.on('roleState', (s) => {
  if (chayaNpc) chayaNpc.visible = !s.chayakkaran;
  if (milkmaid) milkmaid.visible = !s.karavakkari;
  document.querySelectorAll('.role-pill').forEach(b => {
    const r = b.dataset.role; if (r === 'visitor') return;
    const t = s[r], taken = !!t && t.id !== socket.id;
    b.disabled = taken; b.title = taken ? 'Taken by ' + t.name : '';
    if (taken && myRole === r && !inGame()) { myRole = null; b.classList.remove('selected'); document.querySelector('.role-pill[data-role="visitor"]').classList.add('selected'); }
  });
});
socket.on('roleResult', ({ role, denied }) => {
  if (denied) {
    showToast('That role was just taken — you joined as a visitor'); myRole = null;
    const pos = myAvatar.position.clone(), ry = myAvatar.rotation.y; scene.remove(myAvatar);
    myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle, null); myAvatar.position.copy(pos); myAvatar.rotation.y = ry; scene.add(myAvatar);
  } else if (role === 'chayakkaran') showToast('🍵 You are the Chayakkadakkaran! Customers tip you when they buy.');
  else if (role === 'karavakkari') showToast('🥛 You are Karavakkari chechi! Customers tip you when they buy.');
});
socket.on('commission', ({ amount, balloons, from, itemId }) => {
  balloonEl.textContent = balloons; refreshAffordability();
  showToast(`💰 ${from} bought ${itemLabel(itemId)} — you earned +${amount} 🎈`);
});

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

socket.on('treasureSpots', (spots) => { rebuildChests(spots); });
socket.on('treasureOpened', ({ chestId }) => { const m = chestMeshes[chestId]; if (m) m.visible = false; });
socket.on('treasureReward', ({ reward, balloons }) => {
  balloonEl.textContent = balloons;
  refreshAffordability();
  if (reward.type === 'balloons') showToast(`Chest opened! +${reward.amount} 🎈`);
  else if (reward.type === 'item') showToast(`Chest opened! Found ${itemLabel(reward.itemId)}`);
  else showToast(`Chest opened! Found a ${reward.name} ✨`);
});

let shopCatalog = [];
let myInventory = {};
const CATEGORY_LABELS = {
  weapon: '🔫 Guns & Weapons', flower: '🌹 Flowers', food: '🍔 Food', drink: '🥤 Drinks',
  chayakkada: '🍵 Chaya & Palaharam', dairy: '🥛 Palu, Thairu & Nei', party: '🎉 Party', accessory: '🕶️ Accessories', clothing: '🧥 Clothing',
  medical: '💊 Medicine & Drinks'
};
function itemLabel(itemId) {
  const item = shopCatalog.find(i => i.id === itemId);
  return item ? `${item.emoji} ${item.name}` : itemId;
}
socket.on('shopCatalog', (items) => {
  shopCatalog = items;
  // Inject client-side medical items so they appear at the main Shop
  MED_ITEMS.forEach(mi => { if (!shopCatalog.find(x => x.id === mi.id)) shopCatalog.push(mi); });
  renderShop();
});
socket.on('purchaseOk', ({ itemId, balloons, inventory }) => {
  balloonEl.textContent = balloons;
  myInventory = inventory;
  showToast(`Bought ${itemLabel(itemId)}!`);
  const bought = shopCatalog.find(i => i.id === itemId);
  if (bought && (bought.shop === 'tea' || bought.shop === 'milk') && Math.random() < 0.7) {
    const q = bought.shop === 'tea'
      ? ['🍵 Chaya kudikku mone, chinthikkaan sheshi varum!', '🍵 Chaya + parippuvada = swargam!', '🍵 Ente ponno, aa chaya kidu!']
      : ['🥛 Palu shudham — vellam cherkkilla, promise!', '🥛 Ammaye orkkum ee thairu kazhikkumbol!', '🥛 Pasu paranjathaa — nalla palu!'];
    setTimeout(() => showToast(q[Math.floor(Math.random() * q.length)]), 700);
  }
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
      div.querySelector('button').onclick = () => {
        // Medical items heal locally instead of going to the server
        const med = MED_ITEMS.find(m => m.id === item.id);
        if (med) { buyMedical(med); return; }
        socket.emit('buyItem', item.id);
      };
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
  const group = makeAvatarMesh(p.gender, p.outfitColor, p.hairStyle, p.role);
  const label = makeLabel(p.name + (p.role === 'chayakkaran' ? ' 🍵' : p.role === 'karavakkari' ? ' 🥛' : ''), p.username ? '@' + p.username : null);
  group.add(label);
  scene.add(group);
  others[p.id] = { group, label, target: p, name: p.name, username: p.username, role: p.role };
  if (p.sitting) group.position.y = -0.35;
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
chatInput.addEventListener('focus', () => { if (!auth.loggedIn) { chatInput.blur(); requireLogin('chat', () => chatInput.focus()); } });
chatInput.addEventListener('pointerdown', () => { if (!auth.loggedIn) requireLogin('chat', () => chatInput.focus()); });
chatForm.addEventListener('submit', e => {
  e.preventDefault();
  if (!auth.loggedIn) { requireLogin('chat'); return; }
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
socket.on('authRequired', ({ feature }) => { requireLogin(feature === 'mic' ? 'mic' : 'chat'); });
socket.on('authState', (st) => {
  if (st.balloons !== undefined && st.loggedIn !== false) { balloonEl.textContent = st.balloons; refreshAffordability(); }
  if (st.loggedOut) balloonEl.textContent = '0';
  if (st.error === 'bad_token') { refreshAuth(); }
});
socket.on('playerRenamed', ({ id, name, username }) => {
  const o = others[id]; if (!o) return;
  o.name = name; o.username = username || null;
  if (o.label) o.group.remove(o.label);
  o.label = makeLabel(name + (o.role === 'chayakkaran' ? ' 🍵' : o.role === 'karavakkari' ? ' 🥛' : ''), username ? '@' + username : null);
  o.group.add(o.label);
});
socket.on('playerLook', ({ id, gender, outfitColor, hairStyle }) => {
  const o = others[id]; if (!o) return;
  const keep = Object.assign({}, o.target, { id, name: o.name, username: o.username, role: o.role, gender, outfitColor, hairStyle });
  const wasVisible = o.group.visible;
  const pos = o.group.position.clone(), ry = o.group.rotation.y;
  scene.remove(o.group); delete others[id];
  addOtherPlayer(keep);
  others[id].group.position.copy(pos); others[id].group.rotation.y = ry; others[id].group.visible = wasVisible;
});
socket.on('connect', () => { if (inGame() && auth.loggedIn) socket.emit('auth', { token: auth.token }); });
function escapeHtml(s){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

/* =========================================================
   7) VOICE — via LiveKit
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
  if (!auth.loggedIn) throw new Error('log in first');
  const getTok = () => fetch(`/livekit-token?identity=${encodeURIComponent(socket.id)}`, { headers: { Authorization: 'Bearer ' + auth.token } });
  let res = await getTok();
  if (res.status === 401) { await refreshAuth(); if (auth.loggedIn) res = await getTok(); }
  let data; try { data = await res.json(); } catch (e) { throw new Error('the voice service is not reachable on this server'); }
  if (res.status === 401) { const e = new Error('please log in again'); e.needLogin = true; throw e; }
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
function syncVoice() {
  voiceChain = voiceChain.then(async () => {
    const want = (micOn || soundOn) && auth.loggedIn && !privRoom;
    try {
      if (want && !lkRoom) await connectVoice();
      if (!want && lkRoom) { const r = lkRoom; lkRoom = null; await r.disconnect(); document.querySelectorAll('audio.lk-voice-audio').forEach(el => el.remove()); }
      if (lkRoom) { await lkRoom.localParticipant.setMicrophoneEnabled(micOn); applyRemoteAudio(); }
    } catch (e) {
      if (e && e.needLogin) openLogin('🎤 Your session ended — log in again to use voice.');
      if (micOn) { micOn = false; if (lkRoom) { try { await lkRoom.localParticipant.setMicrophoneEnabled(false); } catch (e2) {} } }
      showToast('🎤 Voice chat unavailable: ' + (e.message || e));
      paintVoiceBtns();
    }
  });
}
micBtn.onclick = () => {
  const toggle = () => { micOn = !micOn; paintVoiceBtns(); syncVoice(); };
  if (!micOn && !auth.loggedIn) { requireLogin('mic', () => { micOn = true; paintVoiceBtns(); syncVoice(); }); return; }
  toggle();
};
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
const WORLD_HALF = (GRID*BLOCK)/2 + 20;
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



/* =========================================================
   8) PRIVATE VOICE ROOMS — create (Room ID + passcode + share link) / join (ID or link + passcode)
   ========================================================= */
let PRIV_PLACES = [{ id: 'happycup', name: 'Happy Cup', emoji: '☕' }, { id: 'sarovaram', name: 'Sarovaram Park', emoji: '🌳' }, { id: 'beach', name: 'Beach', emoji: '🏖️' }, { id: 'hugamug', name: 'Hug a Mug', emoji: '🫶' }];
let PRIV_MAX = 5;
let privRoom = null, lkPriv = null, privConnecting = false, privMuted = false, privPlace = null, privSize = 2, privMine = null, privFound = null;
const privOverlay = document.getElementById('private-overlay');
const $p = (id) => document.getElementById(id);
const linkRoom = (params.get('room') || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
const roomLink = (id) => location.origin + '/?room=' + id;

function privTab(which) {
  $p('ptab-create').classList.toggle('on', which !== 'join'); $p('ptab-join').classList.toggle('on', which === 'join');
  $p('pv-create').style.display = (which === 'create') ? 'block' : 'none';
  $p('pv-ready').style.display = (which === 'ready') ? 'block' : 'none';
  $p('pv-join').style.display = (which === 'join') ? 'block' : 'none';
}
function renderPrivPlaces() {
  const box = $p('priv-places'); box.innerHTML = '';
  PRIV_PLACES.forEach(pl => {
    const b = document.createElement('button'); b.type = 'button'; b.className = 'priv-place' + (privPlace === pl.id ? ' selected' : '');
    const em = document.createElement('span'); em.className = 'em'; em.textContent = pl.emoji; b.appendChild(em); b.appendChild(document.createTextNode(pl.name));
    b.onclick = () => { privPlace = pl.id; renderPrivPlaces(); $p('priv-start').disabled = false; };
    box.appendChild(b);
  });
}
function renderPrivSize() {
  const box = $p('priv-size'); box.innerHTML = '';
  for (let n = 2; n <= PRIV_MAX; n++) {
    const b = document.createElement('button'); b.type = 'button'; b.textContent = n; b.className = privSize === n ? 'selected' : '';
    b.onclick = () => { privSize = n; renderPrivSize(); }; box.appendChild(b);
  }
}
function openPrivate(view, roomId) {
  if (!inGame()) { showToast('Enter the city first'); return; }
  if (!auth.loggedIn) { requireLogin('private', () => openPrivate(view, roomId)); return; }
  if (drivingCarId) { showToast('🚗 Get out of your vehicle first'); return; }
  socket.emit('privateConfig', (cfg) => { if (cfg && cfg.places) { PRIV_PLACES = cfg.places; PRIV_MAX = cfg.max || 5; renderPrivPlaces(); renderPrivSize(); } });
  renderPrivPlaces(); renderPrivSize();
  if (privRoom && privMine) { showReady(privMine); }
  else if (privRoom) { showToast('You are already in a private room'); return; }
  else if (view === 'join') { privTab('join'); $p('pj-id').value = roomId || ''; $p('pj-pass').value = ''; $p('pj-found').textContent = ''; $p('pj-join').disabled = true; privFound = null; if (roomId) findRoom(); }
  else privTab('create');
  privOverlay.classList.add('open');
}
$p('private-btn').onclick = () => openPrivate('create');
$p('private-close').onclick = () => privOverlay.classList.remove('open');
privOverlay.addEventListener('mousedown', e => { if (e.target === privOverlay) privOverlay.classList.remove('open'); });
$p('ptab-create').onclick = () => { if (privRoom && privMine) showReady(privMine); else if (!privRoom) privTab('create'); };
$p('ptab-join').onclick = () => { if (privRoom) { showToast('Leave your current room to join another'); return; } privTab('join'); };

$p('priv-start').onclick = () => {
  const btn = $p('priv-start'); btn.disabled = true;
  socket.emit('privateCreate', { place: privPlace, capacity: privSize }, (r) => {
    if (r && r.ok) { privMine = r; showReady(r); }
    else { showToast((r && r.error) || 'Could not create the room'); btn.disabled = false; }
  });
};
function showReady(r) {
  $p('pr-title').textContent = r.emoji + ' ' + r.placeName + ' · up to ' + r.capacity + ' people';
  $p('pr-id').textContent = r.roomId; $p('pr-pass').textContent = r.passcode; $p('pr-link').textContent = roomLink(r.roomId);
  privTab('ready');
}
function inviteText(r) { return '🔒 Join my private voice room on Neyyappam City!\n' + r.emoji + ' ' + r.placeName + '\nRoom ID: ' + r.roomId + '\nPasscode: ' + r.passcode + '\nLink: ' + roomLink(r.roomId); }
async function copyText(t, okMsg) {
  try { await navigator.clipboard.writeText(t); showToast(okMsg); }
  catch (e) { const ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); showToast(okMsg); } catch (x) { showToast('Copy failed — select the text manually'); } ta.remove(); }
}
$p('pr-copy-invite').onclick = async () => {
  if (!privMine) return; const text = inviteText(privMine);
  if (navigator.share) { try { await navigator.share({ title: 'Private room', text }); return; } catch (e) { if (e && e.name === 'AbortError') return; } }
  copyText(text, '📋 Invite copied — paste it to your partner');
};
$p('pr-copy-link').onclick = () => { if (privMine) copyText(roomLink(privMine.roomId), '🔗 Link copied (they still need the passcode)'); };
$p('priv-info').onclick = () => { if (privMine) { showReady(privMine); privOverlay.classList.add('open'); } else showToast('Room ID: ' + (privRoom ? privRoom.id : '—')); };

function findRoom() {
  const id = $p('pj-id').value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (id.length < 4) { $p('pj-found').textContent = 'Enter the Room ID'; return; }
  $p('pj-found').textContent = 'Searching…'; privFound = null; $p('pj-join').disabled = true;
  socket.emit('privateLookup', { roomId: id }, (r) => {
    if (r && r.ok) {
      privFound = r.roomId;
      $p('pj-found').textContent = r.emoji + ' ' + r.placeName + ' — ' + r.count + '/' + r.capacity + ' inside' + (r.full ? ' (full)' : '');
      $p('pj-join').disabled = !!r.full; $p('pj-pass').focus();
    } else { $p('pj-found').textContent = '❌ ' + ((r && r.error) || 'Room not found'); }
  });
}
$p('pj-find').onclick = findRoom;
$p('pj-id').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); findRoom(); } });
$p('pj-pass').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $p('pj-join').click(); } });
$p('pj-join').onclick = () => {
  if (!privFound) { findRoom(); return; }
  const btn = $p('pj-join'); btn.disabled = true;
  socket.emit('privateJoin', { roomId: privFound, passcode: $p('pj-pass').value }, (r) => {
    if (r && r.ok) { privOverlay.classList.remove('open'); $p('pj-pass').value = ''; showToast('🔒 You joined the private room'); }
    else { $p('pj-found').textContent = '❌ ' + ((r && r.error) || 'Could not join'); btn.disabled = false; $p('pj-pass').select(); }
  });
};
if (linkRoom) { const t = setInterval(() => { if (inGame()) { clearInterval(t); openPrivate('join', linkRoom); } }, 600); }

function paintPrivBar() {
  const bar = $p('private-bar');
  if (!privRoom) { bar.classList.remove('open'); return; }
  const names = privRoom.members.map(m => m.name).join(', ');
  $p('priv-title').textContent = '🔒 ' + privRoom.emoji + ' ' + privRoom.placeName + ' · ' + privRoom.id + ' · ' + (privRoom.members.length < 2 ? 'waiting for others…' : names);
  $p('priv-info').style.display = privMine ? '' : 'none';
  $p('priv-mute').innerHTML = privMuted ? '<i class="fa-solid fa-microphone-slash"></i> Unmute' : '<i class="fa-solid fa-microphone"></i> Mute';
  bar.classList.add('open');
}
socket.on('privateRoomState', (room) => {
  const first = !privRoom;
  privRoom = room;
  if (first) {
    if (micOn) { micOn = false; paintVoiceBtns(); }
    syncVoice();
    connectPrivate();
  }
  paintPrivBar();
});
async function connectPrivate() {
  if (lkPriv || privConnecting || !privRoom) return;
  privConnecting = true;
  try {
    const r = await new Promise(res => socket.emit('privateJoinToken', res));
    if (!r || !r.ok) throw new Error((r && r.error) || 'no voice token');
    if (!privRoom) return;
    const room = new LivekitClient.Room({ audioCaptureDefaults: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
    room.on(LivekitClient.RoomEvent.TrackSubscribed, (track) => {
      if (track.kind === 'audio') { const el = track.attach(); el.classList.add('lk-priv-audio'); el.style.display = 'none'; document.body.appendChild(el); }
    });
    room.on(LivekitClient.RoomEvent.TrackUnsubscribed, (track) => { track.detach().forEach(el => el.remove()); });
    await room.connect(r.url, r.token);
    lkPriv = room;
    try { await room.startAudio(); } catch (e) {}
    try { await room.localParticipant.setMicrophoneEnabled(!privMuted); } catch (e) { showToast('🎤 Allow the microphone to talk in the private room'); }
    paintPrivBar();
  } catch (e) {
    showToast('Private voice unavailable: ' + (e.message || e));
  } finally { privConnecting = false; }
}
function cleanupPrivate() {
  const r = lkPriv; lkPriv = null; privRoom = null; privMine = null; privMuted = false;
  if (r) { try { r.disconnect(); } catch (e) {} }
  document.querySelectorAll('audio.lk-priv-audio').forEach(el => el.remove());
  privOverlay.classList.remove('open');
  paintPrivBar();
  syncVoice();
}
socket.on('privateRoomClosed', ({ reason }) => {
  const msg = { ended: 'The private room ended', expired: 'The private room closed after being empty too long', logout: 'You were logged out' }[reason] || 'The private room closed';
  cleanupPrivate(); showToast('🔒 ' + msg);
});
$p('priv-leave').onclick =() => { socket.emit('privateLeave'); cleanupPrivate(); showToast('You left the private room'); };
$p('priv-mute').onclick = async () => {
  privMuted = !privMuted;
  if (lkPriv) { try { await lkPriv.localParticipant.setMicrophoneEnabled(!privMuted); } catch (e) {} }
  paintPrivBar();
};


/* =========================================================
   8b) PRIVATE AREAS — each private room is its own themed place, far away from the city.
   ========================================================= */
const PRIVATE_ORIGIN = { x: 5000, z: 5000 };
let privScene = null;
function srnd(i) { const x = Math.sin(i * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); }
function pm(color, o) { return new THREE.MeshStandardMaterial(Object.assign({ color, roughness: 0.9 }, o || {})); }
function pbox(g, w, h, d, color, x, y, z, o) { const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), pm(color, o)); m.position.set(x, y, z); g.add(m); return m; }
function pcyl(g, rt, rb, h, color, x, y, z, seg, o) { const m = new THREE.Mesh(new THREE.CylinderGeometry(rt, rb, h, seg || 12), pm(color, o)); m.position.set(x, y, z); g.add(m); return m; }
function pcone(g, r, h, color, x, y, z) { const m = new THREE.Mesh(new THREE.ConeGeometry(r, h, 8), pm(color)); m.position.set(x, y, z); g.add(m); return m; }
function psph(g, r, color, x, y, z, o, sy) { const m = new THREE.Mesh(new THREE.SphereGeometry(r, 12, 10), pm(color, o)); m.position.set(x, y, z); if (sy) m.scale.y = sy; g.add(m); return m; }
function pdisc(g, r, color, y, o) { const m = new THREE.Mesh(new THREE.CircleGeometry(r, 48), pm(color, o)); m.rotation.x = -Math.PI / 2; m.position.y = y; g.add(m); return m; }
function pplane(g, w, d, color, x, y, z, o) { const m = new THREE.Mesh(new THREE.PlaneGeometry(w, d), pm(color, o)); m.rotation.x = -Math.PI / 2; m.position.set(x, y, z); g.add(m); return m; }
function psign(g, text, y, z, w) { const sp = makeLabel(text); sp.scale.set(w, w / 4, 1); sp.position.set(0, y, z); g.add(sp); return sp; }
function ptree(g, x, z, k) { k = k || 1; pcyl(g, 0.25 * k, 0.35 * k, 2 * k, 0x7a5230, x, k, z, 6); pcone(g, 1.5 * k, 2.6 * k, 0x2e8b3d, x, 3.2 * k, z); pcone(g, 1.15 * k, 2.2 * k, 0x3aa04a, x, 4.4 * k, z); }
function ppalm(g, x, z) { pcyl(g, 0.18, 0.3, 4.6, 0x9a6b3c, x, 2.3, z, 6); for (let i = 0; i < 6; i++) { const a = i / 6 * Math.PI * 2; const l = pbox(g, 0.4, 0.08, 2.6, 0x2fa84f, x + Math.sin(a) * 1.1, 4.7, z + Math.cos(a) * 1.1); l.rotation.y = a; l.rotation.x = 0.4; } }
function pbench(g, x, z) { const b = new THREE.Group(); pbox(b, 2.2, 0.15, 0.7, 0x8b5a2b, 0, 0.55, 0); pbox(b, 2.2, 0.7, 0.12, 0x8b5a2b, 0, 0.95, -0.32); pbox(b, 0.12, 0.55, 0.6, 0x5b3a1a, -0.95, 0.27, 0); pbox(b, 0.12, 0.55, 0.6, 0x5b3a1a, 0.95, 0.27, 0); b.position.set(x, 0, z); b.rotation.y = Math.atan2(-x, -z); g.add(b); }
function plamp(g, x, z, c) { pcyl(g, 0.07, 0.09, 3.2, 0x333333, x, 1.6, z, 6); psph(g, 0.3, c || 0xfff1b0, x, 3.3, z, { emissive: c || 0xfff1b0, emissiveIntensity: 1 }); }
function pmug(g, x, y, z, color, k) { k = k || 1; pcyl(g, 0.22 * k, 0.2 * k, 0.4 * k, color, x, y + 0.2 * k, z, 12); const h = new THREE.Mesh(new THREE.TorusGeometry(0.12 * k, 0.04 * k, 6, 12), pm(color)); h.position.set(x + 0.26 * k, y + 0.2 * k, z); g.add(h); }
function proom(g, half, wallColor, floorColor, ceilColor, wallH) {
  pbox(g, half * 2 + 2, 0.2, half * 2 + 2, floorColor, 0, -0.1, 0);
  [[0, -half, half * 2 + 1, 0.6], [0, half, half * 2 + 1, 0.6]].forEach(w => pbox(g, w[2], wallH, w[3], wallColor, w[0], wallH / 2, w[1]));
  [[-half, 0], [half, 0]].forEach(w => pbox(g, 0.6, wallH, half * 2 + 1, wallColor, w[0], wallH / 2, w[1]));
  const ceil = pplane(g, half * 2 + 2, half * 2 + 2, ceilColor, 0, wallH, 0, { side: THREE.DoubleSide }); ceil.rotation.x = Math.PI / 2;
}
function plight(g, color, intensity, dist, x, y, z) { const l = new THREE.PointLight(color, intensity, dist); l.position.set(x, y, z); g.add(l); }

function buildSarovaram(g) {
  pdisc(g, 70, 0x6fbf4a, -0.02);
  const lake = pdisc(g, 9, 0x4fb3e8, 0.03, { roughness: 0.2, metalness: 0.1 }); lake.position.set(-9, 0.03, -9);
  const path = new THREE.Mesh(new THREE.RingGeometry(11, 13.5, 48), pm(0xd9c79a)); path.rotation.x = -Math.PI / 2; path.position.y = 0.03; g.add(path);
  for (let i = 0; i < 16; i++) { const a = i / 16 * Math.PI * 2, r = 27 + srnd(i) * 6; ptree(g, Math.cos(a) * r, Math.sin(a) * r, 1 + srnd(i + 40) * 0.5); }
  for (let i = 0; i < 4; i++) { const a = i / 4 * Math.PI * 2 + 0.6; pbench(g, Math.cos(a) * 14.5, Math.sin(a) * 14.5); plamp(g, Math.cos(a + 0.5) * 12.3, Math.sin(a + 0.5) * 12.3); }
  const fc = [0xff5c8a, 0xffd23f, 0xffffff, 0xb57bff];
  for (let i = 0; i < 40; i++) { const a = srnd(i + 7) * Math.PI * 2, r = 4 + srnd(i + 90) * 21; if (Math.hypot(Math.cos(a) * r + 9, Math.sin(a) * r + 9) < 10) continue; psph(g, 0.16, fc[i % 4], Math.cos(a) * r, 0.18, Math.sin(a) * r); }
  psign(g, '🌳 Sarovaram Park', 6.5, -18, 9);
  return { radius: 24, bg: 0x8ed1fc, fog: [0xbfe3ff, 30, 110] };
}
function buildBeach(g) {
  pdisc(g, 70, 0xf3dc9f, -0.02);
  pplane(g, 220, 90, 0x2aa9e0, 0, 0.02, -72, { roughness: 0.25, metalness: 0.1 });
  [-32, -39, -48].forEach((z, i) => pplane(g, 220, 1.3 - i * 0.2, 0x8fdcf5, 0, 0.04, z));
  pplane(g, 220, 1.2, 0xffffff, 0, 0.05, -27.5);
  const uc = [0xe74c3c, 0xf1c40f, 0x3498db, 0xff69b4];
  for (let i = 0; i < 4; i++) { const x = -15 + i * 10, z = -6 + (i % 2) * 8; pcyl(g, 0.08, 0.08, 3, 0xffffff, x, 1.5, z, 6); pcone(g, 2.2, 0.8, uc[i], x, 3.1, z); pbox(g, 2.2, 0.08, 0.9, uc[(i + 1) % 4], x + 0.3, 0.04, z + 1.8); pbox(g, 1.1, 0.5, 0.8, 0xffffff, x - 1.6, 0.3, z + 0.6); }
  [[-22, -12], [-24, 4], [22, -10], [24, 6], [-18, 16], [19, 17]].forEach(c => ppalm(g, c[0], c[1]));
  for (let i = 0; i < 18; i++) psph(g, 0.14, 0xffc4d6, (srnd(i + 3) - 0.5) * 40, 0.1, (srnd(i + 33) - 0.5) * 30 + 2, null, 0.6);
  psph(g, 5, 0xffe066, 30, 28, -90, { emissive: 0xffe066, emissiveIntensity: 1 });
  psign(g, '🏖️ Beach', 6.5, -22, 7);
  return { radius: 24, bg: 0x87ceeb, fog: [0xcdeffd, 40, 150] };
}
function buildHappyCup(g) {
  proom(g, 20, 0xf0d9b5, 0xb98a5e, 0x3a261a, 6);
  pdisc(g, 6.5, 0xc0392b, 0.02);
  for (let i = 0; i < 6; i++) {
    const a = i / 6 * Math.PI * 2 + 0.5, x = Math.cos(a) * 8.5, z = Math.sin(a) * 8.5 + 2;
    pcyl(g, 0.95, 0.95, 0.08, 0xfff4e0, x, 1, z, 20); pcyl(g, 0.1, 0.1, 1, 0x4a2f1b, x, 0.5, z, 8); pcyl(g, 0.45, 0.45, 0.06, 0x4a2f1b, x, 0.03, z, 12);
    pmug(g, x + 0.2, 1.04, z, 0xffffff); pmug(g, x - 0.35, 1.04, z + 0.2, 0xffd23f);
    [0, Math.PI].forEach(o => { const cx = x + Math.cos(a + o) * 1.5, cz = z + Math.sin(a + o) * 1.5; pbox(g, 0.7, 0.1, 0.7, 0xa23b2a, cx, 0.6, cz); pbox(g, 0.7, 0.7, 0.1, 0xa23b2a, cx - Math.cos(a + o) * 0.3, 1, cz - Math.sin(a + o) * 0.3); pcyl(g, 0.05, 0.05, 0.6, 0x333333, cx, 0.3, cz, 6); });
    psph(g, 0.28, 0xfff1c1, x, 4.6, z, { emissive: 0xfff1c1, emissiveIntensity: 1 }); pcyl(g, 0.02, 0.02, 1.4, 0x222222, x, 5.3, z, 4);
  }
  pbox(g, 11, 1.2, 1.3, 0x6b4423, 0, 0.6, -14); pbox(g, 11.4, 0.12, 1.6, 0xd9b28a, 0, 1.25, -14);
  pbox(g, 11, 0.12, 0.6, 0x6b4423, 0, 2.3, -18.4); pbox(g, 11, 0.12, 0.6, 0x6b4423, 0, 3.4, -18.4);
  for (let i = 0; i < 9; i++) { pmug(g, -4.6 + i * 1.15, 2.36, -18.4, [0xff6b6b, 0xffd23f, 0x4dd4ac, 0x6ea8fe, 0xffffff][i % 5]); pmug(g, -4.6 + i * 1.15, 3.46, -18.4, [0xffffff, 0xff9ff3, 0xfeca57][i % 3]); }
  pbox(g, 1.4, 0.9, 0.8, 0x888c93, -3, 1.7, -14); pbox(g, 1.1, 0.7, 0.8, 0x5d6168, 1, 1.6, -14);
  [[-15, 15], [15, 15], [-15, -4], [15, -4]].forEach(c => { pcyl(g, 0.5, 0.4, 0.8, 0x9c5a3c, c[0], 0.4, c[1], 10); psph(g, 0.8, 0x2f9e44, c[0], 1.5, c[1]); });
  psign(g, '☕ Happy Cup', 4.6, -18.1, 9);
  plight(g, 0xffe2a8, 1.1, 30, 0, 4.4, -2); plight(g, 0xffe2a8, 0.9, 26, 9, 4.4, 8); plight(g, 0xffe2a8, 0.9, 26, -9, 4.4, 8);
  return { radius: 12, bg: 0x2b1a12, fog: [0x2b1a12, 34, 80] };
}
function buildHugAMug(g) {
  proom(g, 20, 0xf6d6e0, 0xe9c9a6, 0xfbe7ee, 6.5);
  pdisc(g, 5.5, 0xfff2cc, 0.02);
  pcyl(g, 2.2, 1.9, 3.2, 0xffffff, 0, 1.6, -7, 24); pcyl(g, 1.95, 1.95, 0.05, 0x6b3e26, 0, 3.15, -7, 24);
  const h = new THREE.Mesh(new THREE.TorusGeometry(1.1, 0.3, 8, 20), pm(0xffffff)); h.position.set(2.6, 1.7, -7); g.add(h);
  const hg = new THREE.Mesh(new THREE.SphereGeometry(0.5, 10, 8), pm(0xff5c8a)); hg.scale.set(1, 0.9, 0.4); hg.position.set(0, 1.7, -4.8); g.add(hg);
  for (let i = 0; i < 3; i++) psph(g, 0.55 - i * 0.1, 0xffffff, -0.3 + i * 0.35, 4 + i * 0.7, -7, { transparent: true, opacity: 0.45 - i * 0.1, roughness: 1 });
  [[-9, 6, 0xd9577d], [9, 6, 0x7d8cd9], [0, 12, 0xe0a13b]].forEach((c, i) => {
    const sofa = new THREE.Group(); pbox(sofa, 4.4, 0.7, 1.8, c[2], 0, 0.55, 0); pbox(sofa, 4.4, 1.2, 0.5, c[2], 0, 1.1, -0.9); pbox(sofa, 0.5, 1, 1.8, c[2], -2.2, 0.8, 0); pbox(sofa, 0.5, 1, 1.8, c[2], 2.2, 0.8, 0);
    pbox(sofa, 0.8, 0.8, 0.3, 0xffffff, -1.2, 1.1, -0.4); pbox(sofa, 0.8, 0.8, 0.3, 0xffd9e4, 1.2, 1.1, -0.4);
    sofa.position.set(c[0], 0, c[1]); sofa.rotation.y = Math.atan2(-c[0], -c[1] + (i === 2 ? 4 : 0)); g.add(sofa);
  });
  [[-5, 1, 0x6dd5c0], [5, 1, 0xffb86b], [-12, -2, 0xb084f5], [12, -1, 0xff7aa8]].forEach(b => psph(g, 1, b[2], b[0], 0.7, b[1], null, 0.7));
  for (let i = 0; i < 18; i++) { const x = -15 + i * (30 / 17); psph(g, 0.14, [0xffd23f, 0xff7aa8, 0xffffff, 0x7ad9ff][i % 4], x, 4.6 - Math.sin(i / 17 * Math.PI) * 0.9, -12, { emissive: [0xffd23f, 0xff7aa8, 0xffffff, 0x7ad9ff][i % 4], emissiveIntensity: 1 }); }
  [[-16, 14], [16, 14], [-16, -14], [16, -14]].forEach(c => { pcyl(g, 0.5, 0.4, 0.8, 0xc47a8f, c[0], 0.4, c[1], 10); psph(g, 0.8, 0x3aa86a, c[0], 1.5, c[1]); });
  psign(g, '🫶 Hug a Mug', 5, -19.2, 9);
  plight(g, 0xffb3d1, 1.1, 30, 0, 4.6, 0); plight(g, 0xffd9b0, 0.8, 24, 0, 4.6, -10);
  return { radius: 12, bg: 0x3b2230, fog: [0x3b2230, 34, 80] };
}
function buildGenericArea(g, info) {
  pdisc(g, 70, 0x6fbf4a, -0.02);
  for (let i = 0; i < 12; i++) { const a = i / 12 * Math.PI * 2, r = 26 + srnd(i) * 5; ptree(g, Math.cos(a) * r, Math.sin(a) * r, 1 + srnd(i + 20) * 0.4); }
  pbench(g, 6, 5); pbench(g, -6, 5); plamp(g, 0, -8);
  psign(g, (info.emoji || '🔒') + ' ' + info.name, 6, -16, 9);
  return { radius: 22, bg: 0x8ed1fc, fog: [0xbfe3ff, 30, 110] };
}
const PRIVATE_SCENES = { sarovaram: buildSarovaram, beach: buildBeach, happycup: buildHappyCup, hugamug: buildHugAMug };

function disposeGroup(g) {
  g.traverse(o => { if (o.geometry) o.geometry.dispose(); if (o.material) { (Array.isArray(o.material) ? o.material : [o.material]).forEach(m => { if (m.map) m.map.dispose(); m.dispose(); }); } });
}
function enterPrivateScene(placeId, spawn) {
  if (privScene) leavePrivateScene(null);
  const info = PRIV_PLACES.find(p => p.id === placeId) || { id: placeId, name: placeId, emoji: '🔒' };
  const group = new THREE.Group(); group.position.set(PRIVATE_ORIGIN.x, 0, PRIVATE_ORIGIN.z);
  const spec = (PRIVATE_SCENES[placeId] || buildGenericArea)(group, info);
  scene.add(group);
  privScene = { group, radius: spec.radius, prevBg: scene.background, prevFog: scene.fog, back: { x: myAvatar.position.x, y: myAvatar.position.y, z: myAvatar.position.z, rotY: myAvatar.rotation.y } };
  scene.background = new THREE.Color(spec.bg);
  scene.fog = new THREE.Fog(spec.fog[0], spec.fog[1], spec.fog[2]);
  ['shop-overlay', 'stats-overlay', 'inventory-overlay'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
  myAvatar.position.set(spawn.x, 0, spawn.z);
  camYaw = Math.atan2(PRIVATE_ORIGIN.x - spawn.x, PRIVATE_ORIGIN.z - spawn.z);
  socket.emit('move', { x: myAvatar.position.x, y: 0, z: myAvatar.position.z, rotY: myAvatar.rotation.y });
}
function leavePrivateScene(spawn) {
  if (!privScene) return;
  const ps = privScene; privScene = null;
  scene.remove(ps.group); disposeGroup(ps.group);
  scene.background = ps.prevBg; scene.fog = ps.prevFog;
  const to = spawn || ps.back;
  myAvatar.position.set(to.x, to.y || 0, to.z); myAvatar.rotation.y = to.rotY || 0;
  socket.emit('move', { x: myAvatar.position.x, y: myAvatar.position.y, z: myAvatar.position.z, rotY: myAvatar.rotation.y });
}
function clampPrivate() {
  const dx = myAvatar.position.x - PRIVATE_ORIGIN.x, dz = myAvatar.position.z - PRIVATE_ORIGIN.z, d = Math.hypot(dx, dz);
  if (d > privScene.radius) { myAvatar.position.x = PRIVATE_ORIGIN.x + dx / d * privScene.radius; myAvatar.position.z = PRIVATE_ORIGIN.z + dz / d * privScene.radius; }
}
socket.on('spaceChanged', ({ place, spawn }) => {
  Object.keys(others).forEach(id => { scene.remove(others[id].group); delete others[id]; });
  Object.values(handLinks).forEach(m => scene.remove(m)); Object.keys(handLinks).forEach(k => delete handLinks[k]);
  handWith = null; const hb = document.getElementById('hand-btn'); if (hb) hb.classList.remove('selected');
  sitting = false; mySitPos = null; myAvatar.position.y = 0;
  if (place) enterPrivateScene(place, spawn); else leavePrivateScene(spawn);
  refreshOnlineCount();
});
socket.on('disconnect', () => { if (privScene) { leavePrivateScene(null); cleanupPrivate(); } });

/* =========================================================
   Character settings (inside the Stats panel). Name is locked to the account.
   ========================================================= */
function rebuildMyAvatar() {
  const pos = myAvatar.position.clone(), ry = myAvatar.rotation.y, vis = myAvatar.visible;
  scene.remove(myAvatar);
  myAvatar = makeAvatarMesh(myGender, myOutfitColor, myHairStyle, myRole);
  myAvatar.position.copy(pos); myAvatar.rotation.y = ry; myAvatar.visible = vis;
  scene.add(myAvatar);
}
let saveTimer = null;
function lookChanged() {
  rebuildMyAvatar();
  socket.emit('updateLook', { gender: myGender, outfitColor: myOutfitColor, hairStyle: myHairStyle });
  document.getElementById('stat-look').textContent = `${myGender}, ${myHairStyle} hair`;
  clearTimeout(saveTimer); saveTimer = setTimeout(saveProfile, 700);
}
function paintSettings() {
  document.querySelectorAll('#set-gender .set-pill').forEach(b => b.classList.toggle('selected', b.dataset.sg === myGender));
  document.querySelectorAll('#set-hair .set-pill').forEach(b => b.classList.toggle('selected', b.dataset.sh === myHairStyle));
  document.querySelectorAll('#set-color .set-swatch').forEach(b => b.classList.toggle('selected', b.dataset.sc.toLowerCase() === String(myOutfitColor).toLowerCase()));
  const cur = myRole || 'visitor';
  document.querySelectorAll('#set-role .set-pill').forEach(b => b.classList.toggle('selected', b.dataset.sr === cur));
  const note = document.getElementById('set-note');
  note.textContent = auth.loggedIn ? "Your name comes from your account and can't be changed here. Changes are saved for your next visit. Changing \"Play as\" reloads the city."
                                   : "Log in to save your character for next time. Changing \"Play as\" reloads the city.";
}
document.querySelectorAll('#set-gender .set-pill').forEach(b => b.onclick = () => {
  if (myRole) { showToast('Tea-shop roles keep their character — switch to Visitor to change it'); return; }
  myGender = b.dataset.sg; paintSettings(); lookChanged();
});
document.querySelectorAll('#set-hair .set-pill').forEach(b => b.onclick = () => { myHairStyle = b.dataset.sh; paintSettings(); lookChanged(); });
document.querySelectorAll('#set-color .set-swatch').forEach(b => b.onclick = () => { myOutfitColor = b.dataset.sc; paintSettings(); lookChanged(); });
document.querySelectorAll('#set-role .set-pill').forEach(b => b.onclick = () => {
  const want = b.dataset.sr === 'visitor' ? null : b.dataset.sr;
  if (want === myRole) return;
  try { if (want) sessionStorage.setItem('cityRole', want); else sessionStorage.removeItem('cityRole'); } catch (e) {}
  if (auth.loggedIn) { clearTimeout(saveTimer); saveProfile().finally(() => location.reload()); } else location.reload();
});
function afterLoginInGame() {
  if (!auth.profile) { saveProfile(); return; }
  myGender = myRole ? myGender : auth.profile.gender; myOutfitColor = auth.profile.outfit; myHairStyle = auth.profile.hair;
  lookChanged(); paintSettings();
}

document.getElementById('stats-btn').onclick = () => {
  paintSettings();
  document.getElementById('stat-name').textContent = myName + (auth.loggedIn ? '  🔒' : '');
  document.getElementById('stat-look').textContent = `${myGender}, ${myHairStyle} hair`;
  document.getElementById('stat-health').textContent = Math.round(myHealth) + '%';
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
   HEALTH · DAMAGE · AMBULANCE · HOSPITAL · MEDICINE
   ========================================================= */
const HOSPITAL = { x: 30, z: -25 };
const HOSPITAL_FEE = 25;
const REGEN_SECONDS = 10 * 60;          // 0 → 100 slowly across 10 minutes if no medicine taken
const HOSPITAL_STAY_MS = 25000;

let myHealth = 100;
let inHospital = false;
let hospitalEndAt = 0, hospitalTimer = null;
let ambulance = null;
let invulnUntil = 0, lastDamageAt = -1e9, regenAcc = 0, boostUntil = 0;
let _lastHealthAt = performance.now();

const healthPill = document.getElementById('health-pill');
const healthFill = document.getElementById('health-fill');
const healthVal  = document.getElementById('health-val');
const ambBtn     = document.getElementById('ambulance-btn');

function paintHealth() {
  const pct = Math.max(0, Math.min(100, myHealth));
  healthFill.style.width = pct + '%';
  healthVal.textContent  = Math.round(pct);
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
function applyDamage(amount, reason) {
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
  const white = new THREE.MeshStandardMaterial({ color: 0xf4f4f4, roughness: 0.5 });
  const red   = new THREE.MeshStandardMaterial({ color: 0xd62828, emissive: 0x400000 });
  const dark  = new THREE.MeshStandardMaterial({ color: 0x1a1a1a });
  const glass = new THREE.MeshStandardMaterial({ color: 0x1d2b38, roughness: 0.1 });
  const add = (geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); g.add(o); return o; };
  add(new THREE.BoxGeometry(2.4, 0.75, 1.15), white, 0, 0.6, 0);
  add(new THREE.BoxGeometry(2.2, 0.95, 1.1), white, 0, 1.4, 0);
  add(new THREE.BoxGeometry(2.25, 0.2, 1.13), red, 0, 1.15, 0);
  add(new THREE.BoxGeometry(0.55, 0.14, 0.14), red, -0.6, 1.65, 0.57);
  add(new THREE.BoxGeometry(0.14, 0.55, 0.14), red, -0.6, 1.65, 0.57);
  add(new THREE.BoxGeometry(0.55, 0.14, 0.14), red, -0.6, 1.65, -0.57);
  add(new THREE.BoxGeometry(0.14, 0.55, 0.14), red, -0.6, 1.65, -0.57);
  add(new THREE.BoxGeometry(2.1, 0.55, 0.95), glass, 0.2, 1.42, 0);
  const sirenR = add(new THREE.BoxGeometry(0.22, 0.16, 0.22),
    new THREE.MeshStandardMaterial({ color: 0xff0000, emissive: 0xff0000, emissiveIntensity: 1 }),
    0.4, 1.95, 0);
  const sirenB = add(new THREE.BoxGeometry(0.22, 0.16, 0.22),
    new THREE.MeshStandardMaterial({ color: 0x0066ff, emissive: 0x0066ff, emissiveIntensity: 0.2 }),
    -0.4, 1.95, 0);
  const wheelG = new THREE.CylinderGeometry(0.3, 0.3, 0.2, 12);
  [[-0.8,-0.62],[-0.8,0.62],[0.8,-0.62],[0.8,0.62]].forEach(([x,z]) => {
    const w = new THREE.Mesh(wheelG, dark); w.rotation.z = Math.PI/2; w.position.set(x, 0.3, z); g.add(w);
  });
  g.traverse(o => { if (o.isMesh) o.castShadow = true; });
  g.userData = { sirenR, sirenB };
  return g;
}
function sirenBeep(freqHi) {
  if (!soundOn || !actx || actx.state !== 'running') return;
  const t = actx.currentTime, o = actx.createOscillator(), gn = actx.createGain();
  o.type = 'sine';
  o.frequency.setValueAtTime(freqHi ? 1100 : 750, t);
  o.frequency.linearRampToValueAtTime(freqHi ? 750 : 1100, t + 0.4);
  gn.gain.setValueAtTime(0.0001, t);
  gn.gain.linearRampToValueAtTime(0.14, t + 0.05);
  gn.gain.linearRampToValueAtTime(0.0001, t + 0.5);
  o.connect(gn); gn.connect(master); o.start(t); o.stop(t + 0.55);
}
let _sirenFlip = false;
function callAmbulance(free) {
  if (ambulance || inHospital) return;
  if (!free) {
    const bal = parseInt(balloonEl.textContent, 10) || 0;
    if (bal < HOSPITAL_FEE) { showToast(`🚑 Need ${HOSPITAL_FEE} 🎈 to call an ambulance`); return; }
    balloonEl.textContent = bal - HOSPITAL_FEE;
  }
  showToast(free ? '🚑 You collapsed — ambulance rushing!' : '🚑 Ambulance on the way!');
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
  g.userData.sirenR.material.emissiveIntensity = 0.4 + 0.9 * Math.abs(Math.sin(performance.now() / 180));
  g.userData.sirenB.material.emissiveIntensity = 0.4 + 0.9 * Math.abs(Math.cos(performance.now() / 180));
  const now = performance.now();
  if (now > ambulance.sirenAt && soundOn) { ambulance.sirenAt = now + 550; _sirenFlip = !_sirenFlip; sirenBeep(_sirenFlip); }
  if (d > 3) {
    const sp = Math.min(9 * dt, d);
    g.position.x += dx / d * sp; g.position.z += dz / d * sp;
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
  document.getElementById('hosp-sub').textContent = 'Doctors are treating you…';
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
  myHealth = 40;                       // ← you come back with 40%
  paintHealth();
  myAvatar.visible = true;
  myAvatar.position.set(HOSPITAL.x + 3, 0, HOSPITAL.z + 8);
  invulnUntil = performance.now() + 8000;
  showToast('🏥 Discharged at 40% — buy 💊 medicine or wait to recover');
}

/* Slow regen (~10 min baseline; 5× faster with medicine) */
function healthTick() {
  const now = performance.now();
  const dt  = Math.min(0.1, (now - _lastHealthAt) / 1000);
  _lastHealthAt = now;
  if (inHospital || myHealth >= 100) return;
  if (now - lastDamageAt < 6000) return;                 // pause 6s after damage
  const boost = now < boostUntil ? 5 : 1;
  regenAcc += dt * (100 / REGEN_SECONDS) * boost;
  if (regenAcc >= 0.5) { const add = Math.floor(regenAcc); regenAcc -= add; setHealth(myHealth + add); }
}

/* Vehicle collisions */
function checkVehicleHits() {
  if (inHospital || drivingCarId) return;
  const now = performance.now();
  if (now < invulnUntil) return;
  for (const id in cars) {
    const c = cars[id];
    if (!c.occupiedBy || c.occupiedBy === socket.id) continue;
    const g = c.group;
    const sp = Math.hypot(g.position.x - (c._lx === undefined ? g.position.x : c._lx), g.position.z - (c._lz === undefined ? g.position.z : c._lz));
    c._lx = g.position.x; c._lz = g.position.z;
    if (sp < 0.05) continue;
    const d = Math.hypot(g.position.x - myAvatar.position.x, g.position.z - myAvatar.position.z);
    if (d < 1.8) {
      const dmg = Math.min(45, 15 + sp * 110);
      applyDamage(dmg, 'vehicle');
      invulnUntil = now + 1200;
      const dx = myAvatar.position.x - g.position.x, dz = myAvatar.position.z - g.position.z, dd = Math.hypot(dx, dz) || 1;
      myAvatar.position.x += dx / dd * 2; myAvatar.position.z += dz / dd * 2;
    }
  }
}

/* Medical items (client-side purchase & healing) */
const MED_ITEMS = [
  { id: 'bandage',      name: 'Bandage',       emoji: '🩹', price:  5, category: 'medical', shop: 'main', heal: 25, boost: 15000 },
  { id: 'painkiller',   name: 'Painkiller',    emoji: '💊', price: 10, category: 'medical', shop: 'main', heal: 45, boost: 25000 },
  { id: 'energy_drink', name: 'Energy Drink',  emoji: '🥤', price: 15, category: 'medical', shop: 'main', heal: 70, boost: 40000 },
  { id: 'med_kit',      name: 'Medical Kit',   emoji: '🧰', price: 30, category: 'medical', shop: 'main', heal: 100, boost: 60000 },
];
function buyMedical(mi) {
  const bal = parseInt(balloonEl.textContent, 10) || 0;
  if (bal < mi.price) { showToast('Not enough balloons 🎈'); return; }
  balloonEl.textContent = bal - mi.price;
  setHealth(myHealth + mi.heal);
  boostUntil = performance.now() + mi.boost;
  showToast(`${mi.emoji} Used ${mi.name} — +${mi.heal} HP · recovery boosted`);
  refreshAffordability();
}

paintHealth();

/* =========================================================
   9) RENDER LOOP
   ========================================================= */
function applyShadows() {
  scene.traverse(o => {
    if (!o.isMesh) return;
    o.receiveShadow = true;
    const q = o.geometry && o.geometry.parameters;
    o.castShadow = !o.isInstancedMesh && !(o.material && o.material.transparent) && !['PlaneGeometry', 'ShapeGeometry', 'CircleGeometry'].includes(o.geometry.type) && !(q && q.height !== undefined && q.height < 0.3);
  });
}
applyShadows();
const clock = new THREE.Clock();
function animate() {
  requestAnimationFrame(animate);
  const t = clock.getElapsedTime();
  waterTex.offset.y = t * 0.05;
  Object.values(cars).forEach(c => { if (c.prop && c.occupiedBy && c.occupiedBy !== socket.id) c.prop.rotation.x += 0.6; });
  updateMovement();

  // HEALTH · AMBULANCE · DAMAGE
  healthTick();
  checkVehicleHits();
  updateAmbulance(0.016);
  if (myHealth <= 0 && !inHospital && !ambulance) document.getElementById('death-overlay').classList.add('open');

  keralaTick(t);
  const nowp = performance.now();
  tickAvatar(myAvatar, nowp); rideTick(); if (chayaNpc) tickAvatar(chayaNpc, nowp); if (milkmaid) tickAvatar(milkmaid, nowp); updatePeds(); updateSky(); updateFight(); PEDS.forEach(p => tickAvatar(p.g, nowp));
  updateAtmosphere();
  updateWaypointReadout();
  if (waypointBeacon.visible) waypointBeacon.position.y = 1.2 + Math.sin(t*3)*0.15;
  if (mapOpen) drawMap();
  Object.values(balloonMeshes).forEach(m => { if (m.visible) m.position.y = 1 + Math.sin(t*2 + m.userData.bobOffset)*0.2; });
  Object.values(chestMeshes).forEach(m => { if (m.visible) m.position.y = 0.9 + Math.sin(t*1.6 + m.userData.bobOffset)*0.12; });
  Object.values(others).forEach(o => {
    const ty = o.target.sitting ? -0.35 : o.target.y;
    o.group.position.lerp(new THREE.Vector3(o.target.x, ty, o.target.z), 0.2);
    o.group.rotation.y = o.target.rotY;
    tickAvatar(o.group, nowp);
  });
  updateHandLinks();
  Object.values(cars).forEach(c => {
    if (c.target && c.occupiedBy && c.occupiedBy !== socket.id) {
      c.group.position.lerp(new THREE.Vector3(c.target.x, c.target.y, c.target.z), 0.25);
      c.group.rotation.y = c.target.rotY;
    }
  });
  Object.values(cars).forEach(c => {   // spin wheels by distance rolled
    if (!c.wheels) return; const p = c.group.position;
    if (c._wx !== undefined) { const dx = p.x - c._wx, dz = p.z - c._wz, r = c.group.rotation.y, d = Math.hypot(dx, dz) * (dx * Math.cos(r) - dz * Math.sin(r) >= 0 ? 1 : -1); if (d) c.wheels.forEach(w => w.m.rotation.z -= d / w.R); }
    c._wx = p.x; c._wz = p.z;
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
