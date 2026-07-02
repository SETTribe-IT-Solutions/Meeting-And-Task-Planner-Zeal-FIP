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

        ensureDepartmentStructure($conn);
        ensurePhase2ASchema($conn);
        ensurePortalPagesSchema($conn);
        
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

function ensurePhase2ASchema($conn) {
    // attendance.arrival_time (Phase 2A Feature 3)
    $r = $conn->query("SHOW COLUMNS FROM attendance LIKE 'arrival_time'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE attendance ADD COLUMN arrival_time TIME NULL AFTER status");
    }

    // tasks.progress_notes (Phase 2A Feature 4)
    $r = $conn->query("SHOW COLUMNS FROM tasks LIKE 'progress_notes'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE tasks ADD COLUMN progress_notes TEXT NULL AFTER notes");
    }

    // tasks.updated_at (Phase 2A Feature 5)
    $r = $conn->query("SHOW COLUMNS FROM tasks LIKE 'updated_at'");
    if ($r && $r->num_rows === 0) {
        $conn->query("ALTER TABLE tasks ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    }

    // meeting_attachments table (Phase 2A Feature 1)
    $conn->query("CREATE TABLE IF NOT EXISTS meeting_attachments (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        meeting_id    INT          NOT NULL,
        uploaded_by   INT          NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name   VARCHAR(255) NOT NULL,
        file_size     INT          NOT NULL,
        mime_type     VARCHAR(100) NOT NULL,
        uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (meeting_id)  REFERENCES meetings(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id),
        INDEX idx_ma_meeting_id (meeting_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // task_attachments table (Phase 2A Feature 2)
    $conn->query("CREATE TABLE IF NOT EXISTS task_attachments (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        task_id       INT          NOT NULL,
        uploaded_by   INT          NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name   VARCHAR(255) NOT NULL,
        file_size     INT          NOT NULL,
        mime_type     VARCHAR(100) NOT NULL,
        uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id)     REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id),
        INDEX idx_tka_task_id (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensurePortalPagesSchema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS portal_pages (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        slug       VARCHAR(50) UNIQUE NOT NULL,
        title      VARCHAR(200) NOT NULL,
        icon       VARCHAR(60)  NOT NULL DEFAULT 'bi-file-earmark-text',
        content    MEDIUMTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $seed = [
        ['about-district', 'About District', 'bi-geo-alt-fill',
            '<p>Latur is a district in the Marathwada region of Maharashtra, India. Located in the south-east of Maharashtra, it borders the state of Karnataka. The district is known for its rich cultural heritage and the resilience of its people.</p>' .
            '<h4>Key Facts</h4><ul><li><strong>Headquarters:</strong> Latur City</li><li><strong>Area:</strong> 7,157 sq km</li><li><strong>Population:</strong> ~24.5 lakh (2011 Census)</li><li><strong>Talukas:</strong> 10 — Latur, Udgir, Nilanga, Ausa, Ahmadpur, Chakur, Deoni, Shirur Anantpal, Renapur, Jalkot</li><li><strong>Languages:</strong> Marathi, Urdu, Kannada</li></ul>' .
            '<h4>Major Landmarks</h4><ul><li>Udgir Fort — historical Bahamani-era fortification</li><li>Kharosa Caves — ancient rock-cut caves near Udgir</li><li>Ausa Fort — medieval fort with scenic views</li><li>Shiv Yogi Siddheshwar Temple — revered pilgrimage site</li></ul>' .
            '<h4>Agriculture</h4><p>Latur is a major producer of Tur Dal (pigeon pea) and soybean in Maharashtra. The fertile Deccan black soil supports diverse and productive agriculture across the district.</p>' .
            '<h4>District Administration</h4><p>The district is administered by the District Collector, assisted by Deputy Collectors and officials across 10 talukas. The administration is committed to transparent governance, timely service delivery, and digital inclusion for all citizens.</p>'],

        ['notices', 'Notices & Circulars', 'bi-megaphone-fill',
            '<p>Official notices and circulars issued by Latur District Administration. All government offices and officials are required to comply with the directives listed below.</p>' .
            '<table><thead><tr><th>Date</th><th>Notice</th><th>Department</th></tr></thead><tbody>' .
            '<tr><td>28 Jun 2026</td><td><strong>Weekly Administrative Review Meeting</strong><br>All department heads to attend every Friday at 11:00 AM at Collector Office, Latur.</td><td>All Departments</td></tr>' .
            '<tr><td>25 Jun 2026</td><td><strong>Digital Attendance Now Mandatory</strong><br>Digital attendance via the Meeting &amp; Task Planner portal is mandatory for all government meetings effective 01 July 2026.</td><td>IT Department</td></tr>' .
            '<tr><td>20 Jun 2026</td><td><strong>District Planning Meeting — June 2026</strong><br>All department heads must confirm attendance for the District Planning Meeting on 05 July 2026 at 10:00 AM.</td><td>Administration</td></tr>' .
            '<tr><td>15 Jun 2026</td><td><strong>Updated HR Policy Guidelines</strong><br>New HR policy circular issued. All officers to review and acknowledge receipt before 30 June 2026.</td><td>Administration</td></tr>' .
            '<tr><td>10 Jun 2026</td><td><strong>Monsoon Preparedness Review</strong><br>Special review on monsoon preparedness scheduled for 02 July 2026 at Collector Office.</td><td>Revenue</td></tr>' .
            '</tbody></table>'],

        ['reports', 'Public Reports', 'bi-file-earmark-bar-graph-fill',
            '<p>Official reports and documents published by Latur District Administration. For certified copies, contact the relevant department office directly.</p>' .
            '<h4>Annual Reports</h4><ul><li>District Annual Administrative Report 2025–26</li><li>District Annual Administrative Report 2024–25</li><li>Socio-Economic Review, Latur District 2025</li></ul>' .
            '<h4>Budget &amp; Finance</h4><ul><li>District Budget Summary 2026–27</li><li>Expenditure Report Q4 2025–26</li><li>State Finance Commission Implementation Report</li></ul>' .
            '<h4>Scheme Reports</h4><ul><li>PM Awas Yojana Progress Report 2025–26</li><li>MGNREGS Implementation Report 2025–26</li><li>Jal Jeevan Mission — Latur District Progress</li><li>Swachh Bharat Mission Status Report 2025</li></ul>' .
            '<h4>Meeting Minutes</h4><ul><li>District Review Meeting Minutes — May 2026</li><li>District Review Meeting Minutes — April 2026</li><li>District Planning Committee Meeting — March 2026</li></ul>' .
            '<p><em>Document downloads are currently restricted to authorised officials. Please contact the Administration Department for public copies via RTI.</em></p>'],

        ['contact', 'Contact Us', 'bi-telephone-fill',
            '<h4>Collector Office, Latur</h4><p>Station Road, Near Civil Court,<br>Latur — 413 512,<br>Maharashtra, India</p>' .
            '<h4>Contact Details</h4><table><tr><td><strong>Collector Office</strong></td><td>+91-2382-252200</td></tr><tr><td><strong>General Enquiry</strong></td><td>+91-2382-252201</td></tr><tr><td><strong>Official Email</strong></td><td>collector.latur@maharashtra.gov.in</td></tr><tr><td><strong>Fax</strong></td><td>+91-2382-252203</td></tr><tr><td><strong>IT / Portal Support</strong></td><td>support.latur@nic.in | +91-2382-252210</td></tr></table>' .
            '<h4>Office Hours</h4><p>Monday to Friday: 10:00 AM – 6:00 PM<br>Saturday: 10:00 AM – 2:00 PM (public services only)<br>Sunday &amp; Gazetted Holidays: Closed</p>' .
            '<h4>Taluka Offices</h4><ul><li>Latur Taluka: +91-2382-252500</li><li>Udgir Taluka: +91-2384-222100</li><li>Nilanga Taluka: +91-2380-222200</li><li>Ausa Taluka: +91-2381-222100</li><li>Ahmadpur Taluka: +91-2383-222100</li></ul>'],

        ['administration', 'Administration', 'bi-building-fill',
            '<h4>District Administration Hierarchy</h4>' .
            '<p>Latur District Administration is headed by the District Collector, who is the principal representative of the State Government at the district level. Below is the official hierarchy and contact details of key officers.</p>' .
            '<table><thead><tr><th>Designation</th><th>Department</th><th>Official Contact</th><th>Office Location</th></tr></thead><tbody>' .
            '<tr><td><strong>District Collector &amp; District Magistrate</strong></td><td>General Administration</td><td>collector.latur@maharashtra.gov.in<br>+91-2382-252200</td><td>Collector Office, Station Road, Latur</td></tr>' .
            '<tr><td><strong>Additional Collector</strong></td><td>Revenue &amp; General</td><td>addlcollector.latur@maharashtra.gov.in<br>+91-2382-252202</td><td>Collector Office, Station Road, Latur</td></tr>' .
            '<tr><td><strong>Resident Deputy Collector</strong></td><td>Revenue Administration</td><td>rdc.latur@maharashtra.gov.in<br>+91-2382-252204</td><td>Collector Office, Station Road, Latur</td></tr>' .
            '<tr><td><strong>Superintendent of Police (SP)</strong></td><td>Police Department</td><td>sp.latur@mahapolice.gov.in<br>+91-2382-220100</td><td>SP Office, Civil Lines, Latur</td></tr>' .
            '<tr><td><strong>Deputy Superintendent of Police</strong></td><td>Police Department</td><td>dsp.latur@mahapolice.gov.in<br>+91-2382-220101</td><td>SP Office, Civil Lines, Latur</td></tr>' .
            '<tr><td><strong>Chief District Health Officer (CDHO)</strong></td><td>Health Department</td><td>cdho.latur@maharashtra.gov.in<br>+91-2382-253100</td><td>District Hospital Campus, Latur</td></tr>' .
            '<tr><td><strong>District Education Officer (DEO)</strong></td><td>Education</td><td>deo.latur@maharashtra.gov.in<br>+91-2382-253200</td><td>Shikshan Bhavan, Latur</td></tr>' .
            '<tr><td><strong>District Agriculture Officer</strong></td><td>Agriculture</td><td>dao.latur@maharashtra.gov.in<br>+91-2382-253300</td><td>Krishi Bhavan, Latur</td></tr>' .
            '<tr><td><strong>Executive Engineer, PWD</strong></td><td>Public Works</td><td>ee.pwd.latur@maharashtra.gov.in<br>+91-2382-253400</td><td>PWD Office, Latur</td></tr>' .
            '<tr><td><strong>District Finance Officer</strong></td><td>Finance</td><td>dfo.latur@maharashtra.gov.in<br>+91-2382-253500</td><td>Collector Office, Station Road, Latur</td></tr>' .
            '<tr><td><strong>District Information Technology Officer</strong></td><td>IT / NIC</td><td>support.latur@nic.in<br>+91-2382-252210</td><td>NIC Room, Collector Office, Latur</td></tr>' .
            '</tbody></table>' .
            '<h4>Official Communication</h4>' .
            '<p>All official correspondence must be addressed to the relevant officer through their designated email address above. For RTI applications, address to the <strong>Public Information Officer (PIO), Collector Office, Latur — 413 512</strong>.</p>' .
            '<ul><li><strong>General Correspondence:</strong> collector.latur@maharashtra.gov.in</li><li><strong>RTI Applications:</strong> Submit at Collector Office counter or via email to the PIO</li><li><strong>Grievances:</strong> Visit the District Grievance Cell, Ground Floor, Collector Office</li><li><strong>Emergency:</strong> Police Control Room — 100 | Medical Emergency — 108 | Fire — 101</li></ul>' .
            '<h4>Office Details</h4>' .
            '<p><strong>Collector Office, Latur</strong><br>Station Road, Near Civil Court, Latur — 413 512, Maharashtra</p>' .
            '<table><tr><td><strong>Office Hours</strong></td><td>Monday–Friday: 10:00 AM – 6:00 PM | Saturday: 10:00 AM – 2:00 PM</td></tr><tr><td><strong>Public Service Counter</strong></td><td>Monday–Saturday: 10:00 AM – 5:00 PM (excluding holidays)</td></tr><tr><td><strong>Grievance Cell</strong></td><td>Every Monday: 11:00 AM – 1:00 PM (walk-in, no appointment needed)</td></tr></table>'],

        ['help', 'Help & FAQs', 'bi-question-circle-fill',
            '<h4>Login &amp; Access</h4>' .
            '<p><strong>Q: How do I log in to the portal?</strong><br>Use your official government email ID and the password provided by the IT Department or your Organizer. Contact your Organizer if you do not have credentials.</p>' .
            '<p><strong>Q: I forgot my password. What do I do?</strong><br>Contact your Organizer or IT Support at support.latur@nic.in to reset your password.</p>' .
            '<p><strong>Q: The captcha is hard to read.</strong><br>Click the refresh button (↺) next to the captcha to generate a new one. The captcha is case-insensitive — you can type in uppercase or lowercase.</p>' .
            '<h4>Meetings</h4>' .
            '<p><strong>Q: How do I confirm attendance for a meeting?</strong><br>Go to Meetings → select the meeting → click "Mark Attendance". Your status will be updated immediately.</p>' .
            '<p><strong>Q: Who can create a meeting?</strong><br>Only users with Organizer or Collector roles can create and schedule meetings.</p>' .
            '<h4>Tasks</h4>' .
            '<p><strong>Q: How do I update my task status?</strong><br>Go to Tasks → find your task → use the status dropdown to mark it as In Progress or Completed.</p>' .
            '<p><strong>Q: I cannot see tasks assigned to me.</strong><br>Ensure you are logged in with the correct account. Tasks are visible only to assigned employees and to Organizers/Collectors.</p>' .
            '<h4>Technical Support</h4><p>📧 support.latur@nic.in &nbsp;|&nbsp; 📞 +91-2382-252210<br>Office hours: Monday–Friday, 10:00 AM – 5:00 PM</p>'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO portal_pages (slug, title, icon, content) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        foreach ($seed as $p) {
            $stmt->bind_param('ssss', $p[0], $p[1], $p[2], $p[3]);
            $stmt->execute();
        }
        $stmt->close();
    }
}

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
?>
