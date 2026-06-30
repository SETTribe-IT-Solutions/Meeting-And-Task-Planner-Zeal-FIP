<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

if (!isset($_SESSION['role']) || !isOrganizer()) {
    header('Location: ../users/login.php');
    exit();
}

include_once '../../includes/header.php';

$conn        = getDBConnection();
$departments = getDepartments();
$old         = $_SESSION['user_form_old'] ?? ['name' => '', 'department' => '', 'email' => '', 'role' => 'Employee'];
$errors      = $_SESSION['user_form_errors'] ?? [];
unset($_SESSION['user_form_errors'], $_SESSION['user_form_old']);

$currentRole   = $_SESSION['role'];
$currentUserId = (int)$_SESSION['user_id'];

$result = $conn->query(
    "SELECT id, name, email, role, department, isDeleted FROM users ORDER BY isDeleted ASC, role ASC, name ASC"
);
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$totalUsers = count($users);
$employeeCount = count(array_filter($users, fn($u) => $u['role'] === 'Employee'));
$organizerCount = count(array_filter($users, fn($u) => $u['role'] === 'Organizer'));

$editUser = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($users as $u) {
        if ($u['id'] === $editId) {
            $editUser = $u;
            $old['name'] = $u['name'];
            $old['department'] = $u['department'];
            $old['email'] = $u['email'];
            break;
        }
    }
}
?>

<!-- Page Header -->
<div class="card p-4 border-0 mb-4 shadow-sm bg-white animate-on-scroll">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">User Management</h3>
            <p class="text-muted mb-0">Manage department user accounts across Latur administration.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Stats badges -->
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-users me-1"></i><?php echo $totalActive; ?> Active
            </span>
            <span class="badge badge-role-employee px-3 py-2"><?php echo $employeeCount; ?> Employees</span>
            <span class="badge badge-role-organizer px-3 py-2"><?php echo $orgCount; ?> Organizers</span>
            <?php if ($disabledCount > 0): ?>
            <span class="badge bg-danger px-3 py-2"><?php echo $disabledCount; ?> Disabled</span>
            <?php endif; ?>
            <!-- Create User button — Organizer only -->
            <?php if ($currentRole === 'Organizer'): ?>
            <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold"
                    data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-user-plus me-2"></i>Create User
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

    <div class="col-lg-4 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-user-plus text-primary me-2"></i> <?php echo $editUser ? 'Edit User' : 'Create User'; ?>
            </h5>

    <div class="table-filter-bar">
        <div class="table-search-input">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search by name, email, department..." data-table-search="usersTableWrapper">
        </div>
        <span class="table-result-count"><?php echo count($users); ?> records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-enhanced table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User Name</th>
                    <th>Email ID</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Status</th>
                    <?php if ($currentRole === 'Organizer'): ?>
                    <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-users fa-2x mb-2 d-block opacity-25"></i>
                            No users found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($users as $user):
                        $isDisabled  = $user['isDeleted'] === 'Yes';
                        $isProtected = ($user['id'] == $currentUserId) || ($user['role'] === 'Collector');
                        $roleBadge   = match($user['role']) {
                            'Collector' => 'badge-role badge-role-collector',
                            'Organizer' => 'badge-role badge-role-organizer',
                            'Employee'  => 'badge-role badge-role-employee',
                            default     => 'bg-secondary'
                        };
                    ?>
                    <tr class="<?php echo $isDisabled ? 'opacity-50' : ''; ?>">
                        <td class="text-muted small"><?php echo $i++; ?></td>
                        <td>
                            <div class="fw-semibold <?php echo $isDisabled ? 'text-muted' : 'text-dark'; ?>">
                                <?php echo htmlspecialchars($user['name']); ?>
                                <?php if ($user['id'] == $currentUserId): ?>
                                    <span class="badge bg-secondary ms-1" style="font-size:.62rem;vertical-align:middle;">You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?php echo htmlspecialchars($user['department']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $roleBadge; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isDisabled): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size:.75rem;">
                                    <i class="fas fa-ban me-1" style="font-size:.65rem;"></i>Disabled
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size:.75rem;">
                                    <i class="fas fa-circle me-1" style="font-size:.5rem;vertical-align:middle;"></i>Active
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($currentRole === 'Organizer'): ?>
                        <td class="text-end">
                            <?php if ($isProtected): ?>
                                <span class="text-muted small">—</span>
                            <?php else: ?>
                                <div class="d-flex gap-1 justify-content-end flex-nowrap">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-3"
                                        title="Edit User"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                        data-dept="<?php echo htmlspecialchars($user['department'], ENT_QUOTES); ?>"
                                        data-role="<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-sm btn-outline-warning rounded-3"
                                        title="Reset Password"
                                        data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <form action="../../controllers/UserController.php" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('<?php echo $isDisabled
                                              ? 'Re-enable ' . addslashes($user['name']) . '? They will be able to log in again.'
                                              : 'Disable ' . addslashes($user['name']) . '? They will not be able to log in.'; ?>')">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                        <button type="submit"
                                            class="btn btn-sm <?php echo $isDisabled ? 'btn-outline-success' : 'btn-outline-danger'; ?> rounded-3"
                                            title="<?php echo $isDisabled ? 'Enable User' : 'Disable User'; ?>">
                                            <i class="fas <?php echo $isDisabled ? 'fa-user-check' : 'fa-user-slash'; ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="../../controllers/UserController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">User Name</label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required minlength="3" maxlength="100">
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/UserController.php" method="POST" id="createUserForm">
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 py-2 small">
                        <?php foreach ($errors as $e): ?>
                            <div><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3"
                                   placeholder="e.g. Rajesh Patil"
                                   value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
                                   required minlength="3" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Department</label>
                            <select name="department" class="form-select rounded-3" required>
                                <option value="">Select dept...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"
                                        <?php echo (($old['department'] ?? '') === $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Role</label>
                            <select name="role" class="form-select rounded-3" required>
                                <option value="Employee" <?php echo (($old['role'] ?? 'Employee') === 'Employee') ? 'selected' : ''; ?>>Employee</option>
                                <option value="Organizer" <?php echo (($old['role'] ?? '') === 'Organizer') ? 'selected' : ''; ?>>Organizer</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Email ID</label>
                            <input type="email" name="email" class="form-control rounded-3"
                                   placeholder="user@project.local"
                                   value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                                   required maxlength="150">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="createPassword"
                                       class="form-control rounded-start-3"
                                       placeholder="Min 8 chars, uppercase, lowercase, number"
                                       required minlength="8" maxlength="64">
                                <button type="button" class="btn btn-outline-secondary rounded-end-3"
                                        onclick="togglePw('createPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">8–64 characters · uppercase · lowercase · number</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="fas fa-user-plus me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

                <div class="mb-4">
                    <label class="form-label">Password <?php echo $editUser ? '<span class="text-muted fs-6 fw-normal">(Leave blank to keep current)</span>' : ''; ?></label>
                    <input type="password" name="password" class="form-control rounded-3" <?php echo $editUser ? '' : 'required'; ?> minlength="8" maxlength="64">
                    <small class="text-muted">Use 8-64 characters with uppercase, lowercase, and a number.</small>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/UserController.php" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="user_id" id="editUserId">

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 w-100">
                        <i class="fas fa-save me-1"></i> <?php echo $editUser ? 'Save Changes' : 'Create User'; ?>
                    </button>
                    <?php if ($editUser): ?>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3 w-100">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Reset Password Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="resetPasswordModalLabel" style="color:var(--gov-blue);">
                        <i class="fas fa-key me-2 text-warning"></i>Reset Password
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Set a new password for <strong id="resetUserName"></strong>.</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/UserController.php" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="user_id" id="resetUserId">

            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Department</th>
                            <th>Email ID</th>
                            <th>Role</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($user['department']); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $roleBadge = match($user['role']) {
                                            'Collector' => 'badge-role badge-role-collector',
                                            'Organizer' => 'badge-role badge-role-organizer',
                                            'Employee' => 'badge-role badge-role-employee',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary me-1 rounded-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form action="../../controllers/UserController.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-open Create modal if validation errors came back from server
<?php if ($reopenCreate): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('createUserModal')).show();
});
<?php endif; ?>

