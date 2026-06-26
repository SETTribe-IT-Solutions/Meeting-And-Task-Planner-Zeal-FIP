<!-- includes/header.php -->
<?php
require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Get user name from session with proper fallback
$userName = 'Guest';
if ($isLoggedIn) {
    if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
        $userName = $_SESSION['full_name'];
    } elseif (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $userName = $_SESSION['username'];
    } elseif (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $userName = $_SESSION['name'];
    } else {
        $userName = getUserNameFromDatabase($_SESSION['user_id']);
    }
}

$userRole = $_SESSION['role'] ?? '';

$basePath = defined('APP_URL') ? APP_URL : '';
$currentLang = $_SESSION['lang'] ?? 'en';
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

        if ($userRole === 'Collector') {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE due_date = ? AND status <> 'Completed'");
            $stmt->bind_param('s', $today);
        } elseif ($userRole === 'Organizer') {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.due_date = ? AND t.status <> 'Completed'");
            $stmt->bind_param('is', $userId, $today);
        } else {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE assigned_to = ? AND due_date = ? AND status <> 'Completed'");
            $stmt->bind_param('is', $userId, $today);
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

        if ($userRole === 'Collector') {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM meetings WHERE meeting_date = ? AND status <> 'Cancelled'");
            $stmt->bind_param('s', $today);
        } elseif ($userRole === 'Organizer') {
            $stmt = $headerConn->prepare("SELECT COUNT(*) AS total FROM meetings WHERE organizer_id = ? AND meeting_date = ? AND status <> 'Cancelled'");
            $stmt->bind_param('is', $userId, $today);
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

        if ($userRole === 'Collector') {
            $stmt = $headerConn->prepare("SELECT id, title, meeting_date, meeting_time FROM meetings WHERE status <> 'Cancelled' AND (meeting_date > ? OR (meeting_date = ? AND meeting_time >= ?)) ORDER BY meeting_date ASC, meeting_time ASC LIMIT 1");
            $stmt->bind_param('sss', $today, $today, $nowTime);
        } elseif ($userRole === 'Organizer') {
            $stmt = $headerConn->prepare("SELECT id, title, meeting_date, meeting_time FROM meetings WHERE organizer_id = ? AND status <> 'Cancelled' AND (meeting_date > ? OR (meeting_date = ? AND meeting_time >= ?)) ORDER BY meeting_date ASC, meeting_time ASC LIMIT 1");
            $stmt->bind_param('isss', $userId, $today, $today, $nowTime);
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
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      position: relative;
    }

    /* Subtle Latur district map outline as watermark pattern */
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 450" opacity="0.04"><path d="M130 70 L190 50 L250 80 L270 130 L240 170 L260 220 L220 260 L240 310 L200 350 L210 400 L160 410 L120 370 L90 320 L100 260 L70 200 L80 140 L100 90 Z" fill="none" stroke="%231a3a5c" stroke-width="1.8"/><circle cx="170" cy="170" r="9" fill="%231a3a5c" opacity="0.5"/><circle cx="210" cy="290" r="11" fill="%231a3a5c" opacity="0.4"/><path d="M150 110 L170 100 L190 115 L180 135 L155 130 Z" fill="none" stroke="%231a3a5c" stroke-width="1.2"/><path d="M180 370 L200 360 L215 380 L200 400 L175 390 Z" fill="none" stroke="%231a3a5c" stroke-width="1.2"/></svg>');
      background-repeat: repeat;
      background-size: 350px 400px;
      pointer-events: none;
      z-index: 0;
    }

    /* main layout: sidebar + content area */
    .app-container {
      display: flex;
      flex: 1;
      min-height: 0;
      position: relative;
      z-index: 1;
    }

    /* ===== NEW HEADER - EXACTLY MATCHING GOVERNMENT STYLE ===== */
    .header {
      background: linear-gradient(135deg, #0b3d5f 0%, #1a5f7a 50%, #0b3d5f 100%);
      background-size: 200% 200%;
      animation: gradientShift 8s ease infinite;
      color: white;
      padding: 0.6rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      position: sticky;
      top: 0;
      z-index: 100;
      flex-wrap: wrap;
      border-bottom: 3px solid #c9a84c;
      position: relative;
      overflow: hidden;
      min-height: 80px;
    }

    /* Tricolor stripe at bottom */
    .header::after {
      content: "";
      position: absolute;
      bottom: -3px;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, 
        #ff9933 0%, #ff9933 33.33%, 
        #ffffff 33.33%, #ffffff 66.66%, 
        #138847 66.66%, #138847 100%);
      opacity: 0.8;
    }

    /* Subtle glow at top */
    .header::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, #c9a84c, transparent);
      opacity: 0.5;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 1;
      flex: 0 0 auto;
    }

    .header-center {
      display: flex;
      flex-direction: column;
      align-items: center;
      z-index: 1;
      flex: 1;
      text-align: center;
      padding: 0 15px;
    }

    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 1;
      flex: 0 0 auto;
    }

    /* Emblem styling */
    .district-emblem {
      width: 55px;
      height: 55px;
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
      color: #ffffff;
      text-shadow: 1px 2px 4px rgba(0,0,0,0.5);
    }

    .title-section .subtitle {
      font-size: 0.65rem;
      opacity: 0.85;
      font-weight: 400;
      display: flex;
      align-items: center;
      gap: 5px;
      color: #e8d5a3;
      letter-spacing: 0.3px;
    }

    .title-section .subtitle i {
      font-size: 0.6rem;
      color: #c9a84c;
    }

    /* Center header text - exactly like image */
    .header-center .main-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: 0.3px;
      text-shadow: 1px 2px 4px rgba(0,0,0,0.3);
      line-height: 1.4;
    }

    .header-center .main-title .highlight {
      color: #c9a84c;
    }

    .header-center .main-title .separator {
      color: #c9a84c;
      margin: 0 4px;
      opacity: 0.5;
    }

    .header-center .portal-name {
      font-size: 0.75rem;
      color: #e8d5a3;
      font-weight: 400;
      letter-spacing: 0.3px;
      margin-top: 1px;
    }

    .header-center .portal-name i {
      color: #c9a84c;
      margin-right: 4px;
      font-size: 0.7rem;
    }

    .header-center .marathi-text {
      font-size: 0.6rem;
      color: #a8b8d0;
      letter-spacing: 0.5px;
      font-weight: 300;
      opacity: 0.7;
      margin-top: 1px;
    }

    .header-center .marathi-text i {
      color: #c9a84c;
      margin: 0 4px;
      font-size: 0.4rem;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 1;
    }

    .date-badge {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      padding: 0.5rem 1.2rem;
      border-radius: 30px;
      font-size: 0.7rem;
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
      border: 1px solid rgba(255,255,255,0.1);
    }

    .user-profile:hover {
      background: rgba(255, 255, 255, 0.18);
      color: white;
      transform: translateY(-1px);
    }

    .user-profile i.fa-user-circle {
      font-size: 1.3rem;
      color: #c9a84c;
    }

    .user-profile span {
      font-size: 0.8rem;
    }

    .user-profile.dropdown-toggle::after,
    .notification-icon.dropdown-toggle::after {
      display: none;
    }

    .notification-icon {
      position: relative;
      font-size: 1.1rem;
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
      width: 17px;
      height: 17px;
      font-size: 0.55rem;
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
      color: #c9a84c;
    }

    .sidebar-footer {
      margin-top: auto;
      border-top: 1px solid #cbd5e0;
      padding-top: 1.2rem;
      font-size: 0.8rem;
      color: #475569;
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
      color: #c05621;
      font-size: 1.4rem;
    }

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

    @media (max-width: 750px) {
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

    @media (max-width: 768px) {
      .header {
        flex-direction: row;
        align-items: center;
        gap: 6px;
        padding: 0.4rem 0.6rem;
        min-height: 60px;
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
      .header-actions .date-badge span { display: none; }
      .header-actions .date-badge { padding: 0.25rem 0.6rem; }
      .header-actions .date-badge i { margin: 0; }
      .title-section h1 { font-size: 0.9rem; }
      .title-section .subtitle { font-size: 0.5rem; }
      .title-section .government-text { font-size: 0.45rem; }
      .district-emblem { width: 35px; height: 35px; padding: 3px; border-width: 2px; }
      .header-left { gap: 6px; }
      .header-right .user-profile span { display: none; }
      .header-right .user-profile { padding: 0.25rem 0.5rem; }
      .header-center .main-title { font-size: 0.7rem; }
      .header-center .portal-name { font-size: 0.5rem; }
      .header-center .marathi-text { font-size: 0.45rem; }
      .header-center .marathi-text i { margin: 0 2px; }
      .header-actions .notification-icon { font-size: 0.9rem; }
      .header-actions .notification-badge { width: 15px; height: 15px; font-size: 0.45rem; top: -2px; right: -4px; }
      .main-content { padding: 1rem; }
    }

    @media (max-width: 480px) {
      .header { padding: 0.3rem 0.4rem; min-height: 50px; }
      .title-section h1 { font-size: 0.7rem; }
      .title-section .subtitle { display: none; }
      .title-section .government-text { font-size: 0.4rem; }
      .district-emblem { width: 28px; height: 28px; padding: 2px; border-width: 2px; }
      .header-left { gap: 4px; }
      .header-center .main-title { font-size: 0.55rem; }
      .header-center .portal-name { display: none; }
      .header-center .marathi-text { font-size: 0.4rem; }
      .header-actions .date-badge { display: none; }
      .header-actions .user-profile i.fa-user-circle { font-size: 1rem; }
      .header-actions .user-profile { padding: 0.2rem 0.4rem; }
      .header-actions .notification-icon { font-size: 0.8rem; }
      .header-actions .notification-badge { width: 13px; height: 13px; font-size: 0.4rem; top: -2px; right: -3px; }
    }
  </style>
</head>
<body>
  <!-- NEW HEADER - EXACTLY MATCHING YOUR IMAGE -->
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
            <i class="fas fa-map-pin"></i> Meeting & Task Planner
          </div>
        </div>
      </a>
    </div>

    <!-- Center Section: Main Government Title - EXACTLY AS IN IMAGE -->
    <div class="header-center">
      <div class="main-title">
        DISTRICT <span class="highlight">LATUR</span><span class="separator">,</span> 
        <span style="font-weight: 400;">GOVERNMENT OF MAHARASHTRA</span>
      </div>
      <div class="portal-name">
        <i class="fas fa-building-columns"></i>
        Collectorate Institutional Inter-Departmental Monitoring Portal
      </div>
      <div class="marathi-text">
        <i class="fas fa-star"></i> लातूर जिल्हा <i class="fas fa-star"></i>
      </div>
    </div>

    <!-- Right Section: Date, Notifications, User -->
    <div class="header-right">
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

  <!-- APP CONTAINER: SIDEBAR + MAIN -->
  <div class="app-container">
    <?php if ($isLoggedIn): ?>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="appSidebar">
      <div class="latur-badge">
        <i class="fas fa-city"></i>
        <span><strong>Latur Division</strong><br><small>Maharashtra</small></span>
      </div>
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/index.php" class="nav-link <?php echo basename($currentPath) == 'index.php' ? 'active' : ''; ?>" <?php echo basename($currentPath) == 'index.php' ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="nav-link <?php echo strpos($currentPath, 'meetings') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'meetings') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-calendar-check"></i>
            <span>Meetings</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/tasks/index.php" class="nav-link <?php echo strpos($currentPath, 'tasks') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'tasks') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-tasks"></i>
            <span>Tasks</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/attendance/index.php" class="nav-link <?php echo strpos($currentPath, 'attendance') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'attendance') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-users"></i>
            <span>Attendance</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/reports/index.php" class="nav-link <?php echo strpos($currentPath, 'reports') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'reports') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
          </a>
        </li>
        <?php if (function_exists('isOrganizer') && isOrganizer()): ?>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/users/index.php" class="nav-link <?php echo strpos($currentPath, 'users/index') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'users/index') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-user-cog"></i>
            <span>Users</span>
          </a>
        </li>
        <li class="nav-item">
          <a href="<?php echo $basePath; ?>/modules/departments/index.php" class="nav-link <?php echo strpos($currentPath, 'departments') !== false ? 'active' : ''; ?>" <?php echo strpos($currentPath, 'departments') !== false ? 'aria-current="page"' : ''; ?>>
            <i class="fas fa-building"></i>
            <span>Departments</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="sidebar-footer">
        <div style="display: flex; align-items: center; gap: 6px; padding: 0 0.5rem;">
          <i class="fas fa-clock"></i>
          <span>Next meeting: <?php echo htmlspecialchars($nextMeetingText); ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; padding: 0 0.5rem;">
          <i class="fas fa-check-circle" style="color: #16a34a;"></i>
          <span><?php echo htmlspecialchars($tasksDueTodayText); ?></span>
        </div>
      </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- MAIN CONTENT AREA -->
    <main class="main-content <?php echo !$isLoggedIn ? 'w-100' : ''; ?>">