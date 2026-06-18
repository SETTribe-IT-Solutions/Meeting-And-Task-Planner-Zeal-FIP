<?php
// Session authentication check
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['name'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>
