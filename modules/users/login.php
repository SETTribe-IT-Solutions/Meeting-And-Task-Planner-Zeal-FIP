<?php
// modules/users/login.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Access Interceptor: If the user is already logged in, bypass login screen completely
if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Preserved values after failed login
$old_email = htmlspecialchars($_SESSION['old_email'] ?? '');
$old_role  = htmlspecialchars($_SESSION['old_role'] ?? '');
unset($_SESSION['old_email'], $_SESSION['old_role']);

// Error / Success messages
$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latur District | Meeting & Task Planner · Government of Maharashtra</title>
    <meta name="description" content="Official Meeting & Task Planner for Latur District Administration, Government of Maharashtra. Secure login portal for district officials.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════
           GOVERNMENT OF INDIA – OFFICIAL PORTAL THEME
           Modeled after NIC district portals
           ═══════════════════════════════════════════ */

        :root {
            --tricolor-saffron: #FF9933;
            --tricolor-white: #FFFFFF;
            --tricolor-green: #138808;
            --navy-primary: #003366;
            --navy-dark: #00254d;
            --navy-light: #004080;
            --gov-maroon: #800000;
            --gold-accent: #DAA520;
            --bg-cream: #f5f0e8;
            --bg-body: #eee8d5;
            --link-blue: #0645AD;
            --border-gray: #c5b99c;
            --text-dark: #333333;
            --text-muted: #666666;
            --header-gradient: linear-gradient(180deg, #003366 0%, #004080 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Poppins', 'Segoe UI', Arial, sans-serif;
            background:
                linear-gradient(rgba(238,232,213,0.82), rgba(238,232,213,0.82)),
                url('../../assets/image_e15bb67f.png') center / cover fixed no-repeat;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { color: var(--link-blue); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ── Tricolor Stripe ── */
        .tricolor-stripe {
            height: 6px;
            display: flex;
        }
        .tricolor-stripe .saffron { flex: 1; background: var(--tricolor-saffron); }
        .tricolor-stripe .white   { flex: 1; background: var(--tricolor-white); }
        .tricolor-stripe .green   { flex: 1; background: var(--tricolor-green); }

        /* ── Skip to Content (Accessibility) ── */
        .skip-link {
            position: absolute;
            top: -40px; left: 0;
            background: var(--navy-primary);
            color: #fff;
            padding: 8px 16px;
            z-index: 9999;
            font-size: 0.85rem;
            transition: top 0.2s;
        }
        .skip-link:focus { top: 0; }

        /* ── Accessibility Toolbar ── */
        .accessibility-bar {
            background: #f0ebe0;
            border-bottom: 1px solid var(--border-gray);
            padding: 4px 0;
            font-size: 0.72rem;
        }

        .access-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .access-left { color: var(--text-muted); }

        .access-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .access-right span { color: var(--text-muted); font-weight: 500; }

        .font-btn {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            padding: 2px 8px;
            cursor: pointer;
            font-size: 0.7rem;
            color: var(--text-dark);
            transition: background 0.2s;
        }
        .font-btn:hover { background: #e8e2d6; }
        .font-btn.active { background: var(--navy-primary); color: #fff; border-color: var(--navy-primary); }

        .theme-btn {
            width: 18px; height: 18px;
            border-radius: 50%;
            border: 2px solid #999;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .theme-btn:hover { transform: scale(1.15); }
        .theme-btn.t-default { background: #fff; }
        .theme-btn.t-dark { background: #333; }
        .theme-btn.t-contrast { background: #000; border-color: #FFD700; }

        .lang-switch {
            display: flex;
            gap: 0;
            border: 1px solid #bbb;
            border-radius: 4px;
            overflow: hidden;
            margin-left: 6px;
        }

        .lang-btn {
            padding: 2px 10px;
            font-size: 0.7rem;
            background: #fff;
            border: none;
            cursor: pointer;
            color: var(--text-dark);
            font-weight: 500;
            border-right: 1px solid #ddd;
        }
        .lang-btn:last-child { border-right: none; }
        .lang-btn.active { background: var(--navy-primary); color: #fff; }
        .lang-btn:hover:not(.active) { background: #f0ebe0; }

        /* ── Main Header ── */
        .gov-header {
            background: var(--header-gradient);
            color: #fff;
            padding: 14px 0;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .header-emblem {
            flex-shrink: 0;
        }

        .header-emblem img {
            height: 72px;
            width: 72px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            border: 2px solid rgba(255,255,255,0.85);
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .header-text {
            flex: 1;
        }

        .header-text .hindi-title {
            font-family: 'Noto Sans Devanagari', sans-serif;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.85);
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        .header-text .main-title {
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 2px 0;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .header-text .sub-title {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.8);
            font-weight: 400;
        }

        .header-right {
            flex-shrink: 0;
            text-align: center;
        }

        .header-right .digital-india {
            font-size: 0.68rem;
            color: rgba(255,255,255,0.6);
            margin-top: 4px;
            letter-spacing: 0.05em;
        }

        .swachh-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.68rem;
            color: rgba(255,255,255,0.85);
        }

        /* ── Navigation Bar ── */
        .gov-nav {
            background: var(--navy-dark);
            border-bottom: 3px solid var(--gold-accent);
        }

        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
        }

        .nav-link-item {
            color: rgba(255,255,255,0.9);
            padding: 10px 16px;
            font-size: 0.78rem;
            font-weight: 500;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: -3px;
        }

        .nav-link-item:hover, .nav-link-item.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
            text-decoration: none;
            border-bottom-color: var(--gold-accent);
        }

        .nav-link-item i { font-size: 0.85rem; }

        /* ── Marquee / Ticker ── */
        .news-ticker {
            background: #fffbeb;
            border-bottom: 1px solid #e5d9b5;
            padding: 7px 0;
            overflow: hidden;
        }

        .ticker-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ticker-label {
            background: #dc2626;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 3px;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            animation: tickerPulse 2s ease-in-out infinite;
        }

        @keyframes tickerPulse {
            0%,100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .ticker-label i { margin-right: 3px; }

        .ticker-text {
            font-size: 0.78rem;
            color: #92400e;
            font-weight: 500;
        }

        /* ── Main Content Area ── */
        .main-content {
            flex: 1;
            max-width: 650px;
            margin: 0 auto;
            padding: 24px 20px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: rgba(238,232,213,0.72);
            border-left: 1px solid rgba(255,255,255,0.32);
            border-right: 1px solid rgba(255,255,255,0.32);
        }

        /* ── Left Panel (Leaders) ── */
        .leaders-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .leader-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            text-align: center;
            padding: 14px 10px 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .leader-card .photo-frame {
            width: 80px;
            height: 90px;
            margin: 0 auto 8px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid var(--border-gray);
            background: #f5f0e8;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .leader-card .photo-frame i {
            font-size: 2.5rem;
            color: #bbb;
        }

        .leader-card .leader-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--navy-primary);
            margin-bottom: 2px;
        }

        .leader-card .leader-role {
            font-size: 0.68rem;
            color: var(--text-muted);
            line-height: 1.3;
        }

        /* ── Center Panel (Login Form) ── */
        .login-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border: 1px solid #e5d08c;
            border-radius: 8px;
            padding: 16px 20px;
            text-align: center;
        }

        .welcome-banner h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--navy-primary);
            margin-bottom: 4px;
        }

        .welcome-banner .marathi-text {
            font-family: 'Noto Sans Devanagari', sans-serif;
            font-size: 0.9rem;
            color: var(--gov-maroon);
            font-weight: 600;
        }

        .welcome-banner p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* ── Login Form Card ── */
        .login-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .login-card-header {
            background: var(--navy-primary);
            color: #fff;
            padding: 12px 20px;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-card-header i { color: var(--gold-accent); }

        .login-card-body {
            padding: 20px 24px;
        }

        /* ── Form Fields ── */
        .gov-field {
            margin-bottom: 14px;
        }

        .gov-field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--navy-primary);
            margin-bottom: 5px;
        }

        .gov-field label .req { color: #dc2626; }

        .gov-input-wrap {
            position: relative;
        }

        .gov-input-wrap .field-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9rem;
            z-index: 2;
            pointer-events: none;
        }

        .gov-input-wrap input,
        .gov-input-wrap select {
            width: 100%;
            padding: 9px 12px 9px 34px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-dark);
            background: #fdfcfa;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            -webkit-appearance: none;
        }

        .gov-input-wrap select {
            cursor: pointer;
            padding-right: 30px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' fill='none' stroke='%23666' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        .gov-input-wrap input:focus,
        .gov-input-wrap select:focus {
            border-color: var(--navy-primary);
            box-shadow: 0 0 0 2px rgba(0,51,102,0.15);
            background: #fff;
        }

        /* Validation States */
        .gov-input-wrap input.is-invalid,
        .gov-input-wrap select.is-invalid {
            border-color: #dc2626;
            box-shadow: 0 0 0 2px rgba(220,38,38,0.1);
        }

        .gov-input-wrap input.is-valid,
        .gov-input-wrap select.is-valid {
            border-color: #16a34a;
            box-shadow: 0 0 0 2px rgba(22,163,74,0.1);
        }

        .field-feedback {
            font-size: 0.7rem;
            margin-top: 4px;
            display: none;
            align-items: center;
            gap: 4px;
        }

        .field-feedback.error { color: #dc2626; }
        .field-feedback.success { color: #16a34a; }
        .field-feedback.show { display: flex; }

        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            z-index: 2;
            padding: 2px;
        }
        .password-toggle-btn:hover { color: var(--navy-primary); }

        /* ── Captcha Row ── */
        .captcha-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            padding: 10px 12px;
            background: #f9f6f0;
            border: 1px solid #e5d9b5;
            border-radius: 4px;
        }

        .captcha-display {
            background: linear-gradient(135deg, #e8e0d0, #ddd5c4);
            padding: 6px 16px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 5px;
            color: var(--navy-dark);
            user-select: none;
            text-decoration: line-through;
            position: relative;
            border: 1px solid #c5b99c;
        }

        .captcha-refresh {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--navy-primary);
            font-size: 1.1rem;
            padding: 4px;
            transition: transform 0.3s;
        }
        .captcha-refresh:hover { transform: rotate(180deg); }

        .captcha-input {
            flex: 1;
        }

        .captcha-input input {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font-size: 0.85rem;
            font-family: inherit;
            outline: none;
        }

        .captcha-input input:focus {
            border-color: var(--navy-primary);
            box-shadow: 0 0 0 2px rgba(0,51,102,0.15);
        }

        /* ── Form Options ── */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 0.78rem;
        }

        .remember-check {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .remember-check input {
            accent-color: var(--navy-primary);
            cursor: pointer;
        }

        .forgot-link {
            color: var(--link-blue);
            font-size: 0.76rem;
        }

        /* ── Submit Button ── */
        .btn-gov-submit {
            width: 100%;
            padding: 10px;
            background: linear-gradient(180deg, #004080, #003366);
            color: #fff;
            border: 1px solid #002244;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.2s, box-shadow 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .btn-gov-submit:hover:not(:disabled) {
            background: linear-gradient(180deg, #0050a0, #003366);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .btn-gov-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-gov-submit .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: none;
        }

        .btn-gov-submit.loading .spinner { display: inline-block; }
        .btn-gov-submit.loading .btn-text { display: none; }
        .btn-gov-submit.loading .btn-loading-text { display: inline; }
        .btn-gov-submit .btn-loading-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .register-row {
            text-align: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5d9b5;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .register-row a { font-weight: 600; }

        /* ── Demo Credentials ── */
        .demo-box {
            background: #f9f6f0;
            border: 1px solid #e5d9b5;
            border-radius: 6px;
            padding: 12px 16px;
        }

        .demo-box h6 {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.74rem;
            padding: 4px 0;
            border-bottom: 1px dashed #e5d9b5;
        }

        .demo-row:last-child { border-bottom: none; }

        .demo-row .role-tag {
            font-weight: 700;
            color: var(--navy-primary);
            min-width: 70px;
        }

        .demo-row code {
            background: #e8e2d6;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            color: var(--text-dark);
        }

        /* ── Right Panel (Quick Links / Info) ── */
        .info-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .info-card {
            background: #fff;
            border: 1px solid var(--border-gray);
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .info-card-header {
            background: var(--navy-primary);
            color: #fff;
            padding: 8px 14px;
            font-size: 0.76rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-card-header i { color: var(--gold-accent); font-size: 0.85rem; }

        .info-card-body {
            padding: 10px 14px;
        }

        .quick-link-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .quick-link-list li {
            padding: 6px 0;
            border-bottom: 1px dashed #e5d9b5;
            font-size: 0.76rem;
        }

        .quick-link-list li:last-child { border-bottom: none; }

        .quick-link-list li a {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--link-blue);
            transition: color 0.2s;
        }

        .quick-link-list li a:hover { color: var(--gov-maroon); }
        .quick-link-list li a i { color: var(--gold-accent); font-size: 0.65rem; }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 6px 0;
            font-size: 0.74rem;
            color: var(--text-muted);
        }

        .contact-item i {
            color: var(--navy-primary);
            margin-top: 2px;
            font-size: 0.85rem;
        }

        .contact-item strong { color: var(--text-dark); }

        /* ── Visitor Counter ── */
        .visitor-counter {
            text-align: center;
            font-size: 0.72rem;
            color: var(--text-muted);
            padding: 8px 12px;
        }

        .visitor-counter .count-digits {
            display: inline-flex;
            gap: 3px;
            margin-top: 4px;
        }

        .visitor-counter .digit {
            background: var(--navy-primary);
            color: #fff;
            padding: 2px 7px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 0.82rem;
        }

        /* ── Alert ── */
        .gov-alert {
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }
        .gov-alert.alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .gov-alert.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        /* ── Gov Footer ── */
        .gov-footer {
            background: var(--navy-dark);
            color: rgba(255,255,255,0.75);
            padding: 16px 0;
            font-size: 0.72rem;
            margin-top: auto;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 12px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-col h6 {
            color: var(--gold-accent);
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-col ul li {
            padding: 2px 0;
        }

        .footer-col ul li a {
            color: rgba(255,255,255,0.65);
            font-size: 0.7rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-col ul li a:hover { color: #fff; text-decoration: underline; }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .footer-bottom .copyright { color: rgba(255,255,255,0.5); }

        .nic-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.5);
            font-size: 0.68rem;
        }

        .nic-badge span { font-weight: 600; color: rgba(255,255,255,0.7); }

        /* ── Bottom Tricolor ── */
        .footer-tricolor { height: 4px; display: flex; }
        .footer-tricolor .saffron { flex: 1; background: var(--tricolor-saffron); }
        .footer-tricolor .white   { flex: 1; background: var(--tricolor-white); }
        .footer-tricolor .green   { flex: 1; background: var(--tricolor-green); }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .main-content {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .header-inner { flex-direction: column; text-align: center; gap: 10px; }
            .header-emblem img { height: 56px; }
            .header-text .main-title { font-size: 1.1rem; }
            .nav-inner { flex-wrap: wrap; justify-content: center; }
            .nav-link-item { padding: 8px 10px; font-size: 0.72rem; }
            .access-inner { flex-direction: column; gap: 4px; }
            .captcha-row { flex-wrap: wrap; }
            .footer-top { flex-direction: column; }
        }

        /* Card shake for errors */
        @keyframes cardShake {
            0%,100%{transform:translateX(0)}
            20%{transform:translateX(-6px)}
            40%{transform:translateX(6px)}
            60%{transform:translateX(-3px)}
            80%{transform:translateX(3px)}
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to Main Content</a>

    <!-- ══════ TRICOLOR STRIPE ══════ -->
    <div class="tricolor-stripe">
        <div class="saffron"></div>
        <div class="white"></div>
        <div class="green"></div>
    </div>

    <!-- ══════ ACCESSIBILITY BAR ══════ -->
    <div class="accessibility-bar">
        <div class="access-inner">
            <div class="access-left">
                <i class="bi bi-clock"></i> Last Updated: <?php echo date('d M Y, h:i A'); ?>
            </div>
            <div class="access-right">
                <span>Screen Reader Access</span>
                <span>|</span>
                <span>Text Size:</span>
                <button class="font-btn" id="fontSmall" title="Decrease Font Size">A-</button>
                <button class="font-btn active" id="fontDefault" title="Default Font Size">A</button>
                <button class="font-btn" id="fontLarge" title="Increase Font Size">A+</button>
                <span>|</span>
                <span>Color:</span>
                <button class="theme-btn t-default" title="Default Theme" id="themeDefault"></button>
                <button class="theme-btn t-dark" title="Dark Theme" id="themeDark"></button>
                <button class="theme-btn t-contrast" title="High Contrast" id="themeContrast"></button>
                <div class="lang-switch">
                    <button class="lang-btn active" id="langEn">ENG</button>
                    <button class="lang-btn" id="langMr">मराठी</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════ MAIN HEADER ══════ -->
    <header class="gov-header">
        <div class="header-inner">
            <div class="header-emblem">
                <img src="../../assets/photo_1763098684.jpg" alt="Latur Municipal Corporation logo">
            </div>
            <div class="header-text">
                <div class="hindi-title">जिल्हा प्रशासन लातूर · महाराष्ट्र शासन</div>
                <div class="main-title">District Administration Latur</div>
                <div class="sub-title">Meeting & Task Planner · Government of Maharashtra</div>
            </div>
            <div class="header-right">
                <div class="swachh-badge">
                    <i class="bi bi-recycle"></i> Digital India Initiative
                </div>
                <div class="digital-india">Making governance smarter</div>
            </div>
        </div>
    </header>

    <!-- ══════ NAVIGATION BAR ══════ -->
    <nav class="gov-nav">
        <div class="nav-inner">
            <a href="#" class="nav-link-item active"><i class="bi bi-house-door"></i> Home</a>
            <a href="#" class="nav-link-item"><i class="bi bi-info-circle"></i> About District</a>
            <a href="#" class="nav-link-item"><i class="bi bi-building"></i> Administration</a>
            <a href="#" class="nav-link-item"><i class="bi bi-megaphone"></i> Notices</a>
            <a href="#" class="nav-link-item"><i class="bi bi-file-earmark-text"></i> Reports</a>
            <a href="#" class="nav-link-item"><i class="bi bi-telephone"></i> Contact</a>
            <a href="#" class="nav-link-item"><i class="bi bi-question-circle"></i> Help</a>
        </div>
    </nav>

    <!-- ══════ NEWS TICKER ══════ -->
    <div class="news-ticker">
        <div class="ticker-inner">
            <span class="ticker-label"><i class="bi bi-megaphone-fill"></i> Latest</span>
            <marquee class="ticker-text" behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
                📢 Weekly administrative review meetings scheduled every Friday at Collector Office, Latur. &nbsp;|&nbsp; 📋 All department heads must confirm attendance for District Planning Meeting – June 2026. &nbsp;|&nbsp; 🔔 New circular: Updated HR policy guidelines available under Reports section. &nbsp;|&nbsp; ✅ Digital attendance now mandatory for all government meetings.
            </marquee>
        </div>
    </div>

    <!-- ══════ MAIN CONTENT ══════ -->
    <div class="main-content" id="main-content">


        <!-- ── Center Panel: Login Form ── -->
        <main class="login-panel">

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="marathi-text">जिल्हा प्रशासन लातूर मध्ये आपले स्वागत आहे</div>
                <h2>Welcome to Latur District Administration Portal</h2>
                <p>Secure access to Meeting & Task Planner for authorized government officials</p>
            </div>

            <!-- Login Card -->
            <div class="login-card" id="loginCard">
                <div class="login-card-header">
                    <i class="bi bi-shield-lock-fill"></i> Authorized Personnel Login
                </div>
                <div class="login-card-body">

                    <?php if (!empty($error)): ?>
                        <div class="gov-alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="gov-alert alert-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../../controllers/AuthController.php" method="POST" id="loginForm" novalidate autocomplete="on">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="login">

                        <!-- Role Dropdown -->
                        <div class="gov-field">
                            <label for="login-role">Login As <span class="req">*</span></label>
                            <div class="gov-input-wrap">
                                <i class="bi bi-person-badge field-icon"></i>
                                <select name="role" id="login-role" required>
                                    <option value="" disabled <?php echo empty($old_role) ? 'selected' : ''; ?>>-- Select Your Role --</option>
                                    <option value="Collector" <?php echo $old_role === 'Collector' ? 'selected' : ''; ?>>🏛️ Collector (District Head)</option>
                                    <option value="Organizer" <?php echo $old_role === 'Organizer' ? 'selected' : ''; ?>>📋 Organizer (Meeting Coordinator)</option>
                                    <option value="Employee" <?php echo $old_role === 'Employee' ? 'selected' : ''; ?>>👤 Employee (Staff Member)</option>
                                </select>
                            </div>
                            <div class="field-feedback error" id="role-error"><i class="bi bi-x-circle-fill"></i> <span>Please select your role</span></div>
                            <div class="field-feedback success" id="role-success"><i class="bi bi-check-circle-fill"></i> <span>Role selected</span></div>
                        </div>

                        <!-- Email -->
                        <div class="gov-field">
                            <label for="login-email">Official Email ID <span class="req">*</span></label>
                            <div class="gov-input-wrap">
                                <i class="bi bi-envelope field-icon"></i>
                                <input type="email" name="email" id="login-email"
                                       placeholder="user@latur.gov.in"
                                       value="<?php echo $old_email; ?>"
                                       required maxlength="150"
                                       autocomplete="email">
                            </div>
                            <div class="field-feedback error" id="email-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                            <div class="field-feedback success" id="email-success"><i class="bi bi-check-circle-fill"></i> <span>Valid email</span></div>
                        </div>

                        <!-- Password -->
                        <div class="gov-field">
                            <label for="login-password">Password <span class="req">*</span></label>
                            <div class="gov-input-wrap">
                                <i class="bi bi-lock field-icon"></i>
                                <input type="password" name="password" id="login-password"
                                       placeholder="Enter your password"
                                       required minlength="6" maxlength="64"
                                       autocomplete="current-password">
                                <button type="button" class="password-toggle-btn" id="togglePassword" tabindex="-1" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <div class="field-feedback error" id="password-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                        </div>

                        <!-- CAPTCHA -->
                        <div class="captcha-row">
                            <div class="captcha-display" id="captchaDisplay"></div>
                            <button type="button" class="captcha-refresh" id="refreshCaptcha" title="Refresh Captcha">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <div class="captcha-input">
                                <input type="text" id="captchaInput" placeholder="Enter captcha" maxlength="6" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="field-feedback error" id="captcha-error" style="margin-top: -10px; margin-bottom: 10px;"><i class="bi bi-x-circle-fill"></i> <span></span></div>

                        <!-- Options -->
                        <div class="form-options-row">
                            <label class="remember-check">
                                <input type="checkbox" name="remember" id="rememberMe"> Remember me
                            </label>
                            <a href="#" class="forgot-link" id="forgotPasswordLink">Forgot Password?</a>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn-gov-submit" id="loginBtn" disabled>
                            <span class="spinner"></span>
                            <span class="btn-text"><i class="bi bi-box-arrow-in-right"></i> LOGIN</span>
                            <span class="btn-loading-text">Authenticating…</span>
                        </button>
                    </form>

                    <div class="register-row">
                        New official? <a href="register.php">Request Access / Register</a>
                    </div>
                </div>
            </div>

            <!-- Demo Credentials -->
            <div class="demo-box">
                <h6><i class="bi bi-info-circle-fill"></i> Demo Login Credentials</h6>
                <div class="demo-row">
                    <span class="role-tag">Collector</span>
                    <span><code>collector@project.local</code> / <code>collector123</code></span>
                </div>
                <div class="demo-row">
                    <span class="role-tag">Organizer</span>
                    <span><code>organizer@project.local</code> / <code>admin123</code></span>
                </div>
                <div class="demo-row">
                    <span class="role-tag">Employee</span>
                    <span><code>employee@project.local</code> / <code>employee123</code></span>
                </div>
            </div>
        </main>


    </div>

    <!-- ══════ FOOTER ══════ -->
    <footer class="gov-footer">
        <div class="footer-inner">
            <div class="footer-top">
                <div class="footer-col">
                    <h6>Policies</h6>
                    <ul>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Copyright Policy</a></li>
                        <li><a href="#">Hyperlinking Policy</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h6>Accessibility</h6>
                    <ul>
                        <li><a href="#">Accessibility Statement</a></li>
                        <li><a href="#">Screen Reader Access</a></li>
                        <li><a href="#">Sitemap</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h6>Help</h6>
                    <ul>
                        <li><a href="#">User Manual</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Feedback</a></li>
                        <li><a href="#">Contact IT Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="copyright">
                    © <?php echo date('Y'); ?> District Administration, Latur. All Rights Reserved.
                </div>
                <div class="nic-badge">
                    <i class="bi bi-award"></i>
                    Designed & Developed by <span>National Informatics Centre (NIC)</span>
                </div>
            </div>
        </div>
    </footer>

    <div class="footer-tricolor">
        <div class="saffron"></div>
        <div class="white"></div>
        <div class="green"></div>
    </div>

    <!-- ══════ JAVASCRIPT ══════ -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        // ── DOM Elements ──
        const form = document.getElementById('loginForm');
        const roleSelect = document.getElementById('login-role');
        const emailInput = document.getElementById('login-email');
        const passwordInput = document.getElementById('login-password');
        const captchaInput = document.getElementById('captchaInput');
        const toggleBtn = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('loginBtn');

        const validity = { role: false, email: false, password: false, captcha: false };
        let captchaCode = '';

        // ── CAPTCHA Generator ──
        function generateCaptcha() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
            captchaCode = '';
            for (let i = 0; i < 5; i++) {
                captchaCode += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('captchaDisplay').textContent = captchaCode;
            captchaInput.value = '';
            validity.captcha = false;
            updateSubmitButton();
        }
        generateCaptcha();

        document.getElementById('refreshCaptcha').addEventListener('click', generateCaptcha);

        // ── CAPTCHA Validation ──
        captchaInput.addEventListener('input', function() {
            if (this.value === '') {
                showFieldError('captcha', 'Please enter the captcha code');
                validity.captcha = false;
            } else if (this.value !== captchaCode) {
                showFieldError('captcha', 'Captcha does not match');
                validity.captcha = false;
            } else {
                hideFieldError('captcha');
                validity.captcha = true;
            }
            updateSubmitButton();
        });

        // ── Role Validation ──
        roleSelect.addEventListener('change', function() {
            if (!this.value) {
                showFieldError('role', 'Please select your role');
                showInput(this, false);
                validity.role = false;
            } else {
                showFieldSuccess('role');
                showInput(this, true);
                validity.role = true;
            }
            updateSubmitButton();
        });

        // ── Email Validation ──
        emailInput.addEventListener('input', debounce(validateEmail, 300));
        emailInput.addEventListener('blur', validateEmail);

        function validateEmail() {
            const v = emailInput.value.trim();
            if (!v) { showFieldError('email', 'Email is required'); showInput(emailInput, false); validity.email = false; }
            else if (v.length < 5) { showFieldError('email', 'Email too short'); showInput(emailInput, false); validity.email = false; }
            else if (v.length > 150) { showFieldError('email', 'Max 150 characters'); showInput(emailInput, false); validity.email = false; }
            else if (!/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/.test(v)) { showFieldError('email', 'Enter valid email (e.g. user@latur.gov.in)'); showInput(emailInput, false); validity.email = false; }
            else { showFieldSuccess('email'); showInput(emailInput, true); validity.email = true; }
            updateSubmitButton();
        }

        // ── Password Validation ──
        passwordInput.addEventListener('input', validatePassword);
        passwordInput.addEventListener('blur', validatePassword);

        function validatePassword() {
            const v = passwordInput.value;
            if (!v) { showFieldError('password', 'Password is required'); showInput(passwordInput, false); validity.password = false; }
            else if (v.length < 6) { showFieldError('password', 'Minimum 6 characters (' + v.length + '/6)'); showInput(passwordInput, false); validity.password = false; }
            else if (v.length > 64) { showFieldError('password', 'Maximum 64 characters'); showInput(passwordInput, false); validity.password = false; }
            else { hideFieldError('password'); showInput(passwordInput, null); validity.password = true; }
            updateSubmitButton();
        }

        // ── Toggle Password ──
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'bi bi-eye';
            } else {
                passwordInput.type = 'password';
                icon.className = 'bi bi-eye-slash';
            }
            passwordInput.focus();
        });

        // ── Form Submit ──
        form.addEventListener('submit', function(e) {
            roleSelect.dispatchEvent(new Event('change'));
            validateEmail();
            validatePassword();
            captchaInput.dispatchEvent(new Event('input'));

            if (!validity.role || !validity.email || !validity.password || !validity.captcha) {
                e.preventDefault();
                if (!validity.role) roleSelect.focus();
                else if (!validity.email) emailInput.focus();
                else if (!validity.password) passwordInput.focus();
                else if (!validity.captcha) captchaInput.focus();

                const card = document.getElementById('loginCard');
                card.style.animation = 'none';
                card.offsetHeight;
                card.style.animation = 'cardShake 0.4s ease';
                return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });

        // ── Forgot Password ──
        document.getElementById('forgotPasswordLink').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Contact IT Support:\n📧 support@latur.gov.in\n📞 +91-2382-252200');
        });

        // ── Helpers ──
        function showFieldError(field, msg) {
            const err = document.getElementById(field + '-error');
            const suc = document.getElementById(field + '-success');
            if (err) { err.querySelector('span').textContent = msg; err.classList.add('show'); }
            if (suc) suc.classList.remove('show');
        }

        function showFieldSuccess(field) {
            const err = document.getElementById(field + '-error');
            const suc = document.getElementById(field + '-success');
            if (err) err.classList.remove('show');
            if (suc) suc.classList.add('show');
        }

        function hideFieldError(field) {
            const err = document.getElementById(field + '-error');
            const suc = document.getElementById(field + '-success');
            if (err) err.classList.remove('show');
            if (suc) suc.classList.remove('show');
        }

        function showInput(el, isValid) {
            if (!el || !el.classList) return;
            el.classList.remove('is-valid', 'is-invalid');
            if (isValid === true) el.classList.add('is-valid');
            else if (isValid === false) el.classList.add('is-invalid');
        }

        function updateSubmitButton() {
            submitBtn.disabled = !(validity.role && validity.email && validity.password && validity.captcha);
        }

        function debounce(fn, ms) {
            let t;
            return function() { clearTimeout(t); t = setTimeout(() => fn.apply(this, arguments), ms); };
        }

        // Pre-check filled fields
        if (roleSelect.value) roleSelect.dispatchEvent(new Event('change'));
        if (emailInput.value) validateEmail();

        // Auto dismiss alerts
        document.querySelectorAll('.gov-alert').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity 0.4s, transform 0.4s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 400);
            }, 6000);
        });

        // ── Accessibility: Font Size ──
        const fontBtns = [document.getElementById('fontSmall'), document.getElementById('fontDefault'), document.getElementById('fontLarge')];
        const sizes = ['14px', '16px', '18px'];

        fontBtns.forEach((btn, i) => {
            btn.addEventListener('click', function() {
                document.body.style.fontSize = sizes[i];
                fontBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });


    });
    </script>
</body>
</html>
