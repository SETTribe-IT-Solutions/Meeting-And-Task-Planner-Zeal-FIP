<?php
// controllers/AuthController.php
// Handles user authentication with comprehensive server-side validation

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class AuthController {

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

        // ── 1b. Rate Limiting (session-based, no DB or cache required) ──
        // Track failed attempts per session. Lock for 15 minutes after 5 failures.
        $maxAttempts  = 5;
        $lockDuration = 15 * 60; // seconds
        $now          = time();

        if (!empty($_SESSION['login_lockout_until']) && $now < $_SESSION['login_lockout_until']) {
            $remaining = ceil(($_SESSION['login_lockout_until'] - $now) / 60);
            $_SESSION['error'] = "Too many failed login attempts. Please try again in {$remaining} minute" . ((int)$remaining === 1 ? '' : 's') . ".";
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
                $this->recordFailedAttempt($maxAttempts, $lockDuration);
                $_SESSION['error'] = 'No account found with this email address.';
                header('Location: ../modules/users/login.php');
                exit();
            }

            // Role will be taken from the database record; no separate role selection required.

            // Verify password with backward-compatible plain-text migration.
            // If the stored value is a bcrypt hash, use password_verify().
            // If it is plain text (legacy), do a direct comparison and
            // immediately upgrade the hash in the database — transparent to the user.
            $storedPassword = $user['password'];
            $isHashed       = (bool) preg_match('/^\$2[ayb]\$/', $storedPassword);

            $passwordValid = false;
            if ($isHashed) {
                $passwordValid = password_verify($password, $storedPassword);
            } elseif ($password === $storedPassword) {
                // Plain-text match — authenticate and upgrade to bcrypt on the spot
                $passwordValid = true;
                $newHash       = password_hash($password, PASSWORD_BCRYPT);
                $upgradeStmt   = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upgradeStmt->bind_param("si", $newHash, $user['id']);
                $upgradeStmt->execute();
            }

            if (!$passwordValid) {
                $this->recordFailedAttempt($maxAttempts, $lockDuration);
                $_SESSION['error'] = 'Incorrect password. Please try again.';
                header('Location: ../modules/users/login.php');
                exit();
            }

            // ── 6. Successful Login ──
            // Reset rate-limit counters on success
            unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

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

    /**
     * Increment the session-based failed-login counter.
     * Applies a lockout when the maximum attempt count is reached.
     */
    private function recordFailedAttempt(int $maxAttempts, int $lockDuration): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= $maxAttempts) {
            $_SESSION['login_lockout_until'] = time() + $lockDuration;
            $_SESSION['login_attempts']      = 0;
        }
    }
}

// Trigger login if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $auth->login();
}
