<?php
// modules/users/reset-password.php
// Password Reset Page - User resets their password using token

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/PasswordResetController.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

// Get token from URL
$token = $_GET['token'] ?? '';
$tokenValid = false;
$tokenData = null;

if (!empty($token)) {
    $tokenData = PasswordResetController::verifyResetToken($token);
    $tokenValid = $tokenData['valid'] ?? false;
}

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Meeting & Task Planner | Government of Maharashtra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --tricolor-saffron: #FF9933;
            --tricolor-white: #FFFFFF;
            --tricolor-green: #138808;
            --navy-primary: #003366;
            --navy-dark: #00254d;
            --navy-light: #004080;
        }

        * {
            font-family: 'Inter', 'Noto Sans Devanagari', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .reset-password-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .reset-password-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .reset-password-header {
            background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-light) 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .reset-password-header h2 {
            margin: 0 0 10px 0;
            font-weight: 700;
            font-size: 24px;
        }

        .reset-password-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .reset-password-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--navy-primary);
            font-size: 14px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s;
            position: relative;
        }

        .form-control:focus {
            border-color: var(--navy-light);
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
            outline: none;
        }

        .password-input-group {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: var(--navy-light);
            border: none;
            background: none;
            font-size: 16px;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--tricolor-green) 0%, #0f6b1d 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(19, 136, 8, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .reset-password-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }

        .reset-password-footer a {
            color: var(--navy-light);
            text-decoration: none;
            font-weight: 600;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }

        .strength-meter {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 4px;
        }

        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .strength-meter-fill.strength-weak {
            width: 33%;
            background: #dc3545;
        }

        .strength-meter-fill.strength-medium {
            width: 66%;
            background: #ffc107;
        }

        .strength-meter-fill.strength-strong {
            width: 100%;
            background: #138808;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid var(--navy-light);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: var(--navy-primary);
        }

        .info-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }

        .info-box li {
            margin: 5px 0;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .spinner-border-sm {
            width: 1.5rem;
            height: 1.5rem;
        }

        /* Smart Alert Styles */
        .smart-alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 450px;
        }

        .smart-alert {
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .smart-alert.alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .smart-alert.alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .smart-alert-content {
            flex: 1;
        }

        .smart-alert-title {
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 4px 0;
        }

        .smart-alert-message {
            font-size: 13px;
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <!-- Smart Alert Container -->
    <div class="smart-alert-container" id="alertContainer"></div>

    <div class="reset-password-container">
        <div class="reset-password-card">
            <div class="reset-password-header">
                <h2><i class="fas fa-key"></i></h2>
                <h2>Reset Your Password</h2>
                <p>Create a new, strong password for your account</p>
            </div>

            <div class="reset-password-body">
                <?php if (!$tokenValid): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Invalid or Expired Link</strong>
                        <p style="margin: 8px 0 0 0; font-size: 13px;">
                            This password reset link is invalid or has expired. Please request a new one.
                        </p>
                    </div>
                    <a href="forgot-password.php" class="btn btn-primary w-100">Request New Reset Link</a>
                <?php else: ?>
                    <div class="info-box">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>Contains uppercase letters (A-Z)</li>
                            <li>Contains lowercase letters (a-z)</li>
                            <li>Contains numbers (0-9)</li>
                            <li>Contains special characters (!@#$%^&*)</li>
                        </ul>
                    </div>

                    <form id="resetPasswordForm" method="POST" action="../../controllers/PasswordResetController.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="newPassword">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <div class="password-input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="newPassword" 
                                    name="new_password" 
                                    placeholder="Enter your new password"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('newPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <small id="passwordStrength">Password strength: —</small>
                                <div class="strength-meter">
                                    <div class="strength-meter-fill" id="strengthMeter"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <div class="password-input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirmPassword" 
                                    name="confirm_password" 
                                    placeholder="Confirm your new password"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner-border spinner-border-sm text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p style="margin-top: 10px; font-size: 13px;">Resetting password...</p>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-check"></i> Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="reset-password-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const button = event.target.closest('button');
        const icon = button.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function calculatePasswordStrength(password) {
        let strength = 0;
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/.test(password)
        };

        Object.values(checks).forEach(check => {
            if (check) strength++;
        });

        return strength;
    }

    function updatePasswordStrength() {
        const password = document.getElementById('newPassword').value;
        const strength = calculatePasswordStrength(password);
        const strengthText = document.getElementById('passwordStrength');
        const strengthMeter = document.getElementById('strengthMeter');

        // Remove all strength classes
        strengthMeter.classList.remove('strength-weak', 'strength-medium', 'strength-strong');

        if (password.length === 0) {
            strengthText.textContent = 'Password strength: —';
            strengthMeter.style.width = '0';
        } else if (strength <= 2) {
            strengthText.textContent = 'Password strength: Weak ❌';
            strengthMeter.classList.add('strength-weak');
        } else if (strength <= 3) {
            strengthText.textContent = 'Password strength: Medium ⚠️';
            strengthMeter.classList.add('strength-medium');
        } else {
            strengthText.textContent = 'Password strength: Strong ✓';
            strengthMeter.classList.add('strength-strong');
        }
    }

    document.getElementById('newPassword').addEventListener('input', updatePasswordStrength);

    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const submitBtn = document.getElementById('submitBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');

        // Validation
        if (!newPassword || !confirmPassword) {
            showSmartAlert('Please fill in all fields.', 'error', 'Validation Error');
            return;
        }

        if (newPassword !== confirmPassword) {
            showSmartAlert('Passwords do not match.', 'error', 'Validation Error');
            return;
        }

        const strength = calculatePasswordStrength(newPassword);
        if (strength < 4) {
            showSmartAlert('Password does not meet security requirements.', 'error', 'Weak Password');
            return;
        }

        // Show loading spinner
        submitBtn.disabled = true;
        loadingSpinner.style.display = 'block';

        try {
            const formData = new FormData(this);
            const response = await fetch('../../controllers/PasswordResetController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                showSmartAlert(data.message, 'success', 'Success');
                
                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            } else {
                showSmartAlert(data.message, 'error', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            showSmartAlert('An error occurred. Please try again.', 'error', 'Error');
        } finally {
            submitBtn.disabled = false;
            loadingSpinner.style.display = 'none';
        }
    });

    function showSmartAlert(message, type = 'info', title = '') {
        const container = document.getElementById('alertContainer');
        if (!container) return;

        const icons = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>',
            'info': '<i class="fas fa-info-circle"></i>'
        };

        const iconHtml = icons[type] || icons['info'];

        const alertHtml = `
            <div class="smart-alert alert-${type}">
                <div class="smart-alert-icon">${iconHtml}</div>
                <div class="smart-alert-content">
                    <div class="smart-alert-title">${title}</div>
                    <p class="smart-alert-message">${message}</p>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', alertHtml);

        setTimeout(() => {
            const alerts = container.querySelectorAll('.smart-alert');
            if (alerts.length > 0) {
                alerts[0].remove();
            }
        }, 5000);
    }
    </script>
</body>
</html>
