<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';

function failTaskCreation(string $message, bool $isAjax): void
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        $_SESSION['error'] = $message;
        header('Location: ../modules/tasks/create.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/tasks/create.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in first.';
    header('Location: ../modules/users/login.php');
    exit();
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');

$requiredFields = ['meeting_id', 'title', 'assigned_to', 'due_date', 'priority'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        failTaskCreation('Please fill in all task details.', $isAjax);
    }
}

$meetingId = (int) $_POST['meeting_id'];
$title = trim($_POST['title']);
$dueDate = trim($_POST['due_date']);
$priority = trim($_POST['priority']);
$notes = trim($_POST['notes'] ?? '');

$dueDateObj = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
$dateErrors = DateTimeImmutable::getLastErrors();
$hasDateErrors = $dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);
if (!$dueDateObj || $hasDateErrors) {
    failTaskCreation('Please select a valid due date.', $isAjax);
}

if ($dueDateObj < new DateTimeImmutable('today')) {
    failTaskCreation('Past dates are not allowed. Please select today or a future date.', $isAjax);
}

// assigned_to can be array (multiple) or single
$assignedToInput = $_POST['assigned_to'] ?? [];
if (is_array($assignedToInput)) {
    $assignedIds = array_map('intval', $assignedToInput);
} else {
    $assignedIds = [(int)$assignedToInput];
}
// Primary assigned_to stored on tasks table (first selected)
$assignedTo = isset($assignedIds[0]) ? (int)$assignedIds[0] : 0;

try {
    $conn = getDBConnection();

    // Guard: tasks may only be created for Completed meetings
    $mtgChk = $conn->prepare("SELECT status FROM meetings WHERE id = ? LIMIT 1");
    $mtgChk->bind_param('i', $meetingId);
    $mtgChk->execute();
    $mtgRow = $mtgChk->get_result()->fetch_assoc();
    if (!$mtgRow || strtolower($mtgRow['status']) !== 'completed') {
        failTaskCreation('Tasks can only be created for Completed meetings.', $isAjax);
    }

    $stmt = $conn->prepare(
        "INSERT INTO tasks (meeting_id, title, assigned_to, due_date, priority, notes) VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("isisss", $meetingId, $title, $assignedTo, $dueDate, $priority, $notes);
    $stmt->execute();

    $insertId = $stmt->insert_id;

    // Ensure task_assignments table exists
    $conn->query("CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert assignments
    if (!empty($assignedIds)) {
        $insStmt = $conn->prepare("INSERT INTO task_assignments (task_id, user_id) VALUES (?, ?)");
        foreach ($assignedIds as $uid) {
            $uid = (int)$uid;
            $insStmt->bind_param('ii', $insertId, $uid);
            $insStmt->execute();
        }
    }

    // Handle optional task attachment
    $uploadDir    = dirname(__DIR__) . '/uploads/tasks';
    $uploadResult = validateAndStoreUpload('task_attachment', $uploadDir);
    if ($uploadResult['success']) {
        $upStmt = $conn->prepare(
            "INSERT INTO task_attachments (task_id, uploaded_by, original_name, stored_name, file_size, mime_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $uploaderId = (int)$_SESSION['user_id'];
        $upStmt->bind_param("iissss",
            $insertId, $uploaderId,
            $uploadResult['original_name'], $uploadResult['stored_name'],
            $uploadResult['file_size'], $uploadResult['mime_type']
        );
        $upStmt->execute();
    }

    // If request is AJAX, return JSON
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Task created successfully.', 'task_id' => $insertId]);
        exit();
    }

    $_SESSION['success'] = 'Task created successfully.';
    header('Location: ../index.php?status=success');
    exit();
} catch (Exception $e) {
    error_log('Task creation failed: ' . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unable to create task right now.'] );
        exit();
    }

    $_SESSION['error'] = 'Unable to create task right now.';
    header('Location: ../modules/tasks/create.php');
    exit();
}