// Populate Edit modal
document.getElementById('editUserModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('editUserId').value  = btn.dataset.id;
    document.getElementById('editName').value    = btn.dataset.name;
    document.getElementById('editEmail').value   = btn.dataset.email;
    document.getElementById('editRole').value    = btn.dataset.role;
    var sel = document.getElementById('editDept');
    for (var o of sel.options) o.selected = o.value === btn.dataset.dept;
});

// Populate Reset Password modal
document.getElementById('resetPasswordModal').addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('resetUserId').value         = btn.dataset.id;
    document.getElementById('resetUserName').textContent = btn.dataset.name;
    document.getElementById('newPassword').value         = '';
    document.getElementById('confirmPassword').value     = '';
    document.getElementById('pwMatchMsg').textContent    = '';
    document.getElementById('resetSubmitBtn').disabled   = false;
});

// Live password match
document.getElementById('confirmPassword').addEventListener('input', function () {
    var pw  = document.getElementById('newPassword').value;
    var msg = document.getElementById('pwMatchMsg');
    var btn = document.getElementById('resetSubmitBtn');
    if (!this.value) { msg.textContent = ''; btn.disabled = false; return; }
    if (pw === this.value) {
        msg.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>Passwords match</span>';
        btn.disabled = false;
    } else {
        msg.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</span>';
        btn.disabled = true;
    }
});

// Toggle password visibility
function togglePw(fieldId, btn) {
    var f = document.getElementById(fieldId);
    var i = btn.querySelector('i');
    if (f.type === 'password') { f.type = 'text';     i.classList.replace('fa-eye', 'fa-eye-slash'); }
    else                       { f.type = 'password'; i.classList.replace('fa-eye-slash', 'fa-eye'); }
}
</script>

<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>
