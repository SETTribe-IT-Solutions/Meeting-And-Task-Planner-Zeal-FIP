<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  <title>Latur District | Login · Meeting & Task Planner</title>
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', 'Poppins', Roboto, system-ui, sans-serif;
      background: #f0f4f9;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 1.5rem;
    }

    /* background with Latur Municipal Corporation image */
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('https://lh3.googleusercontent.com/p/AF1QipP5XHmW_GAdbic9qYL0Wrcfcx_qRN3bHj1Wk1iC=s1360-w1360-h1020');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0.12;
      z-index: -1;
      pointer-events: none;
    }

    .login-container {
      width: 100%;
      max-width: 520px;
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(14px);
      border-radius: 28px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      padding: 2.5rem 2.2rem;
      border: 1px solid rgba(255, 255, 255, 0.5);
      animation: fadeSlide 0.6s ease;
    }

    @keyframes fadeSlide {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .emblem-circle {
      background: #f9b81b;
      width: 65px;
      height: 65px;
      border-radius: 50%;
      margin: 0 auto 0.8rem;
      background-image: url('https://lh3.googleusercontent.com/p/AF1QipP5XHmW_GAdbic9qYL0Wrcfcx_qRN3bHj1Wk1iC=s1360-w1360-h1020');
      background-size: cover;
      background-position: center;
      border: 3px solid white;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .login-header h2 {
      color: #0b3d5f;
      font-size: 1.9rem;
      font-weight: 700;
      letter-spacing: -0.3px;
    }

    .login-header p {
      color: #4b5563;
      margin-top: 0.3rem;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    .role-selector {
      display: flex;
      gap: 12px;
      margin: 1.8rem 0 1.5rem;
      justify-content: center;
    }

    .role-card {
      flex: 1;
      background: #f1f5f9;
      border-radius: 16px;
      padding: 0.8rem 0.4rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s;
      border: 2px solid transparent;
      font-weight: 500;
      color: #334155;
    }

    .role-card i {
      display: block;
      font-size: 1.6rem;
      margin-bottom: 6px;
      color: #475569;
    }

    .role-card.active {
      background: #0b3d5f;
      color: white;
      border-color: #f9b81b;
      box-shadow: 0 10px 18px rgba(11, 61, 95, 0.3);
      transform: translateY(-2px);
    }

    .role-card.active i {
      color: #f9b81b;
    }

    .role-card:hover:not(.active) {
      background: #e2e8f0;
    }

    .input-group {
      margin-bottom: 1.4rem;
      position: relative;
    }

    .input-group label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: 600;
      color: #1e293b;
      font-size: 0.9rem;
      letter-spacing: 0.3px;
    }

    .input-field {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 2.8rem;
      border: 1.5px solid #d1d5db;
      border-radius: 14px;
      font-size: 1rem;
      transition: 0.2s;
      background: white;
      outline: none;
    }

    .input-field:focus {
      border-color: #0b3d5f;
      box-shadow: 0 0 0 3px rgba(11, 61, 95, 0.2);
    }

    .input-group i {
      position: absolute;
      left: 14px;
      top: 42px;
      color: #6b7280;
      font-size: 1.1rem;
    }

    .forgot-row {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1.5rem;
    }

    .forgot-link {
      color: #0b3d5f;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .login-btn {
      width: 100%;
      background: #0b3d5f;
      color: white;
      border: none;
      padding: 0.95rem;
      border-radius: 30px;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background 0.25s, transform 0.1s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      letter-spacing: 0.4px;
      box-shadow: 0 8px 16px rgba(11, 61, 95, 0.25);
      margin-top: 0.5rem;
    }

    .login-btn:hover {
      background: #0f4a6b;
      transform: scale(1.02);
    }

    .alt-action {
      text-align: center;
      margin-top: 1.6rem;
      color: #4b5563;
      font-size: 0.9rem;
    }

    .alt-action a {
      color: #0b3d5f;
      font-weight: 700;
      text-decoration: none;
      margin-left: 4px;
    }

    .district-footer-note {
      text-align: center;
      margin-top: 1.8rem;
      font-size: 0.8rem;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 2rem 1.5rem;
      }
      .role-selector {
        gap: 8px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <div class="emblem-circle" title="Latur Municipal Corporation"></div>
      <h2>Latur District</h2>
      <p><i class="fas fa-calendar-check" style="color:#f9b81b;"></i> Meeting & Task Planner</p>
    </div>

    <!-- Role Selection: Collector / Organizer / Employee -->
    <div class="role-selector" id="roleSelector">
      <div class="role-card active" data-role="collector">
        <i class="fas fa-user-tie"></i>
        <span>Collector</span>
      </div>
      <div class="role-card" data-role="organizer">
        <i class="fas fa-calendar-alt"></i>
        <span>Organizer</span>
      </div>
      <div class="role-card" data-role="employee">
        <i class="fas fa-user-check"></i>
        <span>Employee</span>
      </div>
    </div>

    <!-- Login Form -->
    <form id="loginForm" onsubmit="handleLogin(event)">
      <div class="input-group">
        <label for="username">Username / ID</label>
        <i class="fas fa-user"></i>
        <input type="text" id="username" class="input-field" placeholder="Enter your ID" required autocomplete="off">
      </div>

      <div class="input-group">
        <label for="password">Password</label>
        <i class="fas fa-lock"></i>
        <input type="password" id="password" class="input-field" placeholder="••••••••" required>
      </div>

      <div class="forgot-row">
        <a href="#" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="login-btn" id="loginButton">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="alt-action">
      Need access? <a href="#">Request account</a>
    </div>
    <div class="district-footer-note">
      <i class="fas fa-map-pin" style="color:#f97316;"></i> Latur District Administration
    </div>
  </div>

  <script>
    // Role selection interaction
    const roleCards = document.querySelectorAll('.role-card');
    let selectedRole = 'collector'; // default

    roleCards.forEach(card => {
      card.addEventListener('click', function() {
        roleCards.forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        selectedRole = this.getAttribute('data-role');
        
        // Optional: update placeholder or button text based on role
        const loginBtn = document.getElementById('loginButton');
        const roleText = this.querySelector('span').innerText;
        loginBtn.innerHTML = `<i class="fas fa-sign-in-alt"></i> Sign In as ${roleText}`;
      });
    });

    // Handle login submission
    function handleLogin(event) {
      event.preventDefault();
      
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      
      if (!username || !password) {
        alert('Please fill in both username and password.');
        return;
      }

      // Simulate login based on role (demo purpose)
      console.log(`Login attempt: Role=${selectedRole}, Username=${username}`);
      
      // Show role-based message (in production, redirect to respective dashboard)
      switch(selectedRole) {
        case 'collector':
          alert(`✅ Welcome Collector ${username}!\nRedirecting to District Admin Dashboard...`);
          break;
        case 'organizer':
          alert(`📋 Welcome Organizer ${username}!\nMeeting coordination panel loading...`);
          break;
        case 'employee':
          alert(`👤 Welcome Employee ${username}!\nTask planner & assignments ready.`);
          break;
        default: break;
      }
      
      // Here you would typically redirect: window.location.href = '/dashboard';
      // For demonstration, we reset or keep focus.
      // Optionally clear fields (uncomment if desired):
      // document.getElementById('password').value = '';
    }

    // Set initial button text based on default active role
    window.addEventListener('DOMContentLoaded', () => {
      const activeCard = document.querySelector('.role-card.active');
      if (activeCard) {
        const roleText = activeCard.querySelector('span').innerText;
        document.getElementById('loginButton').innerHTML = `<i class="fas fa-sign-in-alt"></i> Sign In as ${roleText}`;
      }
    });
  </script>
</body>
</html>