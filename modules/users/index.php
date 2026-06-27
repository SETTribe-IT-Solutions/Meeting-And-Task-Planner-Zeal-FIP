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

$conn = getDBConnection();
$departments = getDepartments();
$old = $_SESSION['user_form_old'] ?? ['name' => '', 'department' => '', 'email' => ''];
$errors = $_SESSION['user_form_errors'] ?? [];
unset($_SESSION['user_form_errors']);

$result = $conn->query("SELECT id, name, email, role, department FROM users WHERE isDeleted = 'No' ORDER BY id DESC");
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

<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">User Management</h3>
                    <p class="text-muted mb-0">Create department users and review active user accounts.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2"><i class="fas fa-users me-1"></i> <?php echo $totalUsers; ?> Users</span>
                    <span class="badge badge-role-employee px-3 py-2"><?php echo $employeeCount; ?> Employees</span>
                    <span class="badge badge-role-organizer px-3 py-2"><?php echo $organizerCount; ?> Organizers</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-user-plus text-primary me-2"></i> <?php echo $editUser ? 'Edit User' : 'Create User'; ?>
            </h5>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-3">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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

                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select rounded-3" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo htmlspecialchars($department); ?>" <?php echo (($old['department'] ?? '') === $department) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email ID</label>
                    <input type="email" name="email" class="form-control rounded-3" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required maxlength="150">
                </div>

                <div class="mb-4">
                    <label class="form-label">Password <?php echo $editUser ? '<span class="text-muted fs-6 fw-normal">(Leave blank to keep current)</span>' : ''; ?></label>
                    <input type="password" name="password" class="form-control rounded-3" <?php echo $editUser ? '' : 'required'; ?> minlength="8" maxlength="64">
                    <small class="text-muted">Use 8-64 characters with uppercase, lowercase, and a number.</small>
                </div>

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

    <div class="col-lg-8 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4" id="usersTableWrapper" data-paginate data-per-page="10">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-users text-primary me-2"></i> User List
            </h5>

            <div class="table-filter-bar">
                <div class="table-search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search users..." data-table-search="usersTableWrapper">
                </div>
                <span class="table-result-count"><?php echo $totalUsers; ?> records</span>
            </div>

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

<?php
unset($_SESSION['user_form_old']);
include_once '../../includes/footer.php';
?>
