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
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$userDepartment = $_SESSION['department'] ?? '';

$sql = "SELECT m.id, m.title, m.meeting_date, m.meeting_time, m.location, m.mode, m.department, m.status, u.id AS organizer_id, u.name AS organizer_name
        FROM meetings m
        JOIN users u ON m.organizer_id = u.id";

if ($role === 'Employee') {
    $sql .= " WHERE (m.department = ? OR EXISTS (SELECT 1 FROM attendance a WHERE a.meeting_id = m.id AND a.user_id = ?))";
}

$sql .= " ORDER BY m.meeting_date ASC, m.meeting_time ASC";

$stmt = $conn->prepare($sql);
if ($role === 'Employee') {
    $stmt->bind_param('si', $userDepartment, $user_id);
}
$stmt->execute();
$result  = $stmt->get_result();
$meetings = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="meeting-info-badge">
                        <i class="fas fa-clock text-primary me-1"></i>
                        <span><strong>Next:</strong> <?php echo htmlspecialchars($nextMeetingText ?? 'No upcoming meetings'); ?></span>
                    </div>
                    <div class="meeting-info-badge">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        <span><?php echo htmlspecialchars($tasksDueTodayText ?? '0 tasks due today'); ?></span>
                    </div>
                    <?php if ($role === 'Organizer'): ?>
                    <a href="create.php" class="btn btn-primary rounded-3"><i class="fas fa-plus-circle me-1"></i> Schedule Meeting</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats (click to filter) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-3 stat-filter-card" data-filter="all" role="button" title="Show all meetings">
                    <div class="stat-label">TOTAL</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $totalMeetings; ?></div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-3 stat-filter-card" data-filter="scheduled" role="button" title="Show scheduled meetings">
                    <div class="stat-label">SCHEDULED</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $scheduledCount; ?></div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-3 stat-filter-card" data-filter="completed" role="button" title="Show completed meetings">
                    <div class="stat-label">COMPLETED</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $completedCount; ?></div>
                </div>
            </div>
            <div class="col-md-3 animate-on-scroll">
                <div class="card stat-card stat-danger border-0 p-3 stat-filter-card" data-filter="cancelled" role="button" title="Show cancelled meetings">
                    <div class="stat-label">CANCELLED</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $cancelledCount; ?></div>
                </div>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm" id="meetingsTableWrapper" data-paginate data-per-page="10">
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
                            <tr style="cursor: pointer;" data-row-status="<?php echo strtolower(htmlspecialchars($meeting['status'])); ?>" onclick="window.location='view.php?id=<?php echo $meeting['id']; ?>'">
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
                                <td class="text-end" onclick="event.stopPropagation();">
                                    <div class="d-flex gap-1 justify-content-end flex-nowrap">
                                    <?php if ($role === 'Organizer'): ?>
                                        <?php if (strtolower($meeting['status']) === 'scheduled'): ?>
                                        <a href="edit.php?id=<?php echo (int)$meeting['id']; ?>"
                                           class="btn btn-sm btn-outline-warning rounded-3 px-2"
                                           title="Edit Meeting">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success rounded-3 px-2"
                                            title="Mark as Completed"
                                            data-meeting-id="<?php echo (int)$meeting['id']; ?>"
                                            data-meeting-title="<?php echo htmlspecialchars($meeting['title']); ?>"
                                            onclick="openCompleteModal(this)">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-3 px-2"
                                            title="Cancel Meeting"
                                            data-meeting-id="<?php echo (int)$meeting['id']; ?>"
                                            data-meeting-title="<?php echo htmlspecialchars($meeting['title']); ?>"
                                            data-meeting-date="<?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?>"
                                            onclick="openCancelModal(this)">
                                            <i class="fas fa-calendar-times"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (strtolower($meeting['status']) === 'completed'): ?>
                                        <a href="../tasks/create.php?meeting_id=<?php echo (int)$meeting['id']; ?>"
                                           class="btn btn-sm btn-outline-primary rounded-3 open-add-task-modal"
                                           data-meeting-id="<?php echo (int)$meeting['id']; ?>"
                                           data-meeting-title="<?php echo htmlspecialchars($meeting['title']); ?>"
                                           data-meeting-date="<?php echo htmlspecialchars($meeting['meeting_date']); ?>"
                                           data-meeting-department="<?php echo htmlspecialchars($meeting['department']); ?>"
                                           data-organizer-id="<?php echo (int)$meeting['organizer_id']; ?>"
                                           data-organizer-name="<?php echo htmlspecialchars($meeting['organizer_name']); ?>"
                                           title="Add Task">
                                            <i class="fas fa-plus-circle"></i>
                                        </a>
                                        <?php else: ?>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary rounded-3 px-2"
                                            title="Tasks can only be added after the meeting is Completed"
                                            disabled style="opacity:0.4; cursor:not-allowed;">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="view.php?id=<?php echo (int)$meeting['id']; ?>"
                                           class="btn btn-sm btn-outline-secondary rounded-3 px-2"
                                           title="View Meeting">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    </div>
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

