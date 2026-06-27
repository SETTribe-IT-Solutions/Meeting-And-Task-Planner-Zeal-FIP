<?php
// modules/meetings/attendance.php
// Per-meeting attendance report page
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
$meetingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($meetingId <= 0) {
    echo "<div class='alert alert-danger rounded-3'>Invalid Meeting ID.</div>";
    include_once '../../includes/footer.php';
    exit();
}

// Fetch meeting info
$stmt = $conn->prepare("SELECT m.*, u.name as organizer_name FROM meetings m JOIN users u ON m.organizer_id = u.id WHERE m.id = ?");
$stmt->bind_param("i", $meetingId);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    echo "<div class='alert alert-danger rounded-3'>Meeting not found.</div>";
    include_once '../../includes/footer.php';
    exit();
}

// Filters
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build attendance query with filters
$sql = "SELECT a.id as attendance_id, a.status as att_status, a.remarks, a.check_in_time,
               u.id as user_id, u.name as user_name, u.email as user_email, u.department as user_dept
        FROM attendance a
        JOIN users u ON a.user_id = u.id
        WHERE a.meeting_id = ?";
$params = [$meetingId];
$types = "i";

if (!empty($statusFilter)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND (u.name LIKE ? OR u.department LIKE ?)";
    $searchWildcard = "%" . $searchQuery . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

$sql .= " ORDER BY u.name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance stats
$totalAttendees = count($attendees);
$presentCount = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Present'));
$absentCount = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Absent'));
$lateCount = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Late'));
$pendingCount = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Pending'));

$presentPct = $totalAttendees > 0 ? round(($presentCount / $totalAttendees) * 100) : 0;
$absentPct = $totalAttendees > 0 ? round(($absentCount / $totalAttendees) * 100) : 0;
$latePct = $totalAttendees > 0 ? round(($lateCount / $totalAttendees) * 100) : 0;
?>

<div class="row g-4">
    <div class="col-12">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Meetings</a></li>
                <li class="breadcrumb-item"><a href="view.php?id=<?php echo $meetingId; ?>"><?php echo htmlspecialchars($meeting['title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Attendance</li>
            </ol>
        </nav>

        <!-- Meeting Header Card -->
        <div class="card p-4 border-0 shadow-sm mb-4 animate-on-scroll" style="background: linear-gradient(135deg, #0b3d5f 0%, #1a5f7a 100%); color: white; border-radius: var(--radius-xl, 16px);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1"><i class="fas fa-clipboard-list me-2"></i>Attendance Report</h3>
                    <p class="mb-0 text-white-50"><?php echo htmlspecialchars($meeting['title']); ?></p>
                    <div class="d-flex gap-3 mt-2 flex-wrap">
                        <span class="d-flex align-items-center gap-1" style="font-size: 0.85rem; opacity: 0.85;">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?>
                        </span>
                        <span class="d-flex align-items-center gap-1" style="font-size: 0.85rem; opacity: 0.85;">
                            <i class="far fa-clock"></i> <?php echo formatTime12Hour($meeting['meeting_time']); ?>
                        </span>
                        <span class="d-flex align-items-center gap-1" style="font-size: 0.85rem; opacity: 0.85;">
                            <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($meeting['organizer_name']); ?>
                        </span>
                        <span class="d-flex align-items-center gap-1" style="font-size: 0.85rem; opacity: 0.85;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($meeting['department']); ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="view.php?id=<?php echo $meetingId; ?>" class="btn btn-sm btn-outline-light rounded-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Meeting
                    </a>
                </div>
            </div>
        </div>

        <!-- Attendance Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-3">
                    <div class="stat-label">PRESENT</div>
                    <div class="stat-value counter-value" data-target="<?php echo $presentCount; ?>" style="font-size:1.5rem;">0</div>
                    <small class="mt-1" style="opacity:0.9;"><?php echo $presentPct; ?>%</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-danger border-0 p-3">
                    <div class="stat-label">ABSENT</div>
                    <div class="stat-value counter-value" data-target="<?php echo $absentCount; ?>" style="font-size:1.5rem;">0</div>
                    <small class="mt-1" style="opacity:0.9;"><?php echo $absentPct; ?>%</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-3">
                    <div class="stat-label">LATE</div>
                    <div class="stat-value counter-value" data-target="<?php echo $lateCount; ?>" style="font-size:1.5rem;">0</div>
                    <small class="mt-1" style="opacity:0.9;"><?php echo $latePct; ?>%</small>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-3">
                    <div class="stat-label">PENDING</div>
                    <div class="stat-value counter-value" data-target="<?php echo $pendingCount; ?>" style="font-size:1.5rem;">0</div>
                    <small class="mt-1" style="opacity:0.9;"><?php echo $totalAttendees; ?> total</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="id" value="<?php echo $meetingId; ?>">
                <div class="col-md-4">
                    <label class="form-label">Search Attendee</label>
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Name or department..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="">All Statuses</option>
                        <option value="Present" <?php echo $statusFilter === 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Absent" <?php echo $statusFilter === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                        <option value="Late" <?php echo $statusFilter === 'Late' ? 'selected' : ''; ?>>Late</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 py-2 flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="attendance.php?id=<?php echo $meetingId; ?>" class="btn btn-outline-secondary rounded-3 py-2" title="Reset"><i class="fas fa-undo"></i></a>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-outline-success rounded-3 py-2 flex-grow-1" onclick="exportTableToCSV('attendance_report.csv')" title="Export to Excel/CSV"><i class="fas fa-file-csv me-1"></i> CSV</button>
                    <button type="button" class="btn btn-outline-secondary rounded-3 py-2" onclick="window.print()" title="Print / PDF"><i class="fas fa-print"></i></button>
                </div>
            </form>
        </div>

        <!-- Attendance Table -->
        <div class="card p-4 border-0 shadow-sm bg-white animate-on-scroll" id="attendanceDetailWrapper" data-paginate data-per-page="10">
            <?php if (empty($attendees)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>No attendance records found for this meeting.</p>
                </div>
            <?php else: ?>
                <div class="table-filter-bar">
                    <div class="table-search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Quick filter..." data-table-search="attendanceDetailWrapper">
                    </div>
                    <span class="table-result-count"><?php echo $totalAttendees; ?> records</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-enhanced table-hover align-middle mb-0" id="attendanceTable">
                        <thead>
                            <tr>
                                <th>Attendee Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Attendance Status</th>
                                <th>Check-in Time</th>
                                <th>Meeting Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendees as $att): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($att['user_name']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($att['user_dept']); ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($att['user_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $att['att_status'];
                                        $badgeClass = match($status) {
                                            'Present' => 'badge-status-completed',
                                            'Absent' => 'badge-status-cancelled',
                                            'Late' => 'bg-warning text-dark',
                                            'Pending' => 'badge-status-scheduled',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td>
                                        <?php echo !empty($att['check_in_time']) ? formatTime12Hour($att['check_in_time']) : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo !empty($att['remarks']) ? htmlspecialchars($att['remarks']) : '—'; ?></span>
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

<!-- Export to CSV Script -->
<script>
function exportTableToCSV(filename) {
    var table = document.getElementById('attendanceTable');
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

<!-- Print Styles -->
<style>
@media print {
    .header, .sidebar, .sidebar-backdrop, .footer, .table-filter-bar,
    .breadcrumb, form, .btn, .pagination-wrapper, nav { display: none !important; }
    .main-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    .stat-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<?php include_once '../../includes/footer.php'; ?>
