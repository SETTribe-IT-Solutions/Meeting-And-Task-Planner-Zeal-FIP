<?php
// controllers/RegisterController.php
// Handles user registration with full server-side validation

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

class RegisterController {

    // Allowed values for dropdowns (whitelist validation)
    private static $VALID_ROLES = ['Collector', 'Organizer', 'Employee'];

    private static $VALID_TALUKAS = [
        'Latur', 'Udgir', 'Ahmedpur', 'Nilanga', 'Ausa',
        'Renapur', 'Chakur', 'Deoni', 'Jalkot', 'Shirur Anantpal'
    ];

    private static $VALID_DESIGNATIONS = [
        'Tehsildar', 'Naib Tehsildar', 'Talathi', 'Clerk', 'Gram Sevak',
        'BDO (Block Development Officer)', 'District Engineer', 'Medical Officer',
        'Education Officer', 'Agriculture Officer', 'Accountant', 'Data Entry Operator',
        'Office Superintendent', 'Section Officer', 'Other'
    ];

    private static $VALID_GENDERS = ['Male', 'Female', 'Other'];

    /**
     * Main registration handler
     */
    public function register() {
        // ── 1. CSRF Validation ──
        $submitted_token = trim($_POST['csrf_token'] ?? '');
        if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
            $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
            $this->redirectBack();
        }

        // ── 2. Collect & Sanitize Inputs ──
        $name        = trim($_POST['name'] ?? '');
        $email       = strtolower(trim($_POST['email'] ?? ''));
        $phone       = trim($_POST['phone'] ?? '');
        $gender      = trim($_POST['gender'] ?? '');
        $role        = trim($_POST['role'] ?? '');
        $department  = trim($_POST['department'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $taluka      = trim($_POST['taluka'] ?? '');
        $password    = $_POST['password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';
        $terms       = isset($_POST['terms']) ? true : false;

        // Preserve old values for repopulation (except password)
        $_SESSION['old_name']        = $name;
        $_SESSION['old_email']       = $email;
        $_SESSION['old_phone']       = $phone;
        $_SESSION['old_gender']      = $gender;
        $_SESSION['old_role']        = $role;
        $_SESSION['old_department']  = $department;
        $_SESSION['old_designation'] = $designation;
        $_SESSION['old_taluka']      = $taluka;

        // ── 3. Field-level Validation ──
        $errors = [];

        // Name validation
        if (empty($name)) {
            $errors['name'] = 'Full name is required.';
        } elseif (mb_strlen($name) < 3) {
            $errors['name'] = 'Name must be at least 3 characters long.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters.';
        } elseif (!preg_match("/^[A-Za-z\s.'\-]+$/", $name)) {
            $errors['name'] = 'Name can only contain letters, spaces, dots, apostrophes, and hyphens.';
        } elseif (count(array_filter(explode(' ', $name), fn($w) => strlen($w) > 0)) < 2) {
            $errors['name'] = 'Please enter your full name (first and last name).';
        }

        // Email validation
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] = 'Email must not exceed 150 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (preg_match('/\s/', $email)) {
            $errors['email'] = 'Email must not contain spaces.';
        }

        // Phone validation
        if (empty($phone)) {
            $errors['phone'] = 'Mobile number is required.';
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            $errors['phone'] = 'Enter a valid 10-digit Indian mobile number starting with 6-9.';
        } elseif (preg_match('/^(\d)\1{9}$/', $phone)) {
            $errors['phone'] = 'Invalid phone number (all same digits).';
        }

        // Gender validation
        if (empty($gender)) {
            $errors['gender'] = 'Gender is required.';
        } elseif (!in_array($gender, self::$VALID_GENDERS, true)) {
            $errors['gender'] = 'Invalid gender selection.';
        }

        // Role validation (dropdown whitelist)
        if (empty($role)) {
            $errors['role'] = 'Please select your role.';
        } elseif (!in_array($role, self::$VALID_ROLES, true)) {
            $errors['role'] = 'Invalid role selection.';
        }

        // Department validation (dropdown whitelist)
        if (empty($department)) {
            $errors['department'] = 'Please select your department.';
        } elseif (!in_array($department, getDepartments(), true)) {
            $errors['department'] = 'Invalid department selection.';
        }

        // Designation validation (dropdown whitelist)
        if (empty($designation)) {
            $errors['designation'] = 'Please select your designation.';
        } elseif (!in_array($designation, self::$VALID_DESIGNATIONS, true)) {
            $errors['designation'] = 'Invalid designation selection.';
        }

        // Taluka validation (dropdown whitelist)
        if (empty($taluka)) {
            $errors['taluka'] = 'Please select your taluka.';
        } elseif (!in_array($taluka, self::$VALID_TALUKAS, true)) {
            $errors['taluka'] = 'Invalid taluka selection.';
        }

        // Password validation
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 64) {
            $errors['password'] = 'Password must not exceed 64 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one special character.';
        } elseif (preg_match('/\s/', $password)) {
            $errors['password'] = 'Password must not contain spaces.';
        }

        // Confirm password
        if (empty($confirm)) {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // Terms acceptance
        if (!$terms) {
            $errors['terms'] = 'You must accept the terms and conditions.';
        }

        // ── 4. If validation errors, redirect back ──
        if (!empty($errors)) {
            $_SESSION['field_errors'] = $errors;
            $_SESSION['error'] = 'Please correct the highlighted errors and try again.';
            $this->redirectBack();
        }

        // ── 5. Database Operations ──
        try {
            $conn = getDBConnection();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND isDeleted = 'No' LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing) {
                $_SESSION['error'] = 'This email address is already registered. Please use a different email or sign in.';
                $this->redirectBack();
            }

            // Hash the password securely
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insert new user
            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role, department, isDeleted) 
                 VALUES (?, ?, ?, ?, ?, 'No')"
            );
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $department);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Clear old form values
                foreach (['name', 'email', 'phone', 'gender', 'role', 'department', 'designation', 'taluka'] as $k) {
                    unset($_SESSION['old_' . $k]);
                }

                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['success'] = 'Account created successfully! You can now sign in with your credentials.';
                header('Location: ../modules/users/login.php');
                exit();
            } else {
                $_SESSION['error'] = 'Registration failed. Please try again.';
                $this->redirectBack();
            }

        } catch (Exception $e) {
            $_SESSION['error'] = 'An error occurred during registration. Please try again later.';
            // Log the actual error (in production, use proper logging)
            error_log('Registration Error: ' . $e->getMessage());
            $this->redirectBack();
        }
    }

    /**
     * Redirect back to registration page
     */
    private function redirectBack() {
        header('Location: ../modules/users/login.php');
        exit();
    }
}

// Trigger registration if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new RegisterController();
    $controller->register();
}
?>
