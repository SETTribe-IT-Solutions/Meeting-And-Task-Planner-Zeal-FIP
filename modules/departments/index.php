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

// Fetch departments
$result = $conn->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$totalDepts = count($departments);
$activeDepts = count(array_filter($departments, fn($d) => ($d['is_active'] ?? 'Yes') === 'Yes'));
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 border-0 mb-4 shadow-sm bg-white animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Department Management</h3>
                    <p class="text-muted mb-0">Configure departments for organizational structure.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary px-3 py-2"><i class="fas fa-building me-1"></i> <?php echo $totalDepts; ?> Total</span>
                    <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i> <?php echo $activeDepts; ?> Active</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-plus-circle text-primary me-2"></i> Add Department
            </h5>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="../../controllers/DepartmentController.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="name" class="form-control rounded-3" required placeholder="e.g., Revenue, Education">
                </div>
                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief overview of department functions..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary rounded-3 w-100">
                    <i class="fas fa-plus-circle me-1"></i> Add Department
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-8 animate-on-scroll">
        <div class="card border-0 shadow-sm bg-white p-4" id="deptTableWrapper" data-paginate data-per-page="10">
            <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                <i class="fas fa-building text-primary me-2"></i> All Departments
            </h5>

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
                                        <form action="../../controllers/DepartmentController.php" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to toggle this department?');">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo ($dept['is_active'] ?? 'Yes') === 'Yes' ? 'No' : 'Yes'; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo ($dept['is_active'] ?? 'Yes') === 'Yes' ? 'btn-outline-danger' : 'btn-outline-success'; ?> rounded-3" 
                                                    data-bs-toggle="tooltip" title="<?php echo ($dept['is_active'] ?? 'Yes') === 'Yes' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo ($dept['is_active'] ?? 'Yes') === 'Yes' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
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

<?php include_once '../../includes/footer.php'; ?>
