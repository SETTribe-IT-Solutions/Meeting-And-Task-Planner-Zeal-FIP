<?php
// controllers/MOMController.php
// Minutes of Meeting (MOM) Management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/EmailService.php';

class MOMController {

    /**
     * Create a new meeting note (MOM)
     * @return array ['success'=>bool, 'message'=>string, 'mom_id'=>int|null]
     */
    public static function createMOM() {
        try {
            // Validate CSRF token
            $submitted_token = trim($_POST['csrf_token'] ?? '');
            if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
                return ['success' => false, 'message' => 'Invalid security token.'];
            }

            // Collect & Sanitize
            $meeting_id = (int)($_POST['meeting_id'] ?? 0);
            $note_title = trim($_POST['note_title'] ?? '');
            $note_description = trim($_POST['note_description'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $linked_task_id = !empty($_POST['linked_task_id']) ? (int)$_POST['linked_task_id'] : null;
            $created_by = (int)($_SESSION['user_id'] ?? 0);

            // Validation
            if ($meeting_id <= 0) {
                return ['success' => false, 'message' => 'Valid meeting ID is required.'];
            }

            if (mb_strlen($note_title) < 3 || mb_strlen($note_title) > 200) {
                return ['success' => false, 'message' => 'Note title must be between 3 and 200 characters.'];
            }

            if (mb_strlen($note_description) < 10 || mb_strlen($note_description) > 5000) {
                return ['success' => false, 'message' => 'Note description must be between 10 and 5000 characters.'];
            }

            // Database insertion
            $conn = getDBConnection();
            
            // Verify meeting exists and user is authorized
            $stmt = $conn->prepare("SELECT organizer_id FROM meetings WHERE id = ?");
            $stmt->bind_param("i", $meeting_id);
            $stmt->execute();
            $meeting = $stmt->get_result()->fetch_assoc();

            if (!$meeting) {
                return ['success' => false, 'message' => 'Meeting not found.'];
            }

            // Verify user is the organizer or admin
            if ($meeting['organizer_id'] != $created_by && $_SESSION['role'] !== 'Organizer' && $_SESSION['role'] !== 'Collector') {
                return ['success' => false, 'message' => 'You are not authorized to create notes for this meeting.'];
            }

            // Insert MOM
            $stmt = $conn->prepare(
                "INSERT INTO meeting_notes (meeting_id, note_title, note_description, department, linked_task_id, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isssii", $meeting_id, $note_title, $note_description, $department, $linked_task_id, $created_by);

            if ($stmt->execute()) {
                $mom_id = $conn->insert_id;
                
                // Create system notification
                self::createMOMNotification($meeting_id, $mom_id, 'created', $note_title);

                return [
                    'success' => true,
                    'message' => 'Meeting note created successfully.',
                    'mom_id' => $mom_id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create meeting note.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error creating MOM: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while creating the note.'
            ];
        }
    }

    /**
     * Update a meeting note (MOM)
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function updateMOM() {
        try {
            // Validate CSRF token
            $submitted_token = trim($_POST['csrf_token'] ?? '');
            if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
                return ['success' => false, 'message' => 'Invalid security token.'];
            }

            // Collect & Sanitize
            $mom_id = (int)($_POST['mom_id'] ?? 0);
            $note_title = trim($_POST['note_title'] ?? '');
            $note_description = trim($_POST['note_description'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $linked_task_id = !empty($_POST['linked_task_id']) ? (int)$_POST['linked_task_id'] : null;
            $user_id = (int)($_SESSION['user_id'] ?? 0);

            // Validation
            if ($mom_id <= 0) {
                return ['success' => false, 'message' => 'Valid meeting note ID is required.'];
            }

            if (mb_strlen($note_title) < 3 || mb_strlen($note_title) > 200) {
                return ['success' => false, 'message' => 'Note title must be between 3 and 200 characters.'];
            }

            if (mb_strlen($note_description) < 10 || mb_strlen($note_description) > 5000) {
                return ['success' => false, 'message' => 'Note description must be between 10 and 5000 characters.'];
            }

            $conn = getDBConnection();

            // Verify MOM exists and user is authorized
            $stmt = $conn->prepare("SELECT created_by, meeting_id FROM meeting_notes WHERE id = ?");
            $stmt->bind_param("i", $mom_id);
            $stmt->execute();
            $mom = $stmt->get_result()->fetch_assoc();

            if (!$mom) {
                return ['success' => false, 'message' => 'Meeting note not found.'];
            }

            // Verify user is creator or admin
            if ($mom['created_by'] != $user_id && $_SESSION['role'] !== 'Organizer' && $_SESSION['role'] !== 'Collector') {
                return ['success' => false, 'message' => 'You are not authorized to update this note.'];
            }

            // Update MOM
            $stmt = $conn->prepare(
                "UPDATE meeting_notes 
                 SET note_title = ?, note_description = ?, department = ?, linked_task_id = ?, updated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->bind_param("sssii", $note_title, $note_description, $department, $linked_task_id, $mom_id);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Meeting note updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update meeting note.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error updating MOM: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while updating the note.'
            ];
        }
    }

    /**
     * Delete a meeting note (MOM)
     * @return array ['success'=>bool, 'message'=>string]
     */
    public static function deleteMOM() {
        try {
            $mom_id = (int)($_POST['mom_id'] ?? 0);
            $user_id = (int)($_SESSION['user_id'] ?? 0);

            if ($mom_id <= 0) {
                return ['success' => false, 'message' => 'Valid meeting note ID is required.'];
            }

            $conn = getDBConnection();

            // Verify MOM exists and user is authorized
            $stmt = $conn->prepare("SELECT created_by FROM meeting_notes WHERE id = ?");
            $stmt->bind_param("i", $mom_id);
            $stmt->execute();
            $mom = $stmt->get_result()->fetch_assoc();

            if (!$mom) {
                return ['success' => false, 'message' => 'Meeting note not found.'];
            }

            // Verify user is creator or admin
            if ($mom['created_by'] != $user_id && $_SESSION['role'] !== 'Organizer' && $_SESSION['role'] !== 'Collector') {
                return ['success' => false, 'message' => 'You are not authorized to delete this note.'];
            }

            // Delete MOM
            $stmt = $conn->prepare("DELETE FROM meeting_notes WHERE id = ?");
            $stmt->bind_param("i", $mom_id);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Meeting note deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete meeting note.'
                ];
            }
        } catch (Exception $e) {
            error_log("Error deleting MOM: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while deleting the note.'
            ];
        }
    }

