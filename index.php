<?php
// index.php - Main Dashboard
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/AuthController.php';
$basePath = APP_URL;

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('modules/users/login.php');
}

// Get user data
$auth = new AuthController();
$user = $auth->getUserById($_SESSION['user_id']);
$conn = getDBConnection();

// Get statistics based on roles
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'] ?? '';
$today = date('Y-m-d');

if ($role === 'Collector') {
    // Collector sees everything
    $meetings_result = $conn->query("SELECT COUNT(*) as total FROM meetings");
    $meetings_organized = $meetings_result->fetch_assoc()['total'] ?? 0;
    
    $upcoming_result = $conn->query("SELECT COUNT(*) as total FROM meetings WHERE meeting_date >= '$today' AND status != 'Cancelled'");
    $upcoming_meetings = $upcoming_result->fetch_assoc()['total'] ?? 0;
    
    $tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status IN ('Pending', 'In Progress')");
    $pending_tasks = $tasks_result->fetch_assoc()['total'] ?? 0;

    $completed_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'Completed'");
    $completed_tasks = $completed_tasks_result->fetch_assoc()['total'] ?? 0;

    $total_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks");
    $total_tasks = $total_tasks_result->fetch_assoc()['total'] ?? 0;

    $todays_meetings_result = $conn->query("SELECT COUNT(*) as total FROM meetings WHERE meeting_date = '$today' AND status != 'Cancelled'");
    $todays_meetings = $todays_meetings_result->fetch_assoc()['total'] ?? 0;

    $overdue_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE due_date < '$today' AND status IN ('Pending', 'In Progress')");
    $overdue_tasks = $overdue_tasks_result->fetch_assoc()['total'] ?? 0;

    $total_users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE isDeleted = 'No'");
    $total_users = $total_users_result->fetch_assoc()['total'] ?? 0;
    
    $upcoming_query = "SELECT m.*, u.name as organizer_name 
                      FROM meetings m 
                      JOIN users u ON m.organizer_id = u.id 
                      WHERE m.meeting_date >= ? AND m.status != 'Cancelled' 
                      ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                      LIMIT 5";
    $stmt = $conn->prepare($upcoming_query);
    if ($stmt) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        error_log('Prepare failed (collector upcoming): ' . $conn->error . ' | Query: ' . $upcoming_query);
        $upcoming = [];
    }

    $tasks_query = "SELECT t.*, u.name as assignee_name, m.title as meeting_title 
                    FROM tasks t 
                    JOIN users u ON t.assigned_to = u.id 
                    JOIN meetings m ON t.meeting_id = m.id 
                    WHERE t.status IN ('Pending', 'In Progress') 
                    ORDER BY t.due_date ASC 
                    LIMIT 5";
    $active_tasks = $conn->query($tasks_query)->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'Organizer') {
    // Organizer sees their organized meetings and tasks assigned under them
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $meetings_organized = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (organizer meetings_organized): ' . $conn->error);
        $meetings_organized = 0;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ? AND meeting_date >= ? AND status != 'Cancelled'");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming_meetings = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (organizer upcoming_meetings): ' . $conn->error);
        $upcoming_meetings = 0;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status IN ('Pending', 'In Progress')");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pending_tasks = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (organizer pending_tasks): ' . $conn->error);
        $pending_tasks = 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status = 'Completed'");
    if ($stmt) { $stmt->bind_param("i", $user_id); $stmt->execute(); $completed_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $completed_tasks = 0; }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ?");
    if ($stmt) { $stmt->bind_param("i", $user_id); $stmt->execute(); $total_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $total_tasks = 0; }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ? AND meeting_date = ? AND status != 'Cancelled'");
    if ($stmt) { $stmt->bind_param("is", $user_id, $today); $stmt->execute(); $todays_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $todays_meetings = 0; }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.due_date < ? AND t.status IN ('Pending', 'In Progress')");
    if ($stmt) { $stmt->bind_param("is", $user_id, $today); $stmt->execute(); $overdue_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $overdue_tasks = 0; }

    $total_users = 0;
    
    $stmt = $conn->prepare("SELECT m.*, u.name as organizer_name 
                            FROM meetings m 
                            JOIN users u ON m.organizer_id = u.id 
                            WHERE m.organizer_id = ? AND m.meeting_date >= ? AND m.status != 'Cancelled' 
                            ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                            LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        error_log('Prepare failed (organizer upcoming list): ' . $conn->error);
        $upcoming = [];
    }

    $stmt = $conn->prepare("SELECT t.*, u.name as assignee_name, m.title as meeting_title 
                            FROM tasks t 
                            JOIN users u ON t.assigned_to = u.id 
                            JOIN meetings m ON t.meeting_id = m.id 
                            WHERE m.organizer_id = ? AND t.status IN ('Pending', 'In Progress') 
                            ORDER BY t.due_date ASC 
                            LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $active_tasks = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        error_log('Prepare failed (organizer active_tasks): ' . $conn->error);
        $active_tasks = [];
    }
} else {
    // Employee sees meetings in their department / invited to, and tasks assigned to them
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE m.department = ? OR a.user_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $department, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $meetings_organized = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (employee meetings_organized): ' . $conn->error);
        $meetings_organized = 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date >= ? AND m.status != 'Cancelled'");
    if ($stmt) {
        $stmt->bind_param("sis", $department, $user_id, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming_meetings = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (employee upcoming_meetings): ' . $conn->error);
        $upcoming_meetings = 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status IN ('Pending', 'In Progress')");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pending_tasks = $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
    } else {
        error_log('Prepare failed (employee pending_tasks): ' . $conn->error);
        $pending_tasks = 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'Completed'");
    if ($stmt) { $stmt->bind_param("i", $user_id); $stmt->execute(); $completed_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $completed_tasks = 0; }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ?");
    if ($stmt) { $stmt->bind_param("i", $user_id); $stmt->execute(); $total_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $total_tasks = 0; }

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date = ? AND m.status != 'Cancelled'");
    if ($stmt) { $stmt->bind_param("sis", $department, $user_id, $today); $stmt->execute(); $todays_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $todays_meetings = 0; }

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND due_date < ? AND status IN ('Pending', 'In Progress')");
    if ($stmt) { $stmt->bind_param("is", $user_id, $today); $stmt->execute(); $overdue_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0; } else { $overdue_tasks = 0; }

    $total_users = 0;

    $stmt = $conn->prepare("SELECT DISTINCT m.*, u.name as organizer_name 
                            FROM meetings m 
                            JOIN users u ON m.organizer_id = u.id 
                            LEFT JOIN attendance a ON m.id = a.meeting_id 
                            WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date >= ? AND m.status != 'Cancelled' 
                            ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                            LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("sis", $department, $user_id, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        error_log('Prepare failed (employee upcoming list): ' . $conn->error);
        $upcoming = [];
    }

    $stmt = $conn->prepare("SELECT t.*, u.name as assignee_name, m.title as meeting_title 
                            FROM tasks t 
                            JOIN users u ON t.assigned_to = u.id 
                            JOIN meetings m ON t.meeting_id = m.id 
                            WHERE t.assigned_to = ? AND t.status IN ('Pending', 'In Progress') 
                            ORDER BY t.due_date ASC 
                            LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $active_tasks = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
        error_log('Prepare failed (employee active_tasks): ' . $conn->error);
        $active_tasks = [];
    }
}

$task_completion_pct = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

include __DIR__ . '/includes/header.php';
?>

<style>
    /* Modern Admin Panel CSS - Government Style */
    :root {
        --primary-blue: #1a237e;
        --primary-gold: #c9a959;
        --primary-dark: #0d1445;
        --accent-saffron: #ff9933;
        --accent-green: #138808;
        --bg-light: #f5f6fa;
        --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
        --text-dark: #1a1a2e;
        --text-muted: #6b7280;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: var(--bg-light);
        font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, sans-serif;
    }

    /* ============================================================ */
    /* ===== NEW: BACKGROUND IMAGE WITH OVERLAY ===== */
    /* ============================================================ */
    /* This adds a background image to the entire page */
    body {
        background-image: url('<?php echo $basePath; ?>/assets/latur-district-map.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        background-repeat: no-repeat;
        position: relative;
        min-height: 100vh;
    }

    /* Semi-transparent white overlay for readability */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.88);
        z-index: 0;
        pointer-events: none;
    }

    /* Ensure all content appears above the overlay */
    .gov-header,
    .nav-tabs-custom,
    .dashboard-container,
    .gov-footer {
        position: relative;
        z-index: 1;
    }

    /* Make cards slightly transparent to show background */
    .welcome-section,
    .quick-action-panel,
    .stat-card-modern,
    .meeting-log {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: background 0.3s ease;
    }

    /* Keep header solid */
    .gov-header {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }

    /* Keep nav tabs solid */
    .nav-tabs-custom {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* ============================================================ */
    /* ===== END OF BACKGROUND IMAGE STYLES ===== */
    /* ============================================================ */

    /* Header - Government Style */
    .gov-header {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
        padding: 0.75rem 2rem;
        border-bottom: 4px solid var(--accent-saffron);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 15px rgba(0,0,0,0.2);
    }

    .gov-header-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .gov-header-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
        text-decoration: none;
    }

    .gov-header-logo .logo-icon {
        font-size: 2rem;
        background: var(--accent-saffron);
        color: var(--primary-dark);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .gov-header-logo .logo-text {
        font-size: 1.1rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .gov-header-logo .logo-text small {
        display: block;
        font-size: 0.7rem;
        font-weight: 400;
        opacity: 0.8;
    }

    .gov-header-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .gov-header-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
        padding: 0.4rem 1rem 0.4rem 0.4rem;
        background: rgba(255,255,255,0.1);
        border-radius: 50px;
        border: 1px solid rgba(255,255,255,0.15);
    }

    .gov-header-user .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--accent-saffron);
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .gov-header-user .user-info {
        font-size: 0.85rem;
    }

    .gov-header-user .user-info .user-role {
        display: block;
        font-size: 0.65rem;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Navigation Tabs */
    .nav-tabs-custom {
        background: white;
        padding: 0 2rem;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        gap: 0;
        overflow-x: auto;
    }

    .nav-tabs-custom .nav-item {
        padding: 0.9rem 1.5rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        white-space: nowrap;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .nav-tabs-custom .nav-item:hover {
        color: var(--primary-blue);
        background: #f8f9fa;
    }

    .nav-tabs-custom .nav-item.active {
        color: var(--primary-blue);
        border-bottom-color: var(--accent-saffron);
        font-weight: 600;
    }

    .nav-tabs-custom .nav-item i {
        font-size: 1rem;
    }

    /* Main Container */
    .dashboard-container {
        padding: 1.5rem 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Welcome Section */
    .welcome-section {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        border-left: 5px solid var(--accent-saffron);
        border-right: 5px solid var(--accent-green);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .welcome-section::before {
        content: '🇮🇳';
        position: absolute;
        top: -5px;
        right: 10px;
        font-size: 5rem;
        opacity: 0.05;
    }

    .welcome-section .welcome-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 0.25rem;
    }

    .welcome-section .welcome-subtitle {
        color: var(--text-muted);
        font-size: 1rem;
    }

    .welcome-section .welcome-tags {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .welcome-section .welcome-tags .tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 1rem;
        background: var(--bg-light);
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-dark);
    }

    .welcome-section .welcome-tags .tag i {
        color: var(--accent-saffron);
    }

    /* Quick Action Panel - Like in the image */
    .quick-action-panel {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--card-shadow);
        border: 1px solid #e5e7eb;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .quick-action-panel .panel-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .quick-action-panel .action-buttons {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .quick-action-panel .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        border: 2px solid #e5e7eb;
        background: white;
        color: var(--text-dark);
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
    }

    .quick-action-panel .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .quick-action-panel .action-btn.primary {
        background: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
    }

    .quick-action-panel .action-btn.primary:hover {
        background: var(--primary-dark);
    }

    .quick-action-panel .action-btn.success {
        background: var(--accent-green);
        color: white;
        border-color: var(--accent-green);
    }

    .quick-action-panel .action-btn.success:hover {
        background: #0d6e0a;
    }

    .quick-action-panel .action-btn.warning {
        background: var(--accent-saffron);
        color: white;
        border-color: var(--accent-saffron);
    }

    .quick-action-panel .action-btn.warning:hover {
        background: #e68a00;
    }

    .quick-action-panel .action-btn.exit {
        background: #dc3545;
        color: white;
        border-color: #dc3545;
    }

    .quick-action-panel .action-btn.exit:hover {
        background: #b02a37;
    }

    /* Stats Cards - Like in the image */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .stat-card-modern {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: var(--card-shadow);
        border: 1px solid #e5e7eb;
        transition: all 0.3s;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .stat-card-modern:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .stat-card-modern .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        font-weight: 600;
    }

    .stat-card-modern .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 0.25rem 0;
    }

    .stat-card-modern .stat-icon {
        font-size: 2rem;
        opacity: 0.15;
        float: right;
    }

    .stat-card-modern .stat-change {
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .stat-card-modern .stat-change .up { color: var(--accent-green); }
    .stat-card-modern .stat-change .down { color: #dc3545; }

    /* Meeting Log - Like in the image */
    .meeting-log {
        background: rgba(255, 255, 255, 0.92);
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 1.5rem;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .meeting-log-header {
        padding: 1rem 1.5rem;
        background: var(--bg-light);
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .meeting-log-header .log-title {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--primary-dark);
    }

    .meeting-log-header .log-title i {
        color: var(--accent-saffron);
        margin-right: 0.5rem;
    }

    .meeting-log-header .log-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        background: var(--primary-blue);
        color: white;
        font-weight: 500;
    }

    .meeting-log-body {
        padding: 1rem 1.5rem;
        max-height: 300px;
        overflow-y: auto;
    }

    .meeting-log-body .log-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .meeting-log-body .log-item:last-child {
        border-bottom: none;
    }

    .meeting-log-body .log-item .log-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .meeting-log-body .log-item .log-info .log-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: var(--primary-blue);
    }

    .meeting-log-body .log-item .log-info .log-details .log-title-text {
        font-weight: 500;
        font-size: 0.9rem;
        color: var(--text-dark);
    }

    .meeting-log-body .log-item .log-info .log-details .log-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .meeting-log-body .log-item .log-status {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-weight: 500;
    }

    .log-status.scheduled { background: #dbeafe; color: #1e40af; }
    .log-status.ongoing { background: #fef3c7; color: #92400e; }
    .log-status.completed { background: #d1fae5; color: #065f46; }
    .log-status.cancelled { background: #fee2e2; color: #991b1b; }

    /* Empty State */
    .empty-state-log {
        text-align: center;
        padding: 2rem;
        color: var(--text-muted);
    }

    .empty-state-log i {
        font-size: 3rem;
        color: #d1d5db;
        display: block;
        margin-bottom: 0.5rem;
    }

    /* Footer */
    .gov-footer {
        background: var(--primary-dark);
        color: rgba(255,255,255,0.7);
        padding: 1rem 2rem;
        text-align: center;
        font-size: 0.8rem;
        border-top: 3px solid var(--accent-saffron);
        margin-top: 2rem;
    }

    .gov-footer strong {
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .gov-header {
            padding: 0.5rem 1rem;
            flex-direction: column;
            align-items: stretch;
        }
        .gov-header-left, .gov-header-right {
            justify-content: center;
        }
        .nav-tabs-custom {
            padding: 0 1rem;
            overflow-x: auto;
            flex-wrap: nowrap;
        }
        .nav-tabs-custom .nav-item {
            padding: 0.7rem 1rem;
            font-size: 0.75rem;
        }
        .dashboard-container {
            padding: 1rem;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .welcome-section .welcome-title {
            font-size: 1.3rem;
        }
        .quick-action-panel .action-buttons {
            flex-direction: column;
        }
        .quick-action-panel .action-btn {
            justify-content: center;
        }
        .meeting-log-body .log-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        body {
            background-attachment: scroll;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- ==================== HEADER ==================== -->
<header class="gov-header">
    <div class="gov-header-left">
        <a href="#" class="gov-header-logo">
            <span class="logo-icon">🏛</span>
            <span class="logo-text">
                DISTRICT LATUR
                <small>Government of Maharashtra</small>
            </span>
        </a>
    </div>
    <div class="gov-header-right">
        <div class="gov-header-user">
            <span class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?></span>
            <span class="user-info">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
            </span>
        </div>
    </div>
</header>

<!-- ==================== NAVIGATION TABS ==================== -->
<nav class="nav-tabs-custom">
    <a href="#" class="nav-item active">
        <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="nav-item">
        <i class="fas fa-calendar-check"></i> Schedule Meeting
    </a>
    <a href="#" class="nav-item">
        <i class="fas fa-tasks"></i> Tasks
    </a>
    <a href="#" class="nav-item">
        <i class="fas fa-user-check"></i> Attendance
    </a>
    <a href="#" class="nav-item">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
    <a href="#" class="nav-item">
        <i class="fas fa-users"></i> Users
    </a>
    <a href="#" class="nav-item">
        <i class="fas fa-building"></i> Departments
    </a>
</nav>

<!-- ==================== DASHBOARD CONTENT ==================== -->
<div class="dashboard-container">

    <!-- Welcome Section -->
    <div class="welcome-section animate-on-scroll">
        <div class="welcome-title">
            <i class="fas fa-hand-sparkles" style="color: var(--accent-saffron);"></i> 
            Desk of <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
        </div>
        <div class="welcome-subtitle">
            Collectorate Institutional Inter-Departmental Monitoring Portal
        </div>
        <div class="welcome-tags">
            <span class="tag">
                <i class="fas fa-calendar-plus"></i> 
                <strong><?php echo $upcoming_meetings; ?></strong> Upcoming Meetings
            </span>
            <span class="tag">
                <i class="fas fa-tasks"></i> 
                <strong><?php echo $pending_tasks; ?></strong> Pending Tasks
            </span>
            <span class="tag">
                <i class="fas fa-user-shield"></i> 
                Role: <?php echo htmlspecialchars($role); ?>
            </span>
        </div>
    </div>

    <!-- Quick Action Panel -->
    <div class="quick-action-panel animate-on-scroll">
        <div class="panel-title">
            <i class="fas fa-cubes"></i> WORKSPACE DIRECTORY PANEL • LATUR ADMINISTRATION SYNC
        </div>
        <div class="action-buttons">
            <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="action-btn primary">
                <i class="fas fa-plus-circle"></i> + Meeting
            </a>
            <a href="<?php echo $basePath; ?>/modules/tasks/create.php" class="action-btn success">
                <i class="fas fa-tasks"></i> Task
            </a>
            <a href="<?php echo $basePath; ?>/modules/logout.php" class="action-btn exit">
                <i class="fas fa-sign-out-alt"></i> Exit
            </a>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid animate-on-scroll">
        <div class="stat-card-modern">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-label">TOTAL DIRECTIVES</div>
            <div class="stat-number counter-value" data-target="<?php echo $meetings_organized; ?>">0</div>
            <div class="stat-change"><span class="up">↑</span> <?php echo $todays_meetings; ?> today</div>
        </div>
        <div class="stat-card-modern">
            <i class="fas fa-chart-line stat-icon"></i>
            <div class="stat-label">ACTIVE TRACKS</div>
            <div class="stat-number counter-value" data-target="<?php echo $upcoming_meetings; ?>">0</div>
            <div class="stat-change"><span class="up">↑</span> Next 30 days</div>
        </div>
        <div class="stat-card-modern">
            <i class="fas fa-clipboard-list stat-icon"></i>
            <div class="stat-label">OPEN TASKS</div>
            <div class="stat-number counter-value" data-target="<?php echo $pending_tasks; ?>">0</div>
            <div class="stat-change"><span class="up">↑</span> <?php echo $completed_tasks; ?> completed</div>
        </div>
    </div>

    <!-- Executive Meeting Log -->
    <div class="meeting-log animate-on-scroll">
        <div class="meeting-log-header">
            <span class="log-title">
                <i class="fas fa-calendar-alt"></i> Executive Meeting Log
            </span>
            <span class="log-badge"><?php echo count($upcoming); ?> Scheduled</span>
        </div>
        <div class="meeting-log-body">
            <?php if (empty($upcoming)): ?>
                <div class="empty-state-log">
                    <i class="fas fa-calendar-times"></i>
                    <p>No upcoming meetings scheduled.</p>
                    <?php if (isOrganizer()): ?>
                        <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle"></i> Schedule Meeting
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming as $meeting): ?>
                    <div class="log-item">
                        <div class="log-info">
                            <div class="log-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="log-details">
                                <div class="log-title-text">
                                    <?php echo htmlspecialchars($meeting['title']); ?>
                                </div>
                                <div class="log-meta">
                                    <i class="far fa-clock"></i> <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?> at <?php echo formatTime12Hour($meeting['meeting_time']); ?>
                                    &nbsp;•&nbsp; <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($meeting['location']); ?>
                                    &nbsp;•&nbsp; <i class="fas fa-user"></i> <?php echo htmlspecialchars($meeting['organizer_name']); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $status = strtolower($meeting['status']);
                        $status_class = match($status) {
                            'scheduled' => 'scheduled',
                            'ongoing' => 'ongoing',
                            'completed' => 'completed',
                            'cancelled' => 'cancelled',
                            default => 'scheduled'
                        };
                        ?>
                        <span class="log-status <?php echo $status_class; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Tasks Section -->
    <div class="meeting-log animate-on-scroll">
        <div class="meeting-log-header">
            <span class="log-title">
                <i class="fas fa-tasks" style="color: var(--accent-green);"></i> Active Tasks
            </span>
            <span class="log-badge" style="background: var(--accent-green);"><?php echo count($active_tasks); ?> Pending</span>
        </div>
        <div class="meeting-log-body">
            <?php if (empty($active_tasks)): ?>
                <div class="empty-state-log">
                    <i class="fas fa-check-circle" style="color: var(--accent-green);"></i>
                    <p>No active tasks assigned.</p>
                </div>
            <?php else: ?>
                <?php foreach ($active_tasks as $task): ?>
                    <div class="log-item">
                        <div class="log-info">
                            <div class="log-icon" style="background: #fef3c7; color: #92400e;">
                                <i class="fas fa-clipboard"></i>
                            </div>
                            <div class="log-details">
                                <div class="log-title-text">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </div>
                                <div class="log-meta">
                                    <i class="fas fa-users"></i> <?php echo htmlspecialchars($task['meeting_title']); ?>
                                    &nbsp;•&nbsp; <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($task['assignee_name']); ?>
                                    &nbsp;•&nbsp; <i class="far fa-calendar-alt"></i> Due: <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $pBadge = match($task['priority']) { 
                            'High' => 'scheduled', 
                            'Medium' => 'ongoing', 
                            'Low' => 'completed', 
                            default => 'scheduled' 
                        };
                        ?>
                        <span class="log-status <?php echo $pBadge; ?>">
                            <?php echo htmlspecialchars($task['priority']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==================== FOOTER ==================== -->
<footer class="gov-footer">
    <strong>District Latur, Government of Maharashtra</strong> — Collectorate Institutional Inter-Departmental Monitoring Portal &bull; 
    <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> All Rights Reserved
</footer>

<?php include __DIR__ . '/includes/footer.php'; ?>