<!-- Cancel Meeting Modal -->
<div class="modal fade" id="cancelMeetingModal" tabindex="-1" aria-labelledby="cancelMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #c0392b, #e74c3c);">
                <h5 class="modal-title fw-bold" id="cancelMeetingModalLabel">
                    <i class="fas fa-calendar-times me-2"></i>Cancel Meeting
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../../controllers/MeetingCancelController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="meeting_id" id="cancelMeetingId">
                <div class="modal-body py-4">
                    <p class="mb-1">You are about to cancel:</p>
                    <p class="fw-bold fs-6 text-dark mb-1" id="cancelMeetingTitle"></p>
                    <p class="text-muted small mb-3">Scheduled for <span id="cancelMeetingDate" class="fw-semibold text-dark"></span></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason / Remark <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="cancel_reason" class="form-control rounded-3" rows="3" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                    <div class="alert alert-warning rounded-3 mb-0 py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        This will mark the meeting as <strong>Cancelled</strong>. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i> Keep Meeting
                    </button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4">
                        <i class="fas fa-calendar-times me-1"></i> Yes, Cancel Meeting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark as Completed Modal -->
<div class="modal fade" id="completeMeetingModal" tabindex="-1" aria-labelledby="completeMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #16a34a, #22c55e);">
                <h5 class="modal-title fw-bold" id="completeMeetingModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Mark as Completed
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../../controllers/MeetingCompleteController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="meeting_id" id="completeMeetingId">
                <div class="modal-body py-4">
                    <p class="mb-1">Mark as completed:</p>
                    <p class="fw-bold fs-6 text-dark mb-3" id="completeMeetingTitle"></p>
                    <div class="alert alert-success rounded-3 mb-0 py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        This will mark the meeting as <strong>Completed</strong> and unlock task assignment.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Go Back
                    </button>
                    <button type="submit" class="btn btn-success rounded-3 px-4">
                        <i class="fas fa-check-circle me-1"></i> Yes, Mark Completed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.stat-filter-card { cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; }
.stat-filter-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important; }
.stat-filter-card.stat-active { outline: 3px solid rgba(0,0,0,0.25); outline-offset: 2px; transform: translateY(-2px); }
.meeting-info-badge {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    padding: 0.45rem 1rem;
    font-size: 0.82rem;
    color: #334155;
    font-weight: 500;
    white-space: nowrap;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
<script>
function openCancelModal(btn) {
    document.getElementById('cancelMeetingId').value    = btn.dataset.meetingId;
    document.getElementById('cancelMeetingTitle').textContent = btn.dataset.meetingTitle;
    document.getElementById('cancelMeetingDate').textContent  = btn.dataset.meetingDate;
    // Clear reason field on each open
    var ta = document.querySelector('#cancelMeetingModal textarea[name="cancel_reason"]');
    if (ta) ta.value = '';
    new bootstrap.Modal(document.getElementById('cancelMeetingModal')).show();
}

function openCompleteModal(btn) {
    document.getElementById('completeMeetingId').value = btn.dataset.meetingId;
    document.getElementById('completeMeetingTitle').textContent = btn.dataset.meetingTitle;
    new bootstrap.Modal(document.getElementById('completeMeetingModal')).show();
}

// Stat card click-to-filter
document.addEventListener('DOMContentLoaded', function () {
    var wrapper  = document.getElementById('meetingsTableWrapper');
    var countEl  = wrapper ? wrapper.querySelector('.table-result-count') : null;
    var searchEl = wrapper ? wrapper.querySelector('[data-table-search]') : null;

    document.querySelectorAll('.stat-filter-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var filter = this.dataset.filter;

            // Wait a tick to ensure app.js pagination has initialised
            setTimeout(function () {
                var allRows = wrapper._paginateAllRows || Array.from(wrapper.querySelectorAll('tbody tr'));
                var matched = (filter === 'all')
                    ? allRows
                    : allRows.filter(function (row) { return (row.dataset.rowStatus || '') === filter; });

                if (wrapper._paginateSetFiltered) {
                    wrapper._paginateSetFiltered(matched);
                } else {
                    allRows.forEach(function (r) { r.style.display = 'none'; });
                    matched.forEach(function (r) { r.style.display = ''; });
                }

                if (countEl) countEl.textContent = matched.length + ' record' + (matched.length !== 1 ? 's' : '');
                if (searchEl) searchEl.value = '';

                // Active visual
                document.querySelectorAll('.stat-filter-card').forEach(function (c) { c.classList.remove('stat-active'); });
                card.classList.add('stat-active');
            }, 0);
        });
    });
});
</script>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel"><i class="fas fa-tasks me-2 text-warning"></i> Assign New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../../controllers/TaskController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
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
