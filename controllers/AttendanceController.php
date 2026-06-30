<?php
// controllers/AttendanceController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please log in first.';
    header('Location: ../modules/users/login.php');
    exit();
}

$conn = getDBConnection();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification — same pattern used throughout the application
    $submitted_token = trim($_POST['csrf_token'] ?? '');
    if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
        header('Location: ../index.php');
        exit();
    }

    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    $meetingId = isset($_POST['meeting_id']) ? (int)$_POST['meeting_id'] : 0;
    
    if ($action === 'update') {
        $attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;

        // Whitelist: only accept known attendance status values
        $ALLOWED_ATT_STATUSES = ['Present', 'Present with Late', 'Absent', 'Not Updated'];
        $rawStatus = isset($_POST['status']) ? trim($_POST['status']) : 'Not Updated';
        $status    = in_array($rawStatus, $ALLOWED_ATT_STATUSES, true) ? $rawStatus : 'Not Updated';

        $remarks = isset($_POST['remarks']) ? trim(stripslashes($_POST['remarks'])) : '';

        // --- Arrival Time logic ---
        // Fetch existing arrival_time so we can preserve it if already set
        $existRow = $conn->prepare("SELECT arrival_time FROM attendance WHERE id = ?");
        $existRow->bind_param("i", $attendanceId);
        $existRow->execute();
        $existData     = $existRow->get_result()->fetch_assoc();
        $existingArrival = $existData ? $existData['arrival_time'] : null;

        $arrivalTime = null;
        if (in_array($status, ['Present', 'Present with Late'], true)) {
            // Organizer-supplied override (e.g. from "Edit Arrival Time" button)
            $postedTime = trim($_POST['arrival_time'] ?? '');
            if ($postedTime !== '' && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $postedTime)) {
                $arrivalTime = strlen($postedTime) === 5 ? $postedTime . ':00' : $postedTime;
            } elseif ($existingArrival !== null) {
                $arrivalTime = $existingArrival; // preserve previously recorded time
            } else {
                $arrivalTime = date('H:i:s'); // auto-fill with current server time
            }
        }
        // Absent / Not Updated: arrival_time stays NULL (already $arrivalTime = null)

        if ($role === 'Employee') {
            // Employees can only update their own status
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ?, arrival_time = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssii", $status, $remarks, $arrivalTime, $attendanceId, $user_id);
        } else {
            // Organizers and Collectors can update anyone's attendance
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ?, arrival_time = ? WHERE id = ?");
            $stmt->bind_param("sssi", $status, $remarks, $arrivalTime, $attendanceId);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Attendance record updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update attendance.';
        }

        // Validate redirect to prevent open-redirect attacks.
        // Only allow relative paths that point to known internal modules.
        $redirectUrl = "../modules/meetings/view.php?id=" . $meetingId;
        if (!empty($_POST['redirect'])) {
            $r = trim($_POST['redirect']);
            if (preg_match('#^\.\./modules/[a-zA-Z0-9_/.\-?=&]+$#', $r)) {
                $redirectUrl = $r;
            }
        }
        header("Location: " . $redirectUrl);
        exit();
    } 
    elseif ($action === 'add') {
        if (!isOrganizer()) {
            $_SESSION['error'] = 'Only organizers/collectors can add attendees.';
            header('Location: ../index.php');
            exit();
        }
        
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($meetingId && $userId) {
            $stmt = $conn->prepare("SELECT m.department FROM meetings m WHERE m.id = ? LIMIT 1");
            $stmt->bind_param("i", $meetingId);
            $stmt->execute();
            $meeting = $stmt->get_result()->fetch_assoc();

            if (!$meeting) {
                $_SESSION['error'] = 'Meeting not found.';
                header("Location: ../modules/meetings/view.php?id=" . $meetingId);
                exit();
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'Employee' AND department = ? AND isDeleted = 'No' LIMIT 1");
            $stmt->bind_param("is", $userId, $meeting['department']);
            $stmt->execute();
            $validEmployee = $stmt->get_result();

            if ($validEmployee->num_rows === 0) {
                $_SESSION['error'] = 'Please select an employee from this meeting department.';
                header("Location: ../modules/meetings/view.php?id=" . $meetingId);
                exit();
            }

            // Check if already registered
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE meeting_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $meetingId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = 'User is already invited to this meeting.';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (meeting_id, user_id, status) VALUES (?, ?, 'Not Updated')");
                $stmt->bind_param("ii", $meetingId, $userId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Attendee added successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to add attendee.';
                }
            }
        }
        
        header("Location: ../modules/meetings/view.php?id=" . $meetingId);
        exit();
    }
}

header('Location: ../index.php');
exit();
