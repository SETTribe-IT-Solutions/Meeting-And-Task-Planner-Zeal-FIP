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

if ($role === 'Collector' || $role === 'Organizer') {
    // Organizer (super admin) and Collector both see all data
    $meetings_result = $conn->query("SELECT COUNT(*) as total FROM meetings");
    $meetings_organized = $meetings_result->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE meeting_date >= ? AND status != 'Cancelled'");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $upcoming_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status IN ('Pending', 'In Progress')");
    $pending_tasks = $tasks_result->fetch_assoc()['total'] ?? 0;

    $completed_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'Completed'");
    $completed_tasks = $completed_tasks_result->fetch_assoc()['total'] ?? 0;

    $total_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks");
    $total_tasks = $total_tasks_result->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE meeting_date = ? AND status != 'Cancelled'");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $todays_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE due_date < ? AND status IN ('Pending', 'In Progress')");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $overdue_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $total_users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE isDeleted = 'No'");
    $total_users = $total_users_result->fetch_assoc()['total'] ?? 0;

    $mom_total_result = $conn->query("SELECT COUNT(*) as total FROM mom_records");
    $mom_total = $mom_total_result->fetch_assoc()['total'] ?? 0;

    $latest_mom_result = $conn->query("SELECT note_title, created_at FROM mom_records ORDER BY created_at DESC LIMIT 1");
    $latest_mom = $latest_mom_result->fetch_assoc();
    $latest_mom_title = $latest_mom['note_title'] ?? 'None yet';

    $pending_mom_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks t JOIN mom_records mr ON mr.linked_task_id = t.id WHERE t.status IN ('Pending', 'In Progress')");
    $pending_mom_tasks = $pending_mom_tasks_result->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT m.*, u.name as organizer_name
                            FROM meetings m
                            JOIN users u ON m.organizer_id = u.id
                            WHERE m.meeting_date >= ? AND m.status != 'Cancelled'
                            ORDER BY m.meeting_date ASC, m.meeting_time ASC
                            LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $upcoming = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
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

    $mom_total_result = $conn->query("SELECT COUNT(*) as total FROM mom_records");
    $mom_total = $mom_total_result->fetch_assoc()['total'] ?? 0;

    $latest_mom_result = $conn->query("SELECT note_title, created_at FROM mom_records ORDER BY created_at DESC LIMIT 1");
    $latest_mom = $latest_mom_result->fetch_assoc();
    $latest_mom_title = $latest_mom['note_title'] ?? 'None yet';

    $pending_mom_tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks t JOIN mom_records mr ON mr.linked_task_id = t.id WHERE t.status IN ('Pending', 'In Progress')");
    $pending_mom_tasks = $pending_mom_tasks_result->fetch_assoc()['total'] ?? 0;

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
include __DIR__ . '/includes/smart-alert.php';
?>

<!-- Welcome Card -->
<div class="welcome-card mb-4 animate-on-scroll">
    <div class="welcome-card-inner">
        <div class="welcome-card-content">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
        </div>
        <img class="welcome-card-image" src="<?php echo $basePath; ?>/assets/image_e15bb67f.png" alt="Latur municipal building">
    </div>
</div>

<style>
    .welcome-card {
      padding: 1.8rem 2rem;
    }
    .welcome-card-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 2rem;
    }
    .welcome-card-content {
      flex: 1 1 0;
      min-width: 0;
    }
    .welcome-card-content h2 {
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
    }
    .welcome-card-image {
      flex: 0 0 340px;
      width: 340px;
      height: 160px;
      border-radius: 14px;
      object-fit: cover;
      box-shadow: 0 8px 24px rgba(11, 61, 95, 0.18);
      transition: transform 0.4s ease;
    }
    .welcome-card-image:hover {
      transform: scale(1.02);
    }
    @media (max-width: 992px) {
      .welcome-card-inner {
        flex-direction: column;
        align-items: stretch;
      }
      .welcome-card-image {
        flex: none;
        width: 100%;
        height: 160px;
      }
    }
</style>

