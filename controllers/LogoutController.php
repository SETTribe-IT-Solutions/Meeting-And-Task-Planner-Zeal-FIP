<?php
// controllers/LogoutController.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/AuthController.php';

// Initialize auth controller
$auth = new AuthController();

// Process logout
$result = $auth->logout();

// Redirect to login page
header('Location: ../modules/users/login.php');
exit();
?>