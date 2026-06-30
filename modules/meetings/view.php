<?php
// modules/meetings/view.php
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

// 1. Fetch meeting info
$stmt = $conn->prepare("SELECT m.*, u.name as organizer_name, u.email as organizer_email 
                        FROM meetings m 
                        JOIN users u ON m.organizer_id = u.id 
                        WHERE m.id = ?");
$stmt->bind_param("i", $meetingId);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    echo "<div class='alert alert-danger rounded-3'>Meeting not found.</div>";
    include_once '../../includes/footer.php';
    exit();
}


// 3. Fetch attendees
$stmt = $conn->prepare("SELECT a.id as attendance_id, a.status as att_status, a.remarks as att_remarks, u.id as user_id, u.name as user_name, u.email as user_email, u.department as user_dept 
                        FROM attendance a 
                        JOIN users u ON a.user_id = u.id 
                        WHERE a.meeting_id = ? 
                        ORDER BY u.name ASC");
$stmt->bind_param("i", $meetingId);
$stmt->execute();
$attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Fetch tasks related to this meeting
$stmt = $conn->prepare("SELECT t.*, u.name as assignee_name 
                        FROM tasks t 
                        JOIN users u ON t.assigned_to = u.id 
                        WHERE t.meeting_id = ? 
                        ORDER BY t.due_date ASC");
$stmt->bind_param("i", $meetingId);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch meeting attachment
$atchStmt = $conn->prepare("SELECT id, original_name, file_size FROM meeting_attachments WHERE meeting_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$atchStmt->bind_param('i', $meetingId);
$atchStmt->execute();
$meetingAttachment = $atchStmt->get_result()->fetch_assoc();

// 5. Fetch non-attendee users for the invitation dropdown (Organizer/Collector only)
$nonAttendees = [];
if (isOrganizer()) {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE role = 'Employee' AND department = ? AND isDeleted = 'No' AND id NOT IN (SELECT user_id FROM attendance WHERE meeting_id = ?) ORDER BY name ASC");
    $stmt->bind_param("si", $meeting['department'], $meetingId);
    $stmt->execute();
    $nonAttendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Attendance stats
$totalAttendees   = count($attendees);
$presentCount     = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Present'));
$presentLateCount = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Present with Late'));
$absentCount      = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Absent'));
$notUpdatedCount  = count(array_filter($attendees, fn($a) => $a['att_status'] === 'Not Updated'));

$statusBadge = match(strtolower($meeting['status'])) {
    'scheduled' => 'badge-status-scheduled',
    'ongoing'   => 'badge-status-ongoing',
    'completed' => 'badge-status-completed',
    'cancelled' => 'badge-status-cancelled',
    default     => 'bg-secondary'
};

$isScheduled = strtolower($meeting['status']) === 'scheduled';
$canAct      = ($role === 'Organizer' && $isScheduled);
?>

<div class="row g-4">
    <div class="col-12">

        <!-- Top action bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Meetings
            </a>
            <?php if ($canAct): ?>
            <div class="d-flex gap-2 flex-wrap">
                <a href="edit.php?id=<?php echo $meetingId; ?>"
                   class="btn btn-warning rounded-3 fw-semibold px-4">
                    <i class="fas fa-pencil-alt me-2"></i>Edit
                </a>
                <button type="button"
                    class="btn btn-success rounded-3 fw-semibold px-4"
                    data-bs-toggle="modal" data-bs-target="#completeMeetingModal">
                    <i class="fas fa-check-circle me-2"></i>Mark Completed
                </button>
                <button type="button"
                    class="btn btn-danger rounded-3 fw-semibold px-4"
                    data-bs-toggle="modal" data-bs-target="#cancelMeetingModal">
                    <i class="fas fa-calendar-times me-2"></i>Cancel Meeting
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Meeting Cover Card -->
        <div class="card border-0 shadow-sm text-white mb-4 overflow-hidden animate-on-scroll" style="border-radius: var(--radius-xl);">
            <div class="p-4 position-relative" style="background: linear-gradient(135deg, #0b3d5f 0%, #1a5f7a 50%, #0d4b6e 100%);">
                <div class="position-absolute top-0 end-0 opacity-25" style="font-size: 8rem; line-height: 1; margin: -15px -10px 0 0;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="position-relative" style="z-index: 2;">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2"><?php echo htmlspecialchars($meeting['department']); ?> Wing</span>
                        <span class="badge <?php echo $statusBadge; ?> px-3 py-2 fs-6"><?php echo htmlspecialchars($meeting['status']); ?></span>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($meeting['title']); ?></h2>
                    <p class="mb-0 text-white-50">Organized by: <strong class="text-white"><?php echo htmlspecialchars($meeting['organizer_name']); ?></strong> (<?php echo htmlspecialchars($meeting['organizer_email']); ?>)</p>
                    <?php if (strtolower($meeting['status']) === 'cancelled' && !empty($meeting['cancel_reason'])): ?>
                <div class="mt-2 p-2 rounded-3 small" style="background: rgba(239,68,68,0.15); color: #fca5a5;">
                    <i class="fas fa-info-circle me-1"></i><strong>Cancellation reason:</strong> <?php echo htmlspecialchars($meeting['cancel_reason']); ?>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-3 mt-3 flex-wrap">
                        <span class="d-flex align-items-center gap-2" style="background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?>
                        </span>
                        <span class="d-flex align-items-center gap-2" style="background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="far fa-clock"></i> <?php echo formatTime12Hour($meeting['meeting_time']); ?>
                        </span>
                        <span class="d-flex align-items-center gap-2" style="background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem;">
                            <i class="fas <?php echo strtolower($meeting['mode']) === 'online' ? 'fa-video' : 'fa-building'; ?>"></i> <?php echo htmlspecialchars($meeting['mode']); ?>
                        </span>
                    </div>
                    <!-- Meeting Action Buttons -->
                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        <a href="attendance.php?id=<?php echo $meetingId; ?>" class="btn btn-sm btn-outline-light rounded-3">
                            <i class="fas fa-clipboard-list me-1"></i> View Attendance
                        </a>
                        <?php if (isOrganizer() && (int)$meeting['organizer_id'] === (int)$_SESSION['user_id']): ?>
                            <a href="edit.php?id=<?php echo $meetingId; ?>" class="btn btn-sm btn-warning rounded-3 text-dark">
                                <i class="fas fa-edit me-1"></i> Edit Meeting
                            </a>
                            <form action="../../controllers/MeetingController.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this meeting? This will also delete related agenda, attendees, attendance, and linked records. This action cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                                <button type="submit" class="btn btn-sm btn-danger rounded-3">
                                    <i class="fas fa-trash-alt me-1"></i> Delete Meeting
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Left Column: Details & Agenda -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4 bg-white p-4 animate-on-scroll">
            <h5 class="fw-bold mb-4 border-bottom pb-2" style="color: var(--gov-blue);"><i class="fas fa-info-circle text-primary me-2"></i> Meeting Details</h5>
            <div class="row g-3">
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">DATE</small>
                    <span class="fw-semibold text-dark"><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></span>
                </div>
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">TIME</small>
                    <span class="fw-semibold text-dark"><?php echo formatTime12Hour($meeting['meeting_time']); ?></span>
                </div>
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">MODE</small>
                    <span class="fw-semibold text-dark"><i class="fas <?php echo strtolower($meeting['mode']) === 'online' ? 'fa-video' : 'fa-building'; ?> me-1 text-secondary"></i> <?php echo htmlspecialchars($meeting['mode']); ?></span>
                </div>
                <div class="col-12 col-sm-12">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">LOCATION / LINK</small>
                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($meeting['location']); ?></span>
                </div>
                <?php if ($meetingAttachment): ?>
                <div class="col-12 mt-1">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">ATTACHMENT</small>
                    <a href="download_attachment.php?meeting_id=<?php echo $meetingId; ?>"
                       class="btn btn-sm btn-outline-primary rounded-3 mt-1">
                        <i class="fas fa-download me-1"></i><?php echo htmlspecialchars($meetingAttachment['original_name']); ?>
                        <span class="text-muted small ms-1">(<?php echo round($meetingAttachment['file_size'] / 1024, 1); ?> KB)</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Agenda Section -->
        <div class="card border-0 shadow-sm mb-4 bg-white p-4 animate-on-scroll">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);"><i class="fas fa-file-alt text-warning me-2"></i> Agenda</h5>
            <div class="bg-light p-3 rounded-3 text-dark" style="white-space: pre-line;">
                <?php echo htmlspecialchars($meeting['agenda']); ?>
            </div>
        </div>

        <!-- Meeting Tasks -->
        <div class="card border-0 shadow-sm bg-white p-4 animate-on-scroll" id="meetingTasksTableWrapper" data-paginate data-per-page="5">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0" style="color: var(--gov-blue);"><i class="fas fa-tasks text-success me-2"></i> Action Items & Tasks</h5>
                <?php if ($role === 'Organizer'): ?>
                    <?php if (strtolower($meeting['status']) === 'completed'): ?>
                    <a href="../tasks/create.php?meeting_id=<?php echo $meetingId; ?>" class="btn btn-sm btn-outline-success rounded-3">
                        <i class="fas fa-plus"></i> Assign Task
                    </a>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3"
                            disabled style="opacity:0.4; cursor:not-allowed;"
                            title="Tasks can only be assigned after the meeting is Completed">
                        <i class="fas fa-plus"></i> Assign Task
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <i class="bi bi-card-checklist"></i>
                    <p>No tasks assigned for this meeting yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-enhanced table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned Employee</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['assignee_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($task['due_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $p = $task['priority'];
                                        $pBadge = match($p) { 'High'=>'badge-priority-high', 'Medium'=>'badge-priority-medium', 'Low'=>'badge-priority-low', default=>'bg-secondary' };
                                        ?>
                                        <span class="badge <?php echo $pBadge; ?>"><?php echo htmlspecialchars($p); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $s = $task['status'];
                                        $sBadge = match($s) { 'Completed'=>'badge-status-completed', 'In Progress'=>'badge-status-ongoing', 'Pending'=>'badge-status-scheduled', default=>'bg-secondary' };
                                        ?>
                                        <span class="badge <?php echo $sBadge; ?>"><?php echo htmlspecialchars($s); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Attendee Management -->
    <div class="col-lg-5">
        <!-- Attendance Stats -->
        <div class="card border-0 shadow-sm bg-white p-4 mb-4 animate-on-scroll">
            <h6 class="fw-bold mb-3" style="color: var(--gov-blue);"><i class="fas fa-chart-pie text-primary me-2"></i> Attendance Summary</h6>
            <div class="d-flex gap-2 flex-wrap">
                <div class="text-center flex-fill p-2 rounded-3" style="background: #f0fdf4;">
                    <div class="fw-bold fs-5 text-success"><?php echo $presentCount; ?></div>
                    <small class="text-muted fw-semibold"><i class="fas fa-check-circle text-success me-1" style="font-size:0.75rem;"></i>Present</small>
                </div>
                <div class="text-center flex-fill p-2 rounded-3" style="background: #fffbeb;">
                    <div class="fw-bold fs-5 text-warning"><?php echo $presentLateCount; ?></div>
                    <small class="text-muted fw-semibold"><i class="fas fa-clock text-warning me-1" style="font-size:0.75rem;"></i>Late</small>
                </div>
                <div class="text-center flex-fill p-2 rounded-3" style="background: #fef2f2;">
                    <div class="fw-bold fs-5 text-danger"><?php echo $absentCount; ?></div>
                    <small class="text-muted fw-semibold"><i class="fas fa-times-circle text-danger me-1" style="font-size:0.75rem;"></i>Absent</small>
                </div>
                <div class="text-center flex-fill p-2 rounded-3" style="background: #f8f9fa;">
                    <div class="fw-bold fs-5 text-secondary"><?php echo $notUpdatedCount; ?></div>
                    <small class="text-muted fw-semibold"><i class="fas fa-question-circle text-secondary me-1" style="font-size:0.75rem;"></i>Not Updated</small>
                </div>
            </div>
            <?php if ($totalAttendees > 0): ?>
            <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" style="width: <?php echo round(($presentCount/$totalAttendees)*100); ?>%"></div>
                <div class="progress-bar bg-warning" style="width: <?php echo round(($presentLateCount/$totalAttendees)*100); ?>%"></div>
                <div class="progress-bar bg-danger" style="width: <?php echo round(($absentCount/$totalAttendees)*100); ?>%"></div>
                <div class="progress-bar bg-secondary" style="width: <?php echo round(($notUpdatedCount/$totalAttendees)*100); ?>%"></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm bg-white p-4 mb-4 animate-on-scroll">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);"><i class="fas fa-users text-primary me-2"></i> Attendees & RSVPs</h5>
            
            <?php if (isOrganizer() && !empty($nonAttendees)): ?>
                <!-- Invite attendee dropdown form -->
                <form action="../../controllers/AttendanceController.php" method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                    <label class="form-label">Invite Employee</label>
                    <div class="d-flex gap-2">
                        <select name="user_id" class="form-select rounded-3" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($nonAttendees as $na): ?>
                                <option value="<?php echo $na['id']; ?>"><?php echo htmlspecialchars($na['name']); ?> (<?php echo htmlspecialchars($na['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary rounded-3 px-3">Invite</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (empty($attendees)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <p>No attendees invited yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($attendees as $att): ?>
                        <?php
                        $aStatus = $att['att_status'];
                        $statusConf = match($aStatus) {
                            'Present'           => ['icon' => 'fa-check-circle',    'color' => 'success',   'badge' => 'badge-status-completed'],
                            'Present with Late' => ['icon' => 'fa-clock',           'color' => 'warning',   'badge' => 'badge-status-ongoing'],
                            'Absent'            => ['icon' => 'fa-times-circle',    'color' => 'danger',    'badge' => 'badge-status-cancelled'],
                            default             => ['icon' => 'fa-question-circle', 'color' => 'secondary', 'badge' => 'bg-secondary'],
                        };
                        $attStatuses = [
                            'Present'           => ['icon' => 'fa-check-circle',    'cls' => 'text-success',   'label' => 'Present'],
                            'Present with Late' => ['icon' => 'fa-clock',           'cls' => 'text-warning',   'label' => 'Present with Late'],
                            'Absent'            => ['icon' => 'fa-times-circle',    'cls' => 'text-danger',    'label' => 'Absent'],
                            'Not Updated'       => ['icon' => 'fa-question-circle', 'cls' => 'text-secondary', 'label' => 'Not Updated'],
                        ];
                        ?>
                        <div class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <div class="flex-fill">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($att['user_name']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($att['user_dept']); ?> | <?php echo htmlspecialchars($att['user_email']); ?></small>
                                    <?php if (!empty($att['arrival_time'])): ?>
                                        <small class="text-success d-block mt-1"><i class="fas fa-clock me-1"></i>Arrived: <?php echo htmlspecialchars(formatTime12Hour($att['arrival_time'])); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($att['att_remarks'])): ?>
                                        <div class="small bg-light text-secondary p-2 rounded-2 mt-1">
                                            <i class="far fa-comment me-1"></i><?php echo htmlspecialchars($att['att_remarks']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $aStatus = $att['att_status'];
                                    $aBadge = match($aStatus) { 'Present'=>'badge-status-completed', 'Absent'=>'badge-status-cancelled', 'Pending'=>'badge-status-scheduled', default=>'bg-secondary' };
                                    ?>
                                    <span class="badge <?php echo $aBadge; ?> mb-2"><?php echo htmlspecialchars($aStatus); ?></span>

                                    <!-- Action form if allowed -->
                                    <?php if ($role === 'Collector' || $role === 'Organizer' || ($role === 'Employee' && $att['user_id'] == $user_id)): ?>
                                        <form action="../../controllers/AttendanceController.php" method="POST" class="d-block mt-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="attendance_id" value="<?php echo $att['attendance_id']; ?>">
                                            <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                                            <div class="d-flex align-items-center gap-1">
                                                <select name="status" class="form-select form-select-sm rounded-3" style="font-size: 0.8rem; width: 100px;" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $aStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Present" <?php echo $aStatus === 'Present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="Absent" <?php echo $aStatus === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                                    <option value="Late" <?php echo $aStatus === 'Late' ? 'selected' : ''; ?>>Late</option>
                                                </select>
                                                <button type="button" class="btn btn-outline-secondary btn-sm p-1 rounded-3" 
                                                        style="font-size: 0.75rem;"
                                                        onclick="promptMeetingRemarks(this, '<?php echo htmlspecialchars(addslashes($att['att_remarks'] ?? '')); ?>')">
                                                    <i class="fas fa-comment"></i>
                                                </button>
                                                <input type="hidden" name="remarks" value="<?php echo htmlspecialchars($att['att_remarks'] ?? ''); ?>">
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Collector: read-only badge -->
                                        <span class="badge <?php echo $statusConf['badge']; ?>">
                                            <i class="fas <?php echo $statusConf['icon']; ?> me-1"></i><?php echo htmlspecialchars($aStatus); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function promptMeetingRemarks(attendanceId, currentRemarks) {
    var newRemarks = prompt("Enter attendance feedback/remarks:", currentRemarks || '');
    if (newRemarks !== null) {
        document.getElementById('rmk_val_' + attendanceId).value = newRemarks;
        document.getElementById('rmk_' + attendanceId).submit();
    }
}
function promptArrivalTime(attendanceId, currentTime) {
    var t = prompt("Enter arrival time (HH:MM, 24-hour format):", currentTime ? currentTime.substring(0, 5) : '');
    if (t !== null) {
        if (t !== '' && !/^\d{2}:\d{2}$/.test(t)) {
            alert('Invalid time format. Use HH:MM (e.g. 09:30).');
            return;
        }
        document.getElementById('atime_val_' + attendanceId).value = t ? t + ':00' : '';
        document.getElementById('atime_' + attendanceId).submit();
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>

<?php if ($canAct): ?>
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
                <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                <div class="modal-body py-4">
                    <p class="mb-1">You are about to cancel:</p>
                    <p class="fw-bold fs-6 text-dark mb-1"><?php echo htmlspecialchars($meeting['title']); ?></p>
                    <p class="text-muted small mb-3">
                        Scheduled for <strong class="text-dark"><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></strong>
                        at <strong class="text-dark"><?php echo formatTime12Hour($meeting['meeting_time']); ?></strong>
                    </p>
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
                <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                <input type="hidden" name="redirect" value="../modules/meetings/view.php?id=<?php echo $meetingId; ?>">
                <div class="modal-body py-4">
                    <p class="mb-1">Mark as completed:</p>
                    <p class="fw-bold fs-6 text-dark mb-3"><?php echo htmlspecialchars($meeting['title']); ?></p>
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
<?php endif; ?>
