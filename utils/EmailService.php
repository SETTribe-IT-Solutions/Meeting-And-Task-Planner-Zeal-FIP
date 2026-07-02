<?php
// utils/EmailService.php
// Handles all email notifications and password reset emails

require_once __DIR__ . '/../config/db.php';

class EmailService {

    /**
     * Send password reset email
     * @param string $email User email
     * @param string $resetLink Full URL for password reset
     * @param string $userName User's full name
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function sendPasswordResetEmail($email, $resetLink, $userName) {
        try {
            $subject = "Password Reset Request - Meeting & Task Planner";
            
            // HTML email template
            $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; background: #138808; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Request</h2>
        </div>
        <div class="content">
            <p>Hello <strong>$userName</strong>,</p>
            
            <p>We received a request to reset your password. Click the button below to proceed:</p>
            
            <div style="text-align: center;">
                <a href="$resetLink" class="button">Reset Your Password</a>
            </div>
            
            <p>Or copy and paste this link in your browser:</p>
            <p style="word-break: break-all; background: #f0f0f0; padding: 10px; border-radius: 3px;">$resetLink</p>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This link will expire in 1 hour</li>
                    <li>If you didn't request this, please ignore this email</li>
                    <li>Never share this link with anyone</li>
                </ul>
            </div>
            
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 Latur District Administration | Meeting & Task Planner</p>
            <p>Government of Maharashtra</p>
        </div>
    </div>
</body>
</html>
HTML;

            // Plain text version
            $textBody = <<<TEXT
Password Reset Request

Hello $userName,

We received a request to reset your password. 

Click the link below to proceed:
$resetLink

Or copy and paste this URL in your browser:
$resetLink

⚠️ Security Notice:
- This link will expire in 1 hour
- If you didn't request this, please ignore this email
- Never share this link with anyone

This is an automated message. Please do not reply to this email.

© 2026 Latur District Administration | Meeting & Task Planner
Government of Maharashtra
TEXT;

            return self::sendEmail($email, $subject, $htmlBody, $textBody, 'password_reset');
        } catch (Exception $e) {
            error_log("Error sending password reset email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send email. Please try again later.'];
        }
    }

    /**
     * Send meeting notification email
     * @param string $email Recipient email
     * @param string $userName User's name
     * @param array $meetingData Meeting details
     * @param string $type 'created', 'updated', or 'cancelled'
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function sendMeetingNotification($email, $userName, $meetingData, $type = 'created') {
        try {
            $action = $type === 'created' ? 'scheduled' : ($type === 'cancelled' ? 'cancelled' : 'updated');
            $subject = "Meeting " . ucfirst($action) . " - {$meetingData['title']}";
            
            $statusColor = $type === 'cancelled' ? '#dc3545' : '#138808';
            $statusText = strtoupper($action);
            
            $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .status-badge { display: inline-block; background: $statusColor; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
        .meeting-details { background: white; padding: 15px; border-left: 4px solid #004080; margin: 15px 0; border-radius: 3px; }
        .detail-row { margin: 8px 0; }
        .detail-label { font-weight: bold; color: #003366; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Meeting Notification</h2>
        </div>
        <div class="content">
            <p>Dear <strong>$userName</strong>,</p>
            
            <div style="text-align: center;">
                <div class="status-badge">$statusText</div>
            </div>
            
            <p>A meeting has been $action. Here are the details:</p>
            
            <div class="meeting-details">
                <div class="detail-row">
                    <span class="detail-label">📌 Title:</span> {$meetingData['title']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">📅 Date & Time:</span> {$meetingData['meeting_date']} at {$meetingData['meeting_time']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">📍 Location:</span> {$meetingData['location']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">🔗 Mode:</span> {$meetingData['mode']}
                </div>
                {$this->safeHtml(isset($meetingData['duration']) ? '<div class="detail-row"><span class="detail-label">⏱️ Duration:</span> ' . $meetingData['duration'] . ' minutes</div>' : '')}
            </div>
            
            <p>Please log in to the portal for more details and to update your attendance.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 Latur District Administration | Meeting & Task Planner</p>
        </div>
    </div>
</body>
</html>
HTML;

            $textBody = "Meeting " . ucfirst($action) . "\n\n";
            $textBody .= "Title: {$meetingData['title']}\n";
            $textBody .= "Date & Time: {$meetingData['meeting_date']} at {$meetingData['meeting_time']}\n";
            $textBody .= "Location: {$meetingData['location']}\n";
            $textBody .= "Mode: {$meetingData['mode']}\n\n";
            $textBody .= "Please log in to the portal for more details.\n";

            return self::sendEmail($email, $subject, $htmlBody, $textBody, 'meeting_' . $type);
        } catch (Exception $e) {
            error_log("Error sending meeting notification: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send notification email.'];
        }
    }

    /**
     * Send task assignment notification
     * @param string $email Recipient email
     * @param string $userName User's name
     * @param array $taskData Task details
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function sendTaskAssignmentEmail($email, $userName, $taskData) {
        try {
            $subject = "New Task Assigned - {$taskData['title']}";
            
            $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .task-details { background: white; padding: 15px; border-left: 4px solid #FF9933; margin: 15px 0; border-radius: 3px; }
        .detail-row { margin: 8px 0; }
        .detail-label { font-weight: bold; color: #003366; }
        .priority-high { color: #dc3545; }
        .priority-medium { color: #FF9933; }
        .priority-low { color: #138808; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Task Assignment</h2>
        </div>
        <div class="content">
            <p>Dear <strong>$userName</strong>,</p>
            <p>You have been assigned a new task. Please review the details below:</p>
            
            <div class="task-details">
                <div class="detail-row">
                    <span class="detail-label">✓ Title:</span> {$taskData['title']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">📝 Description:</span> {$taskData['description']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">📅 Due Date:</span> {$taskData['due_date']}
                </div>
                <div class="detail-row">
                    <span class="detail-label">🎯 Priority:</span> <span class="priority-{strtolower($taskData['priority'])}">{$taskData['priority']}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📊 Status:</span> {$taskData['status']}
                </div>
            </div>
            
            <p>Log in to the portal to view full details and update task progress.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 Latur District Administration | Meeting & Task Planner</p>
        </div>
    </div>
</body>
</html>
HTML;

            return self::sendEmail($email, $subject, $htmlBody, '', 'task_assigned');
        } catch (Exception $e) {
            error_log("Error sending task assignment email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send task notification.'];
        }
    }

    /**
     * Core email sending function
     * @param string $recipient Email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML content
     * @param string $textBody Plain text content
     * @param string $notificationType Type of notification for logging
     * @return array ['success'=>bool, 'message'=>string]
     */
    private static function sendEmail($recipient, $subject, $htmlBody, $textBody = '', $notificationType = 'system') {
        try {
            // Validate recipient email
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid recipient email.'];
            }

            // Log email in database
            $conn = getDBConnection();
            
            // Get user ID if possible
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $recipient);
            $stmt->execute();
            $result = $stmt->get_result();
            $userId = $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;

