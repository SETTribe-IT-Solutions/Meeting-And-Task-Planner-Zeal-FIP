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

// Get statistics
$user_id = $_SESSION['user_id'];

// Count meetings organized by user
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meetings_organized = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Count upcoming meetings
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings 
                        WHERE organizer_id = ? 
                        AND meeting_date >= ? AND status != 'cancelled'");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$upcoming_meetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Get upcoming meetings list
$stmt = $conn->prepare("SELECT m.*, u.name as organizer_name 
                        FROM meetings m 
                        JOIN users u ON m.organizer_id = u.id 
                        WHERE m.organizer_id = ? 
                        AND m.meeting_date >= ? 
                        AND m.status != 'cancelled'
                        ORDER BY m.meeting_date ASC, m.meeting_time ASC 
                        LIMIT 5");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count pending tasks
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks 
                        WHERE assigned_to = ? AND status IN ('pending', 'in_progress')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_tasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

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

    .welcome-card h2 {
      color: #0b3d5f;
      margin-bottom: 0.8rem;
    }
</style>

<div class="welcome-card mb-4">
    <h2><i class="fas fa-hand-sparkles" style="color: #f9b81b;"></i> Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h2>
    <p style="color: #334155; margin: 0.5rem 0 1.5rem;">Coordinate district meetings, assign tasks, and track progress across Latur talukas.</p>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <div style="background: #eef6ff; padding: 0.8rem 1.5rem; border-radius: 30px;">
            <i class="fas fa-calendar-plus" style="color: #0b3d5f;"></i> <strong>Upcoming:</strong> <?php echo $upcoming_meetings; ?> Meetings
        </div>
        <div style="background: #fef3c7; padding: 0.8rem 1.5rem; border-radius: 30px;">
            <i class="fas fa-tasks" style="color: #b45309;"></i> <strong>Pending:</strong> <?php echo $pending_tasks; ?> Tasks
        </div>
    </div>
    <p style="margin-top: 2rem; font-style: italic; color: #475569;">
        <i class="fas fa-quote-left"></i> Efficient planning for Latur's development <i class="fas fa-quote-right"></i>
    </p>
</div>

<!-- News Ticker -->
<div class="bg-white border-bottom py-2 mb-4 overflow-hidden">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center">
            <span class="badge bg-danger me-3">LATEST NEWS</span>
            <marquee class="text-muted small" behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
                Notice: Weekly administrative reviews are scheduled for every Friday. | New HR Policy updates for 2026 are now available under Reports. | Please confirm your attendance for the upcoming District Planning meeting.
            </marquee>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
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
    <div class="col-md-3">
        <div class="card border-0 border-start border-4 border-success h-100 shadow-sm bg-white">
            <div class="card-body p-4 text-gov-blue">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold mb-0">UPCOMING</p>
                        <h2 class="mb-0 fw-bold text-dark"><?php echo $upcoming_meetings; ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1 text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
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
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-calendar-week"></i> Upcoming Meetings
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <div class="text-center py-3">
                <i class="bi bi-calendar2-week fs-1 text-muted"></i>
                <p class="text-muted mt-2">No upcoming meetings found.</p>
                <?php if (isOrganizer()): ?>
                <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle"></i> Create Meeting
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
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
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($meeting['title']); ?>
                                </a>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($meeting['meeting_time'])); ?></td>
                            <td><?php echo htmlspecialchars($meeting['location']); ?></td>
                            <td><?php echo htmlspecialchars($meeting['organizer_name']); ?></td>
                            <td>
                                <?php
                                $status = $meeting['status'];
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

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (isOrganizer()): ?>
                    <a href="<?php echo $basePath; ?>/modules/meetings/create.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create New Meeting
                    </a>
                    <a href="<?php echo $basePath; ?>/modules/meetings/list.php" class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View All Meetings
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $basePath; ?>/modules/users/profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">