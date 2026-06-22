<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

if (!isset($_SESSION['role']) || !isOrganizer()) {
    header('Location: ../users/login.php');
    exit();
}

$conn = getDBConnection();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editDepartment = null;

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT id, name, description, is_active FROM departments WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editDepartment = $stmt->get_result()->fetch_assoc();
}

$old = $_SESSION['department_old'] ?? [];
$formData = [
    'department_id' => $editDepartment['id'] ?? ($old['department_id'] ?? 0),
    'name' => $editDepartment['name'] ?? ($old['name'] ?? ''),
    'description' => $editDepartment['description'] ?? ($old['description'] ?? ''),
    'status' => $editDepartment['is_active'] ?? ($old['status'] ?? 'Yes')
];

$errors = $_SESSION['department_errors'] ?? [];
unset($_SESSION['department_errors']);

$result = $conn->query("SELECT id, name, description, is_active, created_at FROM departments ORDER BY name ASC");
$departments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include_once '../../includes/header.php';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #0b3d5f;">Department Management</h3>
                    <p class="text-muted mb-0">Add, edit, delete, and view departments used across users and meetings.</p>
                </div>
                <span class="badge bg-primary px-3 py-2">Departments</span>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2">
                <i class="fas fa-building text-primary me-2"></i>
                <?php echo $editDepartment ? 'Edit Department' : 'Add Department'; ?>
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

            <form action="../../controllers/DepartmentController.php" method="POST">
                <input type="hidden" name="action" value="<?php echo $editDepartment ? 'update' : 'create'; ?>">
                <input type="hidden" name="department_id" value="<?php echo (int)$formData['department_id']; ?>">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Department Name</label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?php echo htmlspecialchars($formData['name']); ?>" required minlength="2" maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Department Description <span class="text-muted">(optional)</span></label>
                    <textarea name="description" class="form-control rounded-3" rows="4" maxlength="1000"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Department Status</label>
                    <select name="status" class="form-select rounded-3" required>
                        <option value="Yes" <?php echo $formData['status'] === 'Yes' ? 'selected' : ''; ?>>Active</option>
                        <option value="No" <?php echo $formData['status'] === 'No' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 flex-grow-1" style="background-color: var(--gov-blue);">
                        <i class="fas fa-save me-1"></i> <?php echo $editDepartment ? 'Update' : 'Add'; ?> Department
                    </button>
                    <?php if ($editDepartment): ?>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold text-gov-blue mb-3 border-bottom pb-2">
                <i class="fas fa-list text-primary me-2"></i> Department List
            </h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:#eef6ff; border-top: 2px solid #0b3d5f;">
                        <tr>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No departments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($department['name']); ?></td>
                                    <td class="text-muted small">
                                        <?php echo htmlspecialchars($department['description'] ?: 'No description'); ?>
                                    </td>
                                    <td>
                                        <?php if ($department['is_active'] === 'Yes'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="index.php?edit=<?php echo (int)$department['id']; ?>" class="btn btn-sm btn-outline-primary rounded-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="../../controllers/DepartmentController.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this department? Existing users and meetings will keep their current department text.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="department_id" value="<?php echo (int)$department['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
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
unset($_SESSION['department_old']);
include_once '../../includes/footer.php';
?>
