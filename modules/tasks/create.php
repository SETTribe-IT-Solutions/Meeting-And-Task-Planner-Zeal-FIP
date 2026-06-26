<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

if ($_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'You have view-only access to tasks.';
    header('Location: index.php');
    exit();
}

require_once '../../config/db.php';
include_once '../../includes/header.php';

$conn = getDBConnection();
$meetingRes = $conn->query('SELECT id, title FROM meetings ORDER BY meeting_date DESC');
$meetings = $meetingRes->fetch_all(MYSQLI_ASSOC);

$userRes = $conn->query('SELECT id, name, email FROM users WHERE isDeleted = "No" ORDER BY name');
$users = $userRes->fetch_all(MYSQLI_ASSOC);
$today = date('Y-m-d');
// Preselect meeting if provided
$prefillMeetingId = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;
// If prefill meeting provided, fetch its department and department users
$prefillDepartment = '';
$prefillDeptUsers = [];
if ($prefillMeetingId > 0) {
    $mstmt = $conn->prepare('SELECT department FROM meetings WHERE id = ?');
    $mstmt->bind_param('i', $prefillMeetingId);
    $mstmt->execute();
    $mres = $mstmt->get_result()->fetch_assoc();
    if ($mres && !empty($mres['department'])) {
        $prefillDepartment = $mres['department'];
        $ustmt = $conn->prepare('SELECT id, name, email FROM users WHERE department = ? AND isDeleted = "No" ORDER BY name');
        $ustmt->bind_param('s', $prefillDepartment);
        $ustmt->execute();
        $prefillDeptUsers = $ustmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header text-white fw-bold py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                <i class="fas fa-tasks fs-5 text-warning"></i>
                <span>Assign New Task</span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/TaskController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Meeting Reference</label>
                            <select name="meeting_id" class="form-select rounded-3" required>
                                <option value="">Select meeting</option>
                                <?php foreach ($meetings as $meeting): ?>
                                    <option value="<?php echo (int)$meeting['id']; ?>" <?php echo $prefillMeetingId === (int)$meeting['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($meeting['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Task Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required placeholder="e.g., Prepare financial report">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department" id="department_select" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php
                                    $deptRes = $conn->query('SELECT id, name FROM departments WHERE is_active = "Yes" ORDER BY name');
                                    $depts = $deptRes ? $deptRes->fetch_all(MYSQLI_ASSOC) : [];
                                    foreach ($depts as $d):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($d['name']); ?>" <?php echo ($prefillDepartment === $d['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To (Department Employees)</label>
                            <select name="assigned_to[]" id="assigned_to_select" class="form-select rounded-3" multiple required style="min-height:120px;">
                                <?php if (!empty($prefillDeptUsers)): ?>
                                    <?php foreach ($prefillDeptUsers as $pu): ?>
                                        <option value="<?php echo (int)$pu['id']; ?>"><?php echo htmlspecialchars($pu['name']) . ' (' . htmlspecialchars($pu['email']) . ')'; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select one or more employees from the chosen department.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control rounded-3" required min="<?php echo htmlspecialchars($today); ?>" data-past-date-message="Past dates are not allowed. Please select today or a future date.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Priority Level</label>
                            <select name="priority" class="form-select rounded-3" required>
                                <option value="">Select priority</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Additional Notes / Instructions</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Provide context or specific instructions for this task..."></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fas fa-save me-1"></i> Save Task</button>
                        <a href="../../index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
