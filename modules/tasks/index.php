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

if ($role === 'Collector') {
    // Collector sees all tasks
    $sql = "SELECT t.*, u.name as assignee_name, m.title as meeting_title, o.name as organizer_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN meetings m ON t.meeting_id = m.id
            JOIN users o ON m.organizer_id = o.id
            WHERE 1=1";
} elseif ($role === 'Organizer') {
    // Organizer sees tasks for meetings they created
    $sql = "SELECT t.*, u.name as assignee_name, m.title as meeting_title, o.name as organizer_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN meetings m ON t.meeting_id = m.id
            JOIN users o ON m.organizer_id = o.id
            WHERE m.organizer_id = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    // Employee sees tasks assigned to them
    $sql = "SELECT t.*, u.name as assignee_name, m.title as meeting_title, o.name as organizer_name 
            FROM tasks t 
            JOIN users u ON t.assigned_to = u.id 
            JOIN meetings m ON t.meeting_id = m.id
            JOIN users o ON m.organizer_id = o.id
            WHERE t.assigned_to = ?";
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
    $sql .= " AND (t.title LIKE ? OR m.title LIKE ? OR u.name LIKE ?)";
    $searchWildcard = "%" . $searchQuery . "%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "sss";
}

$sql .= " ORDER BY t.due_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <div class="card p-4 border-0 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #0b3d5f;">Task Board</h3>
                    <p class="text-muted mb-0">Monitor responsibilities and update completion status.</p>
                </div>
                <?php if ($role === 'Organizer' || $role === 'Collector'): ?>
                <a href="create.php" class="btn btn-primary rounded-3 px-3 py-2" style="background-color: #0b3d5f; border-color: #0b3d5f;">
                    <i class="fas fa-plus-circle me-1"></i> Assign New Task
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card p-4 border-0 shadow-sm mb-4 bg-white">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Search</label>
                    <input type="text" name="search" class="form-control rounded-3" placeholder="Task title, meeting, employee..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Status</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">Priority</label>
                    <select name="priority" class="form-select rounded-3">
                        <option value="">All Priorities</option>
                        <option value="Low" <?php echo $priorityFilter === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo $priorityFilter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo $priorityFilter === 'High' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary rounded-3 py-2" style="background-color: #0b3d5f; border-color: #0b3d5f;">Filter</button>
                </div>
            </form>
        </div>

        <!-- Tasks Table -->
        <div class="card p-4 border-0 shadow-sm bg-white">
            <?php if (empty($tasks)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-card-checklist fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No tasks found matching your filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background:#eef6ff; border-top: 2px solid #0b3d5f;">
                            <tr>
                                <th>Task Title</th>
                                <th>Meeting Reference</th>
                                <th>Assigned To</th>
                                <th>Due Date</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($task['notes']); ?></small>
                                    </td>
                                    <td>
                                        <a href="../meetings/view.php?id=<?php echo $task['meeting_id']; ?>" class="text-decoration-none fw-semibold">
                                            <?php echo htmlspecialchars($task['meeting_title']); ?>
                                        </a>
                                        <small class="text-muted d-block">By: <?php echo htmlspecialchars($task['organizer_name']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($task['assignee_name']); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $dueDate = strtotime($task['due_date']);
                                        $isOverdue = ($dueDate < strtotime($today) && $task['status'] !== 'Completed');
                                        ?>
                                        <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo date('d M Y', $dueDate); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priority = $task['priority'];
                                        $pClass = match($priority) {
                                            'High' => 'danger',
                                            'Medium' => 'warning text-dark',
                                            'Low' => 'success',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $pClass; ?>"><?php echo htmlspecialchars($priority); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $task['status'];
                                        $sClass = match($status) {
                                            'Completed' => 'success',
                                            'In Progress' => 'info text-dark',
                                            'Pending' => 'warning text-dark',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $sClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($role === 'Employee' && $task['assigned_to'] == $user_id): ?>
                                            <!-- Employee update actions -->
                                            <form action="../../controllers/UpdateTaskStatusController.php" method="POST" class="d-inline-block">
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