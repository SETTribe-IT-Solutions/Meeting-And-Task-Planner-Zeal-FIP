<?php
include("../includes/auth_check.php");
include("../config/db.php");

function redirect_with_status($type, $code, $meeting_id = 0) {
    $query = $type . '=' . urlencode($code);
    if ($meeting_id > 0) {
        $query .= '&meeting_id=' . intval($meeting_id);
    }

    header('Location: register_employee.php?' . $query);
    exit();
}

function users_table_has_column($conn, $column_name) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $column_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $column_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return $column_count > 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('error', 'invalid_request');
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$meeting_id = isset($_POST['meeting_id']) ? intval($_POST['meeting_id']) : 0;

if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
    redirect_with_status('error', 'missing_data', $meeting_id);
}

if (strlen($name) < 2 || strlen($name) > 100) {
    redirect_with_status('error', 'invalid_name', $meeting_id);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    redirect_with_status('error', 'invalid_email', $meeting_id);
}

if (strlen($department) > 100) {
    redirect_with_status('error', 'invalid_department', $meeting_id);
}

if (strlen($password) < 6) {
    redirect_with_status('error', 'invalid_password', $meeting_id);
}

if ($password !== $confirm_password) {
    redirect_with_status('error', 'password_mismatch', $meeting_id);
}

if (!isset($conn) || !$conn) {
    redirect_with_status('error', 'save_failed', $meeting_id);
}

$email_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
if (!$email_stmt) {
    redirect_with_status('error', 'save_failed', $meeting_id);
}

mysqli_stmt_bind_param($email_stmt, "s", $email);
mysqli_stmt_execute($email_stmt);
mysqli_stmt_store_result($email_stmt);

if (mysqli_stmt_num_rows($email_stmt) > 0) {
    mysqli_stmt_close($email_stmt);
    redirect_with_status('error', 'duplicate_email', $meeting_id);
}
mysqli_stmt_close($email_stmt);

if ($meeting_id > 0) {
    $meeting_stmt = mysqli_prepare($conn, "SELECT id FROM meetings WHERE id = ? LIMIT 1");
    if (!$meeting_stmt) {
        redirect_with_status('error', 'save_failed', $meeting_id);
    }

    mysqli_stmt_bind_param($meeting_stmt, "i", $meeting_id);
    mysqli_stmt_execute($meeting_stmt);
    mysqli_stmt_store_result($meeting_stmt);

    if (mysqli_stmt_num_rows($meeting_stmt) === 0) {
        mysqli_stmt_close($meeting_stmt);
        redirect_with_status('error', 'invalid_meeting');
    }
    mysqli_stmt_close($meeting_stmt);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'employee';
$has_department_column = users_table_has_column($conn, 'department');

mysqli_begin_transaction($conn);

if ($has_department_column) {
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
} else {
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
}

if (!$insert_stmt) {
    mysqli_rollback($conn);
    redirect_with_status('error', 'save_failed', $meeting_id);
}

if ($has_department_column) {
    mysqli_stmt_bind_param($insert_stmt, "sssss", $name, $email, $password_hash, $role, $department);
} else {
    mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $password_hash, $role);
}

if (!mysqli_stmt_execute($insert_stmt)) {
    mysqli_stmt_close($insert_stmt);
    mysqli_rollback($conn);
    redirect_with_status('error', 'save_failed', $meeting_id);
}

$employee_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($meeting_id > 0) {
    $attendee_stmt = mysqli_prepare($conn, "INSERT INTO meeting_attendees (meeting_id, user_id) VALUES (?, ?)");
    if (!$attendee_stmt) {
        mysqli_rollback($conn);
        redirect_with_status('error', 'save_failed', $meeting_id);
    }

    mysqli_stmt_bind_param($attendee_stmt, "ii", $meeting_id, $employee_id);

    if (!mysqli_stmt_execute($attendee_stmt)) {
        mysqli_stmt_close($attendee_stmt);
        mysqli_rollback($conn);
        redirect_with_status('error', 'save_failed', $meeting_id);
    }

    mysqli_stmt_close($attendee_stmt);
}

mysqli_commit($conn);

$success_code = $meeting_id > 0 ? 'employee_added_to_meeting' : 'employee_added';
redirect_with_status('msg', $success_code, $meeting_id);
?>
