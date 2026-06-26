<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isOrganizer()) {
    $_SESSION['error'] = 'Only organizers and collectors can manage departments.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->bind_param("i", $departmentId);
        $stmt->execute();
        $_SESSION['success'] = 'Department deleted successfully.';
    } catch (Exception $e) {
        error_log('Department deletion failed: ' . $e->getMessage());
        $_SESSION['department_errors'] = ['Unable to delete department right now.'];
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
