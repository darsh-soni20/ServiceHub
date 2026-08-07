<?php
session_start();
// Allow visiting login page even when a session exists,
// so users can log in with additional roles simultaneously.
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ServiceHub | Welcome</title>
  <style>
    :root {
      --navy: #10293c;
      --ink: #173247;
      --muted: #718494;
      --line: #dce6ea;
      --mint: #dff7e8;
      --lime: #a6eb5c;
      --teal: #16a085;
      --sky: #e7f7f7;
      --coral: #ffb27a;
      --white: #fff;
      --shadow: 0 28px 60px rgba(22, 54, 69, .13);
    }

    * { box-sizing: border-box; }
    body {
      min-height: 100vh;
      margin: 0;
      overflow-x: hidden;
      color: var(--ink);
      background: #f8fbfb;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    button, input, select { font: inherit; }
    button { cursor: pointer; }

    .page {
      width: min(1180px, calc(100% - 40px));
      min-height: 100vh;
      margin: auto;
      padding: 28px 0 42px;
      position: relative;
    }

    .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
    .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: var(--navy); font-weight: 800; font-size: 1.35rem; letter-spacing: -.7px; }
    .brand-mark {
      width: 36px; height: 36px; display: grid; place-items: center; border-radius: 11px;
      background: var(--navy); color: var(--lime); box-shadow: 0 7px 16px rgba(16, 41, 60, .18);
    }
    .brand-mark svg { width: 22px; height: 22px; }
    .secure { display: flex; gap: 8px; align-items: center; color: var(--muted); font-size: .84rem; }
    .secure svg { width: 17px; }

    .shell {
      min-height: 680px;
      display: grid;
      grid-template-columns: 46% 54%;
      background: var(--white);
      border: 1px solid rgba(215, 227, 230, .9);
      border-radius: 30px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .story {
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: clamp(34px, 5vw, 64px);
      color: var(--navy);
      background: var(--mint);
      isolation: isolate;
    }
    .story::before, .story::after { content: ""; position: absolute; z-index: -1; border-radius: 50%; }
    .story::before { width: 380px; height: 380px; right: -148px; top: -160px; background: #c8f3d4; }
    .story::after { width: 290px; height: 290px; left: -166px; bottom: -128px; background: #b5e8d5; }
    .eyebrow { margin: 0 0 16px; color: #347160; font-size: .77rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .story h1 { max-width: 380px; margin: 0; font-size: clamp(2.25rem, 4vw, 3.8rem); line-height: 1.05; letter-spacing: -2.3px; }
    .story h1 em { font-style: normal; color: #157f6d; }
    .story-copy { max-width: 350px; margin: 22px 0 0; color: #466b68; font-size: 1.04rem; line-height: 1.65; }
    .trust-row { display: flex; align-items: center; gap: 11px; margin-top: 28px; color: #52716f; font-size: .87rem; }
    .avatars { display: flex; padding-left: 6px; }
    .avatar { width: 29px; height: 29px; margin-left: -6px; display: grid; place-items: center; border: 2px solid var(--mint); border-radius: 50%; background: #f4bb82; font-size: .79rem; }
    .avatar:nth-child(2) { background: #9ac7e0; }.avatar:nth-child(3) { background: #ec9b91; }.avatar:nth-child(4) { background: #b4ca87; }
    .service-card {
      width: min(100%, 336px); padding: 16px; position: relative; margin: 36px auto 0;
      display: grid; grid-template-columns: 54px 1fr; align-items: center; gap: 13px;
      background: rgba(255,255,255,.83); border: 1px solid rgba(255,255,255,.76); border-radius: 19px; box-shadow: 0 16px 28px rgba(30, 104, 82, .13);
      backdrop-filter: blur(8px); animation: float 5s ease-in-out infinite;
    }
    .service-icon { width: 54px; height: 54px; display: grid; place-items: center; border-radius: 14px; background: #fff2e6; color: #e47a2d; }
    .service-icon svg { width: 29px; height: 29px; }
    .service-card strong { display: block; margin-bottom: 3px; font-size: .91rem; }.service-card span { color: var(--muted); font-size: .78rem; }
    .status { position: absolute; top: 14px; right: 14px; width: 9px; height: 9px; border-radius: 99px; background: #38b685; box-shadow: 0 0 0 4px #dcf6e9; }
    .sparkle { position: absolute; color: #f4ad50; animation: twinkle 2.5s ease-in-out infinite; }.sparkle svg { width: 25px; height: 25px; }.s1 { right: 14%; top: 43%; }.s2 { left: 12%; bottom: 28%; animation-delay: -1.1s; }

    .auth { display: flex; justify-content: center; padding: 38px clamp(26px, 6vw, 82px); overflow: hidden; }
    .auth-content { width: min(100%, 438px); align-self: center; }
    .role-pills { display: flex; gap: 6px; margin-bottom: 30px; padding: 5px; overflow-x: auto; border-radius: 13px; background: #f1f5f5; scrollbar-width: none; }
    .role-pills::-webkit-scrollbar { display: none; }
    .role-pill { flex: 1; padding: 10px 8px; white-space: nowrap; border: none; border-radius: 9px; background: transparent; color: #71808b; font-size: .78rem; font-weight: 700; transition: .25s ease; }
    .role-pill.active { color: var(--navy); background: #fff; box-shadow: 0 3px 9px rgba(43, 72, 82, .09); }
    .view { display: none; animation: enter .42s cubic-bezier(.2,.7,.2,1); }.view.active { display: block; }
    .form-head { margin-bottom: 25px; }.form-head h2 { margin: 0 0 8px; color: var(--navy); font-size: 2rem; letter-spacing: -1.2px; }.form-head p { margin: 0; color: var(--muted); line-height: 1.5; font-size: .92rem; }
    form { display: grid; gap: 15px; }
    .field { position: relative; }.field label { display: block; margin-bottom: 7px; color: #506774; font-size: .79rem; font-weight: 750; }
    .field input, .field select { width: 100%; padding: 13px 14px; outline: none; border: 1px solid var(--line); border-radius: 11px; background: #fff; color: var(--ink); font-size: .91rem; transition: border .2s, box-shadow .2s; }
    .field input::placeholder { color: #aebac1; }.field input:focus, .field select:focus { border-color: #3cb89b; box-shadow: 0 0 0 4px rgba(60, 184, 155, .12); }
    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
    .password-wrap input { padding-right: 48px; }.toggle-password { position: absolute; right: 9px; bottom: 8px; width: 34px; height: 34px; border: none; border-radius: 8px; color: #6e818b; background: transparent; }.toggle-password:hover { background: #eef6f4; }
    .form-options { display: flex; justify-content: space-between; align-items: center; margin-top: -2px; color: var(--muted); font-size: .78rem; }.check { display: inline-flex; gap: 7px; align-items: center; }.check input { accent-color: #16836f; }.link { padding: 0; border: none; background: none; color: #147b69; font-weight: 750; }.link:hover { text-decoration: underline; }
    .submit { position: relative; overflow: hidden; margin-top: 4px; padding: 14px 18px; border: none; border-radius: 12px; color: var(--navy); background: var(--lime); font-size: .92rem; font-weight: 850; box-shadow: 0 9px 17px rgba(119, 190, 52, .2); transition: transform .2s, box-shadow .2s; }
    .submit:hover { transform: translateY(-2px); box-shadow: 0 12px 23px rgba(119, 190, 52, .28); }.submit::after { content:""; position:absolute; top:-80%; left:-30%; width:22%; height:260%; transform:rotate(20deg); background:rgba(255,255,255,.45); transition:left .55s; }.submit:hover::after { left:115%; }
    .switch-copy { margin: 23px 0 0; text-align: center; color: var(--muted); font-size: .84rem; }.provider-cta { margin-top: 23px; padding: 15px 16px; display: flex; align-items: center; gap: 12px; border: 1px solid #d3eddf; border-radius: 14px; background: #f5fcf7; }.provider-cta .mini-icon { flex: 0 0 auto; width: 37px; height: 37px; display: grid; place-items: center; border-radius: 10px; color: #fff; background: #2b987f; }.provider-cta .mini-icon svg { width: 20px; }.provider-cta div { flex: 1; }.provider-cta strong { display: block; color: #24574d; font-size: .8rem; }.provider-cta span { display: block; margin-top: 2px; color: #6c9389; font-size: .73rem; }.provider-cta .link { font-size: .76rem; white-space: nowrap; }
    .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; color: #9babb2; font-size: .74rem; }.divider::before, .divider::after { content:""; flex:1; height:1px; background:#e6edef; }.social { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }.social button { padding: 11px; border: 1px solid var(--line); border-radius: 11px; color: #49616e; background: #fff; font-size: .81rem; font-weight: 700; transition: .2s; }.social button:hover { border-color: #a9c7be; background: #f8fcfb; transform: translateY(-1px); }
    .provider-note { margin: 0 0 16px; padding: 11px 13px; border-radius: 10px; color: #426b5e; background: #edf9f2; font-size: .78rem; line-height: 1.45; }.provider-note b { color: #278469; }
    .toast { position: fixed; z-index: 10; left: 50%; bottom: 28px; display: flex; align-items: center; gap: 9px; padding: 13px 18px; border-radius: 12px; color: #fff; background: var(--navy); box-shadow: 0 15px 30px rgba(16,41,60,.23); font-size: .86rem; transform: translate(-50%, 100px); opacity: 0; transition: .35s cubic-bezier(.2,.9,.2,1); }.toast.show { opacity: 1; transform: translate(-50%, 0); }.toast svg { width: 17px; color: var(--lime); }

    @keyframes enter { from { opacity:0; transform:translateX(22px); } to { opacity:1; transform:none; } }
    @keyframes float { 0%,100% { transform:translateY(0) rotate(-1deg); } 50% { transform:translateY(-9px) rotate(1deg); } }
    @keyframes twinkle { 0%,100% { transform:scale(.75) rotate(0); opacity:.45; } 50% { transform:scale(1.15) rotate(25deg); opacity:1; } }
    @media (max-width: 830px) { .shell { grid-template-columns: 1fr; max-width: 580px; margin:auto; }.story { min-height: 390px; padding: 38px; }.service-card { margin-top: 25px; }.auth { padding: 38px 42px 46px; }.story h1 { max-width: 440px; }.topbar { max-width: 580px; margin: 0 auto 18px; } }
    @media (max-width: 480px) { .page { width:min(100% - 24px, 580px); padding-top: 14px; }.secure { display:none; }.shell { border-radius: 22px; }.story { min-height:350px; padding:31px 26px; }.story h1 { font-size: 2.35rem; letter-spacing:-1.7px; }.story-copy { font-size:.91rem; }.auth { padding:31px 22px 38px; }.form-head h2 { font-size:1.75rem; }.field-row { grid-template-columns:1fr; }.service-card { margin-top:20px; }.trust-row { margin-top:18px; }.provider-cta { align-items:flex-start; }.provider-cta .link { padding-top:4px; }.social { grid-template-columns:1fr; } }

    /* ServiceHub brand theme — simple black, white and gold */
    :root { --navy: #101010; --ink: #202020; --muted: #747474; --line: #dedbd5; --mint: #171717; --lime: #d2a15e; --teal: #b47c37; --sky: #f7f4ee; --coral: #d7a461; --white: #fffdf9; --shadow: 0 24px 55px rgba(0,0,0,.18); }
    body { background: #0d0d0e; }
    .page { width: min(1180px, calc(100% - 40px)); }
    .topbar { margin-bottom: 17px; min-height: 61px; }
    .brand { height: 62px; }
    .brand-logo { display: block; width: 210px; max-height: 62px; object-fit: contain; object-position: left center; }
    .secure { color: #d5b486; }
    .secure svg { color: #d2a15e; }
    .shell { min-height: 640px; border: 1px solid #3d3428; border-radius: 22px; box-shadow: 0 28px 70px rgba(0,0,0,.34); }
    .story { padding: clamp(38px, 5vw, 62px); color: #fff; background: linear-gradient(145deg, #121212 0%, #20201f 100%); }
    .story::before { width: 360px; height: 360px; right: -190px; top: -120px; border: 1px solid rgba(212,163,97,.35); background: transparent; }
    .story::after { width: 245px; height: 245px; left: -138px; bottom: -128px; border: 1px solid rgba(212,163,97,.22); background: transparent; }
    .eyebrow { color: #d5a35f; }
    .story h1 { max-width: 370px; font-size: clamp(2.15rem, 3.7vw, 3.35rem); letter-spacing: -1.8px; }
    .story h1 em { color: #d6a363; }
    .story-copy { color: #c8c4bd; }
    .trust-row { color: #bdb7ae; }
    .avatar { border-color: #222; filter: saturate(.75); }
    .service-card { margin-left: 0; border: 1px solid rgba(214,163,99,.45); background: rgba(255,255,255,.05); box-shadow: none; backdrop-filter: none; animation: none; }
    .service-icon { color: #d7a461; background: rgba(214,163,99,.13); }
    .service-card strong { color: #fff; }.service-card span { color: #c9c1b5; }.status { background: #d6a363; box-shadow: 0 0 0 4px rgba(214,163,99,.15); }
    .sparkle { color: #d5a35f; animation: none; opacity: .9; }.s1 { right: 15%; top: 39%; }.s2 { left: 16%; bottom: 24%; }
    .auth { background: #fffdf9; }
    .role-pills { padding: 4px; background: #f2eee7; }
    .role-pill { color: #756f68; }.role-pill.active { color: #181716; background: #fffdf9; box-shadow: 0 2px 7px rgba(46,36,25,.1); }
    .form-head h2 { color: #181716; }.form-head p { color: #77716b; }
    .field label { color: #494541; }.field input, .field select { border-color: #ded8d0; background: #fff; }.field input:focus, .field select:focus { border-color: #bc8848; box-shadow: 0 0 0 4px rgba(188,136,72,.13); }
    .toggle-password:hover { background: #f5ede2; }.link { color: #a86f2a; }.submit { color: #171412; background: linear-gradient(100deg, #c88c43, #e0b06b); box-shadow: 0 8px 15px rgba(171,111,36,.18); }.submit:hover { transform: none; box-shadow: 0 9px 18px rgba(171,111,36,.23); }.submit::after { display: none; }
    .divider { color: #9a938a; }.divider::before, .divider::after { background: #e5ded5; }.social button { color: #4f4943; border-color: #ded8d0; }.social button:hover { border-color: #c99a5e; background: #fffaf3; transform: none; }
    .provider-cta { border-color: #e5cfad; background: #fcf6ed; }.provider-cta .mini-icon { background: #b67b34; }.provider-cta strong { color: #403327; }.provider-cta span { color: #867563; }.provider-note { color: #695338; background: #fcf4e6; }.provider-note b { color: #9d6729; }
    .photo-upload { display:flex; align-items:center; gap:12px; min-height:76px; padding:10px; border:1px dashed #d7bd96; border-radius:12px; background:#fffaf3; cursor:pointer; }
    .photo-preview { width:52px; height:52px; flex:0 0 52px; display:grid; place-items:center; overflow:hidden; border-radius:50%; color:#a66d2a; background:#f2e4d1; }.photo-preview img { width:100%; height:100%; object-fit:cover; }
    .photo-upload-copy { flex:1; min-width:0; }.photo-upload-copy strong { display:block; color:#493522; font-size:.8rem; }.photo-upload-copy span { display:block; margin-top:3px; color:#836f58; font-size:.72rem; line-height:1.35; }
    .photo-upload input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }.upload-button { padding:8px 10px; border:1px solid #d6b98f; border-radius:8px; color:#8f5f28; background:#fff; font-size:.74rem; font-weight:750; white-space:nowrap; }.photo-upload:hover .upload-button { border-color:#b68043; background:#fff8ef; }
    .toast { background: #161514; }.toast svg { color: #e0b06b; }
    .view { animation: softFade .22s ease-out; }
    @keyframes softFade { from { opacity: .35; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .top-actions { display:flex; align-items:center; gap:19px; }
    @media (max-width: 830px) { .story { padding: 38px; }.brand-logo { width: 185px; } }
    @media (max-width: 480px) { .page { width:min(100% - 24px, 580px); }.brand-logo { width: 160px; }.topbar { min-height: 48px; }.shell { border-radius: 17px; }.story { padding: 32px 26px; }.top-actions { gap:10px; } }
  </style>
</head>
<body>
  <main class="page">
    <header class="topbar">
      <a href="#" class="brand" aria-label="ServiceHub home"><img class="brand-logo" src="admin_panel/servicehub-logo.png" alt="ServiceHub — Connecting, Serving, Caring" /></a>
      <div class="top-actions"><span class="secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Secure sign in</span></div>
    </header>

    <section class="shell">
      <aside class="story">
        <div>
          <p class="eyebrow">Connecting • Serving • Caring</p>
          <h1>Trusted help for <em>every home.</em></h1>
          <p class="story-copy">Book reliable local professionals for the services your home needs, all in one place.</p>
          <div class="trust-row"><div class="avatars"><span class="avatar">👩🏽</span><span class="avatar">👨🏾</span><span class="avatar">👩🏻</span><span class="avatar">👨🏻</span></div><span>Trusted by homes across your city</span></div>
        </div>
        <div class="service-card"><span class="status"></span><span class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14.7 6.3 3-3 2 2-3 3"/><path d="m16.2 7.8-5.1 5.1M6.3 8.2 3.4 5.3l2-2 2.9 2.9M8.5 10.4 4.2 14.7l5.1 5.1 4.3-4.3"/><path d="m11.3 13.2 3.5 3.5"/></svg></span><div><strong>Professional service, on time</strong><span>Book, track and manage your services</span></div></div>
        <span class="sparkle s1"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.5c.7 6.5 3.9 9.8 10.5 10.5-6.6.7-9.8 4-10.5 10.5C11.3 16 8.1 12.7 1.5 12 8.1 11.3 11.3 8 12 1.5Z"/></svg></span><span class="sparkle s2"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.5c.7 6.5 3.9 9.8 10.5 10.5-6.6.7-9.8 4-10.5 10.5C11.3 16 8.1 12.7 1.5 12 8.1 11.3 11.3 8 12 1.5Z"/></svg></span>
      </aside>

      <section class="auth" aria-live="polite">
        <div class="auth-content">
          <nav class="role-pills" aria-label="Choose account type">
            <button class="role-pill active" data-role="user">Customer</button>
            <button class="role-pill" data-role="provider">Service provider</button>
            <button class="role-pill" data-role="admin">Admin</button>
          </nav>

          <div id="login" class="view active">
            <div class="form-head"><h2 id="loginTitle">Welcome back</h2><p id="loginSubtitle">Sign in to manage your bookings and services.</p></div>
            <form data-action="login">
              <div class="field"><label for="loginEmail">Email address</label><input id="loginEmail" name="email" type="email" placeholder="you@example.com" required /></div>
              <div class="field password-wrap"><label for="loginPassword">Password</label><input id="loginPassword" name="password" type="password" placeholder="Enter your password" required /><button class="toggle-password" type="button" aria-label="Show password">◉</button></div>
              <div class="form-options"><label class="check"><input type="checkbox" /> Keep me signed in</label><button type="button" class="link" data-message="Password recovery will open here.">Forgot password?</button></div>
              <button class="submit" type="submit"><span id="loginButton">Sign in to ServiceHub</span></button>
            </form>
            <div class="divider">or continue with</div><div class="social"><button type="button" data-message="Google login will connect here.">G&nbsp;&nbsp; Google</button><button type="button" data-message="Phone login will connect here.">◈&nbsp;&nbsp; Phone number</button></div>
            <p class="switch-copy" id="loginSwitch">New to ServiceHub? <button class="link" data-view="register" type="button">Create an account</button></p>
            <div class="provider-cta" id="providerCta"><span class="mini-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3 17.5 3.5l3 3-2.8 2.8"/><path d="m16.1 7.9-8.3 8.3-3.4.6.6-3.4 8.3-8.3"/><path d="m12.5 13.5 3 3"/></svg></span><div><strong>Do you provide home services?</strong><span>Grow your business with ServiceHub.</span></div><button class="link" type="button" data-role-target="provider" data-view="register">Join as partner</button></div>
          </div>

          <div id="register" class="view">
            <div class="form-head"><h2 id="registerTitle">Create your account</h2><p id="registerSubtitle">A few details and you are ready to get things done.</p></div>
            <p id="providerNote" class="provider-note" hidden><b>Partner application:</b> We will review your profile and help you get set up within 1–2 business days.</p>
            <form data-action="register" id="registerForm">
              <div class="field-row"><div class="field"><label for="firstName" id="firstNameLabel">First name</label><input id="firstName" name="firstName" type="text" placeholder="Aarav" required /></div><div class="field"><label for="lastName">Last name</label><input id="lastName" name="lastName" type="text" placeholder="Sharma" required /></div></div>
              <div class="field"><label for="registerEmail">Email address</label><input id="registerEmail" name="email" type="email" placeholder="you@example.com" required /></div>
              <div class="field provider-only" hidden><label for="serviceType">Primary service</label><select id="serviceType" name="serviceType"><option value="">Select your specialty</option><option>Home cleaning</option><option>Appliance repair</option><option>Beauty & wellness</option><option>Plumbing</option><option>Electrical work</option><option>Other home service</option></select></div>
              <div class="field"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" placeholder="+91 98765 43210" required /></div>
              <div class="field"><label for="address">Address</label><input id="address" name="address" type="text" placeholder="Flat 101, Sunshine Apartments" /></div>
              <div class="field-row"><div class="field"><label for="city">City</label><input id="city" name="city" type="text" placeholder="Mumbai" /></div><div class="field"><label for="pincode">Pincode</label><input id="pincode" name="pincode" type="text" placeholder="400001" maxlength="10" /></div></div>
              <div class="field provider-only" hidden><label for="experience">Years of experience</label><input id="experience" name="experience" type="number" min="0" max="50" placeholder="e.g. 5" value="1" /></div>
              <div class="field provider-only" hidden><label for="providerPhoto">Your profile photo</label><label class="photo-upload" for="providerPhoto"><span class="photo-preview" id="photoPreview"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.3"/><path d="M5.8 20a6.2 6.2 0 0 1 12.4 0"/></svg></span><span class="photo-upload-copy"><strong id="photoName">Add a clear face photo</strong><span>JPG, PNG or WebP, up to 5 MB. This helps customers recognise you.</span></span><span class="upload-button">Choose photo</span><input id="providerPhoto" name="providerPhoto" type="file" accept="image/png, image/jpeg, image/webp" /></label></div>
              <div class="field password-wrap"><label for="registerPassword">Create password</label><input id="registerPassword" name="password" type="password" minlength="6" placeholder="At least 6 characters" required /><button class="toggle-password" type="button" aria-label="Show password">◉</button></div>
              <label class="check"><input type="checkbox" required /> I agree to the <button class="link" type="button" data-message="Terms will open here.">Terms</button> and <button class="link" type="button" data-message="Privacy Policy will open here.">Privacy Policy</button>.</label>
              <button class="submit" type="submit"><span id="registerButton">Create customer account</span></button>
            </form>
            <p class="switch-copy" id="registerSwitch">Already have an account? <button class="link" data-view="login" type="button">Sign in</button></p>
          </div>
        </div>
      </section>
    </section>
  </main>
  <div class="toast" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg><span id="toastText"></span></div>

  <script>
    const roleInfo = {
      user: { loginTitle: 'Welcome back', loginSubtitle: 'Sign in to manage your bookings and services.', loginButton: 'Sign in to ServiceHub', loginSwitch: 'New to ServiceHub?', registerTitle: 'Create your account', registerSubtitle: 'A few details and you are ready to get things done.', registerButton: 'Create customer account', firstName: 'First name' },
      provider: { loginTitle: 'Partner sign in', loginSubtitle: 'Manage jobs, earnings, and your ServiceHub profile.', loginButton: 'Sign in as a partner', loginSwitch: 'New service professional?', registerTitle: 'Join as a service partner', registerSubtitle: 'Create your professional profile and start receiving nearby jobs.', registerButton: 'Submit partner application', firstName: 'Your name' },
      admin: { loginTitle: 'Admin sign in', loginSubtitle: 'Manage your platform with confidence.', loginButton: 'Sign in to admin dashboard', loginSwitch: '', registerTitle: '', registerSubtitle: '', registerButton: '', firstName: '' }
    };
    let currentRole = 'user';
    let toastTimer;
    const $ = (selector) => document.querySelector(selector);
    const showToast = (message) => {
      $('#toastText').textContent = message;
      $('.toast').classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => $('.toast').classList.remove('show'), 3200);
    };
    const showView = (name) => {
      document.querySelectorAll('.view').forEach(view => view.classList.toggle('active', view.id === name));
      if (name === 'register') $('#firstName').focus(); else $('#loginEmail').focus();
    };
    const switchRole = (role, view = null) => {
      currentRole = role;
      const info = roleInfo[role];
      document.querySelectorAll('.role-pill').forEach(button => button.classList.toggle('active', button.dataset.role === role));
      ['loginTitle','loginSubtitle','loginButton','registerTitle','registerSubtitle','registerButton','firstNameLabel'].forEach(id => { const el = $('#' + id); if (el && info[id.replace('Label','')]) el.textContent = info[id.replace('Label','')] || info[id]; });
      
      if (role === 'admin') {
          $('#loginSwitch').style.display = 'none';
      } else {
          $('#loginSwitch').style.display = 'block';
          $('#loginSwitch').firstChild.textContent = info.loginSwitch + ' ';
      }

      document.querySelectorAll('.provider-only').forEach(el => el.hidden = role !== 'provider');
      $('#providerNote').hidden = role !== 'provider';
      $('#providerCta').hidden = role !== 'user';
      const providerSelect = $('#serviceType'); const providerPhone = $('#phone'); const providerPhoto = $('#providerPhoto');
      if(providerSelect) providerSelect.required = role === 'provider'; 
      if(providerPhone) providerPhone.required = role === 'provider'; 
      if(providerPhoto) providerPhoto.required = role === 'provider';
      
      if (view) showView(view);
      if (role === 'admin') showView('login');
    };

    document.querySelectorAll('.role-pill').forEach(button => button.addEventListener('click', () => switchRole(button.dataset.role)));
    document.querySelectorAll('[data-view]').forEach(button => button.addEventListener('click', () => {
      if (button.dataset.roleTarget) switchRole(button.dataset.roleTarget, button.dataset.view);
      else showView(button.dataset.view);
    }));
    document.querySelectorAll('.toggle-password').forEach(button => button.addEventListener('click', () => {
      const input = button.parentElement.querySelector('input');
      const isPassword = input.type === 'password'; input.type = isPassword ? 'text' : 'password'; button.textContent = isPassword ? '◌' : '◉'; button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    }));
    document.querySelectorAll('[data-message]').forEach(button => button.addEventListener('click', () => showToast(button.dataset.message)));
    
    const providerPhotoInput = $('#providerPhoto');
    if (providerPhotoInput) {
        providerPhotoInput.addEventListener('change', event => {
          const file = event.target.files[0];
          if (!file) return;
          if (file.size > 5 * 1024 * 1024) { event.target.value = ''; showToast('Please choose an image under 5 MB.'); return; }
          $('#photoName').textContent = file.name;
          const reader = new FileReader();
          reader.onload = () => { $('#photoPreview').innerHTML = `<img src="${reader.result}" alt="Selected provider profile photo">`; };
          reader.readAsDataURL(file);
        });
    }

    document.querySelector('form[data-action="login"]').addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.target;
      if (!form.reportValidity()) return;
      
      const submitBtn = form.querySelector('.submit');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span>Signing in...</span>';
      
      try {
        const response = await fetch('api/auth.php?action=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            email: form.querySelector('[name="email"]').value,
            password: form.querySelector('[name="password"]').value,
            role: currentRole
          })
        });
        const result = await response.json();
        if (result.status === 'success') {
          if (result.token) {
            sessionStorage.setItem('auth_token', result.token);
            sessionStorage.setItem('auth_token_' + currentRole, result.token);
          }
          showToast('Login successful! Redirecting...');
          const targetUrl = result.token ? result.redirect + '?token=' + encodeURIComponent(result.token) : result.redirect;
          setTimeout(() => window.location.href = targetUrl, 800);
        } else {
          showToast(result.message || 'Invalid credentials', true);
          submitBtn.disabled = false;
          submitBtn.innerHTML = `<span>${roleInfo[currentRole].loginButton}</span>`;
        }
      } catch (err) {
        showToast('Connection error. Please try again.', true);
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>${roleInfo[currentRole].loginButton}</span>`;
      }
    });

    document.querySelector('form[data-action="register"]').addEventListener('submit', async (event) => {
      event.preventDefault();
      const form = event.target;
      if (!form.reportValidity()) return;
      
      const submitBtn = form.querySelector('.submit');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span>Registering...</span>';
      
      const payload = {
        name: form.querySelector('[name="firstName"]').value + ' ' + form.querySelector('[name="lastName"]').value,
        email: form.querySelector('[name="email"]').value,
        password: form.querySelector('[name="password"]').value,
        phone: form.querySelector('[name="phone"]').value || '',
        address: form.querySelector('[name="address"]').value || '',
        city: form.querySelector('[name="city"]').value || '',
        pincode: form.querySelector('[name="pincode"]').value || '',
        role: currentRole
      };

      if (currentRole === 'provider') {
        payload.category = form.querySelector('[name="serviceType"]').value;
        payload.experience = parseInt(form.querySelector('[name="experience"]').value) || 1;
      }

      try {
        const response = await fetch('api/auth.php?action=register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.status === 'success') {
          showToast('Registration successful! Please login.');
          form.reset();
          setTimeout(() => showView('login'), 950);
        } else {
          showToast(result.message || 'Registration failed', true);
        }
      } catch (err) {
        showToast('Connection error. Please try again.', true);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>${roleInfo[currentRole].registerButton}</span>`;
      }
    });
  </script>
</body>
</html>



