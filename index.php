<?php
/**
 * God's Family United Methodist Church — Church Management System
 * Public landing / introduction page.
 *
 * This is the entry point of the deployed site (domain root). It presents the
 * system and links into the application, which lives under /V3/public/.
 */
$appEntry   = 'V3/public/login.php';
$logoPath   = 'V3/public/assets/images/logo.png';
$hasLogo    = is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $logoPath));
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Church Management System for God's Family United Methodist Church — member directory, QR attendance, announcements, finance, and reports.">
    <title>God's Family United Methodist Church — Church Management System</title>
    <style>
        :root {
            --navy: #0f172a;
            --navy-soft: #1e293b;
            --gold: #f59e0b;
            --slate: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: var(--bg);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }

        /* ---------- Header ---------- */
        .site-header {
            background: var(--navy);
            color: #fff;
            padding: 18px 24px;
        }
        .site-header-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand-logo {
            width: 52px; height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: var(--navy); font-size: 22px;
        }
        .brand-text .name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .06em;
        }
        .brand-text .sub {
            font-size: 12px;
            color: #94a3b8;
            letter-spacing: .04em;
        }
        .header-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--gold);
            color: #1f2937;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .header-cta:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(245, 158, 11, .32); }

        /* ---------- Hero ---------- */
        .hero {
            position: relative;
            background:
                linear-gradient(135deg, rgba(15, 23, 42, .96) 0%, rgba(30, 41, 59, .94) 55%, rgba(15, 23, 42, .97) 100%);
            color: #fff;
            padding: 72px 24px 84px;
            overflow: hidden;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: auto -12% -55% auto;
            width: 620px; height: 620px;
            background: radial-gradient(circle, rgba(245, 158, 11, .22) 0%, rgba(245, 158, 11, 0) 68%);
            pointer-events: none;
        }
        .hero-inner { position: relative; max-width: 1100px; margin: 0 auto; z-index: 1; }
        .eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--gold);
            border: 1px solid rgba(245, 158, 11, .45);
            border-radius: 999px;
            padding: 6px 14px;
            margin-bottom: 22px;
        }
        .hero h1 {
            font-size: clamp(30px, 4.4vw, 50px);
            line-height: 1.14;
            font-weight: 800;
            letter-spacing: -.02em;
            max-width: 820px;
        }
        .hero h1 span { color: var(--gold); }
        .hero p {
            margin-top: 20px;
            max-width: 660px;
            font-size: 16.5px;
            color: #cbd5e1;
        }
        .hero-actions { margin-top: 34px; display: flex; flex-wrap: wrap; gap: 14px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 15px;
            padding: 13px 26px;
            border-radius: 9px;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .btn-primary {
            background: var(--gold);
            color: #1f2937;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(245, 158, 11, .34); }
        .btn-ghost {
            border: 1px solid rgba(255, 255, 255, .28);
            color: #e2e8f0;
        }
        .btn-ghost:hover { background: rgba(255, 255, 255, .08); }

        /* ---------- Intro ---------- */
        .section { max-width: 1100px; margin: 0 auto; padding: 64px 24px; }
        .section-head { max-width: 720px; margin-bottom: 36px; }
        .section-head h2 {
            font-size: clamp(22px, 2.6vw, 30px);
            letter-spacing: -.01em;
            margin-bottom: 12px;
        }
        .section-head p { color: var(--slate); font-size: 16px; }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
        }
        .feature {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .feature:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(15, 23, 42, .08); }
        .feature-icon {
            width: 42px; height: 42px;
            border-radius: 11px;
            background: rgba(245, 158, 11, .12);
            color: #b45309;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .feature h3 { font-size: 16px; margin-bottom: 6px; }
        .feature p { font-size: 14.2px; color: var(--slate); }

        /* ---------- About / steps ---------- */
        .panel {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 34px;
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 34px;
        }
        .panel h2 { font-size: 24px; margin-bottom: 12px; }
        .panel p { color: var(--slate); font-size: 15.2px; }
        .steps { display: grid; gap: 14px; }
        .step { display: flex; gap: 14px; align-items: flex-start; }
        .step-num {
            flex: 0 0 30px;
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--navy);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .step strong { display: block; font-size: 14.5px; }
        .step span { font-size: 13.6px; color: var(--slate); }

        /* ---------- CTA + footer ---------- */
        .cta-band {
            background: linear-gradient(120deg, var(--navy) 0%, var(--navy-soft) 100%);
            color: #fff;
            text-align: center;
            padding: 58px 24px;
        }
        .cta-band h2 { font-size: clamp(22px, 2.8vw, 30px); margin-bottom: 10px; }
        .cta-band p { color: #cbd5e1; max-width: 620px; margin: 0 auto 26px; }

        .site-footer {
            background: #0b1220;
            color: #94a3b8;
            padding: 30px 24px;
            font-size: 13px;
        }
        .site-footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        @media (max-width: 860px) {
            .panel { grid-template-columns: 1fr; padding: 26px; }
            .hero { padding: 56px 20px 64px; }
            .section { padding: 48px 20px; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="site-header-inner">
        <div class="brand">
            <?php if ($hasLogo): ?>
                <img class="brand-logo" src="<?php echo htmlspecialchars($logoPath); ?>" alt="Church Logo">
            <?php else: ?>
                <div class="brand-logo">G</div>
            <?php endif; ?>
            <div class="brand-text">
                <div class="name">GOD'S FAMILY</div>
                <div class="sub">United Methodist Church</div>
            </div>
        </div>
        <a class="header-cta" href="<?php echo htmlspecialchars($appEntry); ?>">
            Enter System
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <span class="eyebrow">Church Management System</span>
        <h1>One place to manage your <span>church family</span>, attendance, and ministry records.</h1>
        <p>
            The God's Family United Methodist Church Management System centralizes member records,
            QR-based event attendance, announcements, financial reports, and digital member IDs —
            so church leaders can serve the congregation with accurate, real-time information.
        </p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($appEntry); ?>">
                Open the System
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <a class="btn btn-ghost" href="#about">Learn more</a>
        </div>
    </div>
</section>

<section class="section" id="features">
    <div class="section-head">
        <h2>What the system covers</h2>
        <p>Every module is designed around the day-to-day work of the church administration and ministry teams.</p>
    </div>
    <div class="features">
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h3>Member Directory</h3>
            <p>Complete member profiles with categories, contact details, and automatic active/inactive status based on attendance.</p>
        </article>
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3"/></svg>
            </div>
            <h3>QR Event Attendance</h3>
            <p>Scan member ID passes with a phone camera and record attendance in real time, with automatic absence tracking.</p>
        </article>
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l18-7-7 18-2.5-7.5L3 11z"/></svg>
            </div>
            <h3>Announcements & Email</h3>
            <p>Publish church announcements and notify registered members through email in a single step.</p>
        </article>
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3>Collections & Reports</h3>
            <p>Record collections, track finances, and export daily, weekly, monthly, or yearly reports to Excel and PDF.</p>
        </article>
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h4M15 9h2M15 13h2M7 9h2"/></svg>
            </div>
            <h3>Digital Member ID</h3>
            <p>Generate printable front-and-back identification cards with QR access passes for every member.</p>
        </article>
        <article class="feature">
            <div class="feature-icon">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l3-4 3 2 4-6"/></svg>
            </div>
            <h3>Reminders & Analytics</h3>
            <p>Greet celebrants automatically for birthdays and wedding anniversaries, with a live dashboard of church activity.</p>
        </article>
    </div>
</section>

<section class="section" id="about">
    <div class="panel">
        <div>
            <h2>About the system</h2>
            <p>
                This system was built to replace manual record-keeping in the church administration.
                It gives staff and ministry leaders a single, secure workspace — with role-based access
                for the Administrator, Secretary, Treasurer, and Member accounts, so each person sees
                only what their responsibilities require.
            </p>
            <p style="margin-top:14px;">
                All records are stored in a central database and can be exported for meetings,
                reporting, and church conference submissions.
            </p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div><strong>Sign in securely</strong><span>Use your assigned church account.</span></div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div><strong>Manage church records</strong><span>Members, events, attendance, and collections.</span></div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div><strong>Communicate and report</strong><span>Announcements, greetings, and exportable reports.</span></div>
            </div>
        </div>
    </div>
</section>

<section class="cta-band">
    <h2>Ready to get started?</h2>
    <p>Open the system to sign in with your church account and continue where you left off.</p>
    <a class="btn btn-primary" href="<?php echo htmlspecialchars($appEntry); ?>">
        Open the System
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
</section>

<footer class="site-footer">
    <div class="site-footer-inner">
        <div>&copy; <?php echo $currentYear; ?> God's Family United Methodist Church — Church Management System.</div>
        <div>115 Rizal St, Pob 3, Pe&ntilde;aranda, Nueva Ecija, Philippines 3103</div>
    </div>
</footer>

</body>
</html>
