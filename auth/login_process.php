<?php

session_start();
require_once __DIR__ . '/../config/db.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Validation rules
$VALIDATION = [
    'email' => [
        'min_length' => 5, // e.g., a@b.c
        'max_length' => 100,
        'pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'error' => 'Please enter a valid email address'
    ],
    'password' => [
        'min_length' => 6,
        'max_length' => 255,
        'error' => 'Password must be at least 6 characters long'
    ]
];

function validateInput($name, $value, $rules) {
    $value = trim($value);

    if (empty($value)) {
        return ['valid' => false, 'error' => ucfirst($name) . ' is required'];
    }
    if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
        return ['valid' => false, 'error' => $rules['error'] ?? ucfirst($name) . ' must be at least ' . $rules['min_length'] . ' characters'];
    }
    if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
        return ['valid' => false, 'error' => $rules['error'] ?? ucfirst($name) . ' must not exceed ' . $rules['max_length'] . ' characters'];
    }
    if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
        return ['valid' => false, 'error' => $rules['error'] ?? ucfirst($name) . ' format is invalid'];
    }
    return ['valid' => true, 'value' => $value];
}

function sendJsonResponse($response, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Debug: Check if form data is received
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    sendJsonResponse($response, 405);
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    $response['message'] = 'Email and password are required.';
    sendJsonResponse($response, 400);
}

// Validate email
$email_validation = validateInput('email', $email, $VALIDATION['email']);
if (!$email_validation['valid']) {
    $response['message'] = $email_validation['error'];
    sendJsonResponse($response, 400);
}
$email = $email_validation['value'];

// Validate password
$password_validation = validateInput('password', $password, $VALIDATION['password']);
if (!$password_validation['valid']) {
    $response['message'] = $password_validation['error'];
    sendJsonResponse($response, 400);
}
$password = $password; // Keep raw password for password_verify

// Check database connection
if (!isset($conn) || !$conn) {
    $response['message'] = 'Database connection not available.';
    error_log('Login Process DB Error: ' . mysqli_connect_error());
    sendJsonResponse($response, 500);
}

$query = 'SELECT id, name, email, password, role FROM `users` WHERE email = ? LIMIT 1';
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $response['message'] = 'Database query preparation failed.';
    error_log('Login Process Prepare Error: ' . mysqli_error($conn));
    sendJsonResponse($response, 500);
}

mysqli_stmt_bind_param($stmt, 's', $email);
if (!mysqli_stmt_execute($stmt)) {
    $response['message'] = 'Database query execution failed.';
    error_log('Login Process Execute Error: ' . mysqli_stmt_error($stmt));
    sendJsonResponse($response, 500);
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    $response['message'] = 'Database result retrieval failed.';
    error_log('Login Process Get Result Error: ' . mysqli_error($conn));
    sendJsonResponse($response, 500);
}

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    $storedPassword = $user['password'];
    
    // Verify password: Check bcrypt hash OR plain-text fallback (for development/existing test users)
    $password_match = password_verify($password, $storedPassword) || ($password === $storedPassword);

    if ($password_match) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true; // Added for consistency with SessionHelper
        
        $response['success'] = true;
        $response['message'] = 'Login successful!';

        if ($user['role'] == 'organizer') {
            $response['redirect'] = '../dashboards/organizer.php';
        } elseif ($user['role'] == 'collector') {
            $response['redirect'] = '../dashboards/collector.php';
        } else {
            $response['redirect'] = '../dashboards/employee.php';
        }
        sendJsonResponse($response, 200);
    } else {
        $response['message'] = 'Invalid email or password.';
        sendJsonResponse($response, 401);
    }
} else {
    $response['message'] = 'Invalid email or password.';
    sendJsonResponse($response, 401);
}

mysqli_stmt_close($stmt);
mysqli_close($conn); // Ensure connection is closed

?>
