<?php
// controllers/PasswordResetController.php
// Handles password reset functionality

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/EmailService.php';

class PasswordResetController {

    /**
     * Generate password reset token
     * @param int $userId User ID
     * @param string $email User email
     * @return array ['success'=>bool, 'token'=>string, 'message'=>string]
     */
    public static function generateResetToken($userId, $email) {
        try {
            $conn = getDBConnection();
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Insert token into database
            $stmt = $conn->prepare(
                "INSERT INTO password_reset_tokens (user_id, email, token, token_hash, expires_at) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $userId, $email, $token, $tokenHash, $expiresAt);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'token' => $token,
                    'message' => 'Reset token generated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to generate reset token.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error generating reset token: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }

    /**
     * Verify reset token
     * @param string $token Reset token
     * @return array ['valid'=>bool, 'user_id'=>int|null, 'email'=>string|null, 'message'=>string]
     */
    public static function verifyResetToken($token) {
        try {
            $conn = getDBConnection();
            $tokenHash = hash('sha256', $token);
            $now = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare(
                "SELECT id, user_id, email FROM password_reset_tokens 
                 WHERE token_hash = ? AND expires_at > ? AND used_at IS NULL 
                 LIMIT 1"
            );
            $stmt->bind_param("ss", $tokenHash, $now);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return [
                    'valid' => true,
                    'user_id' => $row['user_id'],
                    'email' => $row['email'],
                    'token_id' => $row['id'],
                    'message' => 'Token is valid.'
                ];
            } else {
                return [
                    'valid' => false,
                    'user_id' => null,
                    'email' => null,
                    'message' => 'Invalid or expired reset token.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error verifying reset token: " . $e->getMessage());
            return [
                'valid' => false,
                'user_id' => null,
                'email' => null,
                'message' => 'An error occurred while verifying token.'
            ];
        }
    }

    /**
     * Reset password
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function resetPassword($token, $newPassword) {
        try {
            // Validate password
            $validation = self::validatePassword($newPassword);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            // Verify token
            $tokenVerification = self::verifyResetToken($token);
            if (!$tokenVerification['valid']) {
                return [
                    'success' => false,
                    'message' => $tokenVerification['message']
                ];
            }

            $conn = getDBConnection();
            $userId = $tokenVerification['user_id'];
            $tokenHash = hash('sha256', $token);
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                // Mark token as used
                $stmt = $conn->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?");
                $stmt->bind_param("s", $tokenHash);
                $stmt->execute();

                // Log system notification
                self::createSystemNotification(
                    $userId,
                    'Password Reset Successful',
                    'Your password has been successfully reset. You can now log in with your new password.',
                    'success',
                    'password'
                );

                return [
                    'success' => true,
                    'message' => 'Password has been reset successfully. You can now log in.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reset password. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error resetting password: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }

    /**
     * Validate password strength
     * @param string $password Password to validate
     * @return array ['valid'=>bool, 'message'=>string]
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Password is required.'];
        }

        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        if (strlen($password) > 64) {
            return ['valid' => false, 'message' => 'Password must not exceed 64 characters.'];
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter.'];
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter.'];
        }

        // Check for at least one digit
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one digit.'];
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            return ['valid' => false, 'message' => 'Password must contain at least one special character (!@#$%^&* etc).'];
        }

        return ['valid' => true, 'message' => 'Password is valid.'];
    }

    /**
     * Create system notification for user
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $alertType Alert type (success, error, warning, info)
     * @param string $category Notification category
     * @return bool
     */
    public static function createSystemNotification($userId, $title, $message, $alertType = 'info', $category = 'system') {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                "INSERT INTO system_notifications (user_id, title, message, alert_type, notification_category) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $userId, $title, $message, $alertType, $category);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating system notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notifications for user
     * @param int $userId User ID
     * @param int $limit Number of notifications to fetch
     * @return array Notifications
     */
    public static function getUnreadNotifications($userId, $limit = 10) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                "SELECT id, title, message, alert_type, notification_category, created_at 
                 FROM system_notifications 
                 WHERE user_id = ? AND is_read = 'No' 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notification as read
     * @param int $notificationId Notification ID
     * @return bool
     */
    public static function markNotificationAsRead($notificationId) {
        try {
            $conn = getDBConnection();
            $readStatus = 'Yes';
            $stmt = $conn->prepare(
                "UPDATE system_notifications 
                 SET is_read = ?, read_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->bind_param("si", $readStatus, $notificationId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_reset_email') {
        $email = trim($_POST['email'] ?? '');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit();
        }

        // Check if user exists
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND isDeleted = 'No'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            // For security, don't reveal if email exists
            echo json_encode(['success' => true, 'message' => 'If the email exists, you will receive a password reset link shortly.']);
            exit();
        }

        // Generate reset token
        $tokenResult = PasswordResetController::generateResetToken($user['id'], $email);
        if ($tokenResult['success']) {
            $resetLink = APP_URL . '/modules/users/reset-password.php?token=' . $tokenResult['token'];
            $emailResult = EmailService::sendPasswordResetEmail($email, $resetLink, $user['name']);

            if ($emailResult['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email. It will expire in 1 hour.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send reset email. Please try again later.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => $tokenResult['message']
            ]);
        }
        exit();
    }

    if ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit();
        }

        $result = PasswordResetController::resetPassword($token, $newPassword);
        echo json_encode($result);
        exit();
    }
}
?>
