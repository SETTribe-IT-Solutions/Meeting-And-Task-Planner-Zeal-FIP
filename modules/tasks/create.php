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
$meetingRes = $conn->query('SELECT id, title FROM meetings ORDER BY meeting_date DESC');
$meetings = $meetingRes->fetch_all(MYSQLI_ASSOC);

// Load all users for client-side department filtering (same approach as meeting create)
$allUsersRes = $conn->query("SELECT id, name, email, department FROM users WHERE isDeleted = 'No' ORDER BY name ASC");
$all_users = $allUsersRes ? $allUsersRes->fetch_all(MYSQLI_ASSOC) : [];

$today = date('Y-m-d');
$editTaskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$task = null;
$assignedUserIds = [];

// Preselect meeting if provided
$prefillMeetingId = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;

if ($editTaskId > 0) {
    $tstmt = $conn->prepare('SELECT * FROM tasks WHERE id = ?');
    $tstmt->bind_param('i', $editTaskId);
    $tstmt->execute();
    $task = $tstmt->get_result()->fetch_assoc();
    if ($task) {
        $prefillMeetingId = (int)$task['meeting_id'];
        $astmt = $conn->prepare('SELECT user_id FROM task_assignments WHERE task_id = ?');
        $astmt->bind_param('i', $editTaskId);
        $astmt->execute();
        $ares = $astmt->get_result();
        while ($row = $ares->fetch_assoc()) {
            $assignedUserIds[] = (int)$row['user_id'];
        }
    }
}

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
        $prefillDeptUsers = array_values(array_filter($all_users, function($u) use ($prefillDepartment) {
            return $u['department'] === $prefillDepartment;
        }));
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
.ts-wrapper.multi .ts-control { min-height: 42px; border-radius: 0.5rem; }
.ts-wrapper.multi .ts-control input { color: #212529; }
.ts-dropdown .option { padding: 6px 10px; }
</style>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header text-white fw-bold py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                <i class="fas fa-tasks fs-5 text-warning"></i>
                <span><?php echo $task ? 'Edit Task' : 'Task Details'; ?></span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="../../controllers/TaskController.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <?php if ($task): ?>
                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                    <?php endif; ?>
                    <div class="row g-3">

                        <!-- Meeting Reference + Task Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Reference <span class="text-danger">*</span></label>
                            <select name="meeting_id" class="form-select rounded-3" required>
                                <option value="">Select meeting</option>
                                <?php foreach ($meetings as $meeting): ?>
                                    <option value="<?php echo (int)$meeting['id']; ?>" <?php echo $prefillMeetingId === (int)$meeting['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($meeting['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Task Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required placeholder="e.g., Prepare financial report" value="<?php echo $task ? htmlspecialchars($task['title']) : ''; ?>">
                        </div>

                        <!-- Due Date + Priority -->
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
                            <select name="assigned_to[]" id="assigned_to_select" class="form-select rounded-3" multiple required style="min-height:120px;" data-preselected="<?php echo htmlspecialchars(json_encode($assignedUserIds)); ?>">
                                <?php if (!empty($prefillDeptUsers)): ?>
                                    <?php foreach ($prefillDeptUsers as $pu): ?>
                                        <option value="<?php echo (int)$pu['id']; ?>" <?php echo in_array((int)$pu['id'], $assignedUserIds) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pu['name']) . ' (' . htmlspecialchars($pu['email']) . ')'; ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Select one or more employees from the chosen department.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control rounded-3" required min="<?php echo htmlspecialchars($today); ?>" data-past-date-message="Past dates are not allowed. Please select today or a future date." value="<?php echo $task ? htmlspecialchars($task['due_date']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select rounded-3" required>
                                <option value="">Select priority</option>
                                <option value="Low" <?php echo ($task && $task['priority'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                                <option value="Medium" <?php echo ($task && $task['priority'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="High" <?php echo ($task && $task['priority'] === 'High') ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
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

                        <!-- Assign To — searchable checkbox multi-select (Tom Select) -->
                        <div class="col-12">
                            <label class="form-label">Additional Notes / Instructions</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Provide context or specific instructions for this task..."><?php echo $task ? htmlspecialchars($task['notes']) : ''; ?></textarea>
                        </div>

                        <!-- Attachment (optional) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip text-secondary me-1"></i>Attach File
                                <span class="text-muted fw-normal small">(optional)</span>
                            </label>
                            <input type="file" name="task_attachment" class="form-control rounded-3"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                            <div class="form-text">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG &mdash; Max 10 MB</div>
                        </div>

                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fas fa-save me-1"></i> Save Task</button>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // If there is no prefilled department but a department is selected, trigger load
    var deptSelect = document.getElementById('department_select');
    var assignSelect = document.getElementById('assigned_to_select');
    if (deptSelect && deptSelect.value && assignSelect && assignSelect.options.length === 0) {
        var preselected = [];
        try {
            var dataAttr = assignSelect.getAttribute('data-preselected');
            if (dataAttr) preselected = JSON.parse(dataAttr);
        } catch (e) {}
        
        // Use a slight delay to let app.js initialize
        setTimeout(function() {
            if (typeof populateUsersForDepartment === 'function') {
                populateUsersForDepartment(deptSelect.value, assignSelect, preselected);
            } else {
                // Manually trigger change event which app.js listens to
                deptSelect.dispatchEvent(new Event('change'));
            }
        }, 100);
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>
