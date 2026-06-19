<?php
// controllers/LogoutController.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Wipe out session variables completely
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Send back to the user login interface view
header("Location: ../modules/users/login.php");
exit();
?>