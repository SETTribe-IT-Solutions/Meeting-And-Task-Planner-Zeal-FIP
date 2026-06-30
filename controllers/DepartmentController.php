<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'Only organizers can manage departments.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/departments/index.php');
    exit();
}

// CSRF verification
$submitted_token = trim($_POST['csrf_token'] ?? '');
if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    header('Location: ../modules/departments/index.php');
    exit();
}

$action = $_POST['action'] ?? 'create';
$departmentId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = trim($_POST['status'] ?? 'Yes');
$errors = [];

if ($action === 'delete') {
    try {
        $conn = getDBConnection();

        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $departmentRow = $stmt->get_result()->fetch_assoc();

        if (!$departmentRow) {
            $_SESSION['department_errors'] = ['Department not found.'];
        } else {
            $departmentName = $departmentRow['name'];
            // Check for active employees
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE department = ? AND isDeleted = 'No'");
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $assignedCount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

            // Check for active meetings linked to this department
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM meetings WHERE department = ? AND status != 'Cancelled'");
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $activeMeetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

            // Check for active tasks linked via meetings in this department
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.department = ? AND t.status != 'Completed'");
            $stmt->bind_param("s", $departmentName);
            $stmt->execute();
            $activeTasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

            $warnings = [];
            if ($assignedCount > 0) {
                $warnings[] = "$assignedCount active employee(s) are assigned to this department.";
            }
            if ($activeMeetings > 0) {
                $warnings[] = "$activeMeetings active meeting(s) are linked to this department.";
            }
            if ($activeTasks > 0) {
                $warnings[] = "$activeTasks pending/in-progress task(s) are linked to this department.";
            }

            if (!empty($warnings)) {
                $_SESSION['department_errors'] = array_merge(
                    ['Department cannot be deleted:'],
                    $warnings
                );
            } else {
                $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
                $stmt->bind_param("i", $departmentId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Department deleted successfully.';
                } else {
                    $_SESSION['department_errors'] = ['Unable to delete department right now.'];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Department deletion failed: ' . $e->getMessage());
        $_SESSION['department_errors'] = ['Unable to delete department right now.'];
    }

    header('Location: ../modules/departments/index.php');
    exit();
}

if ($action === 'toggle') {
    try {
        $conn = getDBConnection();
        $newStatus = (isset($_POST['is_active']) && $_POST['is_active'] === 'No') ? 'No' : 'Yes';
        $stmt = $conn->prepare("UPDATE departments SET is_active = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $departmentId);
        $stmt->execute();
        $_SESSION['success'] = 'Department status updated.';
    } catch (Exception $e) {
        error_log('Department toggle failed: ' . $e->getMessage());
        $_SESSION['department_errors'] = ['Unable to update department status.'];
    }

    header('Location: ../modules/departments/index.php');
    exit();
}

$_SESSION['department_old'] = [
    'name' => $name,
    'description' => $description,
    'status' => $status
];

if ($action === 'update') {
    $_SESSION['department_old']['department_id'] = $departmentId;
}

if ($name === '') {
    $errors[] = 'Department name is required.';
} elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = 'Department name must be between 2 and 100 characters.';
} elseif (!preg_match("/^[A-Za-z0-9\s&.'\-]+$/", $name)) {
    $errors[] = 'Department name contains invalid characters.';
}

if (mb_strlen($description) > 1000) {
    $errors[] = 'Department description must not exceed 1000 characters.';
}

if (!in_array($status, ['Yes', 'No'], true)) {
    $errors[] = 'Please select a valid department status.';
}

if ($action === 'update' && $departmentId <= 0) {
    $errors[] = 'Invalid department selected for update.';
}

if (!empty($errors)) {
    $_SESSION['department_errors'] = $errors;
    $redirect = $action === 'update' && $departmentId > 0
        ? '../modules/departments/index.php?edit=' . $departmentId
        : '../modules/departments/index.php';
    header('Location: ' . $redirect);
    exit();
}

try {
    $conn = getDBConnection();

    if ($action === 'update') {
        $stmt = $conn->prepare("SELECT id FROM departments WHERE name = ? AND id <> ? LIMIT 1");
        $stmt->bind_param("si", $name, $departmentId);
    } else {
        $stmt = $conn->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $name);
    }
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['department_errors'] = ['This department already exists.'];
        $redirect = $action === 'update' ? '../modules/departments/index.php?edit=' . $departmentId : '../modules/departments/index.php';
        header('Location: ' . $redirect);
        exit();
    }

    if ($action === 'update') {
        $stmt = $conn->prepare("UPDATE departments SET name = ?, description = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $description, $status, $departmentId);
        $stmt->execute();
        $_SESSION['success'] = 'Department updated successfully.';
    } else {
        $stmt = $conn->prepare("INSERT INTO departments (name, description, is_active) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $description, $status);
        $stmt->execute();
        $_SESSION['success'] = 'Department added successfully.';
    }

    unset($_SESSION['department_old']);
} catch (Exception $e) {
    error_log('Department save failed: ' . $e->getMessage());
    $_SESSION['department_errors'] = ['Unable to save department right now.'];
}

header('Location: ../modules/departments/index.php');
exit();
