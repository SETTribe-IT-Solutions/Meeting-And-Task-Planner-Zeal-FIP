<?php
// modules/tasks/index.php
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
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$priorityFilter = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query based on role and filters
$sql = "";
$params = [];
$types = "";

// Build base SQL with aggregated assignees
$baseSelect = "SELECT t.*, m.title as meeting_title, m.department as meeting_department, o.name as organizer_name, GROUP_CONCAT(DISTINCT ua.name SEPARATOR ', ') AS assignees ";
$baseFrom = "FROM tasks t 
            JOIN meetings m ON t.meeting_id = m.id
            JOIN users o ON m.organizer_id = o.id
            LEFT JOIN task_assignments ta ON ta.task_id = t.id
            LEFT JOIN users ua ON ta.user_id = ua.id";

if ($role === 'Collector') {
    $sql = $baseSelect . $baseFrom . " WHERE 1=1";
} elseif ($role === 'Organizer') {
    $sql = $baseSelect . $baseFrom . " WHERE m.organizer_id = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    // Employee sees tasks assigned to them
    $sql = $baseSelect . $baseFrom . " WHERE EXISTS (SELECT 1 FROM task_assignments t2 WHERE t2.task_id = t.id AND t2.user_id = ?)";
    $params[] = $user_id;
    $types .= "i";
}

if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}
if (!empty($priorityFilter)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
    $types .= "s";
}
if (!empty($searchQuery)) {
    $sql .= " AND (t.title LIKE ? OR m.title LIKE ? OR ua.name LIKE ?)";
    $searchWildcard = "%" . $searchQuery . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "sss";
}

// GROUP BY must follow all WHERE conditions, not precede them
$sql .= " GROUP BY t.id";

$sql .= " ORDER BY t.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// If requested via AJAX, return JSON list of tasks for client-side refresh
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($tasks);
    exit();
}

// Stats
$today = date('Y-m-d');
$totalTasks = count($tasks);
$pendingCount = count(array_filter($tasks, fn($t) => $t['status'] === 'Pending'));
$inProgressCount = count(array_filter($tasks, fn($t) => $t['status'] === 'In Progress'));
$completedCount = count(array_filter($tasks, fn($t) => $t['status'] === 'Completed'));
$overdueCount = count(array_filter($tasks, fn($t) => strtotime($t['due_date']) < strtotime(date('Y-m-d')) && $t['status'] !== 'Completed'));
?>

<div class="row">
    <div class="col-12">
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

        <div class="card p-4 border-0 shadow-sm mb-4 animate-on-scroll">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Task Board</h3>
                    <p class="text-muted mb-0">Monitor responsibilities and update completion status.</p>
                </div>
                <?php if ($role === 'Organizer' || $role === 'Collector'): ?>
                <a href="create.php" class="btn btn-primary rounded-3 px-3 py-2">
                    <i class="fas fa-plus-circle me-1"></i> Assign New Task
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Task Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-3">
                    <div class="stat-label">TOTAL</div>
                    <div class="stat-value counter-value" data-target="<?php echo $totalTasks; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-3">
                    <div class="stat-label">PENDING</div>
                    <div class="stat-value counter-value" data-target="<?php echo $pendingCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-info border-0 p-3">
                    <div class="stat-label">IN PROGRESS</div>
                    <div class="stat-value counter-value" data-target="<?php echo $inProgressCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-3">
                    <div class="stat-label">COMPLETED</div>
                    <div class="stat-value counter-value" data-target="<?php echo $completedCount; ?>" style="font-size:1.5rem;">0</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white animate-on-scroll">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Task title, meeting, employee..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select rounded-3">
                        <option value="">All Priorities</option>
                        <option value="Low" <?php echo $priorityFilter === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $priorityFilter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $priorityFilter === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-3 py-2 flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline-secondary rounded-3 py-2" title="Reset"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Tasks Table -->
        <div class="card p-4 border-0 shadow-sm bg-white animate-on-scroll" id="tasksTableWrapper" data-paginate data-per-page="10">
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <i class="bi bi-card-checklist"></i>
                    <p>No tasks found matching your filters.</p>
                </div>
            <?php else: ?>
                <div class="table-filter-bar">
                    <div class="table-search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Quick filter..." data-table-search="tasksTableWrapper">
                    </div>
                    <span class="table-result-count"><?php echo $totalTasks; ?> records</span>
                    <?php if ($overdueCount > 0): ?>
                        <span class="badge badge-priority-high"><i class="fas fa-exclamation-triangle me-1"></i><?php echo $overdueCount; ?> Overdue</span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-enhanced table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                                <th>Task ID</th>
                                                <th>Task Title</th>
                                                <th>Department</th>
                                                <th>Assigned To</th>
                                                <th>Assigned By</th>
                                                <th>Meeting Reference</th>
                                                <th>Due Date</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <?php
                                $dueDate = strtotime($task['due_date']);
                                $isOverdue = ($dueDate < strtotime($today) && $task['status'] !== 'Completed');
                                ?>
                                <tr class="<?php echo $isOverdue ? 'overdue-row' : ''; ?>">
                                    <td>#<?php echo (int)$task['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($task['notes']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($task['meeting_department'] ?? $task['department'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($task['assignees'] ?? $task['assignee_name'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($task['organizer_name']); ?></div>
                                    </td>
                                    <td>
                                        <a href="../meetings/view.php?id=<?php echo $task['meeting_id']; ?>" class="text-decoration-none fw-semibold">
                                            <?php echo htmlspecialchars($task['meeting_title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="<?php echo $isOverdue ? 'overdue-text' : ''; ?>">
                                            <?php echo date('d M Y', $dueDate); ?>
                                            <?php if ($isOverdue): ?>
                                                <br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> Overdue</small>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priority = $task['priority'];
                                        $pClass = match($priority) {
                                            'High' => 'badge-priority-high',
                                            'Medium' => 'badge-priority-medium',
                                            'Low' => 'badge-priority-low',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $pClass; ?>"><?php echo htmlspecialchars($priority); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $task['status'];
                                        $sClass = match($status) {
                                            'Completed' => 'badge-status-completed',
                                            'In Progress' => 'badge-status-ongoing',
                                            'Pending' => 'badge-status-scheduled',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $sClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($role === 'Employee'): ?>
                                            <!-- Employee update actions -->
                                            <form action="../../controllers/UpdateTaskStatusController.php" method="POST" class="d-inline-block">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block rounded-3 w-auto" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </form>
                                        <?php elseif ($role === 'Organizer' || $role === 'Collector'): ?>
                                            <!-- Admin/Organizer update actions -->
                                            <form action="../../controllers/UpdateTaskStatusController.php" method="POST" class="d-inline-block">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block rounded-3 w-auto" onchange="this.form.submit()">
                                                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="In Progress" <?php echo $status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="Completed" <?php echo $status === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>