<!-- News Ticker (CSS-based) -->
<div class="news-ticker-modern d-flex align-items-center py-2 mb-4 shadow-sm animate-on-scroll">
    <span class="ticker-label ms-3"><i class="fas fa-bullhorn"></i> LATEST</span>
    <div class="ticker-content">
        <span class="ticker-text">
            📢 Weekly administrative reviews are scheduled for every Friday. &nbsp;|&nbsp; 📋 New HR Policy updates for 2026 are now available under Reports. &nbsp;|&nbsp; 🔔 Please confirm your attendance for the upcoming District Planning meeting. &nbsp;|&nbsp; ✅ Digital attendance is now mandatory for all government meetings.
        </span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6 animate-on-scroll">
        <div class="card stat-card stat-primary border-0 h-100 p-4">
            <i class="fas fa-calendar-check stat-icon"></i>
            <div class="stat-label mb-2">TOTAL MEETINGS</div>
            <div class="stat-value counter-value" data-target="<?php echo $meetings_organized; ?>"><?php echo $meetings_organized; ?></div>
            <div class="stat-trend mt-2"><i class="fas fa-calendar-day me-1"></i> <?php echo $todays_meetings; ?> today</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 animate-on-scroll">
        <div class="card stat-card stat-success border-0 h-100 p-4">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-label mb-2">UPCOMING MEETINGS</div>
            <div class="stat-value counter-value" data-target="<?php echo $upcoming_meetings; ?>"><?php echo $upcoming_meetings; ?></div>
            <div class="stat-trend mt-2"><i class="fas fa-arrow-trend-up me-1"></i> Next 30 days</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 animate-on-scroll">
        <div class="card stat-card stat-warning border-0 h-100 p-4">
            <i class="fas fa-tasks stat-icon"></i>
            <div class="stat-label mb-2">PENDING TASKS</div>
            <div class="stat-value counter-value" data-target="<?php echo $pending_tasks; ?>"><?php echo $pending_tasks; ?></div>
            <div class="stat-trend mt-2"><i class="fas fa-check-circle me-1"></i> <?php echo $completed_tasks; ?> completed</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 animate-on-scroll">
        <div class="card stat-card stat-info border-0 h-100 p-4">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-label mb-2">MoM RECORDS</div>
            <div class="stat-value counter-value" data-target="<?php echo $mom_total; ?>"><?php echo $mom_total; ?></div>
            <div class="stat-trend mt-2"><i class="fas fa-clipboard-list me-1"></i> <?php echo htmlspecialchars($latest_mom_title); ?></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 animate-on-scroll">
        <div class="card stat-card <?php echo $overdue_tasks > 0 ? 'stat-danger' : 'stat-info'; ?> border-0 h-100 p-4">
            <i class="fas <?php echo $overdue_tasks > 0 ? 'fa-exclamation-triangle' : 'fa-chart-pie'; ?> stat-icon"></i>
            <div class="stat-label mb-2"><?php echo $overdue_tasks > 0 ? 'OVERDUE TASKS' : 'TASK COMPLETION'; ?></div>
            <div class="stat-value counter-value" data-target="<?php echo $overdue_tasks > 0 ? $overdue_tasks : $task_completion_pct; ?>"><?php echo $overdue_tasks > 0 ? $overdue_tasks : $task_completion_pct; ?></div><?php if ($overdue_tasks === 0): ?><span style="font-size:1.2rem;font-weight:700">%</span><?php endif; ?>
            <div class="stat-trend mt-2">
                <?php if ($overdue_tasks > 0): ?>
                    <i class="fas fa-exclamation-circle me-1"></i> Needs attention
                <?php else: ?>
                    <i class="fas fa-trophy me-1"></i> <?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?> done
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (($role === 'Collector' || $role === 'Organizer') && $total_users > 0): ?>
<!-- Additional Stats Row for Collector/Organizer -->
<div class="row g-4 mb-4">
    <div class="col-md-4 animate-on-scroll">
        <div class="card stat-card stat-purple border-0 h-100 p-4">
            <i class="fas fa-users stat-icon"></i>
            <div class="stat-label mb-2">TOTAL USERS</div>
            <div class="stat-value counter-value" data-target="<?php echo $total_users; ?>"><?php echo $total_users; ?></div>
            <div class="stat-trend mt-2"><i class="fas fa-user-check me-1"></i> Active accounts</div>
        </div>
    </div>
    <div class="col-md-4 animate-on-scroll">
        <div class="card border-0 shadow-sm h-100 p-4">
            <h6 class="fw-bold text-secondary mb-3" style="font-size: 0.8rem; letter-spacing: 0.05em;">TASK COMPLETION RATE</h6>
            <div class="progress mb-2" style="height: 12px; border-radius: 10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $task_completion_pct; ?>%; border-radius: 10px;" aria-valuenow="<?php echo $task_completion_pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted" style="font-size: 0.8rem;"><?php echo $completed_tasks; ?> completed</span>
                <span class="fw-bold text-success" style="font-size: 0.9rem;"><?php echo $task_completion_pct; ?>%</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 animate-on-scroll">
        <div class="card border-0 shadow-sm h-100 p-4">
            <h6 class="fw-bold text-secondary mb-3" style="font-size: 0.8rem; letter-spacing: 0.05em;">MEETINGS TODAY</h6>
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #0b3d5f, #1a5f7a); display: flex; align-items: center; justify-content: center;">
                    <span style="color: #f9b81b; font-size: 1.5rem; font-weight: 800;"><?php echo $todays_meetings; ?></span>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 1.1rem;"><?php echo $todays_meetings > 0 ? 'Meetings scheduled' : 'No meetings today'; ?></div>
                    <div class="text-muted" style="font-size: 0.8rem;"><?php echo date('l, d M Y'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Upcoming Meetings Section -->
<div class="card border-0 shadow-sm mb-4 animate-on-scroll">
    <div class="card-header bg-white py-3 fw-bold" style="color: var(--gov-blue); border-bottom: 2px solid #eef2f6;">
        <i class="fas fa-calendar-week text-primary me-2"></i> Upcoming Meetings
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar2-week"></i>
                <p>No upcoming meetings found.</p>
                <?php if ($role === 'Organizer'): ?>
                <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-primary btn-sm rounded-3">
                    <i class="fas fa-plus-circle"></i> Create Meeting
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Organizer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $meeting): ?>
                        <tr>
                            <td>
                                <a href="<?php echo $basePath; ?>/modules/meetings/view.php?id=<?php echo $meeting['id']; ?>" 
                                   class="text-decoration-none fw-bold" style="color: var(--gov-blue);">
                                    <?php echo htmlspecialchars($meeting['title']); ?>
                                </a>
                            </td>
                            <td><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></td>
                            <td><?php echo formatTime12Hour($meeting['meeting_time']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['organizer_name']); ?></td>
                            <td>
                                <?php
                                $status = strtolower($meeting['status']);
                                $badge_class = match($status) {
                                    'scheduled' => 'badge-status-scheduled',
                                    'ongoing' => 'badge-status-ongoing',
                                    'completed' => 'badge-status-completed',
                                    'cancelled' => 'badge-status-cancelled',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions and Active Tasks -->
<div class="row g-4">
    <div class="col-md-6 animate-on-scroll">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold" style="color: var(--gov-blue); border-bottom: 2px solid #eef2f6;">
                <i class="fas fa-bolt text-warning me-2"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <?php if ($role === 'Organizer'): ?>
                    <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="quick-action-btn text-white" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                        <i class="fas fa-plus-circle"></i> Create New Meeting
                    </a>
                    <a href="<?php echo $basePath; ?>/modules/tasks/create.php" class="quick-action-btn text-dark fw-bold" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                        <i class="fas fa-tasks"></i> Assign New Task
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="quick-action-btn" style="background: #eef6ff; color: #0b3d5f; border: 1.5px solid #bfdbfe;">
                        <i class="fas fa-list"></i> View All Meetings
                    </a>
                    <a href="<?php echo $basePath; ?>/modules/users/profile.php" class="quick-action-btn" style="background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0;">
                        <i class="fas fa-user-circle"></i> View My Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 animate-on-scroll">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold" style="color: var(--gov-blue); border-bottom: 2px solid #eef2f6;">
                <i class="fas fa-tasks text-success me-2"></i> Active Tasks (Next 5)
            </div>
            <div class="card-body">
                <?php if (empty($active_tasks)): ?>
                    <div class="empty-state">
                        <i class="bi bi-check2-circle text-success"></i>
                        <p>No active tasks assigned.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($active_tasks as $task): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <small class="<?php echo (strtotime($task['due_date']) < strtotime($today)) ? 'overdue-text' : 'text-danger'; ?> fw-semibold">Due: <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                </div>
                                <p class="mb-1 text-muted small">Meeting: <?php echo htmlspecialchars($task['meeting_title']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <?php
                                    $pBadge = match($task['priority']) { 'High'=>'badge-priority-high', 'Medium'=>'badge-priority-medium', 'Low'=>'badge-priority-low', default=>'bg-secondary' };
                                    $sBadge = match($task['status']) { 'Completed'=>'badge-status-completed', 'In Progress'=>'badge-status-ongoing', 'Pending'=>'badge-status-scheduled', default=>'bg-secondary' };
                                    ?>
                                    <span class="badge <?php echo $pBadge; ?>"><?php echo htmlspecialchars($task['priority']); ?> Priority</span>
                                    <span class="badge <?php echo $sBadge; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