            // Log email notification
            if ($userId) {
                $stmt = $conn->prepare(
                    "INSERT INTO email_notifications (user_id, recipient_email, subject, notification_type, sent_status) 
                     VALUES (?, ?, ?, ?, 'pending')"
                );
                $stmt->bind_param("isss", $userId, $recipient, $subject, $notificationType);
                $stmt->execute();
            }

            // Send email using PHP mail() function with proper headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Meeting & Task Planner <noreply@laturadmin.gov.in>\r\n";
            $headers .= "Reply-To: support@laturadmin.gov.in\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            $result = mail($recipient, $subject, $htmlBody, $headers);

            // Update email notification status
            if ($userId) {
                $status = $result ? 'sent' : 'failed';
                $stmt = $conn->prepare(
                    "UPDATE email_notifications SET sent_status = ?, sent_at = NOW() 
                     WHERE recipient_email = ? AND subject = ? ORDER BY created_at DESC LIMIT 1"
                );
                $stmt->bind_param("sss", $status, $recipient, $subject);
                $stmt->execute();
            }

            if ($result) {
                error_log("Email sent successfully to $recipient with subject: $subject");
                return ['success' => true, 'message' => 'Email sent successfully.'];
            } else {
                error_log("Failed to send email to $recipient with subject: $subject");
                return ['success' => false, 'message' => 'Failed to send email. Please try again later.'];
            }
        } catch (Exception $e) {
            error_log("EmailService Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error sending email.'];
        }
    }

    /**
     * Helper function for safe HTML output
     */
    private static function safeHtml($html) {
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
}
?>
