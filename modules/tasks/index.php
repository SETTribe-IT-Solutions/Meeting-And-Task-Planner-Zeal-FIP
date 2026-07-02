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

// Build base SQL with aggregated assignees + attachment info
$baseSelect = "SELECT t.*, m.title as meeting_title, m.department as meeting_department, o.name as organizer_name,
    GROUP_CONCAT(DISTINCT ua.name SEPARATOR ', ') AS assignees,
    (SELECT original_name FROM task_attachments WHERE task_id = t.id ORDER BY uploaded_at DESC LIMIT 1) AS att_original_name,
    (SELECT id FROM task_attachments WHERE task_id = t.id ORDER BY uploaded_at DESC LIMIT 1) AS att_id ";
$baseFrom = "FROM tasks t
            JOIN meetings m ON t.meeting_id = m.id
            JOIN users o ON m.organizer_id = o.id
            LEFT JOIN task_assignments ta ON ta.task_id = t.id
            LEFT JOIN users ua ON ta.user_id = ua.id";

if ($role === 'Collector' || $role === 'Organizer') {
    // Both see all tasks — Organizer is super admin
    $sql = $baseSelect . $baseFrom . " WHERE 1=1";
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
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="meeting-info-badge">
                        <i class="fas fa-clock text-primary me-1"></i>
                        <span><strong>Next:</strong> <?php echo htmlspecialchars($nextMeetingText ?? 'No upcoming meetings'); ?></span>
                    </div>
                    <div class="meeting-info-badge">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        <span><?php echo htmlspecialchars($tasksDueTodayText ?? '0 tasks due today'); ?></span>
                    </div>
                    <?php if ($role === 'Organizer'): ?>
                    <a href="create.php" class="btn btn-primary rounded-3 px-3 py-2">
                        <i class="fas fa-plus-circle me-1"></i> Assign New Task
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<style>
.meeting-info-badge {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    padding: 0.45rem 1rem;
    font-size: 0.82rem;
    color: #334155;
    font-weight: 500;
    white-space: nowrap;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

        <!-- Task Stats (click to filter) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-primary border-0 p-3 stat-filter-card" data-filter="all" role="button" title="Show all tasks">
                    <div class="stat-label">TOTAL</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $totalTasks; ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-warning border-0 p-3 stat-filter-card" data-filter="pending" role="button" title="Show pending tasks">
                    <div class="stat-label">PENDING</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $pendingCount; ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-info border-0 p-3 stat-filter-card" data-filter="in progress" role="button" title="Show in-progress tasks">
                    <div class="stat-label">IN PROGRESS</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $inProgressCount; ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 animate-on-scroll">
                <div class="card stat-card stat-success border-0 p-3 stat-filter-card" data-filter="completed" role="button" title="Show completed tasks">
                    <div class="stat-label">COMPLETED</div>
                    <div class="stat-value" style="font-size:1.5rem;"><?php echo $completedCount; ?></div>
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
        <div class="card p-4 border-0 shadow-sm bg-white" id="tasksTableWrapper" data-paginate data-per-page="10">
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
                                <tr class="<?php echo $isOverdue ? 'overdue-row' : ''; ?>"
                                    style="cursor:pointer;"
                                    data-row-status="<?php echo strtolower(htmlspecialchars($task['status'])); ?>"
                                    data-task-id="<?php echo (int)$task['id']; ?>"
                                    data-task-title="<?php echo htmlspecialchars($task['title'], ENT_QUOTES); ?>"
                                    data-task-notes="<?php echo htmlspecialchars($task['notes'] ?? '', ENT_QUOTES); ?>"
                                    data-task-meeting="<?php echo htmlspecialchars($task['meeting_title'], ENT_QUOTES); ?>"
                                    data-task-meeting-id="<?php echo (int)$task['meeting_id']; ?>"
                                    data-task-department="<?php echo htmlspecialchars($task['meeting_department'] ?? $task['department'] ?? '', ENT_QUOTES); ?>"
                                    data-task-assignees="<?php echo htmlspecialchars($task['assignees'] ?? '', ENT_QUOTES); ?>"
                                    data-task-organizer="<?php echo htmlspecialchars($task['organizer_name'], ENT_QUOTES); ?>"
                                    data-task-due="<?php echo date('d M Y', strtotime($task['due_date'])); ?>"
                                    data-task-priority="<?php echo htmlspecialchars($task['priority'], ENT_QUOTES); ?>"
                                    data-task-status="<?php echo htmlspecialchars($task['status'], ENT_QUOTES); ?>"
                                    data-task-overdue="<?php echo $isOverdue ? '1' : '0'; ?>"
                                    data-task-progress-notes="<?php echo htmlspecialchars($task['progress_notes'] ?? '', ENT_QUOTES); ?>"
                                    data-task-updated-at="<?php echo htmlspecialchars($task['updated_at'] ?? '', ENT_QUOTES); ?>"
                                    data-att-id="<?php echo (int)($task['att_id'] ?? 0); ?>"
                                    data-att-name="<?php echo htmlspecialchars($task['att_original_name'] ?? '', ENT_QUOTES); ?>"
                                    onclick="openTaskModal(this)">
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
                                    <td class="text-end" onclick="event.stopPropagation();">
                                        <div class="d-flex gap-1 justify-content-end flex-nowrap">

                                            <?php if ($role === 'Organizer'): ?>
                                            <!-- Edit -->
                                            <a href="edit.php?id=<?php echo (int)$task['id']; ?>"
                                               class="btn btn-sm btn-outline-warning rounded-3 px-2"
                                               title="Edit Task">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <?php endif; ?>

                                            <!-- Update Progress (Status + Notes) -->
                                            <button class="btn btn-sm btn-outline-info rounded-3 px-2"
                                                    title="Update Status &amp; Progress Notes"
                                                    onclick="event.stopPropagation(); openProgressModal(
                                                        '<?php echo (int)$task['id']; ?>',
                                                        <?php echo htmlspecialchars(json_encode($task['title']), ENT_QUOTES); ?>,
                                                        '<?php echo htmlspecialchars($task['status'], ENT_QUOTES); ?>',
                                                        <?php echo htmlspecialchars(json_encode($task['progress_notes'] ?? ''), ENT_QUOTES); ?>,
                                                        <?php echo htmlspecialchars(json_encode($task['updated_at'] ?? ''), ENT_QUOTES); ?>
                                                    )">
                                                <i class="fas fa-comment-alt"></i>
                                            </button>

                                            <!-- Update Status dropdown (quick) -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary rounded-3 px-2 dropdown-toggle"
                                                        type="button"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        title="Update Status">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3" style="min-width:140px;">
                                                    <?php foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $s): ?>
                                                    <li>
                                                        <form action="../../controllers/UpdateTaskStatusController.php" method="POST" class="m-0">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                            <input type="hidden" name="task_id"   value="<?php echo (int)$task['id']; ?>">
                                                            <input type="hidden" name="status"    value="<?php echo $s; ?>">
                                                            <button type="submit"
                                                                    class="dropdown-item <?php echo $status === $s ? 'fw-semibold text-primary' : ''; ?>"
                                                                    style="font-size:0.85rem;">
                                                                <?php if ($status === $s): ?><i class="fas fa-check me-1 small"></i><?php endif; ?>
                                                                <?php echo $s; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>

                                            <?php if ($role === 'Organizer'): ?>
                                            <!-- Delete -->
                                            <form action="../../controllers/DeleteTaskController.php" method="POST" class="m-0"
                                                  onsubmit="return confirm('Delete this task? This cannot be undone.')">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                                <input type="hidden" name="task_id"   value="<?php echo (int)$task['id']; ?>">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger rounded-3 px-2"
                                                        title="Delete Task">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                        </div>
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

