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
$department = $_SESSION['department'] ?? '';
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

$taskCompletionPct = $tTotal > 0 ? round(($tCompleted / $tTotal) * 100) : 0;
$meetingCompletionPct = $mTotal > 0 ? round(($mCompleted / $mTotal) * 100) : 0;

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
    $reportSql .= " AND (m.title LIKE ? OR m.department LIKE ?)";
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
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Administrative Reports</h3>
                    <p class="text-muted mb-0">Overview of meeting tasks, agendas, and attendance delivery.</p>
                </div>
                <span class="badge bg-primary px-3 py-2"><i class="fas fa-chart-line me-1"></i> Quarterly Review</span>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-4 h-100">
                    <i class="fas fa-calendar stat-icon"></i>
                    <div class="stat-label mb-1">TOTAL MEETINGS</div>
                    <div class="stat-value counter-value" data-target="<?php echo $mTotal; ?>">0</div>
                    <div class="stat-trend mt-2"><i class="fas fa-check me-1"></i> <?php echo $mCompleted; ?> completed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-4 h-100">
                    <i class="fas fa-calendar-check stat-icon"></i>
                    <div class="stat-label mb-1">MEETING COMPLETION</div>
                    <div class="d-flex align-items-end gap-1">
                        <div class="stat-value counter-value" data-target="<?php echo $meetingCompletionPct; ?>">0</div>
                        <span style="font-size:1.2rem;font-weight:700;opacity:0.9;">%</span>
                    </div>
                    <div class="progress mt-2" style="height: 5px; background: rgba(255,255,255,0.2);"><div class="progress-bar bg-white" style="width: <?php echo $meetingCompletionPct; ?>%"></div></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-4 h-100">
                    <i class="fas fa-tasks stat-icon"></i>
                    <div class="stat-label mb-1">TOTAL TASKS</div>
                    <div class="stat-value counter-value" data-target="<?php echo $tTotal; ?>">0</div>
                    <div class="stat-trend mt-2"><i class="fas fa-spinner me-1"></i> <?php echo ($tPending + $tInProgress); ?> active</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 animate-on-scroll">
                <div class="card stat-card stat-info border-0 p-4 h-100">
                    <i class="fas fa-chart-pie stat-icon"></i>
                    <div class="stat-label mb-1">TASK COMPLETION</div>
                    <div class="d-flex align-items-end gap-1">
                        <div class="stat-value counter-value" data-target="<?php echo $taskCompletionPct; ?>">0</div>
                        <span style="font-size:1.2rem;font-weight:700;opacity:0.9;">%</span>
                    </div>
                    <div class="progress mt-2" style="height: 5px; background: rgba(255,255,255,0.2);"><div class="progress-bar bg-white" style="width: <?php echo $taskCompletionPct; ?>%"></div></div>
                </div>
            </div>
        </div>

        <!-- Detailed Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 animate-on-scroll">
                <div class="card p-4 border-0 shadow-sm bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color: var(--gov-blue);">Meeting Summary</h5>
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
            <div class="col-md-6 animate-on-scroll">
                <div class="card p-4 border-0 shadow-sm bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color: var(--gov-blue);">Task Execution</h5>
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

        <!-- Filter & Search + Table -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll" id="reportsTableWrapper" data-paginate data-per-page="10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--gov-blue);"><i class="fas fa-table me-2"></i>Meeting Reports Detailed Analysis</h5>
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Search meeting title, wing..." style="width: 250px;" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn btn-primary rounded-3"><i class="fas fa-search"></i></button>
                    <a href="index.php" class="btn btn-outline-secondary rounded-3" title="Reset"><i class="fas fa-undo"></i></a>
                </form>
            </div>
            <div class="mb-3">
                <span class="small text-muted">RSVP Rate shows the percentage of invited participants who have been marked present. Formula: (Accepted Responses ÷ Total Invited Participants) × 100.</span>
            </div>

            <div class="table-filter-bar">
                <div class="table-search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Quick filter..." data-table-search="reportsTableWrapper">
                </div>
                <span class="table-result-count"><?php echo count($meetingsReport); ?> records</span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Meeting Details</th>
                            <th>Wing / Dept</th>
                            <th>RSVP Rate <i class="fas fa-info-circle text-muted" title="RSVP Rate = (Accepted Responses ÷ Total Invited Participants) × 100"></i></th>
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
                                            'completed' => 'badge-status-completed',
                                            'scheduled' => 'badge-status-scheduled',
                                            'cancelled' => 'badge-status-cancelled',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
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
        <!-- ═══════════════════════════════════════ -->
        <!-- ATTENDANCE REPORTS SECTION -->
        <!-- ═══════════════════════════════════════ -->
        <?php
        // Fetch attendance data for reports
        $dateFrom = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

        // Meeting-wise attendance report
        $attReportSql = "SELECT m.id, m.title, m.meeting_date, m.department,
                         COUNT(a.id) as total_invited,
                         SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                         SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                         SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
                         SUM(CASE WHEN a.status = 'Pending' THEN 1 ELSE 0 END) as pending_count
                         FROM meetings m
                         LEFT JOIN attendance a ON m.id = a.meeting_id";

        $attParams = [];
        $attTypes = "";

        if ($role === 'Organizer') {
            $attReportSql .= " WHERE m.organizer_id = ?";
            $attParams[] = $user_id;
            $attTypes .= "i";
        } elseif ($role === 'Employee') {
            $attReportSql .= " WHERE (m.department = ? OR EXISTS (SELECT 1 FROM attendance a2 WHERE a2.meeting_id = m.id AND a2.user_id = ?))";
            $attParams[] = $department;
            $attParams[] = $user_id;
            $attTypes .= "si";
        } else {
            $attReportSql .= " WHERE 1=1";
        }

        if (!empty($dateFrom)) {
            $attReportSql .= " AND m.meeting_date >= ?";
            $attParams[] = $dateFrom;
            $attTypes .= "s";
        }
        if (!empty($dateTo)) {
            $attReportSql .= " AND m.meeting_date <= ?";
            $attParams[] = $dateTo;
            $attTypes .= "s";
        }

        $attReportSql .= " GROUP BY m.id ORDER BY m.meeting_date DESC";

        $stmt = $conn->prepare($attReportSql);
        if (!empty($attParams)) {
            $stmt->bind_param($attTypes, ...$attParams);
        }
        $stmt->execute();
        $attReport = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Department-wise attendance summary
        $deptAttSql = "SELECT m.department,
                       COUNT(DISTINCT m.id) as meeting_count,
                       COUNT(a.id) as total_invited,
                       SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                       SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                       SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count
                       FROM meetings m
                       LEFT JOIN attendance a ON m.id = a.meeting_id";

        $deptParams = [];
        $deptTypes = "";

        if ($role === 'Organizer') {
            $deptAttSql .= " WHERE m.organizer_id = ?";
            $deptParams[] = $user_id;
            $deptTypes .= "i";
        } elseif ($role === 'Employee') {
            $deptAttSql .= " WHERE m.department = ?";
            $deptParams[] = $department;
            $deptTypes .= "s";
        } else {
            $deptAttSql .= " WHERE 1=1";
        }

        if (!empty($dateFrom)) {
            $deptAttSql .= " AND m.meeting_date >= ?";
            $deptParams[] = $dateFrom;
            $deptTypes .= "s";
        }
        if (!empty($dateTo)) {
            $deptAttSql .= " AND m.meeting_date <= ?";
            $deptParams[] = $dateTo;
            $deptTypes .= "s";
        }

        $deptAttSql .= " GROUP BY m.department ORDER BY m.department";

        $stmt = $conn->prepare($deptAttSql);
        if (!empty($deptParams)) {
            $stmt->bind_param($deptTypes, ...$deptParams);
        }
        $stmt->execute();
        $deptAttReport = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>

        <!-- Attendance Reports Header -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll" style="border-left: 4px solid #16a34a;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color: var(--gov-blue);"><i class="fas fa-clipboard-check text-success me-2"></i>Attendance Reports</h4>
                    <p class="text-muted mb-0">Meeting-wise and department-wise attendance analysis.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success rounded-3" onclick="exportReportTableToCSV('attendance_report.csv', 'meetingAttTable')">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-3" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Date Filter for Attendance -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll">
            <form method="GET" class="row g-3 align-items-end">
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control rounded-3" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control rounded-3" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 py-2 flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary rounded-3 py-2" title="Reset"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Meeting-wise Attendance Table -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll" id="meetingAttWrapper" data-paginate data-per-page="10">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-calendar-check text-primary me-2"></i>Meeting-wise Attendance
            </h5>

            <div class="table-filter-bar">
                <div class="table-search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Quick filter..." data-table-search="meetingAttWrapper">
                </div>
                <span class="table-result-count"><?php echo count($attReport); ?> records</span>
            </div>

            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0" id="meetingAttTable">
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Total Invited</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Present %</th>
                            <th>Absent %</th>
                            <th>Late %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attReport)): ?>
                            <tr><td colspan="10" class="text-center py-4 text-muted">No attendance data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attReport as $ar): ?>
                                <?php
                                $arTotal = (int)$ar['total_invited'];
                                $arPresent = (int)$ar['present_count'];
                                $arAbsent = (int)$ar['absent_count'];
                                $arLate = (int)$ar['late_count'];
                                $arPresentPct = $arTotal > 0 ? round(($arPresent / $arTotal) * 100) : 0;
                                $arAbsentPct = $arTotal > 0 ? round(($arAbsent / $arTotal) * 100) : 0;
                                $arLatePct = $arTotal > 0 ? round(($arLate / $arTotal) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <a href="../meetings/attendance.php?id=<?php echo $ar['id']; ?>" class="fw-bold text-decoration-none">
                                            <?php echo htmlspecialchars($ar['title']); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ar['department']); ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($ar['meeting_date'])); ?></td>
                                    <td class="fw-semibold"><?php echo $arTotal; ?></td>
                                    <td><span class="text-success fw-bold"><?php echo $arPresent; ?></span></td>
                                    <td><span class="text-danger fw-bold"><?php echo $arAbsent; ?></span></td>
                                    <td><span class="text-warning fw-bold"><?php echo $arLate; ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 50px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $arPresentPct; ?>%"></div>
                                            </div>
                                            <small class="fw-bold"><?php echo $arPresentPct; ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 50px;">
                                                <div class="progress-bar bg-danger" style="width: <?php echo $arAbsentPct; ?>%"></div>
                                            </div>
                                            <small class="fw-bold"><?php echo $arAbsentPct; ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height: 6px; width: 50px;">
                                                <div class="progress-bar bg-warning" style="width: <?php echo $arLatePct; ?>%"></div>
                                            </div>
                                            <small class="fw-bold"><?php echo $arLatePct; ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Department-wise Attendance Summary -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll" id="deptAttWrapper" data-paginate data-per-page="10">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-building text-info me-2"></i>Department-wise Attendance Summary
            </h5>

            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0" id="deptAttTable">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Meetings</th>
                            <th>Total Invited</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Present %</th>
                            <th>Absent %</th>
                            <th>Late %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deptAttReport)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No department attendance data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($deptAttReport as $dar): ?>
                                <?php
                                $darTotal = (int)$dar['total_invited'];
                                $darPresent = (int)$dar['present_count'];
                                $darAbsent = (int)$dar['absent_count'];
                                $darLate = (int)$dar['late_count'];
                                $darPresentPct = $darTotal > 0 ? round(($darPresent / $darTotal) * 100) : 0;
                                $darAbsentPct = $darTotal > 0 ? round(($darAbsent / $darTotal) * 100) : 0;
                                $darLatePct = $darTotal > 0 ? round(($darLate / $darTotal) * 100) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($dar['department']); ?></td>
                                    <td><?php echo (int)$dar['meeting_count']; ?></td>
                                    <td class="fw-semibold"><?php echo $darTotal; ?></td>
                                    <td><span class="text-success fw-bold"><?php echo $darPresent; ?></span></td>
                                    <td><span class="text-danger fw-bold"><?php echo $darAbsent; ?></span></td>
                                    <td><span class="text-warning fw-bold"><?php echo $darLate; ?></span></td>
                                    <td><small class="fw-bold"><?php echo $darPresentPct; ?>%</small></td>
                                    <td><small class="fw-bold"><?php echo $darAbsentPct; ?>%</small></td>
                                    <td><small class="fw-bold"><?php echo $darLatePct; ?>%</small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Export Script for Reports -->
<script>
function exportReportTableToCSV(filename, tableId) {
    var table = document.getElementById(tableId);
    if (!table) { alert('No data to export.'); return; }
    var csv = [];
    var rows = table.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        cols.forEach(function(col) {
            var text = col.innerText.replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    var csvString = csv.join('\n');
    var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<!-- Print Styles for Reports -->
<style>
@media print {
    .header, .sidebar, .sidebar-backdrop, .footer, .table-filter-bar,
    form, .btn, .pagination-wrapper, .badge.bg-primary { display: none !important; }
    .main-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
    .stat-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .progress-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<?php include_once '../../includes/footer.php'; ?>
