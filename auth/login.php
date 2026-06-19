<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'organizer') {
        header('Location: ../dashboards/organizer.php');
        exit();
    }

    if ($_SESSION['role'] === 'collector') {
        header('Location: ../dashboards/collector.php');
        exit();
    }

    header('Location: ../dashboards/employee.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting & Task Planner - Login</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }
        
        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            position: relative;
            z-index: 2;
            max-width: 1000px;
            width: 100%;
            padding: 20px;
        }
        
        .brand-section {
            animation: slideInLeft 0.8s ease-out;
        }
        
        .brand-section h1 {
            color: white;
            font-size: 3em;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .brand-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2em;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .features {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 1em;
        }
        
        .feature i {
            font-size: 1.5em;
            color: #ffd700;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInRight 0.8s ease-out;
        }
        
        .login-card h2 {
            text-align: center;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.8em;
        }
        
        .login-card .subtitle {
            text-align: center;
            color: #999;
            margin-bottom: 30px;
            font-size: 0.95em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            font-size: 0.95em;
        }
        
        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            color: #667eea;
            font-size: 1.2em;
            z-index: 3;
        }
        
        .form-control {
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 1.1em;
            z-index: 3;
            padding: 0;
        }
        
        .password-toggle:hover {
            color: #764ba2;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9em;
        }
        
        .form-check {
            margin: 0;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .form-check-label {
            cursor: pointer;
            color: #666;
            margin-left: 8px;
        }
        
        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.95em;
        }
        
        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 0.9em;
        }
        
        .success-message {
            background-color: #dcfce7;
            color: #16a34a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #16a34a;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .brand-section {
                display: none;
            }
            
            .brand-section h1 {
                font-size: 2em;
            }
            
            .login-card {
                padding: 30px 20px;
            }
            
            .login-card h2 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Brand Section -->
        <div class="brand-section">
            <h1><i class="fas fa-calendar-check"></i> Meeting & Task Planner</h1>
            <p>Organize your meetings and tasks efficiently in one place.</p>
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-check-circle"></i>
                    <span>Manage meetings and tasks</span>
                </div>
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <span>Collaborate with team members</span>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Track progress and productivity</span>
                </div>
                <div class="feature">
                    <i class="fas fa-lock"></i>
                    <span>Secure and reliable</span>
                </div>
            </div>
        </div>
        
        <!-- Login Card -->
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to continue to your dashboard</p>
            
            <div id="loginMessage" class="mb-3" style="display: none;"></div>

            <form action="login_process.php" method="POST" id="loginForm">
                <!-- Email Field -->
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-group-custom">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            name="email"
                            id="email"
                            class="form-control" 
                            placeholder="Enter your email"
                            required>
                    </div>
                    <div class="text-danger mt-1" id="emailError"></div>
                </div>
                
                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            name="password"
                            id="password" 
                            class="form-control" 
                            placeholder="Enter your password" 
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="text-danger mt-1" id="passwordError"></div>
                
                <!-- Remember & Forgot -->
                <div class="remember-forgot">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>
                
                <!-- Login Button -->
                <button type="submit" class="btn-login" id="loginButton">Sign In</button>
            </form>
            
            <!-- Sign Up Link -->
            <div class="signup-link">
                New here? <a href="../index.php">Back to home</a>
            </div>
        </div>
    </div>
    
    <script>
        // Function to toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Client-side validation functions
        function validateEmail(email) {
            if (!email) return 'Email is required.';
            if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) return 'Please enter a valid email address.';
            return '';
        }

        function validatePassword(password) {
            if (!password) return 'Password is required.';
            if (password.length < 6) return 'Password must be at least 6 characters long.';
            return '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const loginButton = document.getElementById('loginButton');
            const loginMessage = document.getElementById('loginMessage');

            // Clear previous error messages
            function clearErrors() {
                emailError.textContent = '';
                passwordError.textContent = '';
                emailInput.classList.remove('is-invalid');
                passwordInput.classList.remove('is-invalid');
                loginMessage.style.display = 'none';
                loginMessage.className = 'mb-3'; // Reset classes
            }

            // Display general message
            function showMessage(message, type) {
                loginMessage.textContent = message;
                loginMessage.className = `mb-3 ${type}-message`;
                loginMessage.style.display = 'block';
            }

            // Real-time validation on blur
            emailInput.addEventListener('blur', function() {
                const error = validateEmail(emailInput.value);
                emailError.textContent = error;
                emailInput.classList.toggle('is-invalid', !!error);
            });

            passwordInput.addEventListener('blur', function() {
                const error = validatePassword(passwordInput.value);
                passwordError.textContent = error;
                passwordInput.classList.toggle('is-invalid', !!error);
            });

            loginForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                clearErrors(); // Clear previous errors on new submission

                // Client-side validation before sending to server
                const emailValidationResult = validateEmail(emailInput.value);
                const passwordValidationResult = validatePassword(passwordInput.value);

                if (emailValidationResult) {
                    emailError.textContent = emailValidationResult;
                    emailInput.classList.add('is-invalid');
                }
                if (passwordValidationResult) {
                    passwordError.textContent = passwordValidationResult;
                    passwordInput.classList.add('is-invalid');
                }

                if (emailValidationResult || passwordValidationResult) {
                    showMessage('Please correct the errors in the form.', 'error');
                    return; // Stop submission if client-side validation fails
                }

                loginButton.disabled = true;
                loginButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing In...';

                const formData = new FormData(loginForm);

                try {
                    const response = await fetch('login_process.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        showMessage(data.message, 'error');
                        loginButton.disabled = false;
                        loginButton.innerHTML = 'Sign In';
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    showMessage('An unexpected error occurred. Please try again.', 'error');
                    loginButton.disabled = false;
                    loginButton.innerHTML = 'Sign In';
                }
            });
        });
    </script>
</body>
</html>
