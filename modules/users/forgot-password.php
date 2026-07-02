<?php
// modules/users/forgot-password.php
// Forgot Password Page - User requests password reset

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
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
    <title>Forgot Password - Meeting & Task Planner | Government of Maharashtra</title>
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

        .forgot-password-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .forgot-password-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .forgot-password-header {
            background: linear-gradient(135deg, var(--navy-primary) 0%, var(--navy-light) 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .forgot-password-header h2 {
            margin: 0 0 10px 0;
            font-weight: 700;
            font-size: 24px;
        }

        .forgot-password-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .forgot-password-body {
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
        }

        .form-control:focus {
            border-color: var(--navy-light);
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
            outline: none;
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

        .forgot-password-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 13px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }

        .forgot-password-footer a {
            color: var(--navy-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password-footer a:hover {
            color: var(--navy-primary);
            text-decoration: underline;
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

        .info-box i {
            margin-right: 8px;
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
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(500px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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

    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <div class="forgot-password-header">
                <h2><i class="fas fa-lock"></i></h2>
                <h2>Forgot Password?</h2>
                <p>Don't worry! We'll help you reset it.</p>
            </div>

            <div class="forgot-password-body">
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    Enter your registered email address. We'll send you a link to reset your password.
                </div>

                <form id="forgotPasswordForm" method="POST" action="../../controllers/PasswordResetController.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="send_reset_email">

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your registered email"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner-border spinner-border-sm text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p style="margin-top: 10px; font-size: 13px;">Sending reset link...</p>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
            </div>

            <div class="forgot-password-footer">
                Remember your password? <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value.trim();
        const submitBtn = document.getElementById('submitBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');

        if (!email) {
            showSmartAlert('Please enter your email address.', 'error', 'Validation Error');
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
                document.getElementById('forgotPasswordForm').reset();
                
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
