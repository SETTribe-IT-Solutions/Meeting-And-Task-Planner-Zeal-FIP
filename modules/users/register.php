<?php
// modules/users/register.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Preserved old values after failed registration
$old = [
    'name'       => htmlspecialchars($_SESSION['old_name'] ?? ''),
    'email'      => htmlspecialchars($_SESSION['old_email'] ?? ''),
    'phone'      => htmlspecialchars($_SESSION['old_phone'] ?? ''),
    'role'       => htmlspecialchars($_SESSION['old_role'] ?? ''),
    'department' => htmlspecialchars($_SESSION['old_department'] ?? ''),
    'taluka'     => htmlspecialchars($_SESSION['old_taluka'] ?? ''),
    'gender'     => htmlspecialchars($_SESSION['old_gender'] ?? ''),
    'designation'=> htmlspecialchars($_SESSION['old_designation'] ?? ''),
];
// Clear old values
foreach (array_keys($old) as $k) { unset($_SESSION['old_' . $k]); }

$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$field_errors = $_SESSION['field_errors'] ?? [];
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['field_errors']);

// Taluka list for Latur District
$talukas = [
    'Latur', 'Udgir', 'Ahmedpur', 'Nilanga', 'Ausa',
    'Renapur', 'Chakur', 'Deoni', 'Jalkot', 'Shirur Anantpal'
];

// Departments
$departments = getDepartments();

