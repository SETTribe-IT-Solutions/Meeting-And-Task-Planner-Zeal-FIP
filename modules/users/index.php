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
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #0b3d5f;">User Management</h3>
                    <p class="text-muted mb-0">Create department users and review active user accounts.</p>
                </div>
                <span class="badge bg-primary px-3 py-2">Users</span>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2">
                <i class="fas fa-user-plus text-primary me-2"></i> Create User
            </h5>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-3">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success rounded-3">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="../../controllers/UserController.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">User Name</label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>" required minlength="3" maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Department</label>
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
                    <label class="form-label small fw-semibold text-secondary">Email ID</label>
                    <input type="email" name="email" class="form-control rounded-3" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required maxlength="150">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Password</label>
                    <input type="password" name="password" class="form-control rounded-3" required minlength="8" maxlength="64">
                    <small class="text-muted">Use 8-64 characters with uppercase, lowercase, and a number.</small>
                </div>

                <button type="submit" class="btn btn-primary rounded-3 w-100" style="background-color: var(--gov-blue);">
                    <i class="fas fa-save me-1"></i> Create User
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2">
                <i class="fas fa-users text-primary me-2"></i> User List
            </h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#eef6ff; border-top: 2px solid #0b3d5f;">
                        <tr>
                            <th>User Name</th>
                            <th>Department</th>
                            <th>Email ID</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($user['department']); ?></span></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role']); ?></span></td>
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
