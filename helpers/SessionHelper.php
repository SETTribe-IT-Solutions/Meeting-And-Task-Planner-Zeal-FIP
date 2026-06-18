<?php
// Session helper - check if user is logged in and redirect if not

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

function logout() {
    session_start();
    session_destroy();
    setcookie('username', '', time() - 3600, '/');
    header('Location: /Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/login.php');
    exit;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}
?>
