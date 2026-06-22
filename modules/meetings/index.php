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
$meetings = $result->fetch_all(MYSQLI_ASSOC);
?>
<div class="row">
    <div class="col-12">
        <div class="card p-4 border-0 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1">Meeting List</h3>
                    <p class="text-muted mb-0">Official meetings scheduled for departments and teams.</p>
                </div>
                <a href="create.php" class="btn btn-primary rounded-3" style="background-color: var(--gov-blue);">+ Schedule Meeting</a>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#e6f0f8; border-top: 2px solid var(--gov-blue);">
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
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($meeting['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($meeting['location']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($meeting['meeting_date']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($meeting['meeting_time']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($meeting['department']); ?></td>
                                <td><?php echo htmlspecialchars($meeting['mode']); ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?php echo htmlspecialchars($meeting['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($meeting['organizer_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
