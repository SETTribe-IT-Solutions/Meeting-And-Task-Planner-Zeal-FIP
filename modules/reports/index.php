<?php
// modules/reports/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';
include_once '../../includes/header.php';

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department = $_SESSION['department'];
$today = date('Y-m-d');

// Filter
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// 1. Fetch meeting statistics
if ($role === 'Collector') {
    $mTotal = $conn->query("SELECT COUNT(*) as total FROM meetings")->fetch_assoc()['total'] ?? 0;
    $mCompleted = $conn->query("SELECT COUNT(*) as total FROM meetings WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
    $mUpcoming = $conn->query("SELECT COUNT(*) as total FROM meetings WHERE meeting_date >= '$today' AND status != 'Cancelled'")->fetch_assoc()['total'] ?? 0;
    
    $tTotal = $conn->query("SELECT COUNT(*) as total FROM tasks")->fetch_assoc()['total'] ?? 0;
    $tPending = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
    $tCompleted = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;
    $tInProgress = $conn->query("SELECT COUNT(*) as total FROM tasks WHERE status = 'In Progress'")->fetch_assoc()['total'] ?? 0;
} elseif ($role === 'Organizer') {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mTotal = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ? AND status = 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mCompleted = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ? AND meeting_date >= ? AND status != 'Cancelled'");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $mUpcoming = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tTotal = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status = 'Pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tPending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status = 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tCompleted = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ? AND t.status = 'In Progress'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tInProgress = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    // Employee
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE m.department = ? OR a.user_id = ?");
    $stmt->bind_param("si", $department, $user_id);
    $stmt->execute();
    $mTotal = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?) AND m.status = 'Completed'");
    $stmt->bind_param("si", $department, $user_id);
    $stmt->execute();
    $mCompleted = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?) AND m.meeting_date >= ? AND m.status != 'Cancelled'");
    $stmt->bind_param("sis", $department, $user_id, $today);
    $stmt->execute();
    $mUpcoming = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tTotal = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'Pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tPending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'Completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tCompleted = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'In Progress'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tInProgress = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// 2. Fetch Detailed Meeting Reports for the table
$reportSql = "SELECT m.id, m.title, m.meeting_date, m.department, m.status,
             (SELECT COUNT(*) FROM tasks WHERE meeting_id = m.id) as total_tasks,
             (SELECT COUNT(*) FROM tasks WHERE meeting_id = m.id AND status = 'Completed') as completed_tasks,
             (SELECT COUNT(*) FROM attendance WHERE meeting_id = m.id) as total_attendees,
             (SELECT COUNT(*) FROM attendance WHERE meeting_id = m.id AND status = 'Present') as present_attendees
             FROM meetings m";

$params = [];
$types = "";

if ($role === 'Organizer') {
    $reportSql .= " WHERE m.organizer_id = ?";
    $params[] = $user_id;
    $types .= "i";
} elseif ($role === 'Employee') {
    $reportSql .= " LEFT JOIN attendance a ON m.id = a.meeting_id WHERE (m.department = ? OR a.user_id = ?)";
    $params[] = $department;
    $params[] = $user_id;
    $types .= "si";
} else {
    $reportSql .= " WHERE 1=1";
}

if (!empty($searchQuery)) {
    $reportSql .= ($role === 'Collector' ? " AND" : " AND") . " (m.title LIKE ? OR m.department LIKE ?)";
    $searchWildcard = "%" . $searchQuery . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

$reportSql .= " GROUP BY m.id ORDER BY m.meeting_date DESC";

$stmt = $conn->prepare($reportSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$meetingsReport = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #0b3d5f;">Administrative Reports</h3>
                    <p class="text-muted mb-0">Overview of meeting tasks, agendas, and attendance delivery.</p>
                </div>
                <span class="badge bg-primary px-3 py-2">Quarterly Review</span>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Meetings Stat -->
            <div class="col-md-6">
                <div class="card p-4 border-0 shadow-sm bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-gov-blue">Meeting Summary</h5>
                        <i class="bi bi-calendar-check fs-4 text-primary"></i>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Total Meetings</span>
                            <span class="fw-bold"><?php echo $mTotal; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Completed</span>
                            <span class="text-success fw-bold"><?php echo $mCompleted; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Upcoming / Active</span>
                            <span class="text-warning fw-bold"><?php echo $mUpcoming; ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tasks Stat -->
            <div class="col-md-6">
                <div class="card p-4 border-0 shadow-sm bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-gov-blue">Task Execution</h5>
                        <i class="bi bi-journal-check fs-4 text-warning"></i>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Total Assigned Tasks</span>
                            <span class="fw-bold"><?php echo $tTotal; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Completed Tasks</span>
                            <span class="text-success fw-bold"><?php echo $tCompleted; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">In Progress / Pending</span>
                            <span class="text-danger fw-bold"><?php echo ($tPending + $tInProgress); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="fw-bold mb-0 text-gov-blue">Meeting Reports Detailed Analysis</h5>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Search meeting title, wing..." style="width: 250px;" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn btn-primary rounded-3">Search</button>
                </form>
            </div>
            
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#eef6ff; border-top: 2px solid #0b3d5f;">
                        <tr>
                            <th>Meeting Details</th>
                            <th>Wing / Dept</th>
                            <th>RSVP Rate</th>
                            <th>Task Progression</th>
                            <th>Status</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetingsReport)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No reports found matching filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meetingsReport as $rep): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($rep['title']); ?></div>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($rep['meeting_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($rep['department']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $attTotal = $rep['total_attendees'];
                                        $attPres = $rep['present_attendees'];
                                        $attRate = $attTotal > 0 ? round(($attPres / $attTotal) * 100) : 0;
                                        ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $attRate; ?>%"></div>
                                            </div>
                                            <span class="small fw-bold text-dark"><?php echo $attRate; ?>% <small class="text-muted">(<?php echo $attPres; ?>/<?php echo $attTotal; ?>)</small></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $taskTotal = $rep['total_tasks'];
                                        $taskComp = $rep['completed_tasks'];
                                        $taskRate = $taskTotal > 0 ? round(($taskComp / $taskTotal) * 100) : 0;
                                        ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                <div class="progress-bar bg-info" style="width: <?php echo $taskRate; ?>%"></div>
                                            </div>
                                            <span class="small fw-bold text-dark"><?php echo $taskRate; ?>% <small class="text-muted">(<?php echo $taskComp; ?>/<?php echo $taskTotal; ?>)</small></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status = strtolower($rep['status']);
                                        $statusClass = match($status) {
                                            'completed' => 'success',
                                            'scheduled' => 'warning text-dark',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="../meetings/view.php?id=<?php echo $rep['id']; ?>" class="btn btn-sm btn-outline-primary rounded-3">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
