<?php
// Login UI page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In — Meeting & Task Planner</title>
  <link rel="stylesheet" href="../../assets/css/login.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="bg">
    <div class="login-container">
      <div class="brand">
        <img src="../../assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <h1>Meeting & Task Planner</h1>
        <p class="muted">Sign in to continue</p>
      </div>

      <form class="login-card" id="loginForm" autocomplete="off">
        <div id="message" class="message"></div>

        <div class="field">
          <label for="username">Username</label>
          <div class="input-group">
            <i class="fas fa-user"></i>
            <input id="username" name="username" type="text" placeholder="Enter username" minlength="3" maxlength="50" required>
            <span class="error-text" id="usernameError"></span>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input id="password" name="password" type="password" placeholder="Enter password" minlength="6" maxlength="255" required>
            <button type="button" id="togglePwd" class="toggle-btn" aria-label="Show password">
              <i class="fas fa-eye"></i>
            </button>
            <span class="error-text" id="passwordError"></span>
          </div>
        </div>

        <div class="actions">
          <label class="checkbox">
            <input type="checkbox" name="remember">
            <span>Remember me</span>
          </label>
          <a class="forgot" href="#">Forgot?</a>
        </div>

        <button class="submit" type="submit" id="submitBtn">Sign In</button>

        <p class="signup">Don't have an account? <a href="#">Request access</a></p>
      </form>

      <div class="illustration"></div>
    </div>
  </div>

<script>
  // Toggle password visibility
  document.getElementById('togglePwd').addEventListener('click', function(e){
    e.preventDefault();
    var p = document.getElementById('password');
    var icon = this.querySelector('i');
    if(p.type === 'password'){ 
      p.type = 'text'; 
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    }
    else { 
      p.type = 'password'; 
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  });

  // Client-side validation
  function validateUsername(username) {
    if (!username) return 'Username is required';
    if (username.length < 3) return 'Username must be at least 3 characters';
    if (username.length > 50) return 'Username must not exceed 50 characters';
    if (!/^[a-zA-Z0-9_@.-]+$/.test(username)) return 'Username can only contain letters, numbers, _, @, ., and -';
    return '';
  }

  function validatePassword(password) {
    if (!password) return 'Password is required';
    if (password.length < 6) return 'Password must be at least 6 characters';
    if (password.length > 255) return 'Password is too long';
    return '';
  }

  // Real-time validation
  document.getElementById('username').addEventListener('blur', function() {
    const error = validateUsername(this.value);
    document.getElementById('usernameError').textContent = error;
    this.classList.toggle('invalid', !!error);
  });

  document.getElementById('password').addEventListener('blur', function() {
    const error = validatePassword(this.value);
    document.getElementById('passwordError').textContent = error;
    this.classList.toggle('invalid', !!error);
  });

  // Handle form submission
  document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const submitBtn = document.getElementById('submitBtn');
    const messageEl = document.getElementById('message');
    
    // Validate inputs
    const usernameError = validateUsername(username);
    const passwordError = validatePassword(password);
    
    if (usernameError || passwordError) {
      document.getElementById('usernameError').textContent = usernameError;
      document.getElementById('passwordError').textContent = passwordError;
      document.getElementById('username').classList.toggle('invalid', !!usernameError);
      document.getElementById('password').classList.toggle('invalid', !!passwordError);
      return;
    }
    
    const formData = new FormData(this);
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in...';
    messageEl.textContent = '';
    messageEl.className = 'message';
    
    try {
      const response = await fetch('../../database/login_db.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        messageEl.textContent = data.message;
        messageEl.className = 'message success';
        // Redirect after 1 second
        setTimeout(() => {
          window.location.href = data.redirect;
        }, 1000);
      } else {
        messageEl.textContent = data.message;
        messageEl.className = 'message error';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In';
      }
    } catch (error) {
      messageEl.textContent = 'An error occurred. Please try again.';
      messageEl.className = 'message error';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Sign In';
      console.error('Login error:', error);
    }
  });
</script>
</body>
</html>
