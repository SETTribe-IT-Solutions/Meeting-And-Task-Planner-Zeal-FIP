<?php
// config/db.php
// Database configuration file

if (!defined('DB_HOST')) {
    define('DB_HOST', '82.25.121.144');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'u196817721_MTP_DB_U');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'MeetingAndTaskP@2026');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'u196817721_MTP_DB');
}

// Calculate project base URL for routing
if (!defined('APP_URL')) {
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $currentDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $computed   = rtrim(str_replace($docRoot, '', $currentDir), '/');

    // Fallback for Windows junctions/symlinks where PHP resolves __DIR__ to the
    // real path (different drive) while DOCUMENT_ROOT stays on the virtual drive.
    if ($docRoot === '' || preg_match('/^[A-Za-z]:/', ltrim($computed, '/'))) {
        $scriptVirtual = rtrim(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/');
        $scriptReal    = rtrim(str_replace('\\', '/', realpath($scriptVirtual) ?: $scriptVirtual), '/');
        $scriptRealDir = dirname($scriptReal);

        // How many directory levels deep is the script within the app root?
        $relPath = '';
        if ($currentDir !== '' && stripos($scriptRealDir, $currentDir) === 0) {
            $relPath = ltrim(substr($scriptRealDir, strlen($currentDir)), '/');
        }
        $depth = ($relPath !== '') ? count(explode('/', $relPath)) : 0;

        // Walk up `depth` levels from the virtual script directory
        $parts    = explode('/', rtrim(dirname($scriptVirtual), '/'));
        $appParts = array_slice($parts, 0, count($parts) - $depth);
        $computed = rtrim(str_replace($docRoot, '', implode('/', $appParts)), '/');
    }

    define('APP_URL', $computed);
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
        $requiredTables = ['departments', 'users', 'meetings', 'tasks', 'attendance', 'meeting_translations', 'task_assignments'];
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

        ensureDepartmentStructure($conn);
        ensureAttendanceStructure($conn);
        ensureTaskAssignmentsTable($conn);
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

function getDefaultDepartmentRecords() {
    return [
        ['Administration', 'General administration and coordination.'],
        ['Law & Order', 'Public safety, legal coordination, and order management.'],
        ['Revenue', 'Revenue administration and land records.'],
        ['Health', 'Public health services and medical coordination.'],
        ['Education', 'Education planning and institutional coordination.'],
        ['Agriculture', 'Agriculture services and farmer support.'],
        ['Finance', 'Financial planning, budget, and accounts.'],
        ['IT Department', 'Information technology services and digital systems.'],
        ['Rural Development', 'Rural development projects and schemes.'],
        ['Public Works Department', 'Public infrastructure and works management.']
    ];
}

function getDefaultDepartments() {
    return array_column(getDefaultDepartmentRecords(), 0);
}

function ensureDepartmentStructure($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM departments LIKE 'description'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE departments ADD COLUMN description TEXT NULL AFTER name");
    }

    $stmt = $conn->prepare(
        "INSERT INTO departments (name, description, is_active) VALUES (?, ?, 'Yes')
         ON DUPLICATE KEY UPDATE description = COALESCE(description, VALUES(description))"
    );

    if ($stmt) {
        foreach (getDefaultDepartmentRecords() as $department) {
            $stmt->bind_param("ss", $department[0], $department[1]);
            $stmt->execute();
        }
    }
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

// Ensure a CSRF token is always available for authenticated sessions.
// This covers sessions that existed before CSRF was introduced.
if (!empty($_SESSION['user_id']) && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

// APP_DEBUG flag — set to true only in local development, never on production.
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

// Helper function for debugging (output suppressed unless APP_DEBUG is true)
function debug($data) {
    if (!APP_DEBUG) {
        return;
    }
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

// Helper function for sanitizing input.
// NOTE: htmlspecialchars() is intentionally NOT applied here.
// Escaping must happen at the output/render layer (use htmlspecialchars() in views).
// Applying it here causes double-encoding when the value is echoed through a view.
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
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

// Helper function to format time in 12-hour format with AM/PM
function formatTime12Hour($time) {
    if (empty($time)) {
        return '';
    }
    // If it's a string time (HH:MM:SS format)
    $timeArray = explode(':', $time);
    if (count($timeArray) >= 2) {
        $hour = (int)$timeArray[0];
        $minute = $timeArray[1];
        $ampm = ($hour >= 12) ? 'PM' : 'AM';
        if ($hour > 12) {
            $hour = $hour - 12;
        } elseif ($hour === 0) {
            $hour = 12;
        }
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . $minute . ' ' . $ampm;
    }
    return $time;
}
/**
 * Ensure attendance table has 'Late' status and check_in_time column.
 * Called during DB initialization to auto-migrate schema.
 */
function ensureAttendanceStructure($conn) {
    // Add 'Late' to status ENUM if not already present
    $colCheck = $conn->query("SHOW COLUMNS FROM attendance LIKE 'status'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $row = $colCheck->fetch_assoc();
        if (strpos($row['Type'], 'Late') === false) {
            $conn->query("ALTER TABLE attendance MODIFY COLUMN status ENUM('Present','Absent','Pending','Late') DEFAULT 'Pending'");
        }
    }

    // Add check_in_time column if not exists
    $timeCheck = $conn->query("SHOW COLUMNS FROM attendance LIKE 'check_in_time'");
    if ($timeCheck && $timeCheck->num_rows === 0) {
        $conn->query("ALTER TABLE attendance ADD COLUMN check_in_time TIME NULL AFTER status");
    }
}

/**
 * Ensure task_assignments table exists for multi-assignee support.
 * Called during DB initialization to auto-migrate schema.
 */
function ensureTaskAssignmentsTable($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
?>
