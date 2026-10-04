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
  #priv-mod{ display:none; width:100%; text-align:center; background:rgba(0,0,0,.35); border-radius:12px; padding:5px 8px; font-size:.92em; }
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
  <div id="priv-mod">🛡️ A moderator has joined this room for safety</div>
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

    <p class="priv-note">You and your people move into your own private copy of the place — nobody else can see you, hear you or walk in. For safety and legal reasons moderators can enter a private room — you will always see a notice when one does. Voice is never recorded.</p>
  </div>
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
const handoffBalloons = 0;   // balances now come only from the signed login token (see AUTH below)

/* =========================================================
   0b) AUTH — site login shared with login.php (same PHP session + "keep me signed in" cookie).
   * AUTH_URL / SITE_URL can be overridden: ?auth=https://neyyappam.com/ajax/city_auth.php&site=https://neyyappam.com
   * city_token is a short-lived signed token; server.js trusts ONLY this, never a uid from the browser.
   ========================================================= */
// This page is plain HTML, so '/ajax/city_auth.php' only works if the game is served from the PHP site itself.
// Anywhere else (Node host, subdomain, localhost) we call the live PHP site by its full address.
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
// Token is valid for 2h; refresh it well before it runs out, and whenever the tab comes back to the foreground.
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
  [[0, 0.62, 1.1, -0.55, 0.38], [0, 0.62, 1.1, 0.55, 0.38]].forEach(() => {});
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

