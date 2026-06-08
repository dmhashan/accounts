<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Gym Management Software Sri Lanka | beForward.lk</title>
    <meta name="description" content="Move your gym forward with beForward.lk, Sri Lanka's modern gym management software for members, payments, attendance, workouts, bookings, reports and branded websites.">
    <meta name="keywords" content="gym management software Sri Lanka, fitness center software, gym membership system, gym payment tracking, member portal, gym website">
    <meta name="author" content="beForward.lk">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="theme-color" content="#050505">
    <link rel="canonical" href="<?= url('/') ?>">
    <link rel="icon" href="<?= asset('images/icon.svg') ?>" type="image/svg+xml">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="beForward.lk">
    <meta property="og:title" content="Gym Management Software Sri Lanka | beForward.lk">
    <meta property="og:description" content="One beautifully connected platform to operate, engage and grow your gym.">
    <meta property="og:url" content="<?= url('/') ?>">
    <meta property="og:image" content="<?= asset('images/backoffice_application.png') ?>">
    <meta property="og:locale" content="en_LK">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Move Your Gym Forward | beForward.lk">
    <meta name="twitter:description" content="A smarter operating system for modern fitness businesses.">
    <meta name="twitter:image" content="<?= asset('images/backoffice_application.png') ?>">

    <?= app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css', 'resources/js/app.js']) ?>

    <style>
        :root {
            --black: #050505;
            --black-2: #0a0a0b;
            --panel: #111113;
            --white: #f5f5f7;
            --muted: #a1a1a6;
            --line: rgba(255,255,255,.12);
            --orange: #ff4d18;
            --gold: #ff9d24;
            --cyan: #18d5e7;
            --blue: #2997ff;
            --ease: cubic-bezier(.16,1,.3,1);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--black);
            color: var(--white);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Helvetica Neue", Arial, sans-serif;
            line-height: 1.45;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        body.menu-open { overflow: hidden; }
        a { color: inherit; text-decoration: none; }
        img { display:block; max-width:100%; }
        button { font:inherit; }
        ::selection { background:var(--cyan); color:#001317; }

        .cursor-glow {
            position: fixed;
            z-index: 0;
            left: 0; top: 0;
            width: 420px; height: 420px;
            margin: -210px 0 0 -210px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(24,213,231,.09), rgba(255,77,24,.035) 38%, transparent 70%);
            pointer-events:none;
            opacity:0;
            transition:opacity .4s;
        }
        body:hover .cursor-glow { opacity:1; }
        .noise {
            position:fixed; inset:0; z-index:20; pointer-events:none; opacity:.035;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.8'/%3E%3C/svg%3E");
        }
        .container { width:min(1200px, calc(100% - 40px)); margin-inline:auto; position:relative; z-index:1; }
        .wide { width:min(1440px, calc(100% - 32px)); margin-inline:auto; position:relative; z-index:1; }
        .gradient-text {
            background:linear-gradient(105deg, var(--orange), var(--gold) 42%, var(--cyan) 82%);
            -webkit-background-clip:text; background-clip:text; color:transparent;
        }
        .eyebrow {
            color:var(--cyan); font-weight:700; font-size:.78rem; letter-spacing:.13em;
            text-transform:uppercase; display:flex; align-items:center; gap:9px;
        }
        .eyebrow::before { content:""; width:6px; height:6px; border-radius:50%; background:var(--cyan); box-shadow:0 0 18px var(--cyan); }
        .display {
            margin:0; font-size:clamp(3.4rem, 9vw, 8rem); line-height:.9; letter-spacing:-.075em; font-weight:700;
        }
        .section-title {
            margin:0; font-size:clamp(2.5rem, 6vw, 5.8rem); line-height:.96; letter-spacing:-.065em; font-weight:700;
        }
        .lead { color:var(--muted); font-size:clamp(1.05rem, 1.5vw, 1.35rem); line-height:1.55; }
        .btn {
            min-height:50px; display:inline-flex; align-items:center; justify-content:center; gap:9px;
            padding:0 22px; border:1px solid transparent; border-radius:999px; cursor:pointer;
            font-size:.9rem; font-weight:700; transition:transform .5s var(--ease), background .3s, border-color .3s;
            position:relative; overflow:hidden;
        }
        .btn span, .btn svg { position:relative; z-index:1; }
        .btn::before { content:""; position:absolute; inset:0; background:linear-gradient(110deg,var(--orange),var(--gold),var(--cyan)); transition:transform .5s var(--ease); }
        .btn-primary { color:#fff; box-shadow:0 12px 45px rgba(255,77,24,.2); }
        .btn-primary:hover::before { transform:scale(1.12); }
        .btn-secondary { border-color:var(--line); background:rgba(255,255,255,.05); backdrop-filter:blur(12px); }
        .btn-secondary::before { display:none; }
        .btn-secondary:hover { background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.25); }

        .nav {
            position:fixed; z-index:15; top:0; left:0; width:100%;
            background:rgba(5,5,5,.66); border-bottom:1px solid transparent;
            backdrop-filter:saturate(160%) blur(22px); transition:border .3s, background .3s;
        }
        .nav.scrolled { border-color:var(--line); background:rgba(5,5,5,.84); }
        .nav-inner { height:64px; display:flex; align-items:center; justify-content:space-between; }
        .brand { display:flex; align-items:center; gap:9px; }
        .brand img { width:50px; height:auto; transition:filter .35s,transform .35s var(--ease); }
        .brand:hover img { filter:drop-shadow(0 0 18px rgba(24,213,231,.22)); transform:translateY(-1px) rotate(-3deg); }
        .brand-word { color:#f5f5f7; font-size:1.05rem; font-weight:750; letter-spacing:-.055em; }
        .brand-word b { color:var(--cyan); font-weight:750; }
        .desktop-links { display:flex; align-items:center; gap:28px; color:#d2d2d7; font-size:.78rem; font-weight:600; }
        .desktop-links a { transition:color .2s; }
        .desktop-links a:hover { color:#fff; }
        .nav-cta { color:var(--black); background:var(--white); padding:8px 15px; border-radius:999px; font-weight:750; }
        .menu-button { display:none; width:40px; height:40px; border:0; border-radius:50%; background:rgba(255,255,255,.08); color:#fff; cursor:pointer; place-items:center; }
        .mobile-menu { display:none; }

        .hero {
            position:relative; min-height:112vh; padding:150px 0 0; overflow:hidden;
            background:radial-gradient(circle at 50% 30%, #19191c 0%, var(--black) 52%);
        }
        .hero-copy { text-align:center; max-width:1050px; margin:auto; position:relative; z-index:3; }
        .hero-brand-seal {
            width:132px; height:auto; margin:0 auto 24px;
            box-shadow:0 20px 55px rgba(0,0,0,.45),0 0 45px rgba(24,213,231,.12);
            animation:seal-breathe 5s ease-in-out infinite;
        }
        @keyframes seal-breathe { 50% { transform:translateY(-5px) scale(1.025); filter:drop-shadow(0 0 14px rgba(24,213,231,.2)); } }
        .hero-kicker { color:var(--orange); font-size:clamp(1rem,2vw,1.45rem); font-weight:700; margin:0 0 20px; }
        .hero .display { max-width:1000px; margin:auto; }
        .hero .lead { max-width:720px; margin:28px auto 0; }
        .hero-actions { display:flex; justify-content:center; flex-wrap:wrap; gap:12px; margin-top:32px; }
        .hero-stage { height:min(57vw, 680px); min-height:420px; position:relative; margin-top:40px; perspective:1600px; }
        .hero-orbit {
            position:absolute; left:50%; top:52%; width:min(80vw,950px); aspect-ratio:1;
            transform:translate(-50%,-50%); border-radius:50%; border:1px solid rgba(255,255,255,.07);
            box-shadow:0 0 120px rgba(24,213,231,.08), inset 0 0 120px rgba(255,77,24,.05);
        }
        .hero-orbit::before,.hero-orbit::after { content:""; position:absolute; inset:12%; border:1px solid rgba(255,255,255,.05); border-radius:50%; }
        .hero-orbit::after { inset:28%; }
        .hero-screen {
            position:absolute; width:min(78vw,1080px); left:50%; top:7%; transform:translateX(-50%) rotateX(7deg);
            border-radius:24px; overflow:hidden; border:1px solid rgba(255,255,255,.18);
            box-shadow:0 70px 130px rgba(0,0,0,.75), 0 0 90px rgba(24,213,231,.1);
            transform-style:preserve-3d; transition:transform .2s linear;
        }
        .screen-top { height:34px; background:#17171a; display:flex; gap:6px; align-items:center; padding:0 13px; }
        .screen-top i { width:7px;height:7px;border-radius:50%;background:#3a3a3f; }
        .hero-screen img { width:100%; }
        .float-pill {
            position:absolute; z-index:3; padding:13px 17px; border:1px solid rgba(255,255,255,.14);
            border-radius:999px; background:rgba(15,15,17,.65); backdrop-filter:blur(16px);
            box-shadow:0 20px 50px rgba(0,0,0,.4); color:#e8e8ed; font-size:.78rem; font-weight:700;
            animation:float 5s ease-in-out infinite;
        }
        .float-pill.one { left:7%; top:25%; }
        .float-pill.two { right:6%; top:48%; animation-delay:-1.7s; }
        .float-pill.three { left:15%; bottom:12%; animation-delay:-3.2s; }
        @keyframes float { 50% { transform:translateY(-13px); } }

        .intro { padding:150px 0; background:var(--white); color:#1d1d1f; }
        .intro-grid { display:grid; grid-template-columns:.72fr 1.28fr; gap:8vw; align-items:start; }
        .intro .eyebrow { color:#c94118; position:sticky; top:110px; }
        .intro .eyebrow::before { background:#c94118; box-shadow:none; }
        .intro h2 { margin:0; font-size:clamp(2.6rem,5.4vw,5.6rem); line-height:1.02; letter-spacing:-.06em; }
        .intro h2 span { color:#86868b; }

        .story { position:relative; background:#09090a; }
        .story-head { padding:140px 0 80px; text-align:center; }
        .story-head .eyebrow { justify-content:center; }
        .story-head .section-title { max-width:900px; margin:20px auto 0; }
        .story-layout { display:grid; grid-template-columns:.72fr 1.28fr; gap:7vw; align-items:start; }
        .story-copy { padding-bottom:25vh; }
        .story-step { min-height:76vh; display:flex; flex-direction:column; justify-content:center; opacity:.25; transition:opacity .6s var(--ease); }
        .story-step.active { opacity:1; }
        .step-num { color:var(--cyan); font-size:.75rem; letter-spacing:.15em; font-weight:800; }
        .story-step h3 { font-size:clamp(2.3rem,4.2vw,4.4rem); line-height:.98; letter-spacing:-.06em; margin:16px 0; }
        .story-step p { color:var(--muted); font-size:1.08rem; max-width:480px; }
        .story-tags { display:flex; flex-wrap:wrap; gap:7px; margin-top:14px; }
        .story-tags span { padding:7px 11px; border-radius:999px; border:1px solid var(--line); color:#bdbdc2; font-size:.72rem; }
        .story-visual { position:sticky; top:12vh; height:76vh; display:grid; place-items:center; }
        .visual-glow { position:absolute; width:70%; aspect-ratio:1; border-radius:50%; background:linear-gradient(140deg,rgba(255,77,24,.3),rgba(24,213,231,.22)); filter:blur(90px); opacity:.35; }
        .device { position:absolute; opacity:0; transform:scale(.92) translateY(30px); transition:opacity .8s var(--ease),transform .8s var(--ease); }
        .device.active { opacity:1; transform:none; }
        .device-browser { width:100%; border:1px solid var(--line); border-radius:24px; overflow:hidden; background:#17171a; box-shadow:0 50px 100px rgba(0,0,0,.55); }
        .device-browser img { width:100%; }
        .device-phone { height:min(68vh,700px); aspect-ratio:.54; border-radius:42px; padding:8px; background:#1d1d1f; border:1px solid rgba(255,255,255,.22); box-shadow:0 50px 100px rgba(0,0,0,.6); overflow:hidden; }
        .device-phone img { height:100%; width:100%; object-fit:cover; object-position:top; border-radius:34px; }

        .metrics { padding:150px 0; background:#000; }
        .metrics-title { max-width:770px; margin-bottom:70px; }
        .metrics-title .section-title { margin-top:18px; }
        .metric-grid { display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid var(--line); }
        .metric { padding:42px 20px 42px 0; border-bottom:1px solid var(--line); }
        .metric-number { font-size:clamp(3rem,5vw,5.6rem); letter-spacing:-.07em; line-height:1; font-weight:700; }
        .metric-number span { color:var(--cyan); }
        .metric p { color:var(--muted); font-size:.83rem; max-width:160px; margin:15px 0 0; }

        .experience { padding:150px 0; background:#f5f5f7; color:#1d1d1f; overflow:hidden; }
        .experience-head { text-align:center; max-width:920px; margin:0 auto 70px; }
        .experience-head .eyebrow { justify-content:center; color:#c94118; }
        .experience-head .eyebrow::before { background:#c94118; box-shadow:none; }
        .experience-head .section-title { margin-top:20px; }
        .bento { display:grid; grid-template-columns:1.15fr .85fr; gap:18px; }
        .bento-card { position:relative; border-radius:32px; background:#fff; overflow:hidden; min-height:520px; box-shadow:0 25px 70px rgba(0,0,0,.06); }
        .bento-card.dark { background:#111113; color:#fff; }
        .bento-copy { padding:40px; position:relative; z-index:2; }
        .bento-copy small { color:#bf3d18; font-weight:800; }
        .dark .bento-copy small { color:var(--cyan); }
        .bento-copy h3 { font-size:clamp(2rem,3vw,3.2rem); letter-spacing:-.055em; line-height:1; margin:12px 0; }
        .bento-copy p { color:#6e6e73; max-width:470px; }
        .dark .bento-copy p { color:var(--muted); }
        .bento-image { position:absolute; left:7%; right:7%; bottom:-8%; border-radius:20px 20px 0 0; box-shadow:0 20px 60px rgba(0,0,0,.2); transition:transform .8s var(--ease); }
        .bento-card:hover .bento-image { transform:translateY(-15px) scale(1.015); }
        .bento-phone { position:absolute; height:58%; bottom:-4%; right:12%; border-radius:24px 24px 0 0; box-shadow:0 20px 60px rgba(0,0,0,.3); transition:transform .8s var(--ease); }
        .bento-card:hover .bento-phone { transform:translateY(-15px) rotate(-2deg); }
        .bento-wide { grid-column:1/-1; min-height:570px; }
        .bento-wide .bento-copy { max-width:600px; }
        .bento-wide .bento-image { left:28%; right:-5%; bottom:-25%; }

        .proof { min-height:105vh; display:grid; place-items:center; position:relative; overflow:hidden; background:#050505; }
        .proof-bg { position:absolute; inset:0; opacity:.25; }
        .proof-bg img { width:100%; height:100%; object-fit:cover; filter:blur(3px) saturate(.4); transform:scale(1.06); }
        .proof-bg::after { content:"";position:absolute;inset:0;background:radial-gradient(circle at center,rgba(5,5,5,.2),#050505 70%); }
        .proof-copy { position:relative; text-align:center; max-width:950px; padding:100px 20px; }
        .proof-mark { width:11px; height:11px; border-radius:50%; background:#30d158; box-shadow:0 0 28px #30d158; margin:0 auto 26px; }
        .proof .section-title { margin-bottom:24px; }
        .proof .lead { max-width:650px; margin:auto; }
        .proof-chips { display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-top:30px; }
        .proof-chips span { border:1px solid var(--line); border-radius:999px; padding:8px 13px; color:#d2d2d7; font-size:.78rem; backdrop-filter:blur(10px); }

        .faq { padding:140px 0; background:#f5f5f7; color:#1d1d1f; }
        .faq-grid { display:grid; grid-template-columns:.75fr 1.25fr; gap:9vw; }
        .faq .section-title { position:sticky; top:110px; }
        .faq-list { border-top:1px solid #d2d2d7; }
        .faq details { border-bottom:1px solid #d2d2d7; }
        .faq summary { cursor:pointer; list-style:none; font-size:1.08rem; font-weight:700; padding:24px 46px 24px 0; position:relative; }
        .faq summary::-webkit-details-marker { display:none; }
        .faq summary::after { content:"+"; position:absolute; right:4px; font-size:1.4rem; font-weight:400; transition:transform .3s; }
        .faq details[open] summary::after { transform:rotate(45deg); }
        .faq details p { color:#6e6e73; margin:0; padding:0 50px 25px 0; }

        .closing { min-height:92vh; display:grid; place-items:center; position:relative; overflow:hidden; text-align:center; }
        .closing::before { content:""; position:absolute; width:min(1100px,100vw); aspect-ratio:1; border-radius:50%; background:conic-gradient(from 190deg,var(--orange),transparent 28%,var(--cyan),transparent 63%,var(--orange)); filter:blur(100px); opacity:.17; animation:spin 18s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .closing-copy { position:relative; max-width:960px; padding:100px 20px; }
        .closing-copy .lead { max-width:630px; margin:25px auto 30px; }
        .contact-links { display:flex; justify-content:center; flex-wrap:wrap; gap:10px; margin-top:24px; color:#a1a1a6; font-size:.8rem; }
        .contact-links a:hover { color:#fff; }
        footer { padding:28px 0; border-top:1px solid var(--line); color:#86868b; font-size:.72rem; }
        .footer-inner { display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .footer-brand { display:flex; align-items:center; gap:9px; }
        footer img { width:50px; height:auto; opacity:.9; transition:opacity .3s; }
        footer img:hover { opacity:1; }

        [data-reveal] { opacity:0; transform:translateY(35px); transition:opacity .9s var(--ease),transform .9s var(--ease); }
        [data-reveal].visible { opacity:1; transform:none; }

        @media (max-width:900px) {
            .desktop-links { display:none; }
            .menu-button { display:grid; }
            .mobile-menu { display:block; position:fixed; z-index:14; inset:0; background:rgba(5,5,5,.97); padding:110px 24px 40px; transform:translateY(-100%); transition:transform .7s var(--ease); }
            .mobile-menu.open { transform:none; }
            .mobile-menu a { display:block; padding:16px 0; border-bottom:1px solid var(--line); font-size:2rem; letter-spacing:-.04em; }
            .hero { min-height:auto; padding-top:130px; }
            .hero-stage { height:60vw; min-height:330px; }
            .hero-screen { width:94vw; }
            .float-pill { display:none; }
            .intro-grid,.story-layout,.faq-grid { grid-template-columns:1fr; }
            .intro .eyebrow,.faq .section-title { position:static; }
            .intro-grid { gap:28px; }
            .story-copy { padding-bottom:0; }
            .story-step { min-height:auto; padding:70px 0 30px; opacity:1; }
            .story-visual { position:relative; top:auto; height:62vw; min-height:400px; order:-1; }
            .story-layout { display:flex; flex-direction:column; }
            .metric-grid { grid-template-columns:repeat(2,1fr); }
            .bento { grid-template-columns:1fr; }
            .bento-wide { grid-column:auto; }
            .bento-wide .bento-image { left:8%; right:-20%; bottom:-4%; }
        }
        @media (max-width:600px) {
            .container { width:min(100% - 28px,1200px); }
            .brand img { width:44px; height:auto; }
            .nav-inner { height:58px; }
            .display { font-size:clamp(3.2rem,17vw,5.2rem); }
            .section-title { font-size:clamp(2.6rem,13vw,4rem); }
            .hero { padding-top:115px; }
            .hero-brand-seal { width:108px; height:auto; margin-bottom:20px; }
            .hero-stage { min-height:285px; }
            .hero-screen { border-radius:13px; top:10%; }
            .screen-top { height:22px; }
            .hero-orbit { width:110vw; }
            .intro,.metrics,.experience,.faq { padding:100px 0; }
            .story-head { padding:100px 0 40px; }
            .story-visual { min-height:330px; }
            .device-browser { border-radius:14px; }
            .device-phone { height:310px; border-radius:27px; }
            .device-phone img { border-radius:21px; }
            .metric-grid { grid-template-columns:1fr; }
            .metric { display:flex; align-items:end; justify-content:space-between; gap:20px; }
            .metric p { text-align:right; }
            .bento-card,.bento-wide { min-height:500px; border-radius:24px; }
            .bento-copy { padding:28px; }
            .footer-inner { flex-direction:column; text-align:center; }
        }
        @media (prefers-reduced-motion:reduce) {
            *,*::before,*::after { animation:none!important; transition:none!important; scroll-behavior:auto!important; }
            [data-reveal] { opacity:1; transform:none; }
            .cursor-glow { display:none; }
        }
    </style>
</head>
<body>
    <div class="cursor-glow" aria-hidden="true"></div>
    <div class="noise" aria-hidden="true"></div>

    <header class="nav">
        <div class="container nav-inner">
            <a class="brand" href="#top" aria-label="beForward.lk home">
                <img src="<?= asset('images/logo.svg') ?>" alt="beForward.lk">
                <span class="brand-word">beForward<b>.lk</b></span>
            </a>
            <nav class="desktop-links" aria-label="Main navigation">
                <a href="#platform">Platform</a>
                <a href="#experience">Experience</a>
                <a href="#customer">Customer</a>
                <a href="#faq">FAQ</a>
                <a class="nav-cta" href="#contact">Book a demo</a>
            </nav>
            <button class="menu-button" type="button" aria-label="Open menu" aria-expanded="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h16M4 16h16"/></svg>
            </button>
        </div>
    </header>
    <nav class="mobile-menu" aria-label="Mobile navigation">
        <a href="#platform">Platform</a><a href="#experience">Experience</a><a href="#customer">Customer</a><a href="#faq">FAQ</a><a href="#contact">Book a demo</a>
    </nav>

    <main id="top">
        <section class="hero">
            <div class="container hero-copy">
                <p class="hero-kicker" data-reveal>Introducing beForward.lk</p>
                <h1 class="display" data-reveal>One platform.<br><span class="gradient-text">Every rep counts.</span></h1>
                <p class="lead" data-reveal>A beautifully connected gym management system built to simplify operations, strengthen member experiences and move your fitness business forward.</p>
                <div class="hero-actions" data-reveal>
                    <a class="btn btn-primary magnetic" href="#contact"><span>Book your demo</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg></a>
                    <a class="btn btn-secondary magnetic" href="#platform"><span>Explore the platform</span></a>
                </div>
            </div>
            <div class="wide hero-stage">
                <div class="hero-orbit"></div>
                <div class="hero-screen tilt" data-reveal>
                    <div class="screen-top"><i></i><i></i><i></i></div>
                    <img src="<?= asset('images/backoffice_application.png') ?>" alt="beForward.lk gym management dashboard">
                </div>
                <div class="float-pill one">Live business insights</div>
                <div class="float-pill two">Members connected</div>
                <div class="float-pill three">Payments simplified</div>
            </div>
        </section>

        <section class="intro">
            <div class="container intro-grid">
                <div class="eyebrow" data-reveal>Made for modern gyms</div>
                <h2 data-reveal>Less time managing software. <span>More time building a fitness community.</span></h2>
            </div>
        </section>

        <section class="story" id="platform">
            <div class="container story-head">
                <div class="eyebrow" data-reveal>Three experiences. One platform.</div>
                <h2 class="section-title" data-reveal>Designed around everyone who moves your gym forward.</h2>
            </div>
            <div class="container story-layout">
                <div class="story-copy">
                    <article class="story-step active" data-step="0">
                        <span class="step-num">01 / OPERATE</span>
                        <h3>Your entire gym.<br><span class="gradient-text">In clear view.</span></h3>
                        <p>Manage memberships, payments, attendance, staff, inventory, sales, workouts and reports through one calm command centre.</p>
                        <div class="story-tags"><span>Members</span><span>Revenue</span><span>Reports</span><span>Access control</span></div>
                    </article>
                    <article class="story-step" data-step="1">
                        <span class="step-num">02 / ENGAGE</span>
                        <h3>A member experience <span class="gradient-text">they will use.</span></h3>
                        <p>Give every member a polished mobile portal for membership status, payments, workouts, events, notifications and progress.</p>
                        <div class="story-tags"><span>Mobile first</span><span>Self service</span><span>Workouts</span></div>
                    </article>
                    <article class="story-step" data-step="2">
                        <span class="step-num">03 / GROW</span>
                        <h3>Your strongest first impression. <span class="gradient-text">Online.</span></h3>
                        <p>Launch a branded, responsive website that showcases programs, trainers, schedules and offers while turning visitors into leads.</p>
                        <div class="story-tags"><span>Your brand</span><span>Lead capture</span><span>Responsive</span></div>
                    </article>
                </div>
                <div class="story-visual">
                    <div class="visual-glow"></div>
                    <div class="device device-browser active" data-device="0">
                        <div class="screen-top"><i></i><i></i><i></i></div>
                        <img src="<?= asset('images/backoffice_application.png') ?>" alt="Gym backoffice command centre">
                    </div>
                    <div class="device device-phone" data-device="1">
                        <img src="<?= asset('images/members_portal.jpg') ?>" alt="Gym member mobile portal">
                    </div>
                    <div class="device device-browser" data-device="2">
                        <div class="screen-top"><i></i><i></i><i></i></div>
                        <img src="<?= asset('images/customized_webpage.png') ?>" alt="Branded gym website">
                    </div>
                </div>
            </div>
        </section>

        <section class="metrics">
            <div class="container">
                <div class="metrics-title">
                    <div class="eyebrow" data-reveal>Built for the complete journey</div>
                    <h2 class="section-title" data-reveal>Small details. <span class="gradient-text">Big momentum.</span></h2>
                </div>
                <div class="metric-grid">
                    <div class="metric" data-reveal><div class="metric-number" data-count="360">0<span>°</span></div><p>A complete view of gym operations.</p></div>
                    <div class="metric" data-reveal><div class="metric-number" data-count="3">0<span>×</span></div><p>Connected product experiences.</p></div>
                    <div class="metric" data-reveal><div class="metric-number" data-count="24">0<span>/7</span></div><p>Member access from any device.</p></div>
                    <div class="metric" data-reveal><div class="metric-number" data-count="1">0<span></span></div><p>Source of truth for your business.</p></div>
                </div>
            </div>
        </section>

        <section class="experience" id="experience">
            <div class="container experience-head">
                <div class="eyebrow" data-reveal>Intelligence in every interaction</div>
                <h2 class="section-title" data-reveal>Powerful enough for owners. Simple enough for everyone.</h2>
            </div>
            <div class="wide bento">
                <article class="bento-card" data-reveal>
                    <div class="bento-copy"><small>OPERATIONS</small><h3>Know what needs attention.</h3><p>See member activity, revenue, dues and daily performance without digging through disconnected tools.</p></div>
                    <img class="bento-image" src="<?= asset('images/backoffice_application.png') ?>" alt="Gym operations dashboard">
                </article>
                <article class="bento-card dark" data-reveal>
                    <div class="bento-copy"><small>MEMBERS</small><h3>Put progress in their hands.</h3><p>A personal portal designed around the way members move.</p></div>
                    <img class="bento-phone" src="<?= asset('images/members_portal.jpg') ?>" alt="Mobile member experience">
                </article>
                <article class="bento-card bento-wide dark" data-reveal>
                    <div class="bento-copy"><small>GROWTH</small><h3>A website that works as hard as your gym.</h3><p>Create a memorable digital front door for your fitness brand, connected to the same platform that runs your business.</p></div>
                    <img class="bento-image" src="<?= asset('images/customized_webpage.png') ?>" alt="Custom gym website">
                </article>
            </div>
        </section>

        <section class="proof" id="customer">
            <div class="proof-bg"><img src="<?= asset('images/backoffice_application.png') ?>" alt=""></div>
            <div class="proof-copy">
                <div class="proof-mark" data-reveal></div>
                <p class="eyebrow" style="justify-content:center" data-reveal>Live in the real world</p>
                <h2 class="section-title" data-reveal>Already moving <span class="gradient-text">CoreX Fitness</span> forward.</h2>
                <p class="lead" data-reveal>beForward.lk is not a concept. It is powering a real fitness business in Tangalle and evolving around the needs of modern gym teams.</p>
                <div class="proof-chips" data-reveal><span>CoreX Fitness</span><span>Tangalle, Sri Lanka</span><span>Live platform</span></div>
            </div>
        </section>

        <section class="faq" id="faq">
            <div class="container faq-grid">
                <h2 class="section-title" data-reveal>Questions,<br><span class="gradient-text">answered.</span></h2>
                <div class="faq-list" data-reveal>
                    <details><summary>What is beForward.lk?</summary><p>beForward.lk is an all-in-one gym management platform for memberships, payments, attendance, workouts, bookings, reports, member portals and branded gym websites.</p></details>
                    <details><summary>Is it suitable for gyms in Sri Lanka?</summary><p>Yes. It is designed around the everyday needs of Sri Lankan fitness businesses, their teams and their members.</p></details>
                    <details><summary>Can members use it from their phones?</summary><p>Yes. The mobile-friendly member portal provides access to membership status, payments, workouts, events and gym updates.</p></details>
                    <details><summary>Can my gym have its own website?</summary><p>Yes. beForward.lk can power a responsive branded website that presents your programs, trainers, schedules and offers.</p></details>
                    <details><summary>How do I get started?</summary><p>Book a personalised demonstration and we will walk through how the platform can support your current operation and growth plans.</p></details>
                </div>
            </div>
        </section>

        <section class="closing" id="contact">
            <div class="closing-copy">
                <p class="eyebrow" style="justify-content:center" data-reveal>Your next move</p>
                <h2 class="display" data-reveal>Move your gym<br><span class="gradient-text">forward.</span></h2>
                <p class="lead" data-reveal>See how one connected platform can give your team clarity, your members a better experience and your business room to grow.</p>
                <a class="btn btn-primary magnetic" href="mailto:dmhashan@gmail.com" data-reveal><span>Book a personalised demo</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg></a>
                <div class="contact-links" data-reveal><a href="tel:+94779600845">+94 77 9600 845</a><span>•</span><a href="mailto:dmhashan@gmail.com">dmhashan@gmail.com</a><span>•</span><span>beforward.lk</span></div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-inner">
            <div class="footer-brand">
                <img src="<?= asset('images/logo.svg') ?>" alt="beForward.lk">
                <span class="brand-word">beForward<b>.lk</b></span>
            </div>
            <span>&copy; <?= date('Y') ?> beForward.lk. Move Your Gym Forward.</span>
        </div>
    </footer>

    <script>
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const nav = document.querySelector('.nav');
        const menuButton = document.querySelector('.menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');
        const glow = document.querySelector('.cursor-glow');

        window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 20), { passive:true });

        menuButton.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', open);
            document.body.classList.toggle('menu-open', open);
        });
        mobileMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            menuButton.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
        }));

        const revealObserver = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        }), { threshold:.14, rootMargin:'0px 0px -50px' });
        document.querySelectorAll('[data-reveal]').forEach(el => revealObserver.observe(el));

        const storySteps = [...document.querySelectorAll('.story-step')];
        const devices = [...document.querySelectorAll('[data-device]')];
        const storyObserver = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const index = entry.target.dataset.step;
            storySteps.forEach(step => step.classList.toggle('active', step.dataset.step === index));
            devices.forEach(device => device.classList.toggle('active', device.dataset.device === index));
        }), { threshold:.55 });
        storySteps.forEach(step => storyObserver.observe(step));

        const countObserver = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const element = entry.target;
            const target = Number(element.dataset.count);
            const suffix = element.querySelector('span').outerHTML;
            const start = performance.now();
            const tick = now => {
                const progress = Math.min((now - start) / 1300, 1);
                element.innerHTML = Math.round(target * (1 - Math.pow(1 - progress, 3))) + suffix;
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
            countObserver.unobserve(element);
        }), { threshold:.6 });
        document.querySelectorAll('[data-count]').forEach(el => countObserver.observe(el));

        if (!reducedMotion && window.matchMedia('(pointer:fine)').matches) {
            window.addEventListener('pointermove', event => {
                glow.style.transform = `translate3d(${event.clientX}px,${event.clientY}px,0)`;
            }, { passive:true });

            document.querySelectorAll('.magnetic').forEach(button => {
                button.addEventListener('pointermove', event => {
                    const box = button.getBoundingClientRect();
                    button.style.transform = `translate(${(event.clientX-box.left-box.width/2)*.13}px,${(event.clientY-box.top-box.height/2)*.18}px)`;
                });
                button.addEventListener('pointerleave', () => button.style.transform = '');
            });

            const tilt = document.querySelector('.tilt');
            tilt.addEventListener('pointermove', event => {
                const box = tilt.getBoundingClientRect();
                const x = (event.clientX - box.left) / box.width - .5;
                const y = (event.clientY - box.top) / box.height - .5;
                tilt.style.transform = `translateX(-50%) rotateX(${7-y*5}deg) rotateY(${x*7}deg)`;
            });
            tilt.addEventListener('pointerleave', () => tilt.style.transform = 'translateX(-50%) rotateX(7deg)');
        }
    </script>
    <script type="application/ld+json">
        <?= json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', '@id' => url('/').'#organization', 'name' => 'beForward.lk', 'url' => url('/'), 'logo' => asset('images/logo.svg'), 'email' => 'dmhashan@gmail.com', 'telephone' => '+94779600845'],
                ['@type' => 'SoftwareApplication', 'name' => 'beForward.lk Gym Management Software', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web', 'description' => 'All-in-one gym management software for memberships, payments, attendance, workouts, bookings, reports and gym websites.', 'url' => url('/'), 'provider' => ['@id' => url('/').'#organization'], 'areaServed' => ['@type' => 'Country', 'name' => 'Sri Lanka']],
                ['@type' => 'FAQPage', 'mainEntity' => [
                    ['@type' => 'Question', 'name' => 'What is beForward.lk?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'beForward.lk is an all-in-one gym management platform.']],
                    ['@type' => 'Question', 'name' => 'Is it suitable for gyms in Sri Lanka?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. It is designed around the needs of Sri Lankan fitness businesses.']],
                    ['@type' => 'Question', 'name' => 'Can members use it from their phones?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. Members can use a mobile-friendly self-service portal.']],
                ]],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
</body>
</html>
