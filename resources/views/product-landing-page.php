<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>beForward.lk — All-in-One Gym Management System</title>

    <?= app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css', 'resources/js/app.js']) ?>

    <style>
        :root {
            --lp-bg: #1a1d23;
            --lp-surface: #23262d;
            --lp-surface-2: #2c3039;
            --lp-border: #3a3f49;
            --lp-text: #f5f7fa;
            --lp-muted: #8b92a5;
            --lp-accent: #e00b00;
            --lp-accent-hover: #c00900;
            --lp-accent-glow: rgba(224, 11, 0, 0.25);
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--lp-bg);
            color: var(--lp-text);
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            margin: 0;
            overflow-x: hidden;
        }

        /* ── Utility ─────────────────────────────────────── */
        .lp-surface   { background-color: var(--lp-surface); }
        .lp-surface-2 { background-color: var(--lp-surface-2); }
        .lp-border    { border-color: var(--lp-border); }
        .lp-text      { color: var(--lp-text); }
        .lp-muted     { color: var(--lp-muted); }
        .lp-accent    { color: var(--lp-accent); }
        .lp-accent-bg { background-color: var(--lp-accent); }

        /* ── Hero ────────────────────────────────────────── */
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3rem;
            align-items: center;
            min-height: 100vh;
            padding: 5rem 1.5rem 4rem;
        }
        @media (min-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr 1fr;
                padding: 4rem 4rem 4rem;
            }
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(224,11,0,0.12);
            border: 1px solid rgba(224,11,0,0.35);
            color: #ff4f45;
            border-radius: 9999px;
            padding: 0.3rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .hero-title {
            font-size: clamp(2.25rem, 5vw, 3.75rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin: 0 0 1.25rem;
        }

        .hero-title .accent { color: var(--lp-accent); }

        .gradient-underline {
            text-decoration: underline;
            text-decoration-color: var(--lp-accent);
            text-underline-offset: 4px;
        }

        /* ── Buttons ─────────────────────────────────────── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--lp-accent);
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.8rem 1.8rem;
            border-radius: 0.6rem;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
            box-shadow: 0 0 0 0 var(--lp-accent-glow);
        }
        .btn-primary:hover {
            background: var(--lp-accent-hover);
            box-shadow: 0 6px 24px var(--lp-accent-glow);
            transform: translateY(-1px);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            color: var(--lp-text);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.8rem 1.6rem;
            border-radius: 0.6rem;
            border: 1px solid var(--lp-border);
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .btn-ghost:hover {
            border-color: var(--lp-muted);
            background: var(--lp-surface-2);
        }

        /* ── Stats bar ───────────────────────────────────── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1px;
            background: var(--lp-border);
            border-top: 1px solid var(--lp-border);
            border-bottom: 1px solid var(--lp-border);
        }
        @media (min-width: 640px) {
            .stats-bar { grid-template-columns: repeat(4, 1fr); }
        }
        .stat-cell {
            background: var(--lp-surface);
            padding: 1.5rem 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--lp-accent);
            display: block;
        }
        .stat-label {
            font-size: 0.75rem;
            color: var(--lp-muted);
            margin-top: 0.25rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ── Section ─────────────────────────────────────── */
        .section { padding: 5rem 1.5rem; }
        @media (min-width: 1024px) { .section { padding: 6rem 4rem; } }

        .section-tag {
            display: inline-block;
            background: rgba(224,11,0,0.1);
            border: 1px solid rgba(224,11,0,0.3);
            color: #ff4f45;
            border-radius: 9999px;
            padding: 0.25rem 0.8rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .section-title {
            font-size: clamp(1.6rem, 3.5vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin: 0 0 1rem;
        }

        /* ── Module cards ────────────────────────────────── */
        .modules-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.75rem;
        }
        @media (min-width: 768px) { .modules-grid { grid-template-columns: repeat(3, 1fr); } }

        .module-card {
            background: var(--lp-surface);
            border: 1px solid var(--lp-border);
            border-radius: 1rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
        }
        .module-card:hover {
            border-color: rgba(224,11,0,0.5);
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.4), 0 0 0 1px rgba(224,11,0,0.15);
        }

        .module-screenshot {
            width: 100%;
            aspect-ratio: 16/10;
            object-fit: cover;
            object-position: top;
            border-bottom: 1px solid var(--lp-border);
            display: block;
            background: var(--lp-surface-2);
        }

        .module-body { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }

        .module-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .module-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
        }

        .module-desc {
            font-size: 0.83rem;
            color: var(--lp-muted);
            line-height: 1.65;
            margin: 0 0 1.25rem;
            flex: 1;
        }

        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: auto;
        }

        .pill {
            background: var(--lp-surface-2);
            border: 1px solid var(--lp-border);
            border-radius: 9999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            color: var(--lp-muted);
            white-space: nowrap;
        }

        /* ── Feature detail rows ─────────────────────────── */
        .feature-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 3rem;
            align-items: center;
        }
        @media (min-width: 1024px) {
            .feature-row { grid-template-columns: 1fr 1fr; gap: 5rem; }
            .feature-row.reverse { direction: rtl; }
            .feature-row.reverse > * { direction: ltr; }
        }

        .feature-screenshot {
            border-radius: 0.875rem;
            border: 1px solid var(--lp-border);
            width: 100%;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            display: block;
        }

        .feature-list { list-style: none; padding: 0; margin: 1.5rem 0 0; display: flex; flex-direction: column; gap: 0.8rem; }
        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            font-size: 0.875rem;
            color: var(--lp-muted);
            line-height: 1.55;
        }
        .feature-check {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background: rgba(224,11,0,0.15);
            color: var(--lp-accent);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        /* ── Hero phone mockup ───────────────────────────── */
        .phone-stack {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 420px;
        }
        .phone-card {
            width: 180px;
            border-radius: 2.25rem;
            border: 7px solid #2c3039;
            box-shadow: 0 32px 80px rgba(0,0,0,0.7);
            overflow: hidden;
            position: absolute;
        }
        .phone-card img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .phone-left {
            width: 160px;
            height: 340px;
            left: calc(50% - 160px);
            top: 40px;
            transform: rotate(-12deg);
            z-index: 1;
        }
        .phone-center {
            width: 190px;
            height: 390px;
            left: calc(50% - 95px);
            top: 15px;
            z-index: 2;
        }
        .phone-right {
            width: 160px;
            height: 340px;
            left: calc(50% + 0px);
            top: 40px;
            transform: rotate(12deg);
            z-index: 1;
        }

        /* ── Screenshot browser mockup ───────────────────── */
        .browser-bar {
            background: var(--lp-surface-2);
            border-bottom: 1px solid var(--lp-border);
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .browser-dot { width: 10px; height: 10px; border-radius: 50%; }

        /* ── CTA ─────────────────────────────────────────── */
        .cta-section {
            background: linear-gradient(135deg, rgba(224,11,0,0.12) 0%, rgba(224,11,0,0.04) 100%);
            border-top: 1px solid var(--lp-border);
            border-bottom: 1px solid var(--lp-border);
            text-align: center;
            padding: 5rem 1.5rem;
        }

        /* ── Footer ──────────────────────────────────────── */
        footer {
            background: var(--lp-surface);
            border-top: 1px solid var(--lp-border);
            padding: 2rem 1.5rem;
            text-align: center;
        }

        /* ── Divider ─────────────────────────────────────── */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--lp-border), transparent);
            margin: 0;
        }

        /* ── Glow blob ───────────────────────────────────── */
        .glow-blob {
            position: absolute;
            border-radius: 50%;
            background: var(--lp-accent-glow);
            filter: blur(80px);
            pointer-events: none;
        }

        /* ── Scroll reveal ───────────────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: none;
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
    </style>
</head>

<body>

    <!-- ═══════════════════════════════════════════════════
         HERO
    ══════════════════════════════════════════════════════ -->
    <section style="position:relative;overflow:hidden;">
        <!-- Glow blobs -->
        <div class="glow-blob" style="width:500px;height:500px;top:-100px;right:-100px;opacity:0.4;"></div>
        <div class="glow-blob" style="width:300px;height:300px;bottom:-50px;left:-80px;opacity:0.3;"></div>

        <div class="hero-grid" style="max-width:1280px;margin:0 auto;">
            <!-- Left copy -->
            <div>
                <!-- Large logo -->
                <div style="margin-bottom:2rem;">
                    <img src="<?= asset('images/product-logo.svg') ?>" alt="beForward.lk" style="height:64px;width:auto;">
                </div>
                <div class="hero-badge">
                    <span>&#9679;</span> All-in-One Platform
                </div>
                <h1 class="hero-title">
                    The Complete<br>
                    <span class="accent">Gym Management</span><br>
                    System
                </h1>
                <p class="lp-muted" style="font-size:1rem;line-height:1.7;max-width:480px;margin:0 0 2rem;">
                    Empower your gym with a powerful backoffice, a beautiful member portal, and a fully customizable public website — all in one platform built for modern fitness businesses.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;">
                    <a href="#contact" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Contact us
                    </a>
                    <a href="#modules" class="btn-ghost">Explore Features</a>
                </div>
                <!-- Trust badges -->
                <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-top:2.5rem;align-items:center;">
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <span style="color:var(--lp-accent);font-size:1rem;">✓</span>
                        <span style="font-size:0.78rem;color:var(--lp-muted);">Backoffice System</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <span style="color:var(--lp-accent);font-size:1rem;">✓</span>
                        <span style="font-size:0.78rem;color:var(--lp-muted);">Member Portal</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <span style="color:var(--lp-accent);font-size:1rem;">✓</span>
                        <span style="font-size:0.78rem;color:var(--lp-muted);">Website Builder</span>
                    </div>
                </div>
            </div>

            <!-- Right visual — stacked screenshots -->
            <div style="position:relative;display:flex;flex-direction:column;gap:1rem;">
                <!-- Backoffice preview (top) -->
                <div style="background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:0.875rem;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.55);">
                    <div class="browser-bar">
                        <div class="browser-dot" style="background:#ff5f57;"></div>
                        <div class="browser-dot" style="background:#febc2e;"></div>
                        <div class="browser-dot" style="background:#28c840;"></div>
                        <div style="flex:1;background:var(--lp-bg);border-radius:4px;height:18px;margin-left:8px;display:flex;align-items:center;padding:0 8px;">
                            <span style="font-size:0.6rem;color:var(--lp-muted);">app.beForward.lk.com/dashboard</span>
                        </div>
                    </div>
                    <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice Dashboard" style="width:100%;display:block;max-height:220px;object-fit:cover;object-position:top;">
                </div>
                <!-- Two smaller previews below -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div style="background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:0.75rem;overflow:hidden;box-shadow:0 12px 32px rgba(0,0,0,0.4);">
                        <div class="browser-bar" style="padding:0.35rem 0.6rem;">
                            <div class="browser-dot" style="background:#ff5f57;width:8px;height:8px;"></div>
                            <div class="browser-dot" style="background:#febc2e;width:8px;height:8px;"></div>
                            <div class="browser-dot" style="background:#28c840;width:8px;height:8px;"></div>
                        </div>
                        <img src="<?= asset('images/members_portal.jpg') ?>" alt="Member Portal" style="width:100%;display:block;height:100px;object-fit:cover;object-position:top;">
                    </div>
                    <div style="background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:0.75rem;overflow:hidden;box-shadow:0 12px 32px rgba(0,0,0,0.4);">
                        <div class="browser-bar" style="padding:0.35rem 0.6rem;">
                            <div class="browser-dot" style="background:#ff5f57;width:8px;height:8px;"></div>
                            <div class="browser-dot" style="background:#febc2e;width:8px;height:8px;"></div>
                            <div class="browser-dot" style="background:#28c840;width:8px;height:8px;"></div>
                        </div>
                        <img src="<?= asset('images/customized_webpage.png') ?>" alt="Website Builder" style="width:100%;display:block;height:100px;object-fit:cover;object-position:top;">
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
            <span class="stat-number">Real-time</span>
            <span class="stat-label">Data & Reports</span>
        </div>
        <div class="stat-cell reveal reveal-delay-3">
            <span class="stat-number">Mobile</span>
            <span class="stat-label">First Design</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         MODULE OVERVIEW CARDS
    ══════════════════════════════════════════════════════ -->
    <section class="section" id="modules" style="max-width:1280px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:3rem;" class="reveal">
            <div class="section-tag">Platform Modules</div>
            <h2 class="section-title">Everything Your Gym Needs</h2>
            <p class="lp-muted" style="max-width:540px;margin:0 auto;font-size:0.9rem;line-height:1.7;">
                Three purpose-built modules that work seamlessly together — from managing operations behind the scenes to delivering a great experience for your members.
            </p>
        </div>

        <div class="modules-grid">
            <!-- Backoffice -->
            <div class="module-card reveal">
                <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice System" class="module-screenshot">
                <div class="module-body">
                    <div class="module-icon" style="background:rgba(224,11,0,0.1);color:var(--lp-accent);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    </div>
                    <div class="module-title">Backoffice System</div>
                    <p class="module-desc">
                        The operational command centre for gym owners and staff. Manage members, finances, inventory, events, workout programs, and detailed reporting — all from a unified dashboard with role-based access control.
                    </p>
                    <div class="feature-pills">
                        <span class="pill">Member Management</span>
                        <span class="pill">Payments & Billing</span>
                        <span class="pill">Inventory & Sales</span>
                        <span class="pill">Events</span>
                        <span class="pill">Workout Programs</span>
                        <span class="pill">Roles & Permissions</span>
                        <span class="pill">Audit Logs</span>
                        <span class="pill">Reports</span>
                    </div>
                </div>
            </div>

            <!-- Member Portal -->
            <div class="module-card reveal reveal-delay-1">
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
            <div class="module-card reveal reveal-delay-2">
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
    <section class="section" style="background:var(--lp-surface);">
        <div style="max-width:1280px;margin:0 auto;">
            <div class="feature-row">
                <div class="reveal">
                    <div class="section-tag">Module 01</div>
                    <h2 class="section-title">Backoffice<br><span class="lp-accent">Command Centre</span></h2>
                    <p class="lp-muted" style="font-size:0.9rem;line-height:1.7;margin:0.75rem 0 0;">
                        Whether you run a single studio or a multi-location gym chain, the backoffice gives your team the tools to operate at peak efficiency — from onboarding members to reconciling end-of-day finances.
                    </p>
                    <ul class="feature-list">
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Member Lifecycle Management</strong> — Enroll, renew, freeze, and manage member records with full history, custom fields, and profile photos.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Payments & Billing</strong> — Record payments, issue receipts, track outstanding balances, and reconcile daily cash flow against your company accounts.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Point of Sale & Inventory</strong> — Sell supplements, merchandise, and services. Manage stock levels and get low-inventory alerts automatically.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Events & Classes</strong> — Schedule group classes, workshops, and competitions. Handle registrations, capacity limits, and guest lists.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Roles & Permissions</strong> — Assign granular access rights to each staff member with a full audit trail of every action taken.</span>
                        </li>
                        <li>
                            <span class="feature-check">&#10003;</span>
                            <span><strong style="color:var(--lp-text);">Workout Program Builder</strong> — Create structured workout plans, assign them to members or groups, and track completion progress.</span>
                        </li>
                    </ul>
                </div>
                <div class="reveal reveal-delay-1">
                    <div style="background:var(--lp-bg);border:1px solid var(--lp-border);border-radius:0.875rem;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.5);">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div style="flex:1;background:var(--lp-surface);border-radius:4px;height:18px;margin-left:8px;display:flex;align-items:center;padding:0 8px;">
                                <span style="font-size:0.6rem;color:var(--lp-muted);">app.beForward.lk.com/members</span>
                            </div>
                        </div>
                        <img src="<?= asset('images/backoffice_application.png') ?>" alt="Backoffice Screenshot" class="feature-screenshot" style="border-radius:0;border:none;box-shadow:none;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         MEMBER PORTAL DEEP DIVE
    ══════════════════════════════════════════════════════ -->
    <section class="section">
        <div style="max-width:1280px;margin:0 auto;">
            <div class="feature-row reverse">
                <div class="reveal">
                    <div class="section-tag">Module 02</div>
                    <h2 class="section-title">Member <span class="lp-accent">Self-Service Portal</span></h2>
                    <p class="lp-muted" style="font-size:0.9rem;line-height:1.7;margin:0.75rem 0 0;">
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
                    <div style="background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:0.875rem;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.5);">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div style="flex:1;background:var(--lp-surface-2);border-radius:4px;height:18px;margin-left:8px;display:flex;align-items:center;padding:0 8px;">
                                <span style="font-size:0.6rem;color:var(--lp-muted);">portal.beForward.lk.com/dashboard</span>
                            </div>
                        </div>
                        <img src="<?= asset('images/members_portal.jpg') ?>" alt="Member Portal Screenshot" class="feature-screenshot" style="border-radius:0;border:none;box-shadow:none;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         WEBSITE MANAGEMENT DEEP DIVE
    ══════════════════════════════════════════════════════ -->
    <section class="section" style="background:var(--lp-surface);">
        <div style="max-width:1280px;margin:0 auto;">
            <div class="feature-row">
                <div class="reveal">
                    <div class="section-tag">Module 03</div>
                    <h2 class="section-title">Website <span class="lp-accent">Management</span></h2>
                    <p class="lp-muted" style="font-size:0.9rem;line-height:1.7;margin:0.75rem 0 0;">
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
                            <span><strong style="color:var(--lp-text);">Schedule & Pricing Display</strong> — Automatically publish class timetables and membership pricing pulled from the backoffice.</span>
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
                    <div style="background:var(--lp-bg);border:1px solid var(--lp-border);border-radius:0.875rem;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.5);">
                        <div class="browser-bar">
                            <div class="browser-dot" style="background:#ff5f57;"></div>
                            <div class="browser-dot" style="background:#febc2e;"></div>
                            <div class="browser-dot" style="background:#28c840;"></div>
                            <div style="flex:1;background:var(--lp-surface);border-radius:4px;height:18px;margin-left:8px;display:flex;align-items:center;padding:0 8px;">
                                <span style="font-size:0.6rem;color:var(--lp-muted);">yourgymdomain.com</span>
                            </div>
                        </div>
                        <img src="<?= asset('images/customized_webpage.png') ?>" alt="Website Management Screenshot" class="feature-screenshot" style="border-radius:0;border:none;box-shadow:none;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <!-- ═══════════════════════════════════════════════════
         CTA
    ══════════════════════════════════════════════════════ -->
    <section class="cta-section reveal">
        <div style="max-width:640px;margin:0 auto;">
            <div class="section-tag" style="margin-bottom:1rem;">Ready to start?</div>
            <h2 class="section-title" style="margin-bottom:1rem;">
                Take Control of Your Gym Today
            </h2>
            <p class="lp-muted" style="font-size:0.9rem;line-height:1.7;margin:0 0 2rem;">
                Join the platform that brings your members, staff, and operations into a single, beautifully designed system.
            </p>
            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:0.75rem;">
                <a href="#contact" class="btn-primary" style="padding:0.85rem 2rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Contact us
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════════════════ -->
    <footer>
        <div style="display:flex;flex-direction:column;align-items:center;gap:0.75rem;">
            <img src="<?= asset('images/product-logo.svg') ?>" alt="beForward.lk" style="height:28px;width:auto;opacity:0.7;">
            <p class="lp-muted" style="font-size:0.75rem;margin:0;">
                &copy; <?= date('Y') ?> beForward.lk. All-in-one gym management platform.
            </p>
        </div>
    </footer>

    <script>
        // Scroll-reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                }
            });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));


    </script>

</body>

</html>