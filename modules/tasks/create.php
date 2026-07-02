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
$prefillMeetingId = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;
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
<link rel="stylesheet" href="<?php echo $basePath; ?>/assets/vendor/tom-select/css/tom-select.bootstrap5.min.css">
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
                <span>Assign New Task</span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/TaskController.php" method="POST" id="taskForm" enctype="multipart/form-data">
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
                            <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required placeholder="e.g., Prepare financial report">
                        </div>

                        <!-- Due Date + Priority -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date" class="form-control rounded-3" required min="<?php echo htmlspecialchars($today); ?>" data-past-date-message="Past dates are not allowed. Please select today or a future date.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select rounded-3" required>
                                <option value="">Select priority</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
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
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="fas fa-user-check text-primary me-1"></i>Assign To <span class="text-danger">*</span>
                                </label>
                                <div id="assigneeControls" class="d-flex align-items-center gap-2" style="display:none;">
                                    <span id="assigneeCount" class="badge bg-primary rounded-pill px-3 py-1" style="display:none; font-size:0.78rem;"></span>
                                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary rounded-3 px-3">
                                        <i class="fas fa-check-double me-1"></i>Select All
                                    </button>
                                    <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                            <div id="assigneeWidget" style="display:none;">
                                <select name="assigned_to[]" id="assigned_to_select" multiple></select>
                            </div>
                            <div id="assigneeHint" class="text-muted small mt-1">
                                <i class="fas fa-info-circle me-1"></i>Select a department above to load employees.
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Additional Notes / Instructions</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Provide context or specific instructions for this task..."></textarea>
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

<script src="<?php echo $basePath; ?>/assets/vendor/tom-select/js/tom-select.complete.min.js"></script>
<script>
(function () {
    var allUsers = <?php echo json_encode($all_users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        var deptSelect       = document.getElementById('department_select');
        var assigneeWidget   = document.getElementById('assigneeWidget');
        var assigneeControls = document.getElementById('assigneeControls');
        var assigneeHint     = document.getElementById('assigneeHint');
        var assigneeCount    = document.getElementById('assigneeCount');
        var tomSelect        = null;

        function loadAssignees(dept) {
            if (tomSelect) { tomSelect.destroy(); tomSelect = null; }
            assigneeWidget.style.display   = 'none';
            assigneeControls.style.display = 'none';
            assigneeCount.style.display    = 'none';

            if (!dept) {
                assigneeHint.innerHTML = '<i class="fas fa-info-circle me-1"></i>Select a department above to load employees.';
                assigneeHint.style.display = '';
                return;
            }

            var filtered = allUsers.filter(function (u) { return u.department === dept; });

            if (filtered.length === 0) {
                assigneeHint.innerHTML = '<i class="fas fa-exclamation-circle me-1 text-warning"></i>No employees found in this department.';
                assigneeHint.style.display = '';
                return;
            }

            assigneeWidget.style.display = '';
            tomSelect = new TomSelect('#assigned_to_select', {
                options: filtered.map(function (u) {
                    return { value: String(u.id), name: u.name, email: u.email };
                }),
                items: [],
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
                    },
                    no_results: function () {
                        return '<div class="no-results px-3 py-2 text-muted small">No matching employees found.</div>';
                    }
                }
            });

            assigneeHint.style.display    = 'none';
            assigneeControls.style.display = '';
        }

        deptSelect.addEventListener('change', function () { loadAssignees(this.value); });

        document.getElementById('selectAllBtn').addEventListener('click', function () {
            if (!tomSelect) return;
            tomSelect.setValue(Object.keys(tomSelect.options));
        });

        document.getElementById('clearAllBtn').addEventListener('click', function () {
            if (tomSelect) tomSelect.clear();
        });

        // Form validation — ensure at least one assignee selected
        document.getElementById('taskForm').addEventListener('submit', function (e) {
            if (!tomSelect || tomSelect.getValue().length === 0) {
                e.preventDefault();
                assigneeHint.innerHTML = '<i class="fas fa-exclamation-triangle me-1 text-danger"></i>Please select at least one employee to assign this task.';
                assigneeHint.style.display = '';
                assigneeWidget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        <?php if (!empty($prefillDepartment)): ?>
        loadAssignees(<?php echo json_encode($prefillDepartment); ?>);
        <?php endif; ?>
    });
})();
</script>
<?php include_once '../../includes/footer.php'; ?>
