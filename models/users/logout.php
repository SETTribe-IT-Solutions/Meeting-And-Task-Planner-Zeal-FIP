<?php
session_start();

// Destroy session and clear cookies
session_destroy();
setcookie('username', '', time() - 3600, '/');

// Redirect to login
header('Location: ../../login.php');
exit;
?>
