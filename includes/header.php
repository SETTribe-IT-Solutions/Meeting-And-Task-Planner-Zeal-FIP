<!-- includes/header.php -->
<?php
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['full_name'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? '';

$basePath = defined('APP_URL') ? APP_URL : '';
$currentLang = $_SESSION['lang'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  <title>Latur District | Meeting & Task Planner</title>
  <!-- Font Awesome 6 for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', 'Poppins', Roboto, system-ui, sans-serif;
      background:
        linear-gradient(rgba(244, 247, 252, 0.82), rgba(244, 247, 252, 0.82)),
        url('<?php echo $basePath; ?>/assets/image_e15bb67f.png') center / cover fixed no-repeat;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* main layout: sidebar + content area */
    .app-container {
      display: flex;
      flex: 1;
      min-height: 0; /* important for flex children */
    }

    /* ===== HEADER ===== */
    .header {
      background: linear-gradient(135deg, #0b3d5f 0%, #1a5f7a 100%);
      color: white;
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      position: sticky;
      top: 0;
      z-index: 100;
      flex-wrap: wrap;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .district-emblem {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background: #ffffff;
      object-fit: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(255, 255, 255, 0.9);
      box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    }

    .title-section h1 {
      font-size: 1.7rem;
      font-weight: 600;
      letter-spacing: 0.5px;
      line-height: 1.2;
      margin-bottom: 0;
    }

    .title-section .subtitle {
      font-size: 0.85rem;
      opacity: 0.9;
      font-weight: 400;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .date-badge {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(8px);
      padding: 0.5rem 1.2rem;
      border-radius: 30px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.1);
      padding: 0.4rem 1rem;
      border-radius: 30px;
      cursor: pointer;
      transition: background 0.2s;
      color: white;
      text-decoration: none;
    }

    .user-profile:hover {
      background: rgba(255, 255, 255, 0.25);
      color: white;
    }

    .user-profile i {
      font-size: 1.3rem;
    }

    .notification-icon {
      position: relative;
      font-size: 1.3rem;
      margin-right: 8px;
      cursor: pointer;
    }

    .notification-badge {
      position: absolute;
      top: -6px;
      right: -8px;
      background: #f97316;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
      width: 270px;
      background: #ffffff;
      box-shadow: 2px 0 15px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      padding: 1.5rem 0.8rem;
      border-right: 1px solid #e9eef3;
      transition: all 0.2s;
    }

    .nav-menu {
      list-style: none;
      margin-top: 1rem;
      flex: 1;
      padding-left: 0;
    }

    .nav-item {
      margin-bottom: 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 0.8rem 1.2rem;
      border-radius: 12px;
      color: #2c3e50;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.2s ease;
      font-size: 0.95rem;
    }

    .nav-link i {
      width: 22px;
      font-size: 1.1rem;
      text-align: center;
      color: #5e6f7d;
    }

    .nav-link:hover {
      background: #eef6ff;
      color: #0b3d5f;
      transform: translateX(4px);
    }

    .nav-link.active {
      background: #0b3d5f;
      color: white;
      box-shadow: 0 6px 12px rgba(11, 61, 95, 0.3);
      font-weight: 600;
    }

    .nav-link.active i {
      color: #f9b81b;
    }

    .sidebar-footer {
      margin-top: auto;
      border-top: 1px solid #e0e7ef;
      padding-top: 1.2rem;
      font-size: 0.8rem;
      color: #64748b;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .latur-badge {
      background: #fef9e7;
      border-radius: 14px;
      padding: 0.8rem;
      margin: 0 0.5rem 0.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid #f9b81b30;
    }

    .latur-badge i {
      color: #f97316;
      font-size: 1.4rem;
    }

    /* main content placeholder */
    .main-content {
      flex: 1;
      padding: 2rem;
      background: rgba(248, 250, 253, 0.88);
      overflow-y: auto;
    }

    @media (max-width: 750px) {
      .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
      .sidebar {
        width: 80px;
      }
      .nav-link span {
        display: none;
      }
      .nav-link {
        justify-content: center;
        padding: 0.8rem;
      }
      .latur-badge span,
      .sidebar-footer span {
        display: none;
      }
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header class="header">
    <div class="logo-area">
      <img class="district-emblem" src="<?php echo $basePath; ?>/assets/photo_1763098684.jpg" alt="Latur Municipal Corporation logo">
      <div class="title-section">
        <h1>Latur District</h1>
        <div class="subtitle">
          <i class="fas fa-map-pin"></i> Meeting & Task Planner
        </div>
      </div>
    </div>
    <div class="header-actions">
      <div class="date-badge">
        <i class="far fa-calendar-alt"></i>
        <span id="liveDate"></span>
      </div>
      <div class="notification-icon">
        <i class="far fa-bell"></i>
        <span class="notification-badge">3</span>
      </div>
      <?php if ($isLoggedIn): ?>
      <div class="dropdown">
          <a href="#" class="user-profile dropdown-toggle text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <i class="fas fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/modules/users/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?php echo $basePath; ?>/controllers/LogoutController.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
          </ul>
      </div>
      <?php else: ?>
      <a href="<?php echo $basePath; ?>/modules/users/login.php" class="user-profile">
        <i class="fas fa-sign-in-alt"></i>
        <span>Login</span>
      </a>
      <?php endif; ?>
    </div>
  </header>

  <!-- APP CONTAINER: SIDEBAR + MAIN -->
  <div class="app-container">
    <?php if ($isLoggedIn): ?>
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="latur-badge">
        <i class="fas fa-city"></i>
        <span><strong>Latur Division</strong><br><small>Maharashtra</small></span>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'meetings') !== false ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>Meetings</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/tasks/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tasks') !== false ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>
            <span>Tasks</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/attendance/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'attendance') !== false ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Attendance</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/reports/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
          </a>
        </li>
        <?php if (isAdmin()): ?>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/users/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'users/index') !== false ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i>
            <span>Administration</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="sidebar-footer">
        <div style="display: flex; align-items: center; gap: 6px; padding: 0 0.5rem;">
          <i class="fas fa-clock"></i>
          <span>Next meeting: 10:30 AM</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; padding: 0 0.5rem;">
          <i class="fas fa-check-circle" style="color: #16a34a;"></i>
          <span>4 tasks due today</span>
        </div>
      </div>
    </aside>
    <?php endif; ?>

    <!-- MAIN CONTENT AREA -->
    <main class="main-content <?php echo !$isLoggedIn ? 'w-100' : ''; ?>">
