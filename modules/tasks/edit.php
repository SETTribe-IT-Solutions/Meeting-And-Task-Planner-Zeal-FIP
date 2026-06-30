<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Organizer') {
    header('Location: index.php');
    exit();
}

require_once '../../config/db.php';
include_once '../../includes/header.php';

$conn  = getDBConnection();
$taskId = (int)($_GET['id'] ?? 0);

if ($taskId <= 0) {
    $_SESSION['error'] = 'Invalid task.';
    header('Location: index.php');
    exit();
}

// Load task with meeting info
$stmt = $conn->prepare(
    "SELECT t.*, m.title AS meeting_title, m.department AS meeting_department
     FROM tasks t JOIN meetings m ON t.meeting_id = m.id
     WHERE t.id = ? LIMIT 1"
);
$stmt->bind_param('i', $taskId);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    $_SESSION['error'] = 'Task not found.';
    header('Location: index.php');
    exit();
}

// Current assignees from task_assignments table
$taStmt = $conn->prepare("SELECT user_id FROM task_assignments WHERE task_id = ?");
$taStmt->bind_param('i', $taskId);
$taStmt->execute();
$currentAssignees = array_column($taStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');
if (empty($currentAssignees) && $task['assigned_to']) {
    $currentAssignees = [(int)$task['assigned_to']];
}

// Current task attachment
$atchStmt = $conn->prepare("SELECT id, original_name, file_size FROM task_attachments WHERE task_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$atchStmt->bind_param('i', $taskId);
$atchStmt->execute();
$currentAttachment = $atchStmt->get_result()->fetch_assoc();

// All users for client-side department filtering
$allUsersRes = $conn->query("SELECT id, name, email, department FROM users WHERE isDeleted = 'No' ORDER BY name ASC");
$all_users   = $allUsersRes ? $allUsersRes->fetch_all(MYSQLI_ASSOC) : [];

$today = date('Y-m-d');
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
                <i class="fas fa-pencil-alt fs-5 text-warning"></i>
                <span>Edit Task</span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/EditTaskController.php" method="POST" id="editTaskForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">

                    <div class="row g-3">

                        <!-- Meeting Reference (read-only) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Meeting Reference</label>
                            <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($task['meeting_title']); ?>" readonly>
                        </div>

                        <!-- Task Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required
                                   value="<?php echo htmlspecialchars($task['title']); ?>"
                                   placeholder="e.g., Prepare financial report">
                        </div>

                        <!-- Due Date + Priority -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date" class="form-control rounded-3" required
                                   value="<?php echo htmlspecialchars($task['due_date']); ?>"
                                   data-past-date-message="Past dates are not allowed.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select rounded-3" required>
                                <option value="Low"    <?php echo $task['priority'] === 'Low'    ? 'selected' : ''; ?>>Low</option>
                                <option value="Medium" <?php echo $task['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="High"   <?php echo $task['priority'] === 'High'   ? 'selected' : ''; ?>>High</option>
                            </select>
                        </div>

                        <!-- Department (read-only) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control rounded-3 bg-light"
                                   value="<?php echo htmlspecialchars($task['meeting_department']); ?>" readonly>
                            <small class="text-muted">Department is set by the meeting and cannot be changed here.</small>
                        </div>

                        <!-- Assign To — Tom Select -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="fas fa-user-check text-primary me-1"></i>Assign To <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="assigneeCount" class="badge bg-primary rounded-pill px-3 py-1" style="display:none; font-size:0.78rem;"></span>
                                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary rounded-3 px-3">
                                        <i class="fas fa-check-double me-1"></i>Select All
                                    </button>
                                    <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                            <select name="assigned_to[]" id="assigned_to_select" multiple></select>
                            <div id="assigneeHint" class="text-muted small mt-1" style="display:none;">
                                <i class="fas fa-exclamation-circle me-1 text-warning"></i>No employees found in this department.
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Additional Notes / Instructions</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3"
                                      placeholder="Provide context or specific instructions..."><?php echo htmlspecialchars($task['notes'] ?? ''); ?></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip text-secondary me-1"></i>Attachment
                                <span class="text-muted fw-normal small">(optional)</span>
                            </label>
                            <?php if ($currentAttachment): ?>
                            <div class="alert alert-info py-2 rounded-3 mb-2 d-flex align-items-center gap-2">
                                <i class="fas fa-file-alt text-primary"></i>
                                <div class="flex-fill">
                                    <strong><?php echo htmlspecialchars($currentAttachment['original_name']); ?></strong>
                                    <span class="text-muted small ms-2">(<?php echo round($currentAttachment['file_size'] / 1024, 1); ?> KB)</span>
                                </div>
                                <a href="download_attachment.php?task_id=<?php echo $taskId; ?>"
                                   class="btn btn-sm btn-outline-primary rounded-3">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </div>
                            <div class="form-text mb-1">Upload a new file below to <strong>replace</strong> the current attachment.</div>
                            <?php endif; ?>
                            <input type="file" name="task_attachment" class="form-control rounded-3"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                            <div class="form-text">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG &mdash; Max 10 MB</div>
                        </div>

                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3">
                            <i class="fas fa-arrow-left me-1"></i> Back to Tasks
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    var allUsers         = <?php echo json_encode($all_users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var dept             = <?php echo json_encode($task['meeting_department']); ?>;
    var currentAssignees = <?php echo json_encode(array_map('strval', $currentAssignees)); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        var assigneeCount = document.getElementById('assigneeCount');
        var assigneeHint  = document.getElementById('assigneeHint');
        var tomSelect     = null;

        var filtered = allUsers.filter(function (u) { return u.department === dept; });

        if (filtered.length === 0) {
            if (assigneeHint) { assigneeHint.style.display = ''; }
        } else {
            tomSelect = new TomSelect('#assigned_to_select', {
                options: filtered.map(function (u) {
                    return { value: String(u.id), name: u.name, email: u.email };
                }),
                items: currentAssignees,
                valueField: 'value',
                labelField: 'name',
                searchField: ['name', 'email'],
                plugins: ['remove_button', 'checkbox_options'],
                placeholder: 'Search by name or email…',
                maxOptions: null,
                onChange: function () {
                    var n = this.getValue().length;
                    assigneeCount.textContent = n + ' selected';
                    assigneeCount.style.display = n > 0 ? '' : 'none';
                },
                render: {
                    option: function (data, escape) {
                        return '<div class="d-flex justify-content-between align-items-center gap-3 py-1">' +
                            '<span class="fw-semibold">' + escape(data.name) + '</span>' +
                            '<small class="text-muted">' + escape(data.email) + '</small>' +
                            '</div>';
                    },
                    item: function (data, escape) {
                        return '<div>' + escape(data.name) + '</div>';
                    }
                }
            });

            // Trigger initial count display
            var n = currentAssignees.length;
            if (n > 0) { assigneeCount.textContent = n + ' selected'; assigneeCount.style.display = ''; }
        }

        document.getElementById('selectAllBtn').addEventListener('click', function () {
            if (!tomSelect) return;
            tomSelect.setValue(Object.keys(tomSelect.options));
        });
        document.getElementById('clearAllBtn').addEventListener('click', function () {
            if (tomSelect) tomSelect.clear();
        });

        document.getElementById('editTaskForm').addEventListener('submit', function (e) {
            if (!tomSelect || tomSelect.getValue().length === 0) {
                e.preventDefault();
                if (assigneeHint) {
                    assigneeHint.innerHTML = '<i class="fas fa-exclamation-triangle me-1 text-danger"></i>Please select at least one employee.';
                    assigneeHint.style.display = '';
                }
            }
        });
    });
})();
</script>
<?php include_once '../../includes/footer.php'; ?>
