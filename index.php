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
$department = $_SESSION['department'];
$today = date('Y-m-d');

if ($role === 'Collector') {
    // Collector sees everything
    $meetings_result = $conn->query("SELECT COUNT(*) as total FROM meetings");
    $meetings_organized = $meetings_result->fetch_assoc()['total'] ?? 0;
    
    $upcoming_result = $conn->query("SELECT COUNT(*) as total FROM meetings WHERE meeting_date >= '$today' AND status != 'Cancelled'");
    $upcoming_meetings = $upcoming_result->fetch_assoc()['total'] ?? 0;
    
    $tasks_result = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status IN ('Pending', 'In Progress')");
    $pending_tasks = $tasks_result->fetch_assoc()['total'] ?? 0;
    
    $upcoming_query = "SELECT m.*, u.name as organizer_name 
                      FROM meetings m 
                      JOIN users u ON m.organizer_id = u.id 
                      WHERE m.meeting_date >= ? AND m.status != 'Cancelled' 
                      ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                      LIMIT 5";
    $stmt = $conn->prepare($upcoming_query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $meetings_organized = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ? AND meeting_date >= ? AND status != 'Cancelled'");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $upcoming_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status IN ('Pending', 'In Progress')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $stmt = $conn->prepare("SELECT m.*, u.name as organizer_name 
                            FROM meetings m 
                            JOIN users u ON m.organizer_id = u.id 
                            WHERE m.organizer_id = ? AND m.meeting_date >= ? AND m.status != 'Cancelled' 
                            ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                            LIMIT 5");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT t.*, u.name as assignee_name, m.title as meeting_title 
                            FROM tasks t 
                            JOIN users u ON t.assigned_to = u.id 
                            JOIN meetings m ON t.meeting_id = m.id 
                            WHERE m.organizer_id = ? AND t.status IN ('Pending', 'In Progress') 
                            ORDER BY t.due_date ASC 
                            LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // Employee sees meetings in their department / invited to, and tasks assigned to them
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE m.department = ? OR a.user_id = ?");
    $stmt->bind_param("si", $department, $user_id);
    $stmt->execute();
    $meetings_organized = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date >= ? AND m.status != 'Cancelled'");
    $stmt->bind_param("sis", $department, $user_id, $today);
    $stmt->execute();
    $upcoming_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status IN ('Pending', 'In Progress')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT DISTINCT m.*, u.name as organizer_name 
                            FROM meetings m 
                            JOIN users u ON m.organizer_id = u.id 
                            LEFT JOIN attendance a ON m.id = a.meeting_id 
                            WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date >= ? AND m.status != 'Cancelled' 
                            ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                            LIMIT 5");
    $stmt->bind_param("sis", $department, $user_id, $today);
    $stmt->execute();
    $upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT t.*, u.name as assignee_name, m.title as meeting_title 
                            FROM tasks t 
                            JOIN users u ON t.assigned_to = u.id 
                            JOIN meetings m ON t.meeting_id = m.id 
                            WHERE t.assigned_to = ? AND t.status IN ('Pending', 'In Progress') 
                            ORDER BY t.due_date ASC 
                            LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/includes/header.php';
?>

<style>
    .welcome-card {
      background: white;
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 20px rgba(0,0,0,0.04);
      max-width: 100%;
    }

    .welcome-card-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 2rem;
    }

    .welcome-card-content {
      flex: 1 1 420px;
      min-width: 0;
    }

    .welcome-card-image {
      flex: 0 1 420px;
      max-width: 420px;
      width: 38%;
      min-width: 260px;
      aspect-ratio: 16 / 9;
      border-radius: 16px;
      object-fit: cover;
      box-shadow: 0 12px 30px rgba(11, 61, 95, 0.18);
    }

    .welcome-card h2 {
      color: #0b3d5f;
      margin-bottom: 0.8rem;
    }

    @media (max-width: 992px) {
      .welcome-card-inner {
        flex-direction: column;
        align-items: stretch;
      }

      .welcome-card-image {
        width: 100%;
        max-width: none;
        min-width: 0;
      }
    }
</style>

<div class="welcome-card mb-4">
    <div class="welcome-card-inner">
        <div class="welcome-card-content">
            <h2><i class="fas fa-hand-sparkles" style="color: #f9b81b;"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
            <p style="color: #334155; margin: 0.5rem 0 1.5rem;">Coordinate district meetings, assign tasks, and track progress across Latur talukas.</p>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="background: #eef6ff; padding: 0.8rem 1.5rem; border-radius: 30px; color: #0b3d5f;">
                    <i class="fas fa-calendar-plus" style="color: #0b3d5f;"></i> <strong>Meetings:</strong> <?php echo $upcoming_meetings; ?> Upcoming
                </div>
                <div style="background: #fef3c7; padding: 0.8rem 1.5rem; border-radius: 30px; color: #b45309;">
                    <i class="fas fa-tasks" style="color: #b45309;"></i> <strong>Tasks:</strong> <?php echo $pending_tasks; ?> Pending
                </div>
                <div style="background: #f0fdf4; padding: 0.8rem 1.5rem; border-radius: 30px; color: #166534;">
                    <i class="fas fa-user-shield" style="color: #166534;"></i> <strong>Role:</strong> <?php echo htmlspecialchars($role); ?>
                </div>
            </div>
            <p style="margin-top: 2rem; font-style: italic; color: #475569; margin-bottom: 0;">
                <i class="fas fa-quote-left"></i> Efficient planning for Latur's development <i class="fas fa-quote-right"></i>
            </p>
        </div>
        <img class="welcome-card-image" src="<?php echo $basePath; ?>/assets/image_e15bb67f.png" alt="Latur municipal building">
    </div>
</div>

<!-- News Ticker -->
<div class="bg-white border rounded-3 py-2 mb-4 overflow-hidden shadow-sm">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center">
            <span class="badge bg-danger me-3">LATEST NEWS</span>
            <marquee class="text-muted small mb-0" behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
                Notice: Weekly administrative reviews are scheduled for every Friday. | New HR Policy updates for 2026 are now available under Reports. | Please confirm your attendance for the upcoming District Planning meeting.
            </marquee>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 border-start border-4 border-primary h-100 shadow-sm bg-white">
            <div class="card-body p-4 text-gov-blue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-0">TOTAL MEETINGS</p>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $meetings_organized; ?></h2>
                    </div>
                    <i class="bi bi-calendar-event fs-1 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 border-start border-4 border-success h-100 shadow-sm bg-white">
            <div class="card-body p-4 text-gov-blue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-0">UPCOMING MEETINGS</p>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $upcoming_meetings; ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 border-start border-4 border-warning h-100 shadow-sm bg-white">
            <div class="card-body p-4 text-gov-blue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-0">PENDING TASKS</p>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $pending_tasks; ?></h2>
                    </div>
                    <i class="bi bi-tasks fs-1 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Meetings Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-bold text-gov-blue" style="border-bottom: 2px solid #eef2f6;">
        <i class="fas fa-calendar-week text-primary me-2"></i> Upcoming Meetings
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <div class="text-center py-4">
                <i class="bi bi-calendar2-week fs-1 text-muted"></i>
                <p class="text-muted mt-2">No upcoming meetings found.</p>
                <?php if (isOrganizer()): ?>
                <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-primary btn-sm rounded-3">
                    <i class="fas fa-plus-circle"></i> Create Meeting
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light">
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
                                   class="text-decoration-none fw-bold text-gov-blue">
                                    <?php echo htmlspecialchars($meeting['title']); ?>
                                </a>
                            </td>
                            <td><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($meeting['meeting_time'])); ?></td>
                            <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['organizer_name']); ?></td>
                            <td>
                                <?php
                                $status = strtolower($meeting['status']);
                                $badge_class = match($status) {
                                    'scheduled' => 'warning',
                                    'ongoing' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
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
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold text-gov-blue" style="border-bottom: 2px solid #eef2f6;">
                <i class="fas fa-bolt text-warning me-2"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <?php if (isOrganizer()): ?>
                    <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-primary py-2 rounded-3" style="background-color: #0b3d5f; border-color: #0b3d5f;">
                        <i class="fas fa-plus-circle me-1"></i> Create New Meeting
                    </a>
                    <a href="<?php echo $basePath; ?>/modules/tasks/create.php" class="btn btn-warning py-2 rounded-3 text-dark fw-bold">
                        <i class="fas fa-tasks me-1"></i> Assign New Task
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $basePath; ?>/modules/meetings/index.php" class="btn btn-outline-primary py-2 rounded-3">
                        <i class="fas fa-list me-1"></i> View All Meetings
                    </a>
                    <a href="<?php echo $basePath; ?>/modules/users/profile.php" class="btn btn-outline-secondary py-2 rounded-3">
                        <i class="fas fa-user-circle me-1"></i> View My Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold text-gov-blue" style="border-bottom: 2px solid #eef2f6;">
                <i class="fas fa-tasks text-success me-2"></i> Active Tasks (Next 5)
            </div>
            <div class="card-body">
                <?php if (empty($active_tasks)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check2-circle fs-1 text-success"></i>
                        <p class="text-muted mt-2">No active tasks assigned.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($active_tasks as $task): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></h6>
                                    <small class="text-danger fw-semibold">Due: <?php echo date('d M', strtotime($task['due_date'])); ?></small>
                                </div>
                                <p class="mb-1 text-muted small">Meeting: <?php echo htmlspecialchars($task['meeting_title']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-light text-dark border small"><?php echo htmlspecialchars($task['priority']); ?> Priority</span>
                                    <span class="badge bg-warning text-dark small"><?php echo htmlspecialchars($task['status']); ?></span>
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
