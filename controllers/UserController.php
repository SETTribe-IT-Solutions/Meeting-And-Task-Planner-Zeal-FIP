<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isOrganizer()) {
    $_SESSION['error'] = 'Only organizers and collectors can manage users.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/users/index.php');
    exit();
}

$action = $_POST['action'] ?? 'create';

// Handle Delete
if ($action === 'delete') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE users SET isDeleted = 'Yes' WHERE id = ? AND id != ?");
            $stmt->bind_param("ii", $userId, $_SESSION['user_id']);
            $stmt->execute();
            $_SESSION['success'] = 'User deleted successfully.';
        } catch (Exception $e) {
            $_SESSION['user_form_errors'] = ['Failed to delete user.'];
        }
    }
    header('Location: ../modules/users/index.php');
    exit();
}

$name = trim($_POST['name'] ?? '');
$department = trim($_POST['department'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
$errors = [];

$_SESSION['user_form_old'] = [
    'name' => $name,
    'department' => $department,
    'email' => $email
];

if ($name === '') {
    $errors[] = 'User name is required.';
} elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
    $errors[] = 'User name must be between 3 and 100 characters.';
} elseif (!preg_match("/^[A-Za-z\s.'\-]+$/", $name)) {
    $errors[] = 'User name can contain only letters, spaces, dots, apostrophes, and hyphens.';
}

if ($department === '') {
    $errors[] = 'Department is required.';
} elseif (!in_array($department, getDepartments(), true)) {
    $errors[] = 'Please select a valid department.';
}

if ($email === '') {
    $errors[] = 'Email ID is required.';
} elseif (mb_strlen($email) > 150) {
    $errors[] = 'Email ID must not exceed 150 characters.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid Email ID.';
}

if ($action === 'create' || !empty($password)) {
    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8 || strlen($password) > 64) {
        $errors[] = 'Password must be between 8 and 64 characters.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include uppercase, lowercase, and number characters.';
    }
}

if (!empty($errors)) {
    $_SESSION['user_form_errors'] = $errors;
    header('Location: ../modules/users/index.php' . ($action === 'update' ? '?edit='.$userId : ''));
    exit();
}

try {
    $conn = getDBConnection();

    if ($action === 'update' && $userId > 0) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND isDeleted = 'No' AND id != ? LIMIT 1");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['user_form_errors'] = ['This Email ID is already in use by another account.'];
            header('Location: ../modules/users/index.php?edit='.$userId);
            exit();
        }

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, department = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $department, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $email, $department, $userId);
        }
        $stmt->execute();
        $_SESSION['success'] = 'User updated successfully.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND isDeleted = 'No' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['user_form_errors'] = ['This Email ID is already registered.'];
            header('Location: ../modules/users/index.php');
            exit();
        }

        $role = 'Employee';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department, isDeleted) VALUES (?, ?, ?, ?, ?, 'No')");
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $department);
        $stmt->execute();

        $_SESSION['success'] = 'User created successfully.';
    }
    unset($_SESSION['user_form_old']);
} catch (Exception $e) {
    error_log('User action failed: ' . $e->getMessage());
    $_SESSION['user_form_errors'] = ['Unable to process request right now.'];
}

header('Location: ../modules/users/index.php');
exit();