<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0" id="taskModalHeader" style="border-radius: 0.5rem 0.5rem 0 0;">
                <h5 class="modal-title fw-bold text-white" id="taskDetailModalLabel">
                    <i class="fas fa-tasks me-2"></i><span id="modalTaskTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="task-detail-label">Task ID</div>
                        <div class="task-detail-value fw-bold" id="modalTaskId"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="task-detail-label">Status</div>
                        <div id="modalTaskStatus"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="task-detail-label">Priority</div>
                        <div id="modalTaskPriority"></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="task-detail-label">Due Date</div>
                        <div id="modalTaskDue" class="fw-semibold"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="task-detail-label">Assigned To</div>
                        <div class="fw-semibold" id="modalTaskAssignees"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="task-detail-label">Assigned By</div>
                        <div class="fw-semibold" id="modalTaskOrganizer"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="task-detail-label">Meeting Reference</div>
                        <div><a id="modalTaskMeeting" href="#" class="fw-semibold text-decoration-none" style="color:var(--gov-blue);"></a></div>
                    </div>
                    <div class="col-md-6">
                        <div class="task-detail-label">Department</div>
                        <div><span class="badge bg-light text-dark border" id="modalTaskDept"></span></div>
                    </div>
                    <div class="col-12" id="modalNotesRow">
                        <div class="task-detail-label">Instructions / Notes</div>
                        <div class="text-muted" id="modalTaskNotes"></div>
                    </div>
                    <div class="col-12" id="modalProgressRow" style="display:none;">
                        <div class="task-detail-label">Progress Notes</div>
                        <div class="bg-light rounded-3 p-2 text-dark small" id="modalTaskProgressNotes" style="white-space:pre-wrap;"></div>
                    </div>
                    <div class="col-md-6" id="modalUpdatedRow" style="display:none;">
                        <div class="task-detail-label">Last Updated</div>
                        <div class="fw-semibold small" id="modalTaskUpdatedAt"></div>
                    </div>
                    <div class="col-md-6" id="modalAttachmentRow" style="display:none;">
                        <div class="task-detail-label">Attachment</div>
                        <a id="modalAttachmentLink" href="#" class="btn btn-sm btn-outline-primary rounded-3 mt-1">
                            <i class="fas fa-download me-1"></i><span id="modalAttachmentName"></span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button type="button"
                        class="btn btn-info rounded-3 px-3" id="modalProgressBtn"
                        style="display:none;"
                        onclick="openProgressFromModal()">
                    <i class="fas fa-comment-alt me-1"></i> Update Progress
                </button>
                <?php if ($role === 'Organizer'): ?>
                <a id="modalEditBtn" href="#" class="btn btn-warning rounded-3 px-4">
                    <i class="fas fa-pencil-alt me-1"></i> Edit Task
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-labelledby="updateProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#0b3d5f,#1a5f7a); border-radius:0.5rem 0.5rem 0 0;">
                <h5 class="modal-title fw-bold text-white" id="updateProgressModalLabel">
                    <i class="fas fa-comment-alt me-2"></i>Update Task Progress
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../../controllers/UpdateTaskStatusController.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <input type="hidden" name="task_id"   id="progressModalTaskId" value="">
                <div class="modal-body py-4 px-4">
                    <p class="fw-semibold mb-3 text-dark" id="progressModalTitle"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="progressModalStatus" class="form-select rounded-3" required>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Last Updated On</label>
                        <input type="text" id="progressModalUpdatedAt" class="form-control rounded-3 bg-light" readonly>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">
                            Progress Notes <span class="text-muted fw-normal small">(optional, max 1000 chars)</span>
                        </label>
                        <textarea name="progress_notes"
                                  id="progressModalNotes"
                                  class="form-control rounded-3"
                                  rows="5"
                                  maxlength="1000"
                                  placeholder="Describe what has been done, any blockers, or next steps…"
                                  oninput="updateProgressCounter()"></textarea>
                    </div>
                    <div class="text-end">
                        <small class="text-muted" id="progressNotesCounter">1000 characters remaining</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-3 px-4">
                        <i class="fas fa-save me-1"></i> Save Progress
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.stat-filter-card { cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; }
.stat-filter-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.12) !important; }
.stat-filter-card.stat-active { outline: 3px solid rgba(0,0,0,0.25); outline-offset: 2px; transform: translateY(-2px); }
.task-detail-label { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; }
.task-detail-value { font-size: 0.95rem; }
</style>

