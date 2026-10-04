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
    <div id="account-pill"><span id="account-name">Guest</span><button id="account-action" type="button">Log in</button></div>
    <div id="balloon-pill"><i class="fa-solid fa-circle" style="border-radius:50%;"></i> 🎈 <span id="balloon-val">0</span></div>
  </div>
</div>

<button id="mic-btn" title="Microphone (off)"><i class="fa-solid fa-microphone-slash"></i></button>
<button id="sound-btn" title="Sound (off)"><i class="fa-solid fa-volume-xmark"></i></button>
<button id="map-btn"><i class="fa-solid fa-map"></i> Map</button>
<button id="stats-btn"><i class="fa-solid fa-user"></i> Stats</button>
<button id="shop-btn"><i class="fa-solid fa-store"></i> Shop</button>
<button id="inventory-btn"><i class="fa-solid fa-bag-shopping"></i> Bag</button>
<button id="private-btn"><i class="fa-solid fa-lock"></i> Private</button>

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
makeSky();

scene.add(new THREE.HemisphereLight(0xffffff, 0x8f8464, 1.15));
const sun = new THREE.DirectionalLight(0xfff3d6, 1.0);
sun.position.set(10, 25, 8);
sun.castShadow = true;
{ const big = !(typeof matchMedia !== 'undefined' && matchMedia('(pointer:coarse)').matches), sc = sun.shadow.camera;
  sun.shadow.mapSize.set(big ? 2048 : 1024, big ? 2048 : 1024); sc.left = -32; sc.right = 32; sc.top = 32; sc.bottom = -32; sc.near = 1; sc.far = 110;
  sun.shadow.bias = -0.0006; sun.shadow.normalBias = 0.02; }
scene.add(sun); scene.add(sun.target);

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
  [-1.7, -
