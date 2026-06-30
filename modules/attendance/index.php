<?php
// modules/attendance/index.php
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

// Filters
$meetingIdFilter = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// 1. Calculate ratios
if ($role === 'Employee') {
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE user_id = ? GROUP BY status");
    $stmt->bind_param("i", $user_id);
} else {
    // Collector and Organizer (super admin) both see all attendance
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM attendance GROUP BY status");
}
$stmt->execute();
$countsResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statusCounts = ['Present' => 0, 'Absent' => 0, 'Pending' => 0, 'Late' => 0];
$total = 0;
foreach ($countsResult as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    $total += (int)$row['count'];
}

$ratios = ['present' => 0, 'absent' => 0, 'pending' => 0, 'late' => 0];
if ($total > 0) {
    $ratios['present'] = round(($statusCounts['Present'] / $total) * 100);
    $ratios['absent'] = round(($statusCounts['Absent'] / $total) * 100);
    $ratios['pending'] = round(($statusCounts['Pending'] / $total) * 100);
    $ratios['late'] = round(($statusCounts['Late'] / $total) * 100);
}

// 2. Load meetings for dropdown filter
if ($role === 'Collector' || $role === 'Organizer') {
    // Both see all meetings
    $meetingsRes = $conn->query("SELECT id, title FROM meetings ORDER BY meeting_date DESC");
} else {
    $stmt = $conn->prepare("SELECT DISTINCT m.id, m.title FROM meetings m JOIN attendance a ON m.id = a.meeting_id WHERE a.user_id = ? ORDER BY m.meeting_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $meetingsRes = $stmt->get_result();
}
$meetingsList = $meetingsRes->fetch_all(MYSQLI_ASSOC);

// 3. Build attendance records query
$sql = "";
$params = [];
$types = "";

if ($role === 'Collector') {
    $sql = "SELECT a.*, a.check_in_time, m.title as meeting_title, u.name as employee_name, u.department as employee_dept, u.email as employee_email
            FROM attendance a
            JOIN meetings m ON a.meeting_id = m.id
            JOIN users u ON a.user_id = u.id
            WHERE 1=1";
} elseif ($role === 'Organizer') {
    $sql = "SELECT a.*, a.check_in_time, m.title as meeting_title, u.name as employee_name, u.department as employee_dept, u.email as employee_email
            FROM attendance a
            JOIN meetings m ON a.meeting_id = m.id
            JOIN users u ON a.user_id = u.id
            WHERE m.organizer_id = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    $sql = "SELECT a.*, a.check_in_time, m.title as meeting_title, u.name as employee_name, u.department as employee_dept, u.email as employee_email
            FROM attendance a
            JOIN meetings m ON a.meeting_id = m.id
            JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

if ($meetingIdFilter > 0) {
    $sql .= " AND a.meeting_id = ?";
    $params[] = $meetingIdFilter;
    $types .= "i";
}
if (!empty($statusFilter)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND u.name LIKE ?";
    $searchWildcard = "%" . $searchQuery . "%";
    $params[] = $searchWildcard;
    $types .= "s";
}

$sql .= " ORDER BY m.meeting_date DESC, u.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendanceRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-12">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-4 border-0 mb-4 shadow-sm animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Attendance Portal</h3>
                    <p class="text-muted mb-0">Track and log meeting participation across administrative wings.</p>
                </div>
            </div>
        </div>

        <!-- Attendance Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-4">
                    <i class="fas fa-check-circle stat-icon"></i>
                    <div class="stat-label mb-1">PRESENT</div>
                    <div class="stat-value counter-value" data-target="<?php echo $statusCounts['Present']; ?>" style="font-size:2rem;">0</div>
                    <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-white" style="width: <?php echo $ratios['present']; ?>%"></div>
                    </div>
                    <div class="stat-trend mt-2"><i class="fas fa-percent me-1"></i> <?php echo $ratios['present']; ?>% of total</div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-4">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-label mb-1">PRESENT WITH LATE</div>
                    <div class="stat-value counter-value" data-target="<?php echo $statusCounts['Present with Late']; ?>" style="font-size:2rem;">0</div>
                    <div class="progress mt-3" style="height: 6px; background: rgba(0,0,0,0.1);">
                        <div class="progress-bar bg-dark" style="width: <?php echo $ratios['present_late']; ?>%"></div>
                    </div>
                    <div class="stat-trend mt-2"><i class="fas fa-percent me-1"></i> <?php echo $ratios['present_late']; ?>% of total</div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-danger border-0 p-4">
                    <i class="fas fa-times-circle stat-icon"></i>
                    <div class="stat-label mb-1">ABSENT</div>
                    <div class="stat-value counter-value" data-target="<?php echo $statusCounts['Absent']; ?>" style="font-size:2rem;">0</div>
                    <div class="progress mt-3" style="height: 6px; background: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-white" style="width: <?php echo $ratios['absent']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Card -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search Employee</label>
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Enter name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Meeting</label>
                    <select name="meeting_id" class="form-select rounded-3">
                        <option value="0">All Meetings</option>
                        <?php foreach ($meetingsList as $meeting): ?>
                            <option value="<?php echo $meeting['id']; ?>" <?php echo $meetingIdFilter === (int)$meeting['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($meeting['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="">All Statuses</option>
                        <option value="Present" <?php echo $statusFilter === 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Absent" <?php echo $statusFilter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="Late" <?php echo $statusFilter === 'Late' ? 'selected' : ''; ?>>Late</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 py-2 flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary rounded-3 py-2" title="Reset"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Attendance Records List -->
        <div class="card p-4 border-0 shadow-sm bg-white animate-on-scroll" id="attendanceTableWrapper" data-paginate data-per-page="10">
            <?php if (empty($attendanceRecords)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>No attendance records found matching filters.</p>
                </div>
            <?php else: ?>
                <div class="table-filter-bar">
                    <div class="table-search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Quick filter..." data-table-search="attendanceTableWrapper">
                    </div>
                    <span class="table-result-count"><?php echo count($attendanceRecords); ?> records</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-enhanced table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Employee / Wing</th>
                                <th>Meeting Room</th>
                                <th>Status</th>
                                <th>Check-in Time</th>
                                <th>Remarks & Feedback</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($record['employee_dept']); ?> | <?php echo htmlspecialchars($record['employee_email']); ?></small>
                                    </td>
                                    <td>
                                        <a href="../meetings/view.php?id=<?php echo $record['meeting_id']; ?>" class="text-decoration-none fw-semibold">
                                            <?php echo htmlspecialchars($record['meeting_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $record['status'];
                                        $badgeClass = match($status) {
                                            'Present' => 'badge-status-completed',
                                            'Absent' => 'badge-status-cancelled',
                                            'Late' => 'bg-warning text-dark',
                                            'Pending' => 'badge-status-scheduled',
                                            default => 'bg-secondary'
                                        };
                                        $attStatuses = [
                                            'Present'           => ['icon' => 'fa-check-circle',    'cls' => 'text-success',   'label' => 'Present'],
                                            'Present with Late' => ['icon' => 'fa-clock',           'cls' => 'text-warning',   'label' => 'Present with Late'],
                                            'Absent'            => ['icon' => 'fa-times-circle',    'cls' => 'text-danger',    'label' => 'Absent'],
                                            'Not Updated'       => ['icon' => 'fa-question-circle', 'cls' => 'text-secondary', 'label' => 'Not Updated'],
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusConf['badge']; ?>">
                                            <i class="fas <?php echo $statusConf['icon']; ?> me-1"></i><?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($record['arrival_time'])): ?>
                                            <small class="text-success fw-semibold"><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars(formatTime12Hour($record['arrival_time'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo !empty($record['check_in_time']) ? formatTime12Hour($record['check_in_time']) : '—'; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo !empty($record['remarks']) ? htmlspecialchars($record['remarks']) : '—'; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <!-- Inline status editing form -->
                                        <form action="../../controllers/AttendanceController.php" method="POST" class="d-inline-block">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="attendance_id" value="<?php echo $record['id']; ?>">
                                            <input type="hidden" name="meeting_id" value="<?php echo $record['meeting_id']; ?>">
                                            <input type="hidden" name="redirect" value="../modules/attendance/index.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>">
                                            
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                <select name="status" class="form-select form-select-sm rounded-3 w-auto" style="min-width: 110px;" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Present" <?php echo $status === 'Present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="Absent" <?php echo $status === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                                    <option value="Late" <?php echo $status === 'Late' ? 'selected' : ''; ?>>Late</option>
                                                </select>
                                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" 
                                                        title="Edit remarks" 
                                                        onclick="promptRemarks(this, '<?php echo htmlspecialchars(addslashes($record['remarks'] ?? '')); ?>')">
                                                    <i class="fas fa-comment"></i>
                                                </button>
                                            </form>
                                            <!-- Edit Arrival Time (Organizer only, for Present/Late) -->
                                            <?php if ($role === 'Organizer' && in_array($status, ['Present', 'Present with Late'], true)): ?>
                                            <form action="../../controllers/AttendanceController.php" method="POST" class="m-0"
                                                  id="atime_<?php echo $record['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="attendance_id" value="<?php echo $record['id']; ?>">
                                                <input type="hidden" name="meeting_id" value="<?php echo $record['meeting_id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                                <input type="hidden" name="remarks" value="<?php echo htmlspecialchars($record['remarks'] ?? ''); ?>">
                                                <input type="hidden" name="arrival_time" id="atime_val_<?php echo $record['id']; ?>"
                                                       value="<?php echo htmlspecialchars($record['arrival_time'] ?? ''); ?>">
                                                <input type="hidden" name="redirect" value="../modules/attendance/index.php?<?php echo htmlspecialchars($_SERVER['QUERY_STRING'] ?? ''); ?>">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-success rounded-3 px-2"
                                                        title="Edit Arrival Time"
                                                        onclick="promptAttArrivalTime('<?php echo $record['id']; ?>', <?php echo htmlspecialchars(json_encode($record['arrival_time'] ?? ''), ENT_QUOTES); ?>)">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Collector: read-only icon badge -->
                                            <span class="badge <?php echo $statusConf['badge']; ?>">
                                                <i class="fas <?php echo $statusConf['icon']; ?> me-1"></i><?php echo htmlspecialchars($status); ?>
                                            </span>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function promptRemarks(recordId, currentRemarks) {
    var newRemarks = prompt("Enter attendance remarks/feedback:", currentRemarks || '');
    if (newRemarks !== null) {
        document.getElementById('rmk_val_' + recordId).value = newRemarks;
        document.getElementById('rmk_' + recordId).submit();
    }
}
function promptAttArrivalTime(recordId, currentTime) {
    var t = prompt("Enter arrival time (HH:MM, 24-hour format):", currentTime ? currentTime.substring(0, 5) : '');
    if (t !== null) {
        if (t !== '' && !/^\d{2}:\d{2}$/.test(t)) {
            alert('Invalid time format. Use HH:MM (e.g. 09:30).');
            return;
        }
        document.getElementById('atime_val_' + recordId).value = t ? t + ':00' : '';
        document.getElementById('atime_' + recordId).submit();
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>