<script>
function openTaskModal(row) {
    var id        = row.dataset.taskId;
    var title     = row.dataset.taskTitle;
    var notes     = row.dataset.taskNotes;
    var meeting   = row.dataset.taskMeeting;
    var meetingId = row.dataset.taskMeetingId;
    var dept      = row.dataset.taskDepartment;
    var assignees = row.dataset.taskAssignees;
    var organizer = row.dataset.taskOrganizer;
    var due       = row.dataset.taskDue;
    var priority  = row.dataset.taskPriority;
    var status    = row.dataset.taskStatus;
    var overdue   = row.dataset.taskOverdue === '1';

    document.getElementById('modalTaskTitle').textContent = title;
    document.getElementById('modalTaskId').textContent    = '#' + id;

    // Notes — hide row if empty
    var notesRow = document.getElementById('modalNotesRow');
    if (notes) {
        document.getElementById('modalTaskNotes').textContent = notes;
        notesRow.style.display = '';
    } else {
        notesRow.style.display = 'none';
    }

    // Meeting link
    var meetingLink = document.getElementById('modalTaskMeeting');
    meetingLink.textContent = meeting;
    meetingLink.href = '../meetings/view.php?id=' + meetingId;

    document.getElementById('modalTaskDept').textContent      = dept;
    document.getElementById('modalTaskAssignees').textContent = assignees;
    document.getElementById('modalTaskOrganizer').textContent = organizer;

    // Due date + overdue badge
    var dueEl = document.getElementById('modalTaskDue');
    dueEl.innerHTML = due;
    if (overdue) {
        dueEl.innerHTML += ' <span class="badge badge-priority-high ms-1"><i class="fas fa-exclamation-circle me-1"></i>Overdue</span>';
    }

    // Priority badge
    var pMap = { High: 'badge-priority-high', Medium: 'badge-priority-medium', Low: 'badge-priority-low' };
    document.getElementById('modalTaskPriority').innerHTML =
        '<span class="badge ' + (pMap[priority] || 'bg-secondary') + '">' + priority + '</span>';

    // Status badge
    var sMap = { Completed: 'badge-status-completed', 'In Progress': 'badge-status-ongoing', Pending: 'badge-status-scheduled', Cancelled: 'badge-status-cancelled' };
    document.getElementById('modalTaskStatus').innerHTML =
        '<span class="badge ' + (sMap[status] || 'bg-secondary') + '">' + status + '</span>';

    // Header gradient by status
    var gradMap = {
        Completed:   'linear-gradient(135deg, #16a34a, #22c55e)',
        'In Progress':'linear-gradient(135deg, #0b3d5f, #1a5f7a)',
        Pending:     'linear-gradient(135deg, #d97706, #f59e0b)',
        Cancelled:   'linear-gradient(135deg, #64748b, #94a3b8)'
    };
    document.getElementById('taskModalHeader').style.background = gradMap[status] || gradMap['In Progress'];

    // Edit button href
    var editBtn = document.getElementById('modalEditBtn');
    if (editBtn) editBtn.href = 'edit.php?id=' + id;

    // Progress notes
    var progressNotes = row.dataset.taskProgressNotes || '';
    var progressRow   = document.getElementById('modalProgressRow');
    if (progressNotes) {
        document.getElementById('modalTaskProgressNotes').textContent = progressNotes;
        progressRow.style.display = '';
    } else {
        progressRow.style.display = 'none';
    }

    // Last updated
    var updatedAt  = row.dataset.taskUpdatedAt || '';
    var updatedRow = document.getElementById('modalUpdatedRow');
    if (updatedAt) {
        var d = new Date(updatedAt.replace(' ', 'T'));
        var formatted = isNaN(d) ? updatedAt :
            d.toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'}) + ' ' +
            d.toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',hour12:true});
        document.getElementById('modalTaskUpdatedAt').textContent = formatted;
        updatedRow.style.display = '';
    } else {
        updatedRow.style.display = 'none';
    }

    // Attachment download link
    var attId       = row.dataset.attId || '0';
    var attName     = row.dataset.attName || '';
    var attRow      = document.getElementById('modalAttachmentRow');
    var attLink     = document.getElementById('modalAttachmentLink');
    if (attId && attId !== '0' && attName) {
        attLink.href = 'download_attachment.php?task_id=' + id;
        document.getElementById('modalAttachmentName').textContent = attName;
        attRow.style.display = '';
    } else {
        attRow.style.display = 'none';
    }

    // "Update Progress" button in modal footer — store task id for openProgressFromModal()
    var progressBtn = document.getElementById('modalProgressBtn');
    if (progressBtn) {
        progressBtn.dataset.taskId       = id;
        progressBtn.dataset.taskTitle    = title;
        progressBtn.dataset.taskStatus   = status;
        progressBtn.dataset.progressNotes = progressNotes;
        progressBtn.dataset.updatedAt    = updatedAt;
        progressBtn.style.display = '';
    }

    new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
}

