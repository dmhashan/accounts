<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>beForward.lk — All-in-One Gym Management System</title>

    <?= app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css', 'resources/js/app.js']) ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --lp-bg:          #0d0f14;
            --lp-surface:     #13161d;
            --lp-surface-2:   #1a1e27;
            --lp-surface-3:   #21262f;
            --lp-border:      rgba(255,255,255,0.07);
            --lp-border-bright: rgba(255,255,255,0.12);
            --lp-text:        #f0f2f8;
            --lp-muted:       #6b7280;
            --lp-muted-2:     #9ca3af;
            --lp-accent:      #e8150a;
            --lp-accent-2:    #ff3d34;
            --lp-accent-hover:#c41008;
            --lp-accent-glow: rgba(232,21,10,0.28);
            --lp-blue:        #3b82f6;
            --lp-green:       #10b981;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            background: var(--lp-bg);
            color: var(--lp-text);
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ── Noise texture overlay ────────────────────────── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.018;
            pointer-events: none;
            z-index: 9999;
        }

        /* ── Utility ─────────────────────────────────────── */
        .lp-surface   { background-color: var(--lp-surface); }
        .lp-surface-2 { background-color: var(--lp-surface-2); }
        .lp-text      { color: var(--lp-text); }
        .lp-muted     { color: var(--lp-muted); }
        .lp-muted-2   { color: var(--lp-muted-2); }
        .lp-accent    { color: var(--lp-accent); }
        .lp-accent-bg { background-color: var(--lp-accent); }

        .gradient-text {
            background: linear-gradient(135deg, var(--lp-accent-2) 0%, #ff6b5b 50%, var(--lp-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Hero ────────────────────────────────────────── */
        .hero-section {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 70% 40%, rgba(232,21,10,0.13) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 10% 80%, rgba(59,130,246,0.06) 0%, transparent 55%),
                radial-gradient(ellipse 60% 50% at 50% -10%, rgba(232,21,10,0.07) 0%, transparent 60%);
            pointer-events: none;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3.5rem;
            align-items: center;
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 5rem 1.5rem 4rem;
        }
        @media (min-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1.05fr 0.95fr;
                padding: 5rem 4rem;
                gap: 5rem;
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(232,21,10,0.10);
            border: 1px solid rgba(232,21,10,0.30);
            color: var(--lp-accent-2);
            border-radius: 9999px;
            padding: 0.35rem 1rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(8px);
        }

        .hero-badge .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--lp-accent-2);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.7); }
        }

        .hero-title {
            font-size: clamp(2.4rem, 5.5vw, 4.25rem);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }

        .hero-sub {
            font-size: 1.05rem;
            color: var(--lp-muted-2);
            line-height: 1.75;
            max-width: 490px;
            margin-bottom: 2.25rem;
        }

        /* ── Buttons ─────────────────────────────────────── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--lp-accent-2) 0%, var(--lp-accent) 100%);
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.85rem 2rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.2s, box-shadow 0.2s, transform 0.18s;
            box-shadow: 0 4px 20px var(--lp-accent-glow);
            position: relative;
            overflow: hidden;
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.12) 0%, transparent 100%);
            border-radius: inherit;
        }
        .btn-primary:hover {
            opacity: 0.92;
            box-shadow: 0 8px 32px var(--lp-accent-glow);
            transform: translateY(-2px);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.05);
            color: var(--lp-text);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.85rem 1.75rem;
            border-radius: 0.75rem;
            border: 1px solid var(--lp-border-bright);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s, transform 0.18s;
            backdrop-filter: blur(8px);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.09);
            border-color: rgba(255,255,255,0.22);
            transform: translateY(-1px);
        }

        /* ── Trust badges ────────────────────────────────── */
        .trust-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 2.5rem;
        }
        .trust-badge {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            color: var(--lp-muted-2);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--lp-border);
            border-radius: 0.5rem;
            padding: 0.35rem 0.75rem;
        }
        .trust-badge svg { color: var(--lp-accent-2); flex-shrink: 0; }

        /* ── Hero visual panel ───────────────────────────── */
        .hero-visual {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hero-visual::before {
            content: '';
            position: absolute;
            inset: -20px;
            background: radial-gradient(ellipse at center, rgba(232,21,10,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .screen-card {
            position: relative;
            z-index: 1;
            background: var(--lp-surface-2);
            border: 1px solid var(--lp-border-bright);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 1px 0 rgba(255,255,255,0.06) inset;
            transition: transform 0.3s ease;
        }
        .screen-card:hover { transform: translateY(-3px); }

        /* ── Stats bar ───────────────────────────────────── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1px;
            background: var(--lp-border);
        }
        @media (min-width: 640px) {
            .stats-bar { grid-template-columns: repeat(4, 1fr); }
        }
        .stat-cell {
            background: var(--lp-surface);
            padding: 2.25rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .stat-cell::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--lp-accent), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .stat-cell:hover::before { opacity: 1; }
        .stat-number {
            font-size: 2.25rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #ff6b5b 0%, var(--lp-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.7rem;
            color: var(--lp-muted);
            margin-top: 0.5rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        /* ── Section ─────────────────────────────────────── */
        .section { padding: 6rem 1.5rem; }
        @media (min-width: 1024px) { .section { padding: 7rem 4rem; } }

        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(232,21,10,0.08);
            border: 1px solid rgba(232,21,10,0.25);
            color: var(--lp-accent-2);
            border-radius: 9999px;
            padding: 0.3rem 0.9rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: clamp(1.75rem, 3.5vw, 2.75rem);
            font-weight: 900;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 1rem;
        }

        /* ── Module cards ────────────────────────────────── */
        .modules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) { .modules-grid { grid-template-columns: repeat(3, 1fr); } }

        .module-card {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: 1.25rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        }
        .module-card:hover {
            border-color: rgba(232,21,10,0.4);
            transform: translateY(-6px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(232,21,10,0.12), 0 0 60px rgba(232,21,10,0.06);
        }

        .module-screenshot {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            object-position: top;
            border-bottom: 1px solid var(--lp-border);
            display: block;
            background: var(--lp-surface-2);
        }

        .module-body { padding: 1.75rem; flex: 1; display: flex; flex-direction: column; }

        .module-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            flex-shrink: 0;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .module-title {
            font-size: 1.1rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }

        .module-desc {
            font-size: 0.84rem;
            color: var(--lp-muted-2);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            flex: 1;
        }

        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: auto;
        }

        .pill {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--lp-border);
            border-radius: 9999px;
            padding: 0.22rem 0.65rem;
            font-size: 0.68rem;
            color: var(--lp-muted);
            white-space: nowrap;
            font-weight: 500;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
        }
        .module-card:hover .pill {
            border-color: rgba(232,21,10,0.2);
        }

        /* ── Feature detail rows ─────────────────────────── */
        .feature-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3.5rem;
            align-items: center;
        }
        @media (min-width: 1024px) {
            .feature-row { grid-template-columns: 1fr 1fr; gap: 6rem; }
            .feature-row.reverse { direction: rtl; }
            .feature-row.reverse > * { direction: ltr; }
        }

        .feature-screenshot {
            border-radius: 1rem;
            border: 1px solid var(--lp-border-bright);
            width: 100%;
            box-shadow: 0 32px 80px rgba(0,0,0,0.55), 0 1px 0 rgba(255,255,255,0.06) inset;
            display: block;
        }

        .feature-list {
            list-style: none;
            margin: 1.75rem 0 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--lp-muted-2);
            line-height: 1.6;
            padding: 0.75rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--lp-border);
            border-radius: 0.6rem;
            transition: background 0.2s, border-color 0.2s;
        }
        .feature-list li:hover {
            background: rgba(232,21,10,0.04);
            border-color: rgba(232,21,10,0.2);
        }
        .feature-check {
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(232,21,10,0.25) 0%, rgba(232,21,10,0.1) 100%);
            border: 1px solid rgba(232,21,10,0.3);
            color: var(--lp-accent-2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        /* ── Screenshot browser mockup ───────────────────── */
        .browser-bar {
            background: var(--lp-surface-3);
            border-bottom: 1px solid var(--lp-border);
            padding: 0.55rem 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }
        .browser-dot { width: 9px; height: 9px; border-radius: 50%; }
        .browser-url {
            flex: 1;
            background: rgba(0,0,0,0.3);
            border-radius: 4px;
            height: 18px;
            margin-left: 10px;
            display: flex;
            align-items: center;
            padding: 0 10px;
        }
        .browser-url span {
            font-size: 0.58rem;
            color: var(--lp-muted);
            font-family: 'SF Mono', 'Fira Code', monospace;
        }

        /* ── Divider ─────────────────────────────────────── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--lp-border-bright) 30%, var(--lp-border-bright) 70%, transparent);
            margin: 0;
        }

        /* ── Glow blob ───────────────────────────────────── */
        .glow-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
        }

        /* ── CTA section ─────────────────────────────────── */
        .cta-section {
            position: relative;
            overflow: hidden;
            text-align: center;
            padding: 6rem 1.5rem;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 50% 50%, rgba(232,21,10,0.12) 0%, transparent 65%),
                radial-gradient(ellipse 100% 80% at 50% 100%, rgba(232,21,10,0.06) 0%, transparent 60%);
            pointer-events: none;
        }
        .cta-section::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(232,21,10,0.4), transparent);
        }

        /* ── Contact card ────────────────────────────────── */
        .contact-card {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border-bright);
            border-radius: 1.5rem;
            padding: 2.5rem;
            max-width: 480px;
            margin: 2.5rem auto 0;
            position: relative;
            z-index: 1;
            text-align: left;
            box-shadow: 0 24px 64px rgba(0,0,0,0.4);
        }
        .contact-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, transparent 60%);
            pointer-events: none;
        }
        .contact-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--lp-border);
        }
        .contact-row:last-child { border-bottom: none; padding-bottom: 0; }
        .contact-row:first-child { padding-top: 0; }
        .contact-icon {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.6rem;
            background: rgba(232,21,10,0.1);
            border: 1px solid rgba(232,21,10,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--lp-accent-2);
        }
        .contact-label { font-size: 0.7rem; color: var(--lp-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
        .contact-value { font-size: 0.9rem; color: var(--lp-text); font-weight: 600; margin-top: 0.15rem; }

        /* ── Footer ──────────────────────────────────────── */
        footer {
            background: var(--lp-surface);
            border-top: 1px solid var(--lp-border);
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        /* ── Animated grid lines ─────────────────────────── */
        .grid-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.022) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
        }

        /* ── Scroll reveal ───────────────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.6s cubic-bezier(0.16,1,0.3,1), transform 0.6s cubic-bezier(0.16,1,0.3,1);
        }
        .reveal.visible {
            opacity: 1;
            transform: none;
        }
        .reveal-delay-1 { transition-delay: 0.12s; }
        .reveal-delay-2 { transition-delay: 0.24s; }
        .reveal-delay-3 { transition-delay: 0.36s; }

        /* ── Accent line ─────────────────────────────────── */
        .accent-line {
            display: inline-block;
            width: 2.5rem;
            height: 3px;
            background: linear-gradient(90deg, var(--lp-accent-2), var(--lp-accent));
            border-radius: 9999px;
            margin-bottom: 1rem;
        }

        /* ── Module number badge ─────────────────────────── */
        .module-num {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.65rem;
            font-weight: 800;
            color: rgba(255,255,255,0.1);
            letter-spacing: 0.05em;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body>

    <!-- ═══════════════════════════════════════════════════
         HERO
    ══════════════════════════════════════════════════════ -->
    <section class="hero-section">
        <div class="grid-lines"></div>
        <div class="glow-blob" style="width:600px;height:600px;top:-150px;right:-150px;background:rgba(232,21,10,0.2);"></div>
        <div class="glow-blob" style="width:400px;height:400px;bottom:-100px;left:-100px;background:rgba(59,130,246,0.08);"></div>

        <div class="hero-grid">
            <!-- Left copy -->
            <div>
                <!-- Large logo -->
                <div style="margin-bottom:2.25rem;">
                    <img src="<?= asset('images/product-logo.svg') ?>" alt="beForward.lk" style="height:56px;width:auto;filter:drop-shadow(0 0 24px rgba(232,21,10,0.35));">
                </div>
                <div class="hero-badge">
                    <span class="badge-dot"></span> All-in-One Platform
                </div>
                <h1 class="hero-title">
                    The Complete<br>
                    <span class="gradient-text">Gym Management</span><br>
                    System
                </h1>
                <p class="hero-sub">
                    Empower your gym with a powerful backoffice, a beautiful member portal, and a fully customizable public website — all in one platform built for modern fitness businesses.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.85rem;align-items:center;">
                    <a href="#contact" class="btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Contact us
                    </a>
                    <a href="#modules" class="btn-ghost">
                        Explore Features
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <!-- Trust badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Backoffice System
                    </div>
                    <div class="trust-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Member Portal
                    </div>
                    <div class="trust-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Website Builder
                    </div>
                </div>
            </div>

            <!-- Right visual -->
            <div class="hero-visual">
                <!-- Main screen -->
                <div class="screen-card">
                    <div class="browser-bar">
                        <div class="browser-dot" style="background:#ff5f57;"></div>
                        <div class="browser-dot" style="background:#febc2e;"></div>
                        <div class="browser-dot" style="background:#28c840;"></div>
                        <div class="browser-url">
                            <span>app.beforward.lk/dashboard</span>
                        </div>
                    </div>
                    <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice Dashboard" style="width:100%;display:block;max-height:230px;object-fit:cover;object-position:top;">
                </div>
                <!-- Two sub-screens -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="screen-card">
                        <div class="browser-bar" style="padding:0.4rem 0.65rem;">
                            <div class="browser-dot" style="background:#ff5f57;width:7px;height:7px;"></div>
                            <div class="browser-dot" style="background:#febc2e;width:7px;height:7px;"></div>
                            <div class="browser-dot" style="background:#28c840;width:7px;height:7px;"></div>
                        </div>
                        <img src="<?= asset('images/members_portal.jpg') ?>" alt="Member Portal" style="width:100%;display:block;height:110px;object-fit:cover;object-position:top;">
                    </div>
                    <div class="screen-card">
                        <div class="browser-bar" style="padding:0.4rem 0.65rem;">
                            <div class="browser-dot" style="background:#ff5f57;width:7px;height:7px;"></div>
                            <div class="browser-dot" style="background:#febc2e;width:7px;height:7px;"></div>
                            <div class="browser-dot" style="background:#28c840;width:7px;height:7px;"></div>
                        </div>
                        <img src="<?= asset('images/customized_webpage.png') ?>" alt="Website Builder" style="width:100%;display:block;height:110px;object-fit:cover;object-position:top;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         STATS
    ══════════════════════════════════════════════════════ -->
    <div class="stats-bar">
        <div class="stat-cell reveal">
            <span class="stat-number">3</span>
            <span class="stat-label">Integrated Modules</span>
        </div>
        <div class="stat-cell reveal reveal-delay-1">
            <span class="stat-number">360°</span>
            <span class="stat-label">Gym Coverage</span>
        </div>
        <div class="stat-cell reveal reveal-delay-2">
            <span class="stat-number">Real‑time</span>
            <span class="stat-label">Data &amp; Reports</span>
        </div>
        <div class="stat-cell reveal reveal-delay-3">
            <span class="stat-number">Mobile</span>
            <span class="stat-label">First Design</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         MODULE OVERVIEW CARDS
    ══════════════════════════════════════════════════════ -->
    <section class="section" id="modules" style="max-width:1320px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:3.5rem;" class="reveal">
            <div class="section-tag">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                Platform Modules
            </div>
            <h2 class="section-title">Everything Your Gym <span class="gradient-text">Needs</span></h2>
            <p style="max-width:540px;margin:0 auto;font-size:0.9rem;line-height:1.75;color:var(--lp-muted-2);">
                Three purpose-built modules that work seamlessly together — from managing operations behind the scenes to delivering a great experience for your members.
            </p>
        </div>

        <div class="modules-grid">
            <!-- Backoffice -->
            <div class="module-card reveal" style="position:relative;">
                <span class="module-num">01</span>
                <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice System" class="module-screenshot">
                <div class="module-body">
                    <div class="module-icon" style="background:rgba(232,21,10,0.1);color:var(--lp-accent-2);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    </div>
                    <div class="module-title">Backoffice System</div>
                    <p class="module-desc">
                        The operational command centre for gym owners and staff. Manage members, finances, inventory, events, workout programs, and detailed reporting — all from a unified dashboard with role-based access control.
                    </p>
                    <div class="feature-pills">
                        <span class="pill">Member Management</span>
                        <span class="pill">Payments &amp; Billing</span>
                        <span class="pill">Inventory &amp; Sales</span>
                        <span class="pill">Events</span>
                        <span class="pill">Workout Programs</span>
                        <span class="pill">Roles &amp; Permissions</span>
                        <span class="pill">Audit Logs</span>
                        <span class="pill">Reports</span>
                    </div>
                </div>
            </div>

            <!-- Member Portal -->
            <div class="module-card reveal reveal-delay-1" style="position:relative;">
                <span class="module-num">02</span>
                <img src="<?= asset('images/members_portal.jpg') ?>" alt="Member Portal" class="module-screenshot">
                <div class="module-body">
                    <div class="module-icon" style="background:rgba(59,130,246,0.1);color:#60a5fa;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="module-title">Member Portal</div>
                    <p class="module-desc">
                        A dedicated self-service portal for members. View membership status, track workout progress, register for events, monitor payment history, and access personalised fitness programs anytime, anywhere.
                    </p>
                    <div class="feature-pills">
                        <span class="pill">Membership Status</span>
                        <span class="pill">Workout Tracking</span>
                        <span class="pill">Event Registration</span>
                        <span class="pill">Payment History</span>
                        <span class="pill">Progress Reports</span>
                        <span class="pill">Notifications</span>
                    </div>
                </div>
            </div>

            <!-- Website Management -->
            <div class="module-card reveal reveal-delay-2" style="position:relative;">
                <span class="module-num">03</span>
                <img src="<?= asset('images/customized_webpage.png') ?>" alt="Website Management" class="module-screenshot">
                <div class="module-body">
                    <div class="module-icon" style="background:rgba(16,185,129,0.1);color:#34d399;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <div class="module-title">Website Management</div>
                    <p class="module-desc">
                        Give your gym a professional public presence without needing a developer. Customize your landing page, showcase your brand, display class schedules, pricing, and let potential members find you online with ease.
                    </p>
                    <div class="feature-pills">
                        <span class="pill">Custom Branding</span>
                        <span class="pill">Page Builder</span>
                        <span class="pill">Class Schedule</span>
                        <span class="pill">Pricing Display</span>
                        <span class="pill">SEO Ready</span>
                        <span class="pill">Mobile Responsive</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         BACKOFFICE DEEP DIVE
    ══════════════════════════════════════════════════════ -->
    <section class="section" style="background:var(--lp-surface);position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 100% 50%, rgba(232,21,10,0.05) 0%, transparent 60%);pointer-events:none;"></div>
        <div style="max-width:1320px;margin:0 auto;position:relative;">
            <div class="feature-row">
                <div class="reveal">
                    <span class="accent-line"></span>
                    <div class="section-tag">Module 01</div>
                    <h2 class="section-title">Backoffice<br><span class="gradient-text">Command Centre</span></h2>
                    <p style="font-size:0.9rem;line-height:1.75;color:var(--lp-muted-2);margin:0.75rem 0 0;">
                        Whether you run a single studio or a multi-location gym chain, the backoffice gives your team the tools to operate at peak efficiency — from onboarding members to reconciling end-of-day finances.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Member Lifecycle Management</strong> — Enroll, renew, freeze, and manage member records with full history, custom fields, and profile photos.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Payments &amp; Billing</strong> — Record payments, issue receipts, track outstanding balances, and reconcile daily cash flow against your company accounts.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Point of Sale &amp; Inventory</strong> — Sell supplements, merchandise, and services. Manage stock levels and get low-inventory alerts automatically.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Events &amp; Classes</strong> — Schedule group classes, workshops, and competitions. Handle registrations, capacity limits, and guest lists.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Roles &amp; Permissions</strong> — Assign granular access rights to each staff member with a full audit trail of every action taken.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Workout Program Builder</strong> — Create structured workout plans, assign them to members or groups, and track completion progress.</span>
                        </li>
                    </ul>
                </div>
                <div class="reveal reveal-delay-1">
                    <div class="screen-card">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div class="browser-url"><span>app.beforward.lk/members</span></div>
                        </div>
                        <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice Screenshot" style="width:100%;display:block;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         MEMBER PORTAL DEEP DIVE
    ══════════════════════════════════════════════════════ -->
    <section class="section" style="position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 0% 50%, rgba(59,130,246,0.05) 0%, transparent 60%);pointer-events:none;"></div>
        <div style="max-width:1320px;margin:0 auto;position:relative;">
            <div class="feature-row reverse">
                <div class="reveal">
                    <span class="accent-line"></span>
                    <div class="section-tag">Module 02</div>
                    <h2 class="section-title">Member <span class="gradient-text">Self-Service Portal</span></h2>
                    <p style="font-size:0.9rem;line-height:1.75;color:var(--lp-muted-2);margin:0.75rem 0 0;">
                        Give your members the transparency and convenience they expect. A clean, mobile-first portal that keeps them engaged and informed — reducing support requests for your team.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Membership Dashboard</strong> — Real-time view of membership status, expiry date, plan details, and renewal options.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Payment History</strong> — Browse a full transaction ledger, download receipts, and see upcoming dues at a glance.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Workout Programs</strong> — Access assigned workout plans with exercise breakdowns, sets, reps, and instructional notes.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Event Registration</strong> — Browse upcoming classes and events, register with one tap, and manage guest passes.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Notifications</strong> — Stay on top of announcements, membership renewals, and gym updates via in-app notifications.</span>
                        </li>
                    </ul>
                </div>
                <div class="reveal reveal-delay-1">
                    <div class="screen-card">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div class="browser-url"><span>portal.beforward.lk/dashboard</span></div>
                        </div>
                        <img src="<?= asset('images/members_portal.jpg') ?>" alt="Member Portal Screenshot" style="width:100%;display:block;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         WEBSITE MANAGEMENT DEEP DIVE
    ══════════════════════════════════════════════════════ -->
    <section class="section" style="background:var(--lp-surface);position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 60% at 100% 50%, rgba(16,185,129,0.04) 0%, transparent 60%);pointer-events:none;"></div>
        <div style="max-width:1320px;margin:0 auto;position:relative;">
            <div class="feature-row">
                <div class="reveal">
                    <span class="accent-line"></span>
                    <div class="section-tag">Module 03</div>
                    <h2 class="section-title">Website <span class="gradient-text">Management</span></h2>
                    <p style="font-size:0.9rem;line-height:1.75;color:var(--lp-muted-2);margin:0.75rem 0 0;">
                        Your public-facing home, built to convert visitors into members. Customize content, showcase your brand personality, and publish changes in real time — no code required.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Brand Customization</strong> — Upload your logo, set brand colors, and choose a layout that reflects your gym's identity.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Content Management</strong> — Edit hero banners, about sections, testimonials, and class descriptions without touching code.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Schedule &amp; Pricing Display</strong> — Automatically publish class timetables and membership pricing pulled from the backoffice.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Member Acquisition</strong> — Capture leads and direct visitors to register, linking seamlessly with the member portal.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Mobile Responsive</strong> — Looks sharp on any device — phone, tablet, or desktop — out of the box.</span>
                        </li>
                    </ul>
                </div>
                <div class="reveal reveal-delay-1">
                    <div class="screen-card">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div class="browser-url"><span>yourgymdomain.com</span></div>
                        </div>
                        <img src="<?= asset('images/customized_webpage.png') ?>" alt="Website Management Screenshot" style="width:100%;display:block;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         CONTACT / CTA
    ══════════════════════════════════════════════════════ -->
    <section class="cta-section" id="contact">
        <div style="max-width:720px;margin:0 auto;position:relative;z-index:1;">
            <div class="section-tag reveal" style="justify-content:center;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Get in Touch
            </div>
            <h2 class="section-title reveal" style="margin-bottom:1rem;">
                Ready to Transform<br><span class="gradient-text">Your Gym Business?</span>
            </h2>
            <p class="reveal" style="font-size:0.95rem;line-height:1.75;color:var(--lp-muted-2);margin:0 0 0.5rem;">
                Join the platform that brings your members, staff, and operations into a single, beautifully designed system. Reach out and we'll get you set up.
            </p>

            <div class="contact-card reveal reveal-delay-1">
                <div class="contact-row">
                    <div class="contact-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Email us</div>
                        <div class="contact-value">hello@beforward.lk</div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.35 2 2 0 0 1 3.6 2H6.6a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.59 9.91a16 16 0 0 0 6.07 6.07l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Call us</div>
                        <div class="contact-value">+94 77 000 0000</div>
                    </div>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="contact-label">Response time</div>
                        <div class="contact-value">Within 24 hours</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════════════════ -->
    <footer>
        <div style="display:flex;flex-direction:column;align-items:center;gap:1rem;">
            <img src="<?= asset('images/product-logo.svg') ?>" alt="beForward.lk" style="height:32px;width:auto;opacity:0.6;filter:grayscale(0.2);">
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <div style="width:1px;height:12px;background:var(--lp-border);"></div>
            </div>
            <p style="font-size:0.75rem;color:var(--lp-muted);margin:0;">
                &copy; <?= date('Y') ?> beForward.lk &nbsp;&mdash;&nbsp; All-in-one gym management platform.
            </p>
        </div>
    </footer>

    <script>
        // Scroll-reveal with stagger
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    revealObserver.unobserve(e.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

        // Smooth stat counter animation
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const el = e.target;
                    const raw = el.textContent.trim();
                    const num = parseFloat(raw.replace(/[^0-9.]/g, ''));
                    if (!isNaN(num) && num > 0 && num < 10000) {
                        const suffix = raw.replace(/[0-9.]/g, '');
                        let start = 0;
                        const step = num / 40;
                        const timer = setInterval(() => {
                            start = Math.min(start + step, num);
                            el.textContent = (Number.isInteger(num) ? Math.round(start) : start.toFixed(1)) + suffix;
                            if (start >= num) clearInterval(timer);
                        }, 25);
                    }
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('.stat-number').forEach(el => counterObserver.observe(el));
    </script>

</body>

</html>