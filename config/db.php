<?php
// config/db.php
// Database configuration file

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'meeting_planner');

// Calculate project base URL for routing
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$currentDir = str_replace('\\', '/', dirname(__DIR__));
define('APP_URL', rtrim(str_replace($docRoot, '', $currentDir), '/'));

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language detection and persistence
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] === 'mr') ? 'mr' : 'en';
}

/**
 * Translation helper function
 * @param string $key The translation key
 * @return string The translated text or the key if not found
 */
function __($key) {
    static $translations = null;
    if ($translations === null) {
        $lang = $_SESSION['lang'] ?? 'en';
        $path = __DIR__ . "/../includes/{$lang}.php";
        $translations = file_exists($path) ? require $path : [];
    }

    $segments = explode('.', $key);
    $result = $translations;

    foreach ($segments as $segment) {
        if (!isset($result[$segment])) {
            return $key;
        }
        $result = $result[$segment];
    }

    return is_string($result) ? $result : $key;
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Helper function for debugging
function debug($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

// Helper function for sanitizing input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function for redirects
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is organizer
function isOrganizer() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'Organizer' || $_SESSION['role'] === 'Collector');
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Collector';
}
?>