    /**
     * Get MOM for a meeting
     * @param int $meeting_id Meeting ID
     * @return array MOM records
     */
    public static function getMOMByMeeting($meeting_id) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                "SELECT mn.*, u.name as created_by_name 
                 FROM meeting_notes mn 
                 LEFT JOIN users u ON mn.created_by = u.id 
                 WHERE mn.meeting_id = ? 
                 ORDER BY mn.created_at DESC"
            );
            $stmt->bind_param("i", $meeting_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching MOM: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single MOM
     * @param int $mom_id MOM ID
     * @return array|null MOM record
     */
    public static function getMOMById($mom_id) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare(
                "SELECT mn.*, u.name as created_by_name, u.email as created_by_email,
                        t.title as linked_task_title, m.title as meeting_title 
                 FROM meeting_notes mn 
                 LEFT JOIN users u ON mn.created_by = u.id 
                 LEFT JOIN tasks t ON mn.linked_task_id = t.id 
                 LEFT JOIN meetings m ON mn.meeting_id = m.id 
                 WHERE mn.id = ?"
            );
            $stmt->bind_param("i", $mom_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("Error fetching MOM: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create MOM notification
     */
    private static function createMOMNotification($meeting_id, $mom_id, $action, $title) {
        try {
            $conn = getDBConnection();
            
            // Get all attendees of the meeting
            $stmt = $conn->prepare(
                "SELECT DISTINCT a.user_id 
                 FROM attendance a 
                 WHERE a.meeting_id = ?"
            );
            $stmt->bind_param("i", $meeting_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $message = "New meeting note created: " . htmlspecialchars($title);
            $alertType = 'info';

            while ($row = $result->fetch_assoc()) {
                $stmt = $conn->prepare(
                    "INSERT INTO system_notifications (user_id, title, message, alert_type, notification_category, related_entity_type, related_entity_id) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $category = 'meeting';
                $entityType = 'meeting_note';
                $stmt->bind_param("isssssI", $row['user_id'], $title, $message, $alertType, $category, $entityType, $mom_id);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error creating MOM notification: " . $e->getMessage());
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = MOMController::createMOM();
            echo json_encode($result);
            break;

        case 'update':
            $result = MOMController::updateMOM();
            echo json_encode($result);
            break;

        case 'delete':
            $result = MOMController::deleteMOM();
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

    exit();
}
?>
