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
$inactiveDepts = $totalDepts - $activeDepts;

$deptErrors  = $_SESSION['department_errors'] ?? [];
$deptOld     = $_SESSION['department_old']    ?? [];
unset($_SESSION['department_errors'], $_SESSION['department_old']);

// Re-open create modal on create errors (no department_id in old data)
$reopenCreate = !empty($deptErrors) && empty($deptOld['department_id']);
// Re-open edit modal on update errors
$reopenEditId = !empty($deptErrors) && !empty($deptOld['department_id'])
    ? (int)$deptOld['department_id']
    : (isset($_GET['edit']) ? (int)$_GET['edit'] : 0);

include_once '../../includes/header.php';
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

<!-- Flash messages -->
<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible rounded-3 shadow-sm mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible rounded-3 shadow-sm mb-4" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Departments Table — full width -->
<div class="card border-0 shadow-sm bg-white p-4 animate-on-scroll"
     id="deptTableWrapper" data-paginate data-per-page="10">

    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3 flex-wrap gap-2">
        <h5 class="fw-bold mb-0" style="color: var(--gov-blue);">
            <i class="fas fa-building text-primary me-2"></i>All Departments
        </h5>
    </div>

    <div class="table-filter-bar">
        <div class="table-search-input">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search departments..." data-table-search="deptTableWrapper">
        </div>
        <span class="table-result-count"><?php echo $totalDepts; ?> records</span>
    </div>

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
                <div class="modal-body px-4 pt-3 pb-2">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <?php if ($reopenCreate && !empty($deptErrors)): ?>
                    <div class="alert alert-danger rounded-3 py-2 small mb-3">
                        <?php foreach ($deptErrors as $e): ?>
                            <div><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Department Name</label>
                        <input type="text" name="name" class="form-control rounded-3"
                               placeholder="e.g. Revenue, Education"
                               value="<?php echo $reopenCreate ? htmlspecialchars($deptOld['name'] ?? '') : ''; ?>"
                               required minlength="2" maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" class="form-control rounded-3" rows="3"
                                  placeholder="Brief overview of department functions..."
                                  maxlength="1000"><?php echo $reopenCreate ? htmlspecialchars($deptOld['description'] ?? '') : ''; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="fas fa-plus-circle me-1"></i>Add Department
                    </button>
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

                    <?php if ($reopenEditId && !empty($deptErrors)): ?>
                    <div class="alert alert-danger rounded-3 py-2 small mb-3">
                        <?php foreach ($deptErrors as $e): ?>
                            <div><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Department Name</label>
                        <input type="text" name="name" id="editDeptName" class="form-control rounded-3"
                               required minlength="2" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="description" id="editDeptDesc" class="form-control rounded-3"
                                  rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" id="editDeptStatus" class="form-select rounded-3">
                            <option value="Yes">Active</option>
                            <option value="No">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
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
