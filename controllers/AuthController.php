<?php
// controllers/AuthController.php
// Handles user authentication with comprehensive server-side validation

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class AuthController {

    private static $VALID_ROLES = ['Collector', 'Organizer', 'Employee'];
    
    /**
     * Handle login with full validation
     */
    public function login() {
        // ── 1. CSRF Token Validation ──
        $submitted_token = trim($_POST['csrf_token'] ?? '');
        if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
            $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        // ── 2. Collect & Sanitize ──
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Preserve old values for repopulation
        $_SESSION['old_email'] = $email;

        // ── 3. Validation ──
        // Note: Role selection removed from login form. Role will be determined
        // from the user's record in the database after successful authentication.

        // Email validation
        if (empty($email)) {
            $_SESSION['error'] = 'Email address is required.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        if (mb_strlen($email) > 150) {
            $_SESSION['error'] = 'Email must not exceed 150 characters.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        // Password validation
        if (empty($password)) {
            $_SESSION['error'] = 'Password is required.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        if (strlen($password) > 64) {
            $_SESSION['error'] = 'Password must not exceed 64 characters.';
            header('Location: ../modules/users/login.php');
            exit();
        }

        // ── 4. Database Lookup ──
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                "SELECT id, name, password, role, department 
                 FROM users 
                 WHERE email = ? AND isDeleted = 'No' 
                 LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // ── 5. Credential Verification ──
            // Check if user exists
            if (!$user) {
                $_SESSION['error'] = 'No account found with this email address.';
                header('Location: ../modules/users/login.php');
                exit();
            }

            // Role will be taken from the database record; no separate role selection required.

            // Verify password (support both legacy plain text demo passwords and bcrypt hashed)
            $isValidPlainPassword = (
                ($password === 'admin123' && $email === 'organizer@project.local') || 
                ($password === 'employee123' && $email === 'employee@project.local') || 
                ($password === 'collector123' && $email === 'collector@project.local')
            );

            if (!$isValidPlainPassword && !password_verify($password, $user['password'])) {
                $_SESSION['error'] = 'Incorrect password. Please try again.';
                header('Location: ../modules/users/login.php');
                exit();
            }

            // ── 6. Successful Login ──
            // Clear old values
            unset($_SESSION['old_email'], $_SESSION['old_role']);

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['full_name']  = $user['name'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['department'] = $user['department'];

            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: ../index.php');
            exit();

        } catch (Exception $e) {
            $_SESSION['error'] = 'Login failed due to a system error. Please try again.';
            error_log('Login Error: ' . $e->getMessage());
            header('Location: ../modules/users/login.php');
            exit();
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Handle logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        session_destroy();
        return true;
    }
}

// Trigger login if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $auth->login();
}
