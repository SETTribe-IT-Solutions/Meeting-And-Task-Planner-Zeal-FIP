<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'Only Organizers can manage users.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/users/index.php');
    exit();
}

// CSRF check (all actions)
$submitted_token = trim($_POST['csrf_token'] ?? '');
if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ../modules/users/index.php');
    exit();
}

$action = $_POST['action'] ?? 'create';
$conn   = getDBConnection();

// ── helpers ──────────────────────────────────────────────────────────────────

function validatePassword(string $password): ?string {
    if (strlen($password) < 8 || strlen($password) > 64) {
        return 'Password must be between 8 and 64 characters.';
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must include uppercase, lowercase, and a number.';
    }
    return null;
}

// Fetch target user and enforce safety rules
function getTargetUser(mysqli $conn, int $target_id): ?array {
    $stmt = $conn->prepare("SELECT id, name, role, isDeleted FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $target_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function guardTarget(array $target): ?string {
    if ((int)$target['id'] === (int)($_SESSION['user_id'] ?? 0)) {
        return 'You cannot perform this action on your own account.';
    }
    if ($target['role'] === 'Collector') {
        return 'The Collector account cannot be modified here.';
    }
    return null;
}

// ── CREATE ────────────────────────────────────────────────────────────────────
if ($action === 'create') {
    $name       = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = trim($_POST['role'] ?? 'Employee');
    $errors     = [];

    $_SESSION['user_form_old'] = compact('name', 'department', 'email', 'role');

    if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'User name must be 3–100 characters.';
    } elseif (!preg_match("/^[A-Za-z\s.'\-]+$/", $name)) {
        $errors[] = 'User name can only contain letters, spaces, dots, apostrophes, and hyphens.';
    }

    if ($department === '' || !in_array($department, getDepartments(), true)) {
        $errors[] = 'Please select a valid department.';
    }

    if (!in_array($role, ['Employee', 'Organizer'], true)) {
        $errors[] = 'Please select a valid role.';
    }

    if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid Email ID (max 150 chars).';
    }

    $pwErr = validatePassword($password);
    if ($pwErr) $errors[] = $pwErr;

    if (!empty($errors)) {
        $_SESSION['user_form_errors'] = $errors;
        header('Location: ../modules/users/index.php');
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND isDeleted = 'No' LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['user_form_errors'] = ['This Email ID is already registered.'];
        header('Location: ../modules/users/index.php');
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role, department, isDeleted) VALUES (?, ?, ?, ?, ?, 'No')"
    );
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $department);
    $stmt->execute();

    unset($_SESSION['user_form_old']);
    $_SESSION['success'] = 'User created successfully.';
    header('Location: ../modules/users/index.php');
    exit();
}

// ── EDIT ──────────────────────────────────────────────────────────────────────
if ($action === 'edit') {
    $target_id  = (int)($_POST['user_id'] ?? 0);
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role       = trim($_POST['role'] ?? '');
    $errors     = [];

    $target = getTargetUser($conn, $target_id);
    if (!$target) { $_SESSION['error'] = 'User not found.'; header('Location: ../modules/users/index.php'); exit(); }
    $guard = guardTarget($target);
    if ($guard) { $_SESSION['error'] = $guard; header('Location: ../modules/users/index.php'); exit(); }

    if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors[] = 'Name must be 3–100 characters.';
    } elseif (!preg_match("/^[A-Za-z\s.'\-]+$/", $name)) {
        $errors[] = 'Name can only contain letters, spaces, dots, apostrophes, and hyphens.';
    }

    if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid Email ID.';
    }

    if ($department === '' || !in_array($department, getDepartments(), true)) {
        $errors[] = 'Please select a valid department.';
    }

    if (!in_array($role, ['Employee', 'Organizer'], true)) {
        $errors[] = 'Please select a valid role.';
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        header('Location: ../modules/users/index.php');
        exit();
    }

    // Check email uniqueness (exclude current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt->bind_param('si', $email, $target_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'This Email ID is already used by another account.';
        header('Location: ../modules/users/index.php');
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department = ?, role = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $name, $email, $department, $role, $target_id);
    $stmt->execute();

    $_SESSION['success'] = htmlspecialchars($name) . '\'s profile updated successfully.';
    header('Location: ../modules/users/index.php');
    exit();
}

// ── RESET PASSWORD ────────────────────────────────────────────────────────────
if ($action === 'reset_password') {
    $target_id   = (int)($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    $target = getTargetUser($conn, $target_id);
    if (!$target) { $_SESSION['error'] = 'User not found.'; header('Location: ../modules/users/index.php'); exit(); }
    $guard = guardTarget($target);
    if ($guard) { $_SESSION['error'] = $guard; header('Location: ../modules/users/index.php'); exit(); }

    $pwErr = validatePassword($new_password);
    if ($pwErr) { $_SESSION['error'] = $pwErr; header('Location: ../modules/users/index.php'); exit(); }

    if ($new_password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: ../modules/users/index.php');
        exit();
    }

    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $target_id);
    $stmt->execute();

    $_SESSION['success'] = 'Password reset successfully for ' . htmlspecialchars($target['name']) . '.';
    header('Location: ../modules/users/index.php');
    exit();
}

// ── TOGGLE STATUS (Enable / Disable) ─────────────────────────────────────────
if ($action === 'toggle_status') {
    $target_id = (int)($_POST['user_id'] ?? 0);

    $target = getTargetUser($conn, $target_id);
    if (!$target) { $_SESSION['error'] = 'User not found.'; header('Location: ../modules/users/index.php'); exit(); }
    $guard = guardTarget($target);
    if ($guard) { $_SESSION['error'] = $guard; header('Location: ../modules/users/index.php'); exit(); }

    $new_status = ($target['isDeleted'] === 'Yes') ? 'No' : 'Yes';
    $stmt = $conn->prepare("UPDATE users SET isDeleted = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $target_id);
    $stmt->execute();

    $label = $new_status === 'Yes' ? 'disabled' : 're-enabled';
    $_SESSION['success'] = htmlspecialchars($target['name']) . ' has been ' . $label . '.';
    header('Location: ../modules/users/index.php');
    exit();
}

// fallback
header('Location: ../modules/users/index.php');
exit();