/* ---- Sound engine (WebAudio). Silent until the Sound button is on.
   REAL RECORDINGS: if you drop files like cow.mp3, goat.mp3, hen.mp3, rooster.mp3, ox.mp3, crow.mp3, koel.mp3,
   kiss.mp3, laugh.mp3, aiyyo.mp3, bell.mp3 into your site's /sounds/ folder they are used automatically.
   Anything missing falls back to the built-in synthesised voice. ---- */
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
function glottalWave() {            // harmonic-rich "vocal fold" source instead of a plain sawtooth
  if (!glottal) { const n = 40, re = new Float32Array(n), im = new Float32Array(n); for (let k = 1; k < n; k++) im[k] = 1 / Math.pow(k, 1.25); glottal = actx.createPeriodicWave(re, im); }
  return glottal;
}
function noiseBuffer() {
  if (!noiseBuf) { noiseBuf = actx.createBuffer(1, actx.sampleRate, actx.sampleRate); const d = noiseBuf.getChannelData(0); for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1; }
  return noiseBuf;
}
// One vocal sound: pitch glide through f[], harmonic source, breath noise, formant filters [centre, Q, gain, sweepTo?],
// optional vibrato [Hz, depth] and tremolo "am" [Hz, depth] (the trembling "meeeh" of a goat).
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
  ox(v)          { if (S('ox', v)) return; SND.moo(v, 0.72); },                     // the kaalas: deeper than a cow
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
// river house yard: hens, a goat and a cow
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
      if (a.kind === 'hen' || a.kind === 'rooster') {            // hens run away from you (funny, local-only)
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
  a.rotation.y = yaw; b.rotation.y = yaw + Math.PI;        // both face each other, not just the one who kissed
  if (fromId === socket.id || toId === socket.id) kissFreeze = performance.now() + 900;   // a brief, real pause for the moment
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

// Goat headbutt: get too close to an aadu and it sends you flying — everyone sees you shout "aiyyo!"
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
  myAvatar.rotation.y = Math.atan2(-b.x, -b.z);         // face away from the bench, like sitting down on it
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
// Role picker (name gate)
document.querySelectorAll('.role-pill').forEach(b => b.onclick = () => {
  if (b.disabled) return;
  document.querySelectorAll('.role-pill').forEach(x => x.classList.remove('selected')); b.classList.add('selected');
  myRole = b.dataset.role === 'visitor' ? null : b.dataset.role;
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
  animalsTick(); funTick(performance.now()); socialTick();
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

const _geo = {};
const G = (key, make) => _geo[key] || (_geo[key] = make());
const shoeMatShared = new THREE.MeshStandardMaterial({ color: 0x1b1512, roughness: 0.6 });

function makeAvatarMesh(gender, outfitColor, hairStyle, role) {
  const isF = gender === 'female' || role === 'karavakkari';
  const mature = role === 'karavakkari';
  const color = outfitColor ? parseInt(String(outfitColor).replace('#', ''), 16) : (genderColors[gender] || genderColors.other);
  const style = hairStyle || 'short';
  const group = new THREE.Group(), body = new THREE.Group(); group.add(body);
  const skinHex = role === 'karavakkari' ? 0xc68a5d : role === 'chayakkaran' ? 0xae7a50 : 0xefc6a2;
  const M = (c, o) => new THREE.MeshStandardMaterial(Object.assign({ color: c, roughness: 0.75 }, o || {}));
  const skin = M(skinHex, { roughness: 0.5 });
  const hairHex = mature ? 0x120c0a : 0x1d1411, hairMat = M(hairHex, { roughness: 0.4 });
  const gold = M(0xf2b632, { metalness: 0.55, roughness: 0.35 });
  const add = (parent, geo, mat, x, y, z) => { const m = new THREE.Mesh(geo, mat); m.position.set(x, y, z); parent.add(m); return m; };
  const sph = (r, ws = 14, hs = 10) => G('s' + [r, ws, hs].join(), () => new THREE.SphereGeometry(r, ws, hs));
  const cyl = (r1, r2, h, s = 12) => G('c' + [r1, r2, h, s].join(), () => new THREE.CylinderGeometry(r1, r2, h, s));
  const sh = isF ? 0.18 : 0.215, hipX = isF ? 0.095 : 0.09, hipY = 0.9, shY = 1.45, headY = 1.67;
  body.scale.setScalar(isF ? (mature ? 0.935 : 0.94) : 1);

  // ---- torso: a lathe profile gives real chest / waist / hip shape ----
  const prof = isF
    ? (mature ? [[0.001,0.84],[0.158,0.86],[0.195,0.94],[0.165,1.02],[0.122,1.12],[0.14,1.22],[0.158,1.30],[0.145,1.38],[0.12,1.45],[0.05,1.49],[0.001,1.5]]
              : [[0.001,0.84],[0.15,0.86],[0.18,0.94],[0.15,1.02],[0.115,1.12],[0.13,1.22],[0.148,1.30],[0.14,1.38],[0.12,1.45],[0.05,1.49],[0.001,1.5]])
    : [[0.001,0.86],[0.16,0.88],[0.172,0.98],[0.158,1.12],[0.178,1.28],[0.19,1.40],[0.15,1.47],[0.06,1.51],[0.001,1.52]];
  const torsoMat = role === 'chayakkaran' ? M(0xf4f1e8) : role === 'karavakkari' ? M(0xa61e24, { roughness: 0.6 }) : M(color);
  const torso = add(body, G('torso' + isF + mature, () => new THREE.LatheGeometry(prof.map(p => new THREE.Vector2(p[0], p[1])), 22)), torsoMat, 0, 0, 0);
  torso.scale.z = 0.7;
  [-1, 1].forEach(s => add(body, sph(0.058, 10, 8), torsoMat, s * sh, shY - 0.035, 0));
  add(body, cyl(0.048, 0.054, 0.11, 12), skin, 0, 1.535, 0);                        // neck

  // ---- head: skull, jaw, ears, eyes (white + iris + highlight), brows, nose, lips ----
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
  add(head, sph(0.016, 8, 8), skin, 0, -0.012, 0.098).scale.set(0.9, 1.1, 1.2);       // nose
  const lipMat = M(role === 'karavakkari' ? 0xa8323f : isF ? 0xc9686a : 0xb87a68, { roughness: 0.4 });
  add(head, sph(0.0165, 8, 6), lipMat, 0, -0.049, 0.089).scale.set(1.4, 0.42, 0.55);
  add(head, sph(0.0165, 8, 6), lipMat, 0, -0.058, 0.088).scale.set(1.3, 0.45, 0.55);
  if (role === 'chayakkaran') [-1, 0, 1].forEach(s => add(head, sph(0.017, 8, 6), hairMat, s * 0.016, -0.038, 0.095).scale.set(1.7, 0.55, 0.8));   // moustache

  // ---- hair ----
  const cap = add(head, new THREE.SphereGeometry(0.113, 22, 16, 0, Math.PI * 2, 0, Math.PI * 0.42), hairMat, 0, 0.012, -0.004);
  cap.scale.set(0.94, 1.12, 1.0); cap.rotation.x = -0.32;
  add(head, sph(0.1, 14, 10), hairMat, 0, mature || style === 'pony' ? -0.02 : 0.0, -0.035).scale.set(0.9, mature ? 1.0 : 0.8, 0.9);
  if (mature) {                                                       // long braid with jasmine, pottu, jhumkas
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

  // ---- arms (pivot at the shoulder so they can swing) ----
  const arms = [], sleeveMat = torsoMat;
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * sh, shY - 0.03, 0); pv.rotation.z = s * 0.07; body.add(pv);
    add(pv, cyl(0.04, 0.033, 0.29, 10), skin, 0, -0.145, 0);
    add(pv, sph(0.034, 10, 8), skin, 0, -0.29, 0);
    const fa = new THREE.Group(); fa.position.set(0, -0.29, 0); fa.rotation.x = -0.22; pv.add(fa);
    add(fa, cyl(0.033, 0.024, 0.26, 10), skin, 0, -0.13, 0);
    add(fa, sph(0.03, 10, 8), skin, 0, -0.275, 0).scale.set(0.95, 1.3, 0.55);
    add(fa, cyl(0.008, 0.007, 0.05, 5), skin, -s * 0.026, -0.27, 0.014).rotation.z = s * 0.6;        // thumb
    add(pv, cyl(0.05, 0.046, 0.17, 10), sleeveMat, 0, -0.085, 0);
    if (mature) [0, 1].forEach(k => { add(fa, new THREE.TorusGeometry(0.03, 0.006, 6, 12), gold, 0, -0.232 - k * 0.018, 0).rotation.x = Math.PI / 2; });
    arms.push(pv);
  });

  // ---- legs (hip + knee + shoe) ----
  const legs = [], legMat = (isF || role === 'chayakkaran') ? skin : M(0x2e2e38);
  const footMat = role === 'chayakkaran' ? M(0x6b4a2a) : shoeMatShared;
  [-1, 1].forEach(s => {
    const pv = new THREE.Group(); pv.position.set(s * hipX, hipY, 0); body.add(pv);
    add(pv, cyl(0.078, 0.058, 0.44, 12), legMat, 0, -0.22, 0);
    add(pv, sph(0.058, 10, 8), legMat, 0, -0.44, 0);
    const sk = new THREE.Group(); sk.position.set(0, -0.44, 0); pv.add(sk);
    add(sk, cyl(0.056, 0.036, 0.42, 10), legMat, 0, -0.21, 0);
    add(sk, new THREE.BoxGeometry(0.075, 0.055, 0.21), footMat, 0, -0.435, 0.045);
    legs.push(pv);
  });

  // ---- lower garments & costume details ----
  if (role === 'chayakkaran') {                                      // mundu with kasavu border, thorthu on the shoulder
    add(body, cyl(0.19, 0.27, 0.62, 18), M(0xf6f3ea), 0, 0.64, 0);
    add(body, new THREE.TorusGeometry(0.27, 0.014, 6, 24), gold, 0, 0.345, 0).rotation.x = Math.PI / 2;
    add(body, new THREE.TorusGeometry(0.195, 0.02, 6, 20), M(0xe3ddcc), 0, 0.93, 0).rotation.x = Math.PI / 2;
    const cv = document.createElement('canvas'); cv.width = cv.height = 32; const g = cv.getContext('2d');
    g.fillStyle = '#ffffff'; g.fillRect(0, 0, 32, 32); g.fillStyle = '#c0392b'; for (let i = 0; i < 32; i += 8) { g.fillRect(i, 0, 4, 32); g.fillRect(0, i, 32, 4); }
    const tx = new THREE.CanvasTexture(cv); tx.wrapS = tx.wrapT = THREE.RepeatWrapping; tx.repeat.set(2, 6);
    const tw = add(body, new THREE.BoxGeometry(0.1, 0.5, 0.035), new THREE.MeshStandardMaterial({ map: tx, roughness: 0.9 }), -sh + 0.01, 1.27, 0.12); tw.rotation.z = 0.12;
  } else if (role === 'karavakkari') {                               // pavada, davani
    add(body, cyl(0.2, 0.42, 0.86, 22), M(0x1e7a4a, { roughness: 0.65 }), 0, 0.5, 0);
    add(body, new THREE.TorusGeometry(0.42, 0.022, 6, 28), gold, 0, 0.1, 0).rotation.x = Math.PI / 2;
    add(body, new THREE.TorusGeometry(0.33, 0.012, 6, 28), gold, 0, 0.28, 0).rotation.x = Math.PI / 2;
    const sash = new THREE.Group(); sash.position.set(0, 1.22, 0); sash.scale.set(1, 1, 0.7); sash.rotation.z = 0.8; body.add(sash);
    add(sash, new THREE.TorusGeometry(0.175, 0.02, 8, 24), M(0xf2c230), 0, 0, 0).rotation.x = Math.PI / 2;
  } else if (isF) {
    add(body, cyl(0.17, 0.27, 0.46, 18), M(color), 0, 0.7, 0);
  }
  group.userData = { legs, arms, body, head, emote: null };
  group.traverse(o => { if (o.isMesh) o.castShadow = true; });
  return group;
}

// Walk cycle, idle breathing and emotes. Works for me, other players and the stand-in characters.
function tickAvatar(g, now) {
  const u = g && g.userData; if (!u || !u.legs) return;
  const p = g.position, sp = u.lx === undefined ? 0 : Math.hypot(p.x - u.lx, p.z - u.lz);
  u.lx = p.x; u.lz = p.z; u.sp = (u.sp || 0) * 0.75 + sp * 0.25; u.ph = (u.ph || 0) + sp * 9;
  const amp = Math.min(0.75, u.sp * 6), la = Math.sin(u.ph) * amp, T = now / 1000;
  let a0x = -la * 0.85, a1x = la * 0.85, a0z = -0.07, a1z = 0.07, bob = Math.abs(Math.sin(u.ph)) * 0.02 * amp * 4 + Math.sin(T * 2) * 0.003;
  let rx = 0, rz = 0, l0 = la, l1 = -la;
  const e = u.emote && now < u.emote.until ? u.emote : null;
  if (e) {
    const t6 = T * 6;
    if (e.type === 'dance') { a0x = -2.4 + Math.sin(t6) * 0.5; a1x = -2.4 - Math.sin(t6) * 0.5; a0z = -0.5; a1z = 0.5; rz = Math.sin(t6 * 0.5) * 0.12; bob = Math.abs(Math.sin(t6)) * 0.07; l0 = Math.sin(t6) * 0.5; l1 = -Math.sin(t6) * 0.5; }
    else if (e.type === 'laugh') { a0x = a1x = -0.9; bob = Math.abs(Math.sin(T * 14)) * 0.04; rx = 0.12 + Math.sin(T * 10) * 0.06; }
    else if (e.type === 'aiyyo') { a0x = a1x = -2.6; a0z = -0.9; a1z = 0.9; rz = Math.sin(T * 30) * 0.05; bob = Math.abs(Math.sin(T * 18)) * 0.03; }
    else if (e.type === 'kiss') { a1x = -2.0 + Math.sin(T * 6) * 0.15; a1z = 0.4; rx = -0.05; }
  } else u.emote = null;
  u.legs[0].rotation.x = l0; u.legs[1].rotation.x = l1;
  u.arms[0].rotation.x = a0x; u.arms[1].rotation.x = a1x; u.arms[0].rotation.z = a0z; u.arms[1].rotation.z = a1z;
  u.body.position.y = bob; u.body.rotation.x = rx; u.body.rotation.z = rz;
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

let myGender = 'other';
let myOutfitColor = '#9b5de5';
let myHairStyle = 'short';
{
  const keeper = makeAvatarMesh('female', '#ffb703', 'pony');
  keeper.position.set(10, 0.25, 12.7);
  keeper.add(makeLabel('Shopkeeper'));
  scene.add(keeper);
  chayaNpc = makeAvatarMesh('male', '#ffffff', 'short', 'chayakkaran');            // stand-in until a real player takes the role
  chayaNpc.position.set(-10, 0.27, 10.8); chayaNpc.rotation.y = Math.PI;
  chayaNpc.add(makeLabel('Chayakkaran 🤖')); scene.add(chayaNpc);
  milkmaid = makeAvatarMesh('female', '#a61e24', 'short', 'karavakkari');
  milkmaid.position.set(-6, 0.27, -5.5); milkmaid.add(makeLabel('Karavakkari 🤖')); milkProps(milkmaid); scene.add(milkmaid);
}

// Milk pail + stool beside Karavakkari chechi
function milkProps(g) {
  const steel = kMat(0xc9ced6, { metalness: 0.6, roughness: 0.3 });
  const add = (geo, m, x, y, z) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); g.add(o); return o; };
  add(new THREE.CylinderGeometry(0.16, 0.13, 0.3, 12), steel, 0.55, 0.17, 0.35);
  add(new THREE.CylinderGeometry(0.15, 0.15, 0.01, 12), kMat(0xffffff), 0.55, 0.32, 0.35);
  add(new THREE.CylinderGeometry(0.2, 0.2, 0.3, 8), kMat(0x7a4c22), -0.6, 0.15, 0.3);
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

if (handoffName && !auth.loggedIn) document.getElementById('name-input').value = handoffName;

/* =========================================================
   4) MOVEMENT
   ========================================================= */
const keys = {};
const KEYALIAS = { arrowup: 'w', arrowdown: 's', arrowleft: 'a', arrowright: 'd' };   // arrow keys now work like WASD
addEventListener('keydown', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = true; if (KEYALIAS[k] && !/INPUT|TEXTAREA/.test((e.target.tagName || ''))) e.preventDefault(); });
addEventListener('keyup', e => { const k = e.key.toLowerCase(); keys[KEYALIAS[k] || k] = false; });

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
  if (/INPUT|TEXTAREA/.test((e.target.tagName || ''))) return;
  if (sitting || (nearBench && !drivingCarId)) toggleSit(); else triggerVehicleAction();
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
    // Camera looks along (sin yaw, cos yaw); screen-right is (-cos yaw, sin yaw). (The old formula had both axes mirrored.)
    const moveX = -dx*Math.cos(camYaw) - dz*Math.sin(camYaw);
    const moveZ =  dx*Math.sin(camYaw) - dz*Math.cos(camYaw);
    const sp = swimming ? speed * 0.55 : speed;   // swimming is slower
    myAvatar.position.x += moveX*sp;
    myAvatar.position.z += moveZ*sp;
    myAvatar.rotation.y = Math.atan2(moveX, moveZ);
    stats.distanceTraveled += speed;
    checkBalloonPickup();
    checkDeliveryProximity();
    checkTreasureProximity();
  }
  if (privScene) clampPrivate();
  if (performance.now() < kissFreeze) { renderer.render(scene, camera); return; }   // hold still for the kiss
  // Sink into the water while swimming, with a gentle bob
  const targetY = swimming ? -0.65 + Math.sin(performance.now()/350) * 0.06 : (onBridge(myAvatar.position.x, myAvatar.position.z) ? BRIDGE.y : 0);
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

