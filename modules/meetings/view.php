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
    echo "<div class='alert alert-danger'>Invalid Meeting ID.</div>";
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
    echo "<div class='alert alert-danger'>Meeting not found.</div>";
    include_once '../../includes/footer.php';
    exit();
}

// 2. Fetch agenda translations
$stmt = $conn->prepare("SELECT language_code, translated_agenda FROM meeting_translations WHERE meeting_id = ?");
$stmt->bind_param("i", $meetingId);
$stmt->execute();
$translations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$marathiAgenda = '';
foreach ($translations as $trans) {
    if ($trans['language_code'] === 'mr') {
        $marathiAgenda = $trans['translated_agenda'];
    }
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

// 5. Fetch non-attendee users for the invitation dropdown (Organizer/Collector only)
$nonAttendees = [];
if (isOrganizer()) {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE isDeleted = 'No' AND id NOT IN (SELECT user_id FROM attendance WHERE meeting_id = ?)");
    $stmt->bind_param("i", $meetingId);
    $stmt->execute();
    $nonAttendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="row g-4">
    <div class="col-12">
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
        <div class="card p-4 border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #0b3d5f 0%, #1e5c83 100%); border-radius: 20px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="badge bg-warning text-dark fw-bold mb-2"><?php echo htmlspecialchars($meeting['department']); ?> Wing</span>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h2>
                    <p class="mb-0 text-white-50 small">Organized by: <strong><?php echo htmlspecialchars($meeting['organizer_name']); ?></strong> (<?php echo htmlspecialchars($meeting['organizer_email']); ?>)</p>
                </div>
                <span class="badge bg-light text-dark px-3 py-2 fs-6 rounded-3"><?php echo htmlspecialchars($meeting['status']); ?></span>
            </div>
        </div>
    </div>

    <!-- Left Column: Details & Agenda -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4 bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-4 border-bottom pb-2"><i class="fas fa-info-circle text-primary me-2"></i> Meeting Details</h5>
            <div class="row g-3">
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">DATE</small>
                    <span class="fw-semibold text-dark"><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></span>
                </div>
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">TIME</small>
                    <span class="fw-semibold text-dark"><?php echo date('g:i A', strtotime($meeting['meeting_time'])); ?></span>
                </div>
                <div class="col-6 col-sm-4">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">MODE</small>
                    <span class="fw-semibold text-dark"><i class="fas <?php echo strtolower($meeting['mode']) === 'online' ? 'fa-video' : 'fa-building'; ?> me-1 text-secondary"></i> <?php echo htmlspecialchars($meeting['mode']); ?></span>
                </div>
                <div class="col-12 col-sm-12">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.75rem;">LOCATION / LINK</small>
                    <span class="fw-semibold text-dark"><?php echo htmlspecialchars($meeting['location']); ?></span>
                </div>
            </div>
        </div>

        <!-- Agenda Section (Multi-lingual Tabs) -->
        <div class="card border-0 shadow-sm mb-4 bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2"><i class="fas fa-file-alt text-warning me-2"></i> Agenda / कार्यसूची</h5>
            
            <ul class="nav nav-tabs border-bottom-0 mb-3" id="agendaTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold border-0 px-3 py-2 rounded-3 me-2" id="english-tab" data-bs-toggle="tab" data-bs-target="#english-agenda" type="button" role="tab" aria-controls="english-agenda" aria-selected="true">English</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold border-0 px-3 py-2 rounded-3" id="marathi-tab" data-bs-toggle="tab" data-bs-target="#marathi-agenda" type="button" role="tab" aria-controls="marathi-agenda" aria-selected="false">मराठी (Marathi)</button>
                </li>
            </ul>
            <div class="tab-content bg-light p-3 rounded-3" id="agendaTabContent">
                <div class="tab-pane fade show active text-dark" id="english-agenda" role="tabpanel" aria-labelledby="english-tab" style="white-space: pre-line;">
                    <?php echo htmlspecialchars($meeting['agenda']); ?>
                </div>
                <div class="tab-pane fade text-dark" id="marathi-agenda" role="tabpanel" aria-labelledby="marathi-tab" style="white-space: pre-line;">
                    <?php echo !empty($marathiAgenda) ? htmlspecialchars($marathiAgenda) : "<span class='text-muted italic'>मराठी भाषांतर उपलब्ध नाही (Marathi translation not available)</span>"; ?>
                </div>
            </div>
        </div>

        <!-- Meeting Tasks -->
        <div class="card border-0 shadow-sm bg-white p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold text-gov-blue mb-0"><i class="fas fa-tasks text-success me-2"></i> Action Items & Tasks</h5>
                <?php if (isOrganizer()): ?>
                    <a href="../tasks/create.php?meeting_id=<?php echo $meetingId; ?>" class="btn btn-sm btn-outline-success rounded-3">
                        <i class="fas fa-plus"></i> Assign Task
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No tasks assigned for this meeting yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="table-light">
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
                                        $pBadge = match($p) { 'High'=>'danger', 'Medium'=>'warning text-dark', 'Low'=>'success', default=>'secondary' };
                                        ?>
                                        <span class="badge bg-<?php echo $pBadge; ?>"><?php echo htmlspecialchars($p); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $s = $task['status'];
                                        $sBadge = match($s) { 'Completed'=>'success', 'In Progress'=>'info text-dark', 'Pending'=>'warning text-dark', default=>'secondary' };
                                        ?>
                                        <span class="badge bg-<?php echo $sBadge; ?>"><?php echo htmlspecialchars($s); ?></span>
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
        <div class="card border-0 shadow-sm bg-white p-4 mb-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2"><i class="fas fa-users text-primary me-2"></i> Attendees & RSVPs</h5>
            
            <?php if (isOrganizer() && !empty($nonAttendees)): ?>
                <!-- Invite attendee dropdown form -->
                <form action="../../controllers/AttendanceController.php" method="POST" class="mb-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                    <label class="form-label small fw-bold text-secondary">Invite Employee</label>
                    <div class="d-flex gap-2">
                        <select name="user_id" class="form-select rounded-3" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($nonAttendees as $na): ?>
                                <option value="<?php echo $na['id']; ?>"><?php echo htmlspecialchars($na['name']); ?> (<?php echo htmlspecialchars($na['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0b3d5f; border-color: #0b3d5f;">Invite</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (empty($attendees)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-people fs-2"></i>
                    <p class="mb-0 mt-1">No attendees invited yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($attendees as $att): ?>
                        <div class="list-group-item px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($att['user_name']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($att['user_dept']); ?> | <?php echo htmlspecialchars($att['user_email']); ?></small>
                                    
                                    <?php if (!empty($att['att_remarks'])): ?>
                                        <div class="small bg-light text-secondary p-2 rounded-2 mt-1 italic">
                                            <i class="far fa-comment"></i> <?php echo htmlspecialchars($att['att_remarks']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <?php
                                    $aStatus = $att['att_status'];
                                    $aBadge = match($aStatus) { 'Present'=>'success', 'Absent'=>'danger', 'Pending'=>'warning text-dark', default=>'secondary' };
                                    ?>
                                    <span class="badge bg-<?php echo $aBadge; ?> mb-2"><?php echo htmlspecialchars($aStatus); ?></span>

                                    <!-- Action form if allowed -->
                                    <?php if ($role === 'Collector' || $role === 'Organizer' || ($role === 'Employee' && $att['user_id'] == $user_id)): ?>
                                        <form action="../../controllers/AttendanceController.php" method="POST" class="d-block mt-1">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="attendance_id" value="<?php echo $att['attendance_id']; ?>">
                                            <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                                            <div class="d-flex align-items-center gap-1">
                                                <select name="status" class="form-select form-select-sm rounded-3" style="font-size: 0.8rem; width: 100px;" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $aStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Present" <?php echo $aStatus === 'Present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="Absent" <?php echo $aStatus === 'Absent' ? 'selected' : ''; ?>>Absent</option>
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
function promptMeetingRemarks(button, currentRemarks) {
    const inputField = button.form.querySelector('input[name="remarks"]');
    const newRemarks = prompt("Enter attendance feedback/remarks:", currentRemarks);
    if (newRemarks !== null) {
        inputField.value = newRemarks;
        button.form.submit();
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>
