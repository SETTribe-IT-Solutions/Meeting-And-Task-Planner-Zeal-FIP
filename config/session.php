<?php
// config/session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAccess($allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: /index.php?error=unauthorized");
        exit();
    }
}
?>