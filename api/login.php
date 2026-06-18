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

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form inputs
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate inputs
    if (empty($username)) {
        $response['message'] = 'Username is required';
        http_response_code(400);
    } elseif (empty($password)) {
        $response['message'] = 'Password is required';
        http_response_code(400);
    } else {
        try {
            // Query to get user from database
            $stmt = $pdo->prepare('SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            // Verify user exists and password matches
            if ($user && password_verify($password, $user['password'])) {
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Handle "Remember Me" functionality
                if ($remember) {
                    setcookie('username', $user['username'], time() + (30 * 24 * 60 * 60), '/');
                }
                
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                $response['redirect'] = '../dashboards/';
                http_response_code(200);
                
            } else {
                // Invalid credentials
                $response['message'] = 'Invalid username or password';
                http_response_code(401);
            }
            
        } catch (PDOException $e) {
            $response['message'] = 'Database error occurred';
            http_response_code(500);
            error_log($e->getMessage());
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If not POST, return error
$response['message'] = 'Invalid request method';
http_response_code(405);
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