// Called from "Update Progress" button inside task detail modal
function openProgressFromModal() {
    var btn        = document.getElementById('modalProgressBtn');
    var taskId     = btn.dataset.taskId;
    var taskTitle  = btn.dataset.taskTitle;
    var taskStatus = btn.dataset.taskStatus;
    var notes      = btn.dataset.progressNotes;
    var updatedAt  = btn.dataset.updatedAt || '';
    var detailEl   = document.getElementById('taskDetailModal');
    detailEl.addEventListener('hidden.bs.modal', function handler() {
        detailEl.removeEventListener('hidden.bs.modal', handler);
        openProgressModal(taskId, taskTitle, taskStatus, notes, updatedAt);
    });
    bootstrap.Modal.getInstance(detailEl).hide();
}

// Open the Update Progress modal
function openProgressModal(taskId, taskTitle, currentStatus, currentNotes, currentUpdatedAt) {
    document.getElementById('progressModalTaskId').value    = taskId;
    document.getElementById('progressModalTitle').textContent = taskTitle;

    var sel = document.getElementById('progressModalStatus');
    for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (sel.options[i].value === currentStatus);
    }

    var ta = document.getElementById('progressModalNotes');
    ta.value = currentNotes || '';

    var updatedAtInput = document.getElementById('progressModalUpdatedAt');
    if (updatedAtInput) {
        updatedAtInput.value = formatTaskTimestamp(currentUpdatedAt) || new Date().toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    updateProgressCounter();

    new bootstrap.Modal(document.getElementById('updateProgressModal')).show();
}