// --- emotes, kisses, roles ---
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
socket.on('slapReceived', ({ from }) => showToast('🖐️ ' + from + ' slapped you!'));
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
// Someone changed their look in settings: rebuild their avatar where they are standing
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
  if (!auth.loggedIn) throw new Error('log in first');
  const getTok = () => fetch(`/livekit-token?identity=${encodeURIComponent(socket.id)}`, { headers: { Authorization: 'Bearer ' + auth.token } });
  let res = await getTok();
  if (res.status === 401) { await refreshAuth(); if (auth.loggedIn) res = await getTok(); }   // token may have just expired
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
// Mic and Sound are independent: you can listen to others with your mic off. The voice room is joined only when either is on.
function syncVoice() {
  voiceChain = voiceChain.then(async () => {
    const want = (micOn || soundOn) && auth.loggedIn && !privRoom;   // members-only; paused while you are in a private room
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
  if (!micOn && !auth.loggedIn) { requireLogin('mic', () => { micOn = true; paintVoiceBtns(); syncVoice(); }); return; }   // turning ON needs a login
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



/* =========================================================
   8) PRIVATE VOICE ROOMS — create (Room ID + passcode + share link) / join (ID or link + passcode)
   ========================================================= */
let PRIV_PLACES = [{ id: 'happycup', name: 'Happy Cup', emoji: '☕' }, { id: 'sarovaram', name: 'Sarovaram Park', emoji: '🌳' }, { id: 'beach', name: 'Beach', emoji: '🏖️' }, { id: 'hugamug', name: 'Hug a Mug', emoji: '🫶' }];
let PRIV_MAX = 5;
let privRoom = null, lkPriv = null, privConnecting = false, privMuted = false, privPlace = null, privSize = 2, privMods = new Set(), privMine = null, privFound = null;
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
  if (!auth.loggedIn) { requireLogin('private', () => openPrivate(view, roomId)); return; }   // popup first; re-opens after a successful login
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

// ---- create ----
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

// ---- join (search by ID, then passcode) ----
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
// opened from a shared link (?room=ID): once you are in the city, jump to the Join screen
if (linkRoom) { const t = setInterval(() => { if (inGame()) { clearInterval(t); openPrivate('join', linkRoom); } }, 600); }

// ---- inside a room ----
function paintPrivBar() {
  const bar = $p('private-bar');
  if (!privRoom) { bar.classList.remove('open'); return; }
  const names = privRoom.members.map(m => m.name).join(', ');
  $p('priv-title').textContent = '🔒 ' + privRoom.emoji + ' ' + privRoom.placeName + ' · ' + privRoom.id + ' · ' + (privRoom.members.length < 2 ? 'waiting for others…' : names);
  $p('priv-mod').style.display = privMods.size ? 'block' : 'none';
  $p('priv-info').style.display = privMine ? '' : 'none';
  $p('priv-mute').innerHTML = privMuted ? '<i class="fa-solid fa-microphone-slash"></i> Unmute' : '<i class="fa-solid fa-microphone"></i> Mute';
  bar.classList.add('open');
}
socket.on('privateRoomState', (room) => {
  const first = !privRoom;
  privRoom = room;
  if (first) {
    if (micOn) { micOn = false; paintVoiceBtns(); }       // the private room has its own mic; public mic goes off
    syncVoice();                                           // leaves the public voice room
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
    const isMod = (p) => String(p.identity || '').startsWith('mod-');
    room.on(LivekitClient.RoomEvent.ParticipantConnected, (p) => { if (isMod(p)) { privMods.add(p.identity); showToast('🛡️ A moderator joined this private room'); paintPrivBar(); } });
    room.on(LivekitClient.RoomEvent.ParticipantDisconnected, (p) => { if (isMod(p)) { privMods.delete(p.identity); paintPrivBar(); } });
    await room.connect(r.url, r.token);
    lkPriv = room;
    room.remoteParticipants.forEach(p => { if (isMod(p)) privMods.add(p.identity); });
    try { await room.startAudio(); } catch (e) {}
    try { await room.localParticipant.setMicrophoneEnabled(!privMuted); } catch (e) { showToast('🎤 Allow the microphone to talk in the private room'); }
    paintPrivBar();
  } catch (e) {
    showToast('Private voice unavailable: ' + (e.message || e));
  } finally { privConnecting = false; }
}
function cleanupPrivate() {
  const r = lkPriv; lkPriv = null; privRoom = null; privMine = null; privMods.clear(); privMuted = false;
  if (r) { try { r.disconnect(); } catch (e) {} }
  document.querySelectorAll('audio.lk-priv-audio').forEach(el => el.remove());
  privOverlay.classList.remove('open');
  paintPrivBar();
  syncVoice();                                             // public voice resumes if sound/mic were on
}
socket.on('privateRoomClosed', ({ reason }) => {
  const msg = { ended: 'The private room ended', expired: 'The private room closed after being empty too long', closed_by_admin: 'A moderator closed this private room', logout: 'You were logged out' }[reason] || 'The private room closed';
  cleanupPrivate(); showToast('🔒 ' + msg);
});
$p('priv-leave').onclick = () => { socket.emit('privateLeave'); cleanupPrivate(); showToast('You left the private room'); };
$p('priv-mute').onclick = async () => {
  privMuted = !privMuted;
  if (lkPriv) { try { await lkPriv.localParticipant.setMicrophoneEnabled(!privMuted); } catch (e) {} }
  paintPrivBar();
};


/* =========================================================
   8b) PRIVATE AREAS — each private room is its own themed place, far away from the city.
   The server only shows you the people in YOUR room, so nobody else can see or enter it.
   To add a new area: add it to PRIVATE_PLACES in server.js (it gets a simple park until you add a builder here).
   ========================================================= */
const PRIVATE_ORIGIN = { x: 5000, z: 5000 };   // must match server.js
let privScene = null;
function srnd(i) { const x = Math.sin(i * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); }   // same "random" for everyone
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
function buildGenericArea(g, info) {   // used for any area you add on the server before a custom builder exists
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
// The server moves us into / out of a room's area and then sends ONLY the people who are in that same area.
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
  clearTimeout(saveTimer); saveTimer = setTimeout(saveProfile, 700);   // remembered for next visit (members only)
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
// A guest who logs in while already in the city: keep the look they picked on the very first login, otherwise load their saved one
function afterLoginInGame() {
  if (!auth.profile) { saveProfile(); return; }
  myGender = myRole ? myGender : auth.profile.gender; myOutfitColor = auth.profile.outfit; myHairStyle = auth.profile.hair;
  lookChanged(); paintSettings();
}

document.getElementById('stats-btn').onclick = () => {
  paintSettings();
  document.getElementById('stat-name').textContent = myName + (auth.loggedIn ? '  🔒' : '');
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
  keralaTick(t);
  const nowp = performance.now();
  tickAvatar(myAvatar, nowp); if (chayaNpc) tickAvatar(chayaNpc, nowp); if (milkmaid) tickAvatar(milkmaid, nowp);
  sun.position.set(myAvatar.position.x + 18, 40, myAvatar.position.z + 12); sun.target.position.set(myAvatar.position.x, 0, myAvatar.position.z);
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
