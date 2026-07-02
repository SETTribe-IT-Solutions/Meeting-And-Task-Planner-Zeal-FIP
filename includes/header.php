<!-- includes/header.php -->
<?php
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['full_name'] ?? 'Guest';
$userRole = $_SESSION['role'] ?? '';

$basePath = defined('APP_URL') ? APP_URL : '';
$currentLang = $_SESSION['lang'] ?? 'en';
$_langEnUrl = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['lang' => 'en'])));
$_langMrUrl = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['lang' => 'mr'])));
$currentPath = $_SERVER['PHP_SELF'] ?? '';
$today = date('Y-m-d');
$notificationItems = [];
$notificationCount = 0;
$nextMeetingText = 'No upcoming meetings';
$tasksDueTodayText = '0 tasks due today';

if ($isLoggedIn) {
    try {
        $headerConn = getDBConnection();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userDepartment = $_SESSION['department'] ?? '';
        $nowTime = date('H:i:s');

        if ($userRole === 'Collector' || $userRole === 'Organizer') {
            // Both see all tasks — Organizer is super admin
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE due_date = ? AND status <> 'Completed'");
            $stmt->bind_param('s', $today);
        } else {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM tasks t WHERE t.due_date = ? AND t.status <> 'Completed' AND EXISTS (SELECT 1 FROM task_assignments ta WHERE ta.task_id = t.id AND ta.user_id = ?)");
            $stmt->bind_param('si', $today, $userId);
        }

        if ($stmt) {
            $stmt->execute();
            $dueTasksToday = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $tasksDueTodayText = $dueTasksToday . ' task' . ($dueTasksToday === 1 ? '' : 's') . ' due today';
            if ($dueTasksToday > 0) {
                $notificationItems[] = [
                    'icon' => 'fas fa-check-circle text-success',
                    'text' => $tasksDueTodayText,
                    'href' => $basePath . '/modules/tasks/index.php'
                ];
            }
        }

        if ($userRole === 'Collector' || $userRole === 'Organizer') {
            // Both see all meetings
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM meetings WHERE meeting_date = ? AND status <> 'Cancelled'");
            $stmt->bind_param('s', $today);
        } else {
            $stmt = $headerConn->prepare("SELECT COUNT(DISTINCT m.id) AS total FROM meetings m LEFT JOIN attendance a ON a.meeting_id = m.id WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date = ? AND m.status <> 'Cancelled'");
            $stmt->bind_param('sis', $userDepartment, $userId, $today);
        }

        if ($stmt) {
            $stmt->execute();
            $meetingsToday = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            if ($meetingsToday > 0) {
                $notificationItems[] = [
                    'icon' => 'fas fa-calendar-check text-primary',
                    'text' => $meetingsToday . ' meeting' . ($meetingsToday === 1 ? '' : 's') . ' scheduled today',
                    'href' => $basePath . '/modules/meetings/index.php'
                ];
            }
        }

        if ($userRole === 'Collector' || $userRole === 'Organizer') {
            // Both see next meeting from all meetings
            $stmt = $headerConn->prepare("SELECT id, title, meeting_date, meeting_time FROM meetings WHERE status <> 'Cancelled' AND (meeting_date > ? OR (meeting_date = ? AND meeting_time >= ?)) ORDER BY meeting_date ASC, meeting_time ASC LIMIT 1");
            $stmt->bind_param('sss', $today, $today, $nowTime);
        } else {
            $stmt = $headerConn->prepare("SELECT DISTINCT m.id, m.title, m.meeting_date, m.meeting_time FROM meetings m LEFT JOIN attendance a ON a.meeting_id = m.id WHERE (m.department = ? OR a.user_id = ?) AND m.status <> 'Cancelled' AND (m.meeting_date > ? OR (m.meeting_date = ? AND m.meeting_time >= ?)) ORDER BY m.meeting_date ASC, m.meeting_time ASC LIMIT 1");
            $stmt->bind_param('sisss', $userDepartment, $userId, $today, $today, $nowTime);
        }

        if ($stmt) {
            $stmt->execute();
            $nextMeeting = $stmt->get_result()->fetch_assoc();
            if ($nextMeeting) {
                $nextMeetingText = formatTime12Hour($nextMeeting['meeting_time']) . ' - ' . $nextMeeting['title'];
            }
        }

        $notificationCount = count($notificationItems);
    } catch (Throwable $e) {
        error_log('Header summary failed: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
  <title>Latur District | Meeting & Task Planner</title>
  <meta name="description" content="Official Meeting & Task Planner for Latur District Administration. Coordinate meetings, assign tasks, and track progress.">
  <script>(function(){var s={'-1':'13px','0':'16px','1':'19px'};document.documentElement.style.fontSize=s[localStorage.getItem('fontSize')||'0']||'16px';}());</script>
  <!-- Bootstrap 5.3 CSS (local) -->
  <link href="<?php echo $basePath; ?>/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome 6 for icons (local) -->
  <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/vendor/fontawesome/css/all.min.css">
  <!-- Bootstrap Icons (local) -->
  <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
  <!-- Custom Design System CSS -->
  <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/custom.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', 'Poppins', 'Segoe UI', Roboto, system-ui, sans-serif;
      background:
        linear-gradient(rgba(240, 244, 248, 0.88), rgba(240, 244, 248, 0.88)),
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
    /* ===== UTILITY BAR ===== */
    .site-top-bar {
      position: sticky;
      top: 0;
      z-index: 200;
    }

    .utility-bar {
      background: #072d46;
      padding: 5px 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }

    .util-group {
      display: flex;
      align-items: center;
      gap: 3px;
    }

    .util-btn {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.14);
      color: rgba(255,255,255,0.72);
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 0.72rem;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      line-height: 1.8;
      font-family: 'Inter', sans-serif;
      display: inline-block;
    }

    .util-btn:hover {
      background: rgba(255,255,255,0.18);
      color: white;
      text-decoration: none;
    }

    .util-btn.util-active {
      background: rgba(255,255,255,0.9);
      color: #072d46;
      font-weight: 600;
      border-color: transparent;
    }

    .util-sep {
      color: rgba(255,255,255,0.22);
      padding: 0 3px;
      user-select: none;
      font-size: 0.8rem;
    }

    .header {
      background: linear-gradient(135deg, #0b3d5f 0%, #1a5f7a 50%, #0b3d5f 100%);
      background-size: 200% 200%;
      animation: gradientShift 8s ease infinite;
      color: white;
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      flex-wrap: wrap;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
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
      transition: transform 0.3s ease;
    }

    .logo-area:hover .district-emblem {
      transform: scale(1.08);
    }

    .title-section h1 {
      font-size: 1.7rem;
      font-weight: 700;
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
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 0.5rem 1.2rem;
      border-radius: 30px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: background 0.3s ease;
    }

    .date-badge:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255, 255, 255, 0.1);
      border: 0;
      padding: 0.4rem 1rem;
      border-radius: 30px;
      cursor: pointer;
      transition: all 0.3s ease;
      color: white;
      text-decoration: none;
    }

    .user-profile:hover {
      background: rgba(255, 255, 255, 0.25);
      color: white;
      transform: translateY(-1px);
    }

    .user-profile i {
      font-size: 1.3rem;
    }

    .user-profile.dropdown-toggle::after,
    .notification-icon.dropdown-toggle::after {
      display: none;
    }

    .notification-icon {
      position: relative;
      font-size: 1.3rem;
      margin-right: 8px;
      cursor: pointer;
      transition: transform 0.3s ease;
      background: transparent;
      border: 0;
      color: white;
      padding: 0.25rem;
    }

    .notification-icon:hover,
    .notification-icon:focus {
      transform: scale(1.15);
      color: white;
      outline: none;
    }

    .notification-badge {
      position: absolute;
      top: -6px;
      right: -8px;
      background: linear-gradient(135deg, #f97316, #ef4444);
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: 0.7rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      animation: pulse 2s ease-in-out infinite;
    }

    .notification-menu {
      min-width: 290px;
      max-width: 340px;
    }

    .notification-menu .dropdown-item {
      white-space: normal;
      line-height: 1.35;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.15); }
    }

    /* Mobile sidebar toggle */
    .sidebar-toggle-btn {
      display: none;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.25);
      color: white;
      border-radius: 8px;
      padding: 6px 10px;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background 0.3s;
    }
    .sidebar-toggle-btn:hover {
      background: rgba(255,255,255,0.3);
    }

    .sidebar-backdrop {
      display: none;
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
      width: 270px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      box-shadow: 2px 0 20px rgba(0, 0, 0, 0.06);
      display: flex;
      flex-direction: column;
      padding: 1.5rem 0.8rem;
      border-right: 1px solid #e2e8f0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-menu {
      list-style: none;
      margin-top: 1rem;
      flex: 1;
      padding-left: 0;
    }

    .nav-item {
      margin-bottom: 0.4rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 0.75rem 1.2rem;
      border-radius: 12px;
      color: #2c3e50;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-size: 0.95rem;
      position: relative;
      overflow: hidden;
    }

    .nav-link i {
      width: 22px;
      font-size: 1.1rem;
      text-align: center;
      color: #5e6f7d;
      transition: color 0.3s;
    }

    .nav-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(11, 61, 95, 0.04), transparent);
      transition: left 0.5s;
    }

    .nav-link:hover::before {
      left: 100%;
    }

    .nav-link:hover {
      background: linear-gradient(135deg, #eef6ff, #e8f4fd);
      color: #0b3d5f;
      transform: translateX(4px);
      box-shadow: 0 2px 8px rgba(11, 61, 95, 0.06);
    }

    .nav-link:hover i {
      color: #0b3d5f;
    }

    .nav-link.active {
      background: linear-gradient(135deg, #0b3d5f, #1a5f7a);
      color: white;
      box-shadow: 0 6px 16px rgba(11, 61, 95, 0.3);
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
      background: linear-gradient(135deg, #fef9e7, #fff8e1);
      border-radius: 14px;
      padding: 0.8rem;
      margin: 0 0.5rem 0.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid rgba(249, 184, 27, 0.2);
      transition: transform 0.3s ease;
    }

    .latur-badge:hover {
      transform: scale(1.02);
    }

    .latur-badge i {
      color: #f97316;
      font-size: 1.4rem;
    }

    /* main content placeholder */
    .main-content {
      flex: 1;
      padding: 2rem;
      background: linear-gradient(135deg, #f0f4f8 0%, #e8eef4 50%, #f0f4f8 100%);
      overflow-y: auto;
    }

    /* Dropdown menu styling */
    .dropdown-menu {
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.12);
      border-radius: 12px;
      padding: 0.5rem;
      animation: fadeInDown 0.2s ease;
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .dropdown-item {
      border-radius: 8px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .dropdown-item:hover {
      background: #eef6ff;
      transform: translateX(4px);
    }

    /* ── Medium screens (1280px–1440px): compact header + narrower sidebar ── */
    @media (max-width: 1440px) {
      .title-section h1 { font-size: 1.4rem; }
      .sidebar { width: 240px; }
    }

    @media (max-width: 1280px) {
      .title-section h1 { font-size: 1.25rem; }
      .title-section .subtitle { font-size: 0.78rem; }
      .header { padding: 0.6rem 1.2rem; }
      .sidebar { width: 220px; }
      .header-actions { gap: 12px; }
      .date-badge { padding: 0.4rem 0.8rem; font-size: 0.82rem; }
    }

    @media (max-width: 1100px) {
      /* Hide date text, show only icon to reclaim horizontal space */
      .header-actions .date-badge span { display: none; }
      .header-actions .date-badge { padding: 0.4rem 0.7rem; }
      .title-section h1 { font-size: 1.1rem; }
    }

    @media (max-width: 750px) {
      .utility-bar { padding: 4px 1rem; }
      .util-btn { padding: 2px 7px; font-size: 0.68rem; }

      .header {
        flex-direction: row;
        align-items: center;
        gap: 10px;
        padding: 0.6rem 1rem;
      }
      .sidebar-toggle-btn {
        display: inline-flex;
      }
      .sidebar {
        position: fixed;
        left: -280px;
        top: 0;
        height: 100vh;
        z-index: 200;
        box-shadow: 4px 0 20px rgba(0,0,0,0.15);
      }
      .sidebar.sidebar-open {
        left: 0;
      }
      .sidebar-backdrop.show {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.35);
        z-index: 150;
      }
      .nav-link span {
        display: inline;
      }
      .header-actions .date-badge span { display: none; }
      .header-actions .date-badge i { margin: 0; }
      .title-section h1 { font-size: 1.2rem; }
    }
  </style>
</head>
<body>
  <!-- UTILITY BAR + HEADER (sticky wrapper) -->
  <div class="site-top-bar">
    <div class="utility-bar">
      <div class="util-group">
        <button class="util-btn font-btn" data-size="-1" title="Decrease font size"><?php echo $currentLang === 'en' ? 'A-' : 'अ-'; ?></button>
        <button class="util-btn font-btn" data-size="0"  title="Default font size"><?php echo $currentLang === 'en' ? 'A' : 'अ'; ?></button>
        <button class="util-btn font-btn" data-size="1"  title="Increase font size"><?php echo $currentLang === 'en' ? 'A+' : 'अ+'; ?></button>
      </div>
      <div class="util-group">
        <a href="<?php echo $_langEnUrl; ?>" class="util-btn <?php echo $currentLang === 'en' ? 'util-active' : ''; ?>">EN</a>
        <span class="util-sep">|</span>
        <a href="<?php echo $_langMrUrl; ?>" class="util-btn <?php echo $currentLang === 'mr' ? 'util-active' : ''; ?>">मराठी</a>
      </div>
    </div>
  <header class="header">
    <div class="d-flex align-items-center gap-3">
      <?php if ($isLoggedIn): ?>
      <button class="sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Toggle sidebar" aria-controls="appSidebar" aria-expanded="false">
        <i class="fas fa-bars"></i>
      </button>
      <?php endif; ?>
      <a class="logo-area text-white text-decoration-none" href="<?php echo $isLoggedIn ? $basePath . '/index.php' : $basePath . '/modules/users/login.php'; ?>" aria-label="Go to <?php echo $isLoggedIn ? 'dashboard' : 'login page'; ?>">
        <img class="district-emblem" src="<?php echo $basePath; ?>/assets/photo_1763098684.jpg" alt="Latur Municipal Corporation logo">
        <div class="title-section">
          <h1>Latur District</h1>
          <div class="subtitle">
            <i class="fas fa-map-pin"></i> <?php echo __('common.meeting_task_planner'); ?>
          </div>
        </div>
      </a>
    </div>
    <div class="header-actions">
      <div class="date-badge">
        <i class="far fa-calendar-alt"></i>
        <span id="liveDate"></span>
      </div>
      <div class="dropdown">
        <button type="button" class="notification-icon dropdown-toggle" id="notificationDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="View notifications">
          <i class="far fa-bell"></i>
          <?php if ($notificationCount > 0): ?>
            <span class="notification-badge"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end notification-menu shadow border-0" aria-labelledby="notificationDropdown">
          <li><h6 class="dropdown-header">Notifications</h6></li>
          <?php if (!$isLoggedIn): ?>
            <li><span class="dropdown-item-text text-muted small">Log in to view meeting and task notifications.</span></li>
          <?php elseif (!empty($notificationItems)): ?>
            <?php foreach ($notificationItems as $item): ?>
              <li>
                <a class="dropdown-item d-flex align-items-start gap-2" href="<?php echo htmlspecialchars($item['href']); ?>">
                  <i class="<?php echo htmlspecialchars($item['icon']); ?> mt-1"></i>
                  <span><?php echo htmlspecialchars($item['text']); ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><span class="dropdown-item-text text-muted small">No urgent notifications for today.</span></li>
          <?php endif; ?>
          <li><hr class="dropdown-divider"></li>
          <?php if ($isLoggedIn): ?>
            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/modules/tasks/index.php"><i class="fas fa-list-check me-2"></i> View all tasks</a></li>
            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/modules/meetings/index.php"><i class="fas fa-calendar-days me-2"></i> View all meetings</a></li>
          <?php else: ?>
            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/modules/users/login.php"><i class="fas fa-sign-in-alt me-2"></i> Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <?php if ($isLoggedIn): ?>
      <div class="dropdown">
          <button type="button" class="user-profile dropdown-toggle text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <i class="fas fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
          </button>
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
  </div><!-- /.site-top-bar -->

  <!-- APP CONTAINER: SIDEBAR + MAIN -->
  <div class="app-container">
    <?php if ($isLoggedIn): ?>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="appSidebar">
      <div class="latur-badge">
        <i class="fas fa-city"></i>
        <span><strong><?php echo __('common.latur_division'); ?></strong><br><small><?php echo __('common.maharashtra'); ?></small></span>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/index.php" class="nav-link <?php echo (basename($currentPath) == 'index.php' && strpos($currentPath, '/modules/') === false) ? 'active' : ''; ?>" <?php echo (basename($currentPath) == 'index.php' && strpos($currentPath, '/modules/') === false) ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-tachometer-alt"></i>
            <span><?php echo __('common.dashboard'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="nav-link <?php echo strpos($currentPath, 'meetings') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'meetings') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-calendar-check"></i>
            <span><?php echo __('common.meetings'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/tasks/index.php" class="nav-link <?php echo strpos($currentPath, 'tasks') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'tasks') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-tasks"></i>
            <span><?php echo __('common.tasks'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/attendance/index.php" class="nav-link <?php echo strpos($currentPath, 'attendance') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'attendance') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-users"></i>
            <span><?php echo __('common.attendance'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/reports/index.php" class="nav-link <?php echo strpos($currentPath, 'reports') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'reports') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-chart-bar"></i>
            <span><?php echo __('common.reports'); ?></span>
          </a>
        </li>
        <?php if (isOrganizer()): ?>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/users/index.php" class="nav-link <?php echo strpos($currentPath, 'users/index') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'users/index') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-user-cog"></i>
            <span><?php echo __('common.users'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/departments/index.php" class="nav-link <?php echo strpos($currentPath, 'departments') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'departments') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-building"></i>
            <span><?php echo __('common.departments'); ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/pages/index.php" class="nav-link <?php echo strpos($currentPath, 'pages') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'pages') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-globe"></i>
            <span>Portal Pages</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="sidebar-footer"></div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- MAIN CONTENT AREA -->
    <main class="main-content <?php echo !$isLoggedIn ? 'w-100' : ''; ?>">