// Designations
$designations = [
    'Tehsildar', 'Naib Tehsildar', 'Talathi', 'Clerk', 'Gram Sevak',
    'BDO (Block Development Officer)', 'District Engineer', 'Medical Officer',
    'Education Officer', 'Agriculture Officer', 'Accountant', 'Data Entry Operator',
    'Office Superintendent', 'Section Officer', 'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latur District | Register · Meeting & Task Planner</title>
    <meta name="description" content="Register for the Latur District Administration Meeting & Task Planner system. Create your official account to access meeting scheduling and task management.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --gov-blue: #0b3d5f;
            --gov-dark: #072a42;
            --gov-red: #8a151b;
            --gov-gold: #f9b81b;
            --accent-green: #16a34a;
            --surface: #f8fafc;
            --card-bg: rgba(255,255,255,0.97);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(rgba(11,61,95,0.66), rgba(7,42,66,0.78)),
                url('../../assets/image_e15bb67f.png') center / cover fixed no-repeat;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(ellipse at 20% 50%, rgba(249,184,27,0.06) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, rgba(255,255,255,0.04) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 80%, rgba(22,163,74,0.04) 0%, transparent 50%);
            animation: bgShift 20s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes bgShift {
            0% { transform: translate(0,0) rotate(0deg); }
            100% { transform: translate(-5%,3%) rotate(3deg); }
        }

        .register-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
        }

        .register-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
            overflow: hidden;
            animation: cardSlideUp 0.6s cubic-bezier(0.16,1,0.3,1);
        }

        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-accent {
            height: 5px;
            background: linear-gradient(90deg, var(--gov-red), var(--gov-gold), var(--accent-green));
        }

        .card-body-inner {
            padding: 2.2rem 2.2rem 1.2rem;
        }

        /* ── Header ── */
        .register-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .emblem-ring {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 68px; height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f0f5fa, #e2ebf3);
            box-shadow: 0 4px 14px rgba(11,61,95,0.12);
            margin-bottom: 0.7rem;
        }

        .emblem-ring img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
        }

        .register-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gov-blue);
            margin-bottom: 0.2rem;
        }

        .register-header p {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* ── Progress Steps ── */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 1.8rem;
        }

        .step-dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            color: #94a3b8;
            background: #fff;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }

        .step-dot.active {
            border-color: var(--gov-blue);
            color: #fff;
            background: var(--gov-blue);
            box-shadow: 0 0 0 4px rgba(11,61,95,0.12);
        }

        .step-dot.completed {
            border-color: var(--accent-green);
            color: #fff;
            background: var(--accent-green);
        }

        .step-line {
            width: 60px; height: 2px;
            background: #e2e8f0;
            transition: background 0.3s;
        }

        .step-line.completed { background: var(--accent-green); }

        /* ── Form Sections ── */
        .form-step {
            display: none;
            animation: fadeStep 0.35s ease;
        }

        .form-step.active { display: block; }

        @keyframes fadeStep {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .step-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--gov-blue);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.2rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eef2f6;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── Alerts ── */
        .alert-toast {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: alertSlide 0.4s ease;
            margin-bottom: 1.2rem;
        }

        .alert-toast.alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-toast.alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        @keyframes alertSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Form Fields ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 1rem;
        }

        .form-row.full { grid-template-columns: 1fr; }

        .field-group {
            margin-bottom: 1rem;
        }

        .field-group label {
            display: block;
            font-size: 0.76rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.3rem;
            letter-spacing: 0.01em;
        }

        .field-group label .req { color: var(--gov-red); margin-left: 2px; }

        .input-wrap {
            position: relative;
        }

        .input-wrap .icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
            z-index: 2;
            transition: color 0.2s;
            pointer-events: none;
        }

        .input-wrap input,
        .input-wrap select,
        .input-wrap textarea {
            width: 100%;
            padding: 0.7rem 0.85rem 0.7rem 2.4rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            -webkit-appearance: none;
        }

        .input-wrap textarea {
            resize: vertical;
            min-height: 60px;
            padding-top: 0.6rem;
        }

        .input-wrap select {
            cursor: pointer;
            padding-right: 2.2rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' fill='none' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
        }

        .input-wrap input:focus,
        .input-wrap select:focus,
        .input-wrap textarea:focus {
            border-color: var(--gov-blue);
            box-shadow: 0 0 0 3px rgba(11,61,95,0.1);
            background: #fff;
        }

        /* Validation */
        .input-wrap input.is-invalid,
        .input-wrap select.is-invalid,
        .input-wrap textarea.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.08);
        }

        .input-wrap input.is-valid,
        .input-wrap select.is-valid,
        .input-wrap textarea.is-valid {
            border-color: var(--accent-green);
            box-shadow: 0 0 0 3px rgba(22,163,74,0.08);
        }

        .field-msg {
            font-size: 0.7rem;
            margin-top: 0.25rem;
            display: none;
            align-items: center;
            gap: 4px;
            animation: alertSlide 0.25s ease;
        }

        .field-msg.error { color: #ef4444; }
        .field-msg.success { color: var(--accent-green); }
        .field-msg.show { display: flex; }

        /* ── Password ── */
        .password-toggle {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: #94a3b8;
            font-size: 1rem; z-index: 2;
            transition: color 0.2s; padding: 4px;
        }

        .password-toggle:hover { color: var(--gov-blue); }

        .strength-bar {
            display: flex;
            gap: 3px;
            margin-top: 0.35rem;
        }

        .str-seg {
            height: 3px; flex: 1;
            border-radius: 4px;
            background: #e2e8f0;
            transition: background 0.3s;
        }

        .str-seg.active.weak { background: #ef4444; }
        .str-seg.active.medium { background: #f59e0b; }
        .str-seg.active.strong { background: #16a34a; }

        .strength-text {
            font-size: 0.66rem;
            margin-top: 0.15rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* ── Gender Radio ── */
        .gender-options {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .gender-option {
            flex: 1;
            min-width: 90px;
        }

        .gender-option input { display: none; }

        .gender-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.6rem 0.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .gender-option input:checked + label {
            border-color: var(--gov-blue);
            color: var(--gov-blue);
            background: #eef5fa;
            box-shadow: 0 0 0 3px rgba(11,61,95,0.08);
        }

        .gender-option label:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        /* ── Terms ── */
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin: 1.2rem 0;
            font-size: 0.78rem;
            color: #475569;
            line-height: 1.4;
        }

        .terms-check input {
            width: 17px; height: 17px;
            margin-top: 1px;
            accent-color: var(--gov-blue);
            cursor: pointer;
            flex-shrink: 0;
        }

        .terms-check a {
            color: var(--gov-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .terms-check a:hover { text-decoration: underline; }

        /* ── Buttons ── */
        .btn-row {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .btn-gov {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
        }

        .btn-gov:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .btn-gov:active:not(:disabled) { transform: translateY(0); }
        .btn-gov:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-primary-gov {
            color: #fff;
            background: linear-gradient(135deg, var(--gov-blue), var(--gov-dark));
            box-shadow: 0 4px 14px rgba(11,61,95,0.2);
        }

        .btn-primary-gov:hover:not(:disabled) {
            box-shadow: 0 8px 25px rgba(11,61,95,0.3);
        }

        .btn-secondary-gov {
            color: #475569;
            background: #f1f5f9;
            border: 1.5px solid #e2e8f0;
        }

        .btn-secondary-gov:hover:not(:disabled) {
            background: #e2e8f0;
        }

        .btn-gov .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: none;
        }

        .btn-gov.loading .spinner { display: inline-block; }
        .btn-gov.loading .btn-text { display: none; }
        .btn-gov.loading .btn-loading-text { display: inline; }
        .btn-gov .btn-loading-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Footer ── */
        .card-footer-section {
            text-align: center;
            padding: 0 2.2rem 1.8rem;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin: 1.2rem 0 0.8rem;
            color: #cbd5e1;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        .login-link {
            font-size: 0.85rem; color: #475569;
        }

        .login-link a {
            color: var(--gov-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover { color: var(--gov-red); }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 1rem;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .security-badge i { color: var(--accent-green); }

        /* ── Phone prefix ── */
        .phone-prefix {
            position: absolute;
            left: 36px; top: 50%;
            transform: translateY(-50%);
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 600;
            z-index: 2;
            pointer-events: none;
        }

        .input-wrap.has-prefix input {
            padding-left: 5.2rem;
        }

        /* ── Responsive ── */
        @media (max-width: 580px) {
            .card-body-inner { padding: 1.8rem 1.3rem 1rem; }
            .card-footer-section { padding: 0 1.3rem 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
            .gender-option { min-width: 80px; }
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-card" id="registerCard">
        <div class="card-accent"></div>
        <div class="card-body-inner">

            <!-- Header -->
            <div class="register-header">
                <div class="emblem-ring">
                    <img src="../../assets/photo_1763098684.jpg" alt="Latur Municipal Corporation logo">
                </div>
                <h1>Request Official Access</h1>
                <p>Latur District Administration · Meeting & Task Planner</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot active" id="stepDot1">1</div>
                <div class="step-line" id="stepLine1"></div>
                <div class="step-dot" id="stepDot2">2</div>
                <div class="step-line" id="stepLine2"></div>
                <div class="step-dot" id="stepDot3">3</div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert-toast alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert-toast alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form action="../../controllers/RegisterController.php" method="POST" id="registerForm" novalidate autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- ══════════ STEP 1: Personal Details ══════════ -->
                <div class="form-step active" id="step1">
                    <div class="step-title"><i class="bi bi-person-lines-fill"></i> Personal Information</div>

                    <!-- Full Name -->
                    <div class="form-row full">
                        <div class="field-group">
                            <label for="reg-name">Full Name (as per records) <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-person icon"></i>
                                <input type="text" name="name" id="reg-name"
                                       placeholder="e.g. Rahul Shankar Patil"
                                       value="<?php echo $old['name']; ?>"
                                       required minlength="3" maxlength="100"
                                       pattern="^[A-Za-z\s.'-]{3,100}$"
                                       autocomplete="name">
                            </div>
                            <div class="field-msg error" id="name-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                            <div class="field-msg success" id="name-success"><i class="bi bi-check-circle-fill"></i> <span>Valid name</span></div>
                        </div>
                    </div>

                    <!-- Email + Phone -->
                    <div class="form-row">
                        <div class="field-group">
                            <label for="reg-email">Official Email <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-envelope icon"></i>
                                <input type="email" name="email" id="reg-email"
                                       placeholder="user@latur.gov.in"
                                       value="<?php echo $old['email']; ?>"
                                       required maxlength="150"
                                       autocomplete="email">
                            </div>
                            <div class="field-msg error" id="email-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                            <div class="field-msg success" id="email-success"><i class="bi bi-check-circle-fill"></i> <span>Valid email</span></div>
                        </div>
                        <div class="field-group">
                            <label for="reg-phone">Mobile Number <span class="req">*</span></label>
                            <div class="input-wrap has-prefix">
                                <i class="bi bi-phone icon"></i>
                                <span class="phone-prefix">+91</span>
                                <input type="tel" name="phone" id="reg-phone"
                                       placeholder="9876543210"
                                       value="<?php echo $old['phone']; ?>"
                                       required minlength="10" maxlength="10"
                                       pattern="[6-9][0-9]{9}"
                                       autocomplete="tel">
                            </div>
                            <div class="field-msg error" id="phone-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                            <div class="field-msg success" id="phone-success"><i class="bi bi-check-circle-fill"></i> <span>Valid number</span></div>
                        </div>
                    </div>

                    <!-- Gender -->
                    <div class="field-group">
                        <label>Gender <span class="req">*</span></label>
                        <div class="gender-options" id="gender-group">
                            <div class="gender-option">
                                <input type="radio" name="gender" id="gender-male" value="Male" <?php echo $old['gender'] === 'Male' ? 'checked' : ''; ?>>
                                <label for="gender-male"><i class="bi bi-gender-male"></i> Male</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" name="gender" id="gender-female" value="Female" <?php echo $old['gender'] === 'Female' ? 'checked' : ''; ?>>
                                <label for="gender-female"><i class="bi bi-gender-female"></i> Female</label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" name="gender" id="gender-other" value="Other" <?php echo $old['gender'] === 'Other' ? 'checked' : ''; ?>>
                                <label for="gender-other"><i class="bi bi-gender-ambiguous"></i> Other</label>
                            </div>
                        </div>
                        <div class="field-msg error" id="gender-error"><i class="bi bi-x-circle-fill"></i> <span>Please select your gender</span></div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn-gov btn-primary-gov" id="toStep2">
                            <span class="btn-text">Next: Official Details <i class="bi bi-arrow-right"></i></span>
                        </button>
                    </div>
                </div>

                <!-- ══════════ STEP 2: Official Details (Dropdowns) ══════════ -->
                <div class="form-step" id="step2">
                    <div class="step-title"><i class="bi bi-building"></i> Official Details & Posting</div>

                    <!-- Role + Department -->
                    <div class="form-row">
                        <div class="field-group">
                            <label for="reg-role">Role <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-person-badge icon"></i>
                                <select name="role" id="reg-role" required>
                                    <option value="" disabled <?php echo empty($old['role']) ? 'selected' : ''; ?>>-- Select Role --</option>
                                    <option value="Collector" <?php echo $old['role'] === 'Collector' ? 'selected' : ''; ?>>🏛️ Collector</option>
                                    <option value="Organizer" <?php echo $old['role'] === 'Organizer' ? 'selected' : ''; ?>>📋 Organizer</option>
                                    <option value="Employee" <?php echo $old['role'] === 'Employee' ? 'selected' : ''; ?>>👤 Employee</option>
                                </select>
                            </div>
                            <div class="field-msg error" id="role-error"><i class="bi bi-x-circle-fill"></i> <span>Please select a role</span></div>
                            <div class="field-msg success" id="role-success"><i class="bi bi-check-circle-fill"></i> <span>Role selected</span></div>
                        </div>
                        <div class="field-group">
                            <label for="reg-department">Department <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-diagram-3 icon"></i>
                                <select name="department" id="reg-department" required>
                                    <option value="" disabled <?php echo empty($old['department']) ? 'selected' : ''; ?>>-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>" <?php echo $old['department'] === $dept ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-msg error" id="department-error"><i class="bi bi-x-circle-fill"></i> <span>Please select a department</span></div>
                            <div class="field-msg success" id="department-success"><i class="bi bi-check-circle-fill"></i> <span>Department selected</span></div>
                        </div>
                    </div>

                    <!-- Designation + Taluka -->
                    <div class="form-row">
                        <div class="field-group">
                            <label for="reg-designation">Designation <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-briefcase icon"></i>
                                <select name="designation" id="reg-designation" required>
                                    <option value="" disabled <?php echo empty($old['designation']) ? 'selected' : ''; ?>>-- Select Designation --</option>
                                    <?php foreach ($designations as $desig): ?>
                                    <option value="<?php echo $desig; ?>" <?php echo $old['designation'] === $desig ? 'selected' : ''; ?>><?php echo $desig; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-msg error" id="designation-error"><i class="bi bi-x-circle-fill"></i> <span>Please select your designation</span></div>
                            <div class="field-msg success" id="designation-success"><i class="bi bi-check-circle-fill"></i> <span>Designation selected</span></div>
                        </div>
                        <div class="field-group">
                            <label for="reg-taluka">Taluka (Posting) <span class="req">*</span></label>
                            <div class="input-wrap">
                                <i class="bi bi-geo-alt icon"></i>
                                <select name="taluka" id="reg-taluka" required>
                                    <option value="" disabled <?php echo empty($old['taluka']) ? 'selected' : ''; ?>>-- Select Taluka --</option>
                                    <?php foreach ($talukas as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $old['taluka'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-msg error" id="taluka-error"><i class="bi bi-x-circle-fill"></i> <span>Please select your taluka</span></div>
                            <div class="field-msg success" id="taluka-success"><i class="bi bi-check-circle-fill"></i> <span>Taluka selected</span></div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn-gov btn-secondary-gov" id="backToStep1">
                            <span class="btn-text"><i class="bi bi-arrow-left"></i> Back</span>
                        </button>
                        <button type="button" class="btn-gov btn-primary-gov" id="toStep3">
                            <span class="btn-text">Next: Set Password <i class="bi bi-arrow-right"></i></span>
                        </button>
                    </div>
                </div>

                <!-- ══════════ STEP 3: Password & Confirm ══════════ -->
                <div class="form-step" id="step3">
                    <div class="step-title"><i class="bi bi-shield-lock"></i> Create Secure Password</div>

                    <!-- Password -->
                    <div class="field-group">
                        <label for="reg-password">Password <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="bi bi-lock icon"></i>
                            <input type="password" name="password" id="reg-password"
                                   placeholder="Min 8 chars, uppercase, number, symbol"
                                   required minlength="8" maxlength="64"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" id="togglePwd1" tabindex="-1">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                        <div class="strength-bar" id="strengthBar">
                            <div class="str-seg" id="s1"></div>
                            <div class="str-seg" id="s2"></div>
                            <div class="str-seg" id="s3"></div>
                            <div class="str-seg" id="s4"></div>
                            <div class="str-seg" id="s5"></div>
                        </div>
                        <div class="strength-text" id="strengthLabel"></div>
                        <div class="field-msg error" id="password-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="field-group">
                        <label for="reg-confirm-password">Confirm Password <span class="req">*</span></label>
                        <div class="input-wrap">
                            <i class="bi bi-lock-fill icon"></i>
                            <input type="password" name="confirm_password" id="reg-confirm-password"
                                   placeholder="Re-enter your password"
                                   required minlength="8" maxlength="64"
                                   autocomplete="new-password">
                            <button type="button" class="password-toggle" id="togglePwd2" tabindex="-1">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                        <div class="field-msg error" id="confirm-error"><i class="bi bi-x-circle-fill"></i> <span></span></div>
                        <div class="field-msg success" id="confirm-success"><i class="bi bi-check-circle-fill"></i> <span>Passwords match</span></div>
                    </div>

                    <!-- Password Requirements Checklist -->
                    <div style="background: #f0f5fa; border-radius: 10px; padding: 0.8rem 1rem; margin-bottom: 0.6rem; border: 1px solid #e2e8f0;">
                        <p style="font-size: 0.72rem; font-weight: 700; color: #475569; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.04em;">Password Requirements</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.2rem 0.8rem;">
                            <span class="pwd-req" id="req-length" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> Min 8 characters</span>
                            <span class="pwd-req" id="req-upper" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> One uppercase (A-Z)</span>
                            <span class="pwd-req" id="req-lower" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> One lowercase (a-z)</span>
                            <span class="pwd-req" id="req-number" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> One number (0-9)</span>
                            <span class="pwd-req" id="req-special" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> One symbol (!@#$)</span>
                            <span class="pwd-req" id="req-match" style="font-size: 0.72rem; color: #94a3b8;"><i class="bi bi-circle"></i> Passwords match</span>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="terms-check">
                        <input type="checkbox" name="terms" id="termsCheck" required>
                        <span>I confirm that I am an authorized personnel of Latur District Administration and agree to the <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.</span>
                    </div>
                    <div class="field-msg error" id="terms-error" style="margin-top: -0.6rem; margin-bottom: 0.6rem;"><i class="bi bi-x-circle-fill"></i> <span>You must accept the terms to continue</span></div>

                    <div class="btn-row">
                        <button type="button" class="btn-gov btn-secondary-gov" id="backToStep2">
                            <span class="btn-text"><i class="bi bi-arrow-left"></i> Back</span>
                        </button>
                        <button type="submit" class="btn-gov btn-primary-gov" id="submitBtn" disabled>
                            <span class="spinner"></span>
                            <span class="btn-text"><i class="bi bi-person-plus"></i> Create Account</span>
                            <span class="btn-loading-text">Creating Account…</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="card-footer-section">
            <div class="divider">or</div>
            <p class="login-link">Already have an account? <a href="login.php">Sign In Here</a></p>
            <div class="security-badge">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Secured with 256-bit encryption · Government of Maharashtra</span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── DOM elements ──
    const form = document.getElementById('registerForm');
    const steps = [document.getElementById('step1'), document.getElementById('step2'), document.getElementById('step3')];
    const dots  = [document.getElementById('stepDot1'), document.getElementById('stepDot2'), document.getElementById('stepDot3')];
    const lines = [document.getElementById('stepLine1'), document.getElementById('stepLine2')];
    let currentStep = 0;

    // Fields
    const nameInput    = document.getElementById('reg-name');
    const emailInput   = document.getElementById('reg-email');
    const phoneInput   = document.getElementById('reg-phone');
    const roleSelect   = document.getElementById('reg-role');
    const deptSelect   = document.getElementById('reg-department');
    const desigSelect  = document.getElementById('reg-designation');
    const talukaSelect = document.getElementById('reg-taluka');
    const pwdInput     = document.getElementById('reg-password');
    const confirmInput = document.getElementById('reg-confirm-password');
    const termsCheck   = document.getElementById('termsCheck');
    const submitBtn    = document.getElementById('submitBtn');

    // ── Step Navigation ──
    function goToStep(n) {
        steps[currentStep].classList.remove('active');
        steps[n].classList.add('active');

        dots.forEach((d, i) => {
            d.classList.remove('active', 'completed');
            if (i < n) { d.classList.add('completed'); d.innerHTML = '<i class="bi bi-check-lg"></i>'; }
            else if (i === n) { d.classList.add('active'); d.textContent = i + 1; }
            else { d.textContent = i + 1; }
        });

        lines.forEach((l, i) => {
            l.classList.toggle('completed', i < n);
        });

        currentStep = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.getElementById('toStep2').addEventListener('click', function() {
        if (validateStep1()) goToStep(1);
    });

    document.getElementById('backToStep1').addEventListener('click', function() { goToStep(0); });

    document.getElementById('toStep3').addEventListener('click', function() {
        if (validateStep2()) goToStep(2);
    });

    document.getElementById('backToStep2').addEventListener('click', function() { goToStep(1); });

    // ════════════════════════════════════════
    // ── VALIDATION LOGIC ──
    // ════════════════════════════════════════

    // Helper functions
    function showError(field, msg) {
        const err = document.getElementById(field + '-error');
        const suc = document.getElementById(field + '-success');
        if (err) { err.querySelector('span').textContent = msg; err.classList.add('show'); }
        if (suc) suc.classList.remove('show');
        // Mark input
        const inp = document.querySelector(`[name="${field}"]`) || document.getElementById('reg-' + field);
        if (inp && inp.classList) { inp.classList.add('is-invalid'); inp.classList.remove('is-valid'); }
    }

    function showSuccess(field) {
        const err = document.getElementById(field + '-error');
        const suc = document.getElementById(field + '-success');
        if (err) err.classList.remove('show');
        if (suc) suc.classList.add('show');
        const inp = document.querySelector(`[name="${field}"]`) || document.getElementById('reg-' + field);
        if (inp && inp.classList) { inp.classList.remove('is-invalid'); inp.classList.add('is-valid'); }
    }

    function clearField(field) {
        const err = document.getElementById(field + '-error');
        const suc = document.getElementById(field + '-success');
        if (err) err.classList.remove('show');
        if (suc) suc.classList.remove('show');
    }

    // ── NAME ──
    nameInput.addEventListener('input', validateName);
    nameInput.addEventListener('blur', validateName);

    function validateName() {
        const v = nameInput.value.trim();
        if (!v) { showError('name', 'Full name is required'); return false; }
        if (v.length < 3) { showError('name', 'Name must be at least 3 characters'); return false; }
        if (v.length > 100) { showError('name', 'Name must not exceed 100 characters'); return false; }
        if (!/^[A-Za-z\s.'\-]+$/.test(v)) { showError('name', 'Name can only contain letters, spaces, dots, hyphens'); return false; }
        if (/^\s|\s$/.test(nameInput.value)) { showError('name', 'Name should not start or end with spaces'); return false; }
        // Check at least 2 words (first + last name)
        const words = v.split(/\s+/).filter(w => w.length > 0);
        if (words.length < 2) { showError('name', 'Enter your full name (first & last name)'); return false; }
        showSuccess('name');
        return true;
    }

    // ── EMAIL ──
    emailInput.addEventListener('input', debounce(validateEmail, 300));
    emailInput.addEventListener('blur', validateEmail);

    function validateEmail() {
        const v = emailInput.value.trim();
        if (!v) { showError('email', 'Email is required'); return false; }
        if (v.length < 5) { showError('email', 'Email is too short'); return false; }
        if (v.length > 150) { showError('email', 'Email must not exceed 150 characters'); return false; }
        if (/\s/.test(v)) { showError('email', 'Email must not contain spaces'); return false; }
        if (!/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/.test(v)) { showError('email', 'Enter a valid email (e.g. user@latur.gov.in)'); return false; }
        showSuccess('email');
        return true;
    }

    // ── PHONE ──
    phoneInput.addEventListener('input', function() {
        // Allow only digits
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        validatePhone();
    });
    phoneInput.addEventListener('blur', validatePhone);

    function validatePhone() {
        const v = phoneInput.value.trim();
        if (!v) { showError('phone', 'Mobile number is required'); return false; }
        if (!/^[6-9]/.test(v)) { showError('phone', 'Must start with 6, 7, 8, or 9'); return false; }
        if (v.length < 10) { showError('phone', 'Must be exactly 10 digits (' + v.length + '/10)'); return false; }
        if (v.length > 10) { showError('phone', 'Must be exactly 10 digits'); return false; }
        if (/^(\d)\1{9}$/.test(v)) { showError('phone', 'Invalid number (all same digits)'); return false; }
        showSuccess('phone');
        return true;
    }

    // ── GENDER ──
    document.querySelectorAll('input[name="gender"]').forEach(r => {
        r.addEventListener('change', function() {
            const err = document.getElementById('gender-error');
            if (err) err.classList.remove('show');
        });
    });

    function validateGender() {
        const checked = document.querySelector('input[name="gender"]:checked');
        if (!checked) { showError('gender', 'Please select your gender'); return false; }
        clearField('gender');
        return true;
    }

    // ── DROPDOWNS (Step 2) ──
    function validateSelect(id, field) {
        const el = document.getElementById(id);
        if (!el.value) { showError(field, 'Please select ' + field.replace('-', ' ')); return false; }
        showSuccess(field);
        return true;
    }

    roleSelect.addEventListener('change', () => validateSelect('reg-role', 'role'));
    deptSelect.addEventListener('change', () => validateSelect('reg-department', 'department'));
    desigSelect.addEventListener('change', () => validateSelect('reg-designation', 'designation'));
    talukaSelect.addEventListener('change', () => validateSelect('reg-taluka', 'taluka'));

    // ── PASSWORD ──
    pwdInput.addEventListener('input', function() {
        validatePassword();
        updateStrength(this.value);
        updateRequirements(this.value);
        if (confirmInput.value) validateConfirm();
    });

    pwdInput.addEventListener('blur', validatePassword);

    function validatePassword() {
        const v = pwdInput.value;
        if (!v) { showError('password', 'Password is required'); return false; }
        if (v.length < 8) { showError('password', 'Minimum 8 characters required (' + v.length + '/8)'); return false; }
        if (v.length > 64) { showError('password', 'Maximum 64 characters allowed'); return false; }
        if (!/[A-Z]/.test(v)) { showError('password', 'Must contain at least one uppercase letter'); return false; }
        if (!/[a-z]/.test(v)) { showError('password', 'Must contain at least one lowercase letter'); return false; }
        if (!/[0-9]/.test(v)) { showError('password', 'Must contain at least one number'); return false; }
        if (!/[^A-Za-z0-9]/.test(v)) { showError('password', 'Must contain at least one special character'); return false; }
        if (/\s/.test(v)) { showError('password', 'Password must not contain spaces'); return false; }
        clearField('password');
        return true;
    }

    // ── CONFIRM PASSWORD ──
    confirmInput.addEventListener('input', validateConfirm);
    confirmInput.addEventListener('blur', validateConfirm);

    function validateConfirm() {
        const v = confirmInput.value;
        if (!v) { showError('confirm', 'Please confirm your password'); return false; }
        if (v !== pwdInput.value) { showError('confirm', 'Passwords do not match'); return false; }
        showSuccess('confirm');
        updateReqItem('req-match', true);
        return true;
    }

    // ── TERMS ──
    termsCheck.addEventListener('change', function() {
        const err = document.getElementById('terms-error');
        if (this.checked) { err.classList.remove('show'); }
        updateSubmitBtn();
    });

    // ── Password Strength ──
    function updateStrength(pwd) {
        let score = 0;
        if (pwd.length >= 8) score++;
        if (pwd.length >= 12) score++;
        if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;

        const segs = document.querySelectorAll('.str-seg');
        const label = document.getElementById('strengthLabel');
        const levels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
        const cls    = ['', 'weak', 'weak', 'medium', 'strong', 'strong'];
        const colors = ['', '#ef4444', '#ef4444', '#f59e0b', '#16a34a', '#16a34a'];

        const s = Math.min(score, 5);
        segs.forEach((seg, i) => {
            seg.className = 'str-seg';
            if (i < s && pwd.length > 0) seg.classList.add('active', cls[s]);
        });
        label.textContent = pwd.length > 0 ? levels[s] : '';
        label.style.color = colors[s] || '#94a3b8';
    }

    // ── Requirements Checklist ──
    function updateRequirements(pwd) {
        updateReqItem('req-length', pwd.length >= 8);
        updateReqItem('req-upper', /[A-Z]/.test(pwd));
        updateReqItem('req-lower', /[a-z]/.test(pwd));
        updateReqItem('req-number', /[0-9]/.test(pwd));
        updateReqItem('req-special', /[^A-Za-z0-9]/.test(pwd));
        updateReqItem('req-match', confirmInput.value && confirmInput.value === pwd);
    }

    function updateReqItem(id, passed) {
        const el = document.getElementById(id);
        if (!el) return;
        const icon = el.querySelector('i');
        if (passed) {
            el.style.color = '#16a34a';
            icon.className = 'bi bi-check-circle-fill';
        } else {
            el.style.color = '#94a3b8';
            icon.className = 'bi bi-circle';
        }
    }

    // ── Toggle visibility ──
    document.getElementById('togglePwd1').addEventListener('click', () => toggleVis(pwdInput, 'togglePwd1'));
    document.getElementById('togglePwd2').addEventListener('click', () => toggleVis(confirmInput, 'togglePwd2'));

    function toggleVis(input, btnId) {
        const icon = document.getElementById(btnId).querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye'; }
        else { input.type = 'password'; icon.className = 'bi bi-eye-slash'; }
        input.focus();
    }

    // ══════════════════════════════════════
    // ── STEP VALIDATORS ──
    // ══════════════════════════════════════

    function validateStep1() {
        const n = validateName();
        const e = validateEmail();
        const p = validatePhone();
        const g = validateGender();
        if (!n) nameInput.focus();
        else if (!e) emailInput.focus();
        else if (!p) phoneInput.focus();
        if (!n || !e || !p || !g) { shakeCard(); return false; }
        return true;
    }

    function validateStep2() {
        const r = validateSelect('reg-role', 'role');
        const d = validateSelect('reg-department', 'department');
        const s = validateSelect('reg-designation', 'designation');
        const t = validateSelect('reg-taluka', 'taluka');
        if (!r) roleSelect.focus();
        else if (!d) deptSelect.focus();
        else if (!s) desigSelect.focus();
        else if (!t) talukaSelect.focus();
        if (!r || !d || !s || !t) { shakeCard(); return false; }
        return true;
    }

    function validateStep3() {
        const p = validatePassword();
        const c = validateConfirm();
        const t = termsCheck.checked;
        if (!t) document.getElementById('terms-error').classList.add('show');
        if (!p) pwdInput.focus();
        else if (!c) confirmInput.focus();
        if (!p || !c || !t) { shakeCard(); return false; }
        return true;
    }

    // ── Submit Button state ──
    function updateSubmitBtn() {
        const pwdOk = pwdInput.value.length >= 8 && /[A-Z]/.test(pwdInput.value) && /[a-z]/.test(pwdInput.value) && /[0-9]/.test(pwdInput.value) && /[^A-Za-z0-9]/.test(pwdInput.value);
        const confirmOk = confirmInput.value === pwdInput.value && confirmInput.value.length > 0;
        submitBtn.disabled = !(pwdOk && confirmOk && termsCheck.checked);
    }

    pwdInput.addEventListener('input', updateSubmitBtn);
    confirmInput.addEventListener('input', updateSubmitBtn);

    // ── Form Submit ──
    form.addEventListener('submit', function(e) {
        if (!validateStep1() || !validateStep2() || !validateStep3()) {
            e.preventDefault();
            return;
        }
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });

    // ── Shake ──
    function shakeCard() {
        const card = document.getElementById('registerCard');
        card.style.animation = 'none';
        card.offsetHeight;
        card.style.animation = 'cardShake 0.4s ease';
    }

    // ── Debounce ──
    function debounce(fn, ms) {
        let t;
        return function() { clearTimeout(t); t = setTimeout(() => fn.apply(this, arguments), ms); };
    }

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-toast').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.4s, transform 0.4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px)';
            setTimeout(() => el.remove(), 400);
        }, 6000);
    });
});

// Shake keyframe
const s = document.createElement('style');
s.textContent = '@keyframes cardShake{0%,100%{transform:translateX(0)}20%{transform:translateX(-8px)}40%{transform:translateX(8px)}60%{transform:translateX(-4px)}80%{transform:translateX(4px)}}';
document.head.appendChild(s);
</script>

</body>
</html>
