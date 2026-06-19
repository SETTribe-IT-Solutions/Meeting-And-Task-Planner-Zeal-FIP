
error_log("Attempting login for: " . $_POST['email']);
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST['email']]);
$user = $stmt->fetch();
if (!$user) {
    die("Debug: User not found in database.");
}
<?php
// controllers/AuthController.php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $password == $user['password']) {
        // Regenerate ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; // Collector, Organizer, or Employee 
        $_SESSION['name'] = $user['name'];

        // Role-based routing 
        switch ($user['role']) {
            case 'Collector':
                header("Location: ../dashboards/collector.php");
                break;
            case 'Organizer':
                header("Location: ../dashboards/organizer.php");
                break;
            case 'Employee':
                header("Location: ../dashboards/employee.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    } else {
        // Clear and redirect on failure
        header("Location: ../login.php?error=invalid");
        exit();
    }
}
?>