<?php
session_start();

// Include database configuration
require_once '../config/db.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Validation rules
$VALIDATION = [
    'username' => [
        'min_length' => 3,
        'max_length' => 50,
        'pattern' => '/^[a-zA-Z0-9_@.-]+$/',
        'error' => 'Username can contain letters, numbers, underscore, @, ., and hyphen'
    ],
    'password' => [
        'min_length' => 6,
        'max_length' => 255,
    ]
];

function validateInput($name, $value, $rules) {
    $value = trim($value);

    if (empty($value)) {
        return ['valid' => false, 'error' => ucfirst($name) . ' is required'];
    }

    if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
        return ['valid' => false, 'error' => ucfirst($name) . ' must be at least ' . $rules['min_length'] . ' characters'];
    }

    if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
        return ['valid' => false, 'error' => ucfirst($name) . ' must not exceed ' . $rules['max_length'] . ' characters'];
    }

    if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
        return ['valid' => false, 'error' => $rules['error'] ?? ucfirst($name) . ' format is invalid'];
    }

    return ['valid' => true, 'value' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8')];
}

function sendResponse($response, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    sendResponse($response, 405);
}

$username_raw = isset($_POST['username']) ? $_POST['username'] : '';
$password_raw = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

$username_validation = validateInput('username', $username_raw, $VALIDATION['username']);
if (!$username_validation['valid']) {
    $response['message'] = $username_validation['error'];
    sendResponse($response, 400);
}
$username = $username_validation['value'];

$password_validation = validateInput('password', $password_raw, $VALIDATION['password']);
if (!$password_validation['valid']) {
    $response['message'] = $password_validation['error'];
    sendResponse($response, 400);
}
$password = $password_raw;

$ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] :
      (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);

try {
    $query = 'SELECT id, username, pwd FROM user WHERE username = ? LIMIT 1';
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id, $dbUsername, $dbPwd);
    $stmt->fetch();
    $stmt->close();

    if (empty($id)) {
        $response['message'] = 'Invalid username or password';
        sendResponse($response, 401);
    }

    $storedPassword = $dbPwd;
    $passwordValid = false;

    if (password_verify($password, $storedPassword)) {
        $passwordValid = true;
    } elseif ($password === $storedPassword) {
        $passwordValid = true;
    }

    if (!$passwordValid) {
        $response['message'] = 'Invalid username or password';
        sendResponse($response, 401);
    }

    $_SESSION['user_id'] = intval($id);
    $_SESSION['username'] = htmlspecialchars($dbUsername, ENT_QUOTES, 'UTF-8');
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $ip;

    if ($remember) {
        setcookie('username', $dbUsername, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    $response['success'] = true;
    $response['message'] = 'Login successful!';
    $response['redirect'] = '/Meeting_Task/Meeting-And-Task-Planner-Zeal-FIP/dashboards/';
    sendResponse($response, 200);

} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Database error occurred. Please try again later.';
    error_log('Login DB Error: ' . $e->getMessage());
    sendResponse($response, 500);
}
?>
