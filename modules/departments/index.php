<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

if (!isset($_SESSION['role']) || !isOrganizer()) {
    header('Location: ../users/login.php');
    exit();
}

$currentRole = $_SESSION['role'];
$conn        = getDBConnection();

$result      = $conn->query("SELECT * FROM departments ORDER BY is_active DESC, name ASC");
$departments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$totalDepts  = count($departments);
$activeDepts = count(array_filter($departments, fn($d) => ($d['is_active'] ?? 'Yes') === 'Yes'));
$editDepartment = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($departments as $dept) {
        if ($dept['id'] === $editId) {
            $editDepartment = $dept;
            break;
        }
    }
}
?>

<!-- Page Header -->
<div class="card p-4 border-0 mb-4 shadow-sm bg-white animate-on-scroll">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Department Management</h3>
            <p class="text-muted mb-0">Configure the organisational departments for Latur administration.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-building me-1"></i><?php echo $totalDepts; ?> Total
            </span>
            <span class="badge bg-success px-3 py-2">
                <i class="fas fa-check-circle me-1"></i><?php echo $activeDepts; ?> Active
            </span>
            <?php if ($inactiveDepts > 0): ?>
            <span class="badge bg-secondary px-3 py-2"><?php echo $inactiveDepts; ?> Inactive</span>
            <?php endif; ?>
            <?php if ($currentRole === 'Organizer'): ?>
            <button type="button" class="btn btn-primary rounded-3 px-4 fw-semibold"
                    data-bs-toggle="modal" data-bs-target="#createDeptModal">
                <i class="fas fa-plus-circle me-2"></i>Add Department
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

    <div class="col-lg-4 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-plus-circle text-primary me-2"></i> <?php echo $editDepartment ? 'Edit Department' : 'Add Department'; ?>
            </h5>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['department_errors']) && is_array($_SESSION['department_errors'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($_SESSION['department_errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['department_errors']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-enhanced table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <?php if ($currentRole === 'Organizer'): ?>
                    <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>
                            No departments found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($departments as $dept):
                        $isActive = ($dept['is_active'] ?? 'Yes') === 'Yes';
                    ?>
                    <tr class="<?php echo !$isActive ? 'opacity-50' : ''; ?>">
                        <td class="text-muted small"><?php echo $i++; ?></td>
                        <td class="fw-semibold <?php echo !$isActive ? 'text-muted' : 'text-dark'; ?>">
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </td>
                        <td class="text-muted small">
                            <?php echo htmlspecialchars($dept['description'] ?? '—'); ?>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size:.75rem;">
                                    <i class="fas fa-circle me-1" style="font-size:.5rem;vertical-align:middle;"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:.75rem;">
                                    <i class="fas fa-ban me-1" style="font-size:.65rem;"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($currentRole === 'Organizer'): ?>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end flex-nowrap">
                                <!-- Edit -->
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary rounded-3"
                                    title="Edit Department"
                                    data-bs-toggle="modal" data-bs-target="#editDeptModal"
                                    data-id="<?php echo $dept['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>"
                                    data-desc="<?php echo htmlspecialchars($dept['description'] ?? '', ENT_QUOTES); ?>"
                                    data-active="<?php echo $dept['is_active'] ?? 'Yes'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Toggle Active / Inactive -->
                                <form action="../../controllers/DepartmentController.php" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('<?php echo $isActive
                                          ? 'Deactivate ' . addslashes($dept['name']) . '? Users in this department will still exist.'
                                          : 'Activate ' . addslashes($dept['name']) . '?'; ?>')">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $isActive ? 'No' : 'Yes'; ?>">
                                    <button type="submit"
                                        class="btn btn-sm <?php echo $isActive ? 'btn-outline-danger' : 'btn-outline-success'; ?> rounded-3"
                                        title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas <?php echo $isActive ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($currentRole === 'Organizer'): ?>

<!-- ── Add Department Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="createDeptModal" tabindex="-1" aria-labelledby="createDeptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="createDeptModalLabel" style="color:var(--gov-blue);">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Add Department
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Register a new department for the organisation.</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/DepartmentController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="action" value="<?php echo $editDepartment ? 'update' : 'create'; ?>">
                <?php if ($editDepartment): ?>
                    <input type="hidden" name="department_id" value="<?php echo $editDepartment['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="name" class="form-control rounded-3" required placeholder="e.g., Revenue, Education" value="<?php echo $editDepartment ? htmlspecialchars($editDepartment['name']) : ''; ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief overview of department functions..."><?php echo $editDepartment ? htmlspecialchars($editDepartment['description']) : ''; ?></textarea>
                </div>
                <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100">
                    <i class="fas fa-plus-circle me-1"></i> <?php echo $editDepartment ? 'Save Changes' : 'Add Department'; ?>
                </button>
                <?php if ($editDepartment): ?>
                    <a href="index.php" class="btn btn-outline-secondary rounded-3 w-100">Cancel Edit</a>
                <?php endif; ?>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Edit Department Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="editDeptModal" tabindex="-1" aria-labelledby="editDeptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow border-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="editDeptModalLabel" style="color:var(--gov-blue);">
                        <i class="fas fa-edit me-2 text-primary"></i>Edit Department
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Update department name or description.</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <form action="../../controllers/DepartmentController.php" method="POST">
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="department_id" id="editDeptId">

            <div class="table-responsive">
                <table class="table table-enhanced table-hover align-middle mb-0">
                    <thead>
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
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($dept['name']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($dept['description'] ?? '—'); ?></td>
                                    <td>
                                        <?php if (($dept['is_active'] ?? 'Yes') === 'Yes'): ?>
                                            <span class="badge badge-status-completed">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-status-cancelled">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?edit=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-primary me-1 rounded-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="../../controllers/DepartmentController.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this department? This will fail if employees, meetings, or active tasks are still associated.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3">
                                                <i class="fas fa-trash-alt"></i>
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

<script>
// Auto-open Add modal on create validation error
<?php if ($reopenCreate): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('createDeptModal')).show();
});
<?php endif; ?>

// Auto-open Edit modal on update error or ?edit= param
<?php if ($reopenEditId): ?>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.querySelector('[data-bs-target="#editDeptModal"][data-id="<?php echo $reopenEditId; ?>"]');
    if (btn) btn.click();
    else {
        // Populate manually from server-side old values
        document.getElementById('editDeptId').value     = '<?php echo $reopenEditId; ?>';
        document.getElementById('editDeptName').value   = <?php echo json_encode($deptOld['name'] ?? ''); ?>;
        document.getElementById('editDeptDesc').value   = <?php echo json_encode($deptOld['description'] ?? ''); ?>;
        document.getElementById('editDeptStatus').value = <?php echo json_encode($deptOld['status'] ?? 'Yes'); ?>;
        new bootstrap.Modal(document.getElementById('editDeptModal')).show();
    }
});
<?php endif; ?>

// Populate Edit modal from row data attributes
document.getElementById('editDeptModal').addEventListener('show.bs.modal', function (e) {
    if (!e.relatedTarget) return; // skip when opened programmatically above
    var btn = e.relatedTarget;
    document.getElementById('editDeptId').value     = btn.dataset.id;
    document.getElementById('editDeptName').value   = btn.dataset.name;
    document.getElementById('editDeptDesc').value   = btn.dataset.desc;
    document.getElementById('editDeptStatus').value = btn.dataset.active;
});
</script>

<?php endif; ?>

<?php include_once '../../includes/footer.php'; ?>
