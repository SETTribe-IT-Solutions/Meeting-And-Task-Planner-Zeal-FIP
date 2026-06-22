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
    $action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
    $meetingId = isset($_POST['meeting_id']) ? (int)$_POST['meeting_id'] : 0;
    
    if ($action === 'update') {
        $attendanceId = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
        $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'Pending';
        $remarks = isset($_POST['remarks']) ? sanitizeInput($_POST['remarks']) : '';
        
        if ($role === 'Employee') {
            // Employees can only update their own status
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $status, $remarks, $attendanceId, $user_id);
        } else {
            // Organizers and Collectors can update anyone's attendance
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $remarks, $attendanceId);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Attendance record updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update attendance.';
        }
        
        $redirectUrl = isset($_POST['redirect']) ? sanitizeInput($_POST['redirect']) : "../modules/meetings/view.php?id=" . $meetingId;
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
            // Check if already registered
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE meeting_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $meetingId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['error'] = 'User is already invited to this meeting.';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (meeting_id, user_id, status) VALUES (?, ?, 'Pending')");
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