function formatTaskTimestamp(value) {
    if (!value) return '';
    var d = new Date(value.replace(' ', 'T'));
    if (isNaN(d)) return value;
    return d.toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'}) + ' ' +
        d.toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit', hour12:true});
}

function updateProgressCounter() {
    var ta  = document.getElementById('progressModalNotes');
    var cnt = document.getElementById('progressNotesCounter');
    if (cnt) cnt.textContent = (1000 - (ta.value.length > 1000 ? 1000 : ta.value.length)) + ' characters remaining';
}

// Stat card click-to-filter (same pattern as meetings page)
document.addEventListener('DOMContentLoaded', function () {
    var wrapper  = document.getElementById('tasksTableWrapper');
    var countEl  = wrapper ? wrapper.querySelector('.table-result-count') : null;
    var searchEl = wrapper ? wrapper.querySelector('[data-table-search]') : null;

    document.querySelectorAll('.stat-filter-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var filter = this.dataset.filter;

            setTimeout(function () {
                var allRows = wrapper._paginateAllRows || Array.from(wrapper.querySelectorAll('tbody tr'));
                var matched = (filter === 'all')
                    ? allRows
                    : allRows.filter(function (row) { return (row.dataset.rowStatus || '') === filter; });

                if (wrapper._paginateSetFiltered) {
                    wrapper._paginateSetFiltered(matched);
                } else {
                    allRows.forEach(function (r) { r.style.display = 'none'; });
                    matched.forEach(function (r) { r.style.display = ''; });
                }

                if (countEl) countEl.textContent = matched.length + ' record' + (matched.length !== 1 ? 's' : '');
                if (searchEl) searchEl.value = '';

                document.querySelectorAll('.stat-filter-card').forEach(function (c) { c.classList.remove('stat-active'); });
                card.classList.add('stat-active');
            }, 0);
        });
    });
});
</script>