<?php
// middleware/auth.php
function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: /index.php?error=unauthorized");
        exit();
    }
}