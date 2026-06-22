<?php
// config/db.php
// Database configuration file

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'meeting_planner');
}

// Calculate project base URL for routing
if (!defined('APP_URL')) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $currentDir = str_replace('\\', '/', dirname(__DIR__));
    define('APP_URL', rtrim(str_replace($docRoot, '', $currentDir), '/'));
}

// Helper function to execute SQL statements from a file
function _executeSqlFile($conn, $filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $sql = file_get_contents($filePath);
    
    // Remove single-line comments
    $sql = preg_replace('/^[ \t]*(?:--|#).*$/m', '', $sql);
    
    // Remove multi-line comments
    $sql = preg_replace('/ \/\*(.*?)\*\/ /s', '', $sql);
    
    // Split queries by semicolon
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '') {
            continue;
        }
        
        // Skip USE queries since we manage connection/db selection in PHP
        if (stripos($query, 'USE ') === 0) {
            continue;
        }
        
        // If it's an INSERT query, check if the table has existing records first to prevent duplicates
        if (preg_match('/^\s*INSERT\s+INTO\s+([a-zA-Z0-9_`]+)/i', $query, $matches)) {
            $tableName = trim($matches[1], '`');
            
            // Query count of table
            $countCheck = $conn->query("SELECT COUNT(*) as cnt FROM `$tableName`");
            if ($countCheck) {
                $row = $countCheck->fetch_assoc();
                if ($row && $row['cnt'] > 0) {
                    // Table already has records, skip seed insert
                    continue;
                }
            }
        }
        
        if (!$conn->query($query)) {
            error_log("Database initialization query failed: " . $conn->error . " | Query: " . $query);
        }
    }
    return true;
}

// Create database connection and initialize database/tables if needed
function getDBConnection() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    try {
        // Connect to server first without selecting DB
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Create database if not exists
        $dbNameEscaped = "`" . str_replace("`", "``", DB_NAME) . "`";
        if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbNameEscaped")) {
            throw new Exception("Database creation failed: " . $conn->error);
        }
        
        // Select database
        if (!$conn->select_db(DB_NAME)) {
            throw new Exception("Database selection failed: " . $conn->error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        // Check if tables exist
        $requiredTables = ['departments', 'users', 'meetings', 'tasks', 'attendance', 'meeting_translations'];
        $tablesExist = true;
        
        $result = $conn->query("SHOW TABLES");
        $existingTables = [];
        if ($result) {
            while ($row = $result->fetch_row()) {
                $existingTables[] = strtolower($row[0]);
            }
        }
        
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                $tablesExist = false;
                break;
            }
        }
        
        if (!$tablesExist) {
            // Execute schema.sql to create tables and insert seed data
            $schemaPath = dirname(__DIR__) . '/database/schema.sql';
            _executeSqlFile($conn, $schemaPath);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

function getDefaultDepartments() {
    return [
        'Administration',
        'Revenue',
        'HR',
        'Public Works',
        'Health',
        'Education',
        'Agriculture',
        'Water Supply',
        'Social Welfare',
        'Finance',
        'Planning',
        'IT & Technology',
        'Law & Order',
        'Transport',
        'Rural Development',
        'Women & Child Development',
        'Disaster Management',
        'Election',
        'Food & Civil Supplies',
        'Urban Development'
    ];
}

function getDepartments() {
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT name FROM departments WHERE is_active = 'Yes' ORDER BY name ASC");
        if ($result && $result->num_rows > 0) {
            return array_column($result->fetch_all(MYSQLI_ASSOC), 'name');
        }
    } catch (Throwable $e) {
        error_log('Department lookup failed: ' . $e->getMessage());
    }

    return getDefaultDepartments();
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
