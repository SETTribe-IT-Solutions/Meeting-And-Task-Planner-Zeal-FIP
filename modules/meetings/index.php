<?php
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
$result = $conn->query(
    "SELECT m.id, m.title, m.meeting_date, m.meeting_time, m.location, m.mode, m.department, m.status, u.name AS organizer_name
     FROM meetings m
     JOIN users u ON m.organizer_id = u.id
     ORDER BY m.meeting_date DESC, m.meeting_time DESC"
);

if ($result) {
    $meetings = $result->fetch_all(MYSQLI_ASSOC);
} else {
    error_log('Meetings query failed: ' . $conn->error);
    $meetings = [];
}

// Count stats
$totalMeetings = count($meetings);
$scheduledCount = count(array_filter($meetings, fn($m) => strtolower($m['status']) === 'scheduled'));
$completedCount = count(array_filter($meetings, fn($m) => strtolower($m['status']) === 'completed'));
$cancelledCount = count(array_filter($meetings, fn($m) => strtolower($m['status']) === 'cancelled'));
?>
<div class="row">
    <div class="col-12">
        <div class="card p-4 border-0 shadow-sm mb-4 animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Meeting List</h3>
                    <p class="text-muted mb-0">Official meetings scheduled for departments and teams.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-dark border px-3 py-2"><?php echo $totalMeetings; ?> Total</span>
                    <a href="create.php" class="btn btn-primary rounded-3"><i class="fas fa-plus-circle me-1"></i> Schedule Meeting</a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-3">
                    <div class="stat-label">TOTAL</div>
                    <div class="stat-value counter-value" data-target="<?php echo $totalMeetings; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-3">
                    <div class="stat-label">SCHEDULED</div>
                    <div class="stat-value counter-value" data-target="<?php echo $scheduledCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-3">
                    <div class="stat-label">COMPLETED</div>
                    <div class="stat-value counter-value" data-target="<?php echo $completedCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-danger border-0 p-3">
                    <div class="stat-label">CANCELLED</div>
                    <div class="stat-value counter-value" data-target="<?php echo $cancelledCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm animate-on-scroll" id="meetingsTableWrapper" data-paginate data-per-page="10">
            <!-- Table Search -->
            <div class="table-filter-bar">
                <div class="table-search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search meetings..." data-table-search="meetingsTableWrapper">
                </div>
                <span class="table-result-count"><?php echo $totalMeetings; ?> records</span>
            </div>

            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Meeting</th>
                            <th>Date & Time</th>
                            <th>Department</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Organizer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meetings as $meeting): ?>
                            <tr style="cursor: pointer;" onclick="window.location='view.php?id=<?php echo $meeting['id']; ?>'">
                                <td>
                                    <strong><?php echo htmlspecialchars($meeting['title']); ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($meeting['location']); ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></div>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo formatTime12Hour($meeting['meeting_time']); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($meeting['department']); ?></span></td>
                                <td>
                                    <?php
                                    $modeIcon = match(strtolower($meeting['mode'])) {
                                        'online' => 'fa-video',
                                        'offline' => 'fa-building',
                                        'hybrid' => 'fa-arrows-alt',
                                        default => 'fa-circle'
                                    };
                                    ?>
                                    <span><i class="fas <?php echo $modeIcon; ?> me-1 text-muted"></i><?php echo htmlspecialchars($meeting['mode']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusLower = strtolower($meeting['status']);
                                    $badgeClass = match($statusLower) {
                                        'scheduled' => 'badge-status-scheduled',
                                        'ongoing' => 'badge-status-ongoing',
                                        'completed' => 'badge-status-completed',
                                        'cancelled' => 'badge-status-cancelled',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($meeting['status']); ?></span>
                                </td>
                                <td><span class="fw-medium"><?php echo htmlspecialchars($meeting['organizer_name']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
