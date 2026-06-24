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
    "SELECT m.id, m.title, m.meeting_date, m.meeting_time, m.location, m.mode, m.department, m.status, u.id AS organizer_id, u.name AS organizer_name
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
// Load users for modal assignee dropdown
$userRes = $conn->query('SELECT id, name, email FROM users WHERE isDeleted = "No" ORDER BY name');
$users = $userRes ? $userRes->fetch_all(MYSQLI_ASSOC) : [];
$today = date('Y-m-d');
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
                            <th class="text-end">Actions</th>
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
                                <td class="text-end">
                                    <a href="../tasks/create.php?meeting_id=<?php echo (int)$meeting['id']; ?>" class="btn btn-sm btn-outline-primary open-add-task-modal" data-meeting-id="<?php echo (int)$meeting['id']; ?>" data-meeting-title="<?php echo htmlspecialchars($meeting['title']); ?>" data-meeting-date="<?php echo htmlspecialchars($meeting['meeting_date']); ?>" data-meeting-department="<?php echo htmlspecialchars($meeting['department']); ?>" data-organizer-id="<?php echo (int)$meeting['organizer_id']; ?>" data-organizer-name="<?php echo htmlspecialchars($meeting['organizer_name']); ?>" onclick="event.stopPropagation();">
                                        <i class="fas fa-plus-circle me-1"></i> Add Task
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel"><i class="fas fa-tasks me-2 text-warning"></i> Assign New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../../controllers/TaskController.php" method="POST">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Meeting Reference</label>
                            <select name="meeting_id" id="modal_meeting_id" class="form-select rounded-3" required>
                                <option value="">Select meeting</option>
                                <?php foreach ($meetings as $m): ?>
                                    <option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Task Title</label>
                            <input type="text" name="title" id="modal_task_title" class="form-control rounded-3" required placeholder="e.g., Prepare financial report">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department" id="modal_department_select" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php
                                $deptRes = $conn->query('SELECT id, name FROM departments WHERE is_active = "Yes" ORDER BY name');
                                $depts = $deptRes ? $deptRes->fetch_all(MYSQLI_ASSOC) : [];
                                foreach ($depts as $d):
                                ?>
                                    <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To (Department Employees)</label>
                            <select name="assigned_to[]" id="modal_assigned_to" class="form-select rounded-3" multiple required style="min-height:120px;">
                                <!-- Populated dynamically based on department -->
                            </select>
                            <small class="text-muted">Select one or more employees from the chosen department.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="modal_due_date" class="form-control rounded-3" required min="<?php echo htmlspecialchars($today); ?>" data-past-date-message="Past dates are not allowed. Please select today or a future date.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority Level</label>
                            <select name="priority" id="modal_priority" class="form-select rounded-3" required>
                                <option value="">Select priority</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Additional Notes / Instructions</label>
                            <textarea name="notes" id="modal_notes" class="form-control rounded-3" rows="3" placeholder="Provide context or specific instructions for this task..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fas fa-save me-1"></i> Save Task</button>
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
