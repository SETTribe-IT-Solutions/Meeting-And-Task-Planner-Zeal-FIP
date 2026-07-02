<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'Only organizers can create meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

// Core required fields
$requiredFields = ['title', 'meeting_date', 'meeting_time', 'mode', 'agenda', 'department'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Please fill in all required meeting details.';
        header('Location: ../modules/meetings/create.php');
        exit();
    }
}

$title       = trim($_POST['title']);
$meetingDate = trim($_POST['meeting_date']);
$meetingTime = trim($_POST['meeting_time']);
$mode        = trim($_POST['mode']);
$agenda      = trim($_POST['agenda']);
$department  = trim($_POST['department']);
$location    = trim($_POST['location'] ?? '');
$meetingUrl  = trim($_POST['meeting_url'] ?? '');
$duration    = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
$organizerId = (int) $_SESSION['user_id'];

// Mode-specific validation
if ($mode === 'Offline' && empty($location)) {
    $_SESSION['error'] = 'Location is required for Offline meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}
if ($mode === 'Online' && empty($meetingUrl)) {
    $_SESSION['error'] = 'Meeting URL is required for Online meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!in_array($mode, ['Offline', 'Online', 'Hybrid'], true)) {
    $_SESSION['error'] = 'Please select a valid meeting mode.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!in_array($department, getDepartments(), true)) {
    $_SESSION['error'] = 'Please select a valid department.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

$today = date('Y-m-d');
if ($meetingDate < $today) {
    $_SESSION['error'] = 'Please select today or a future date for the meeting.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        "INSERT INTO meetings (title, meeting_date, meeting_time, location, meeting_url, mode, duration, agenda, department, organizer_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new Exception('Unable to prepare meeting insert: ' . $conn->error);
    }
    $stmt->bind_param("ssssssissi",
        $title, $meetingDate, $meetingTime, $location, $meetingUrl,
        $mode, $duration, $agenda, $department, $organizerId
    );
    $stmt->execute();
    $meetingId = $conn->insert_id;

    // Add selected attendees to attendance table
    if (!empty($_POST['attendees']) && is_array($_POST['attendees'])) {
        $validCheck = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'Employee' AND department = ? AND isDeleted = 'No' LIMIT 1");
        $stmtAtt    = $conn->prepare("INSERT IGNORE INTO attendance (meeting_id, user_id, status) VALUES (?, ?, 'Pending')");
        foreach ($_POST['attendees'] as $attendeeId) {
            $attendeeId = (int)$attendeeId;
            $validCheck->bind_param("is", $attendeeId, $department);
            $validCheck->execute();
            if ($validCheck->get_result()->num_rows === 0) continue;
            $stmtAtt->bind_param("ii", $meetingId, $attendeeId);
            $stmtAtt->execute();
        }
    }

    // Handle optional meeting attachment
    $uploadResult = validateAndStoreUpload('meeting_attachment', dirname(__DIR__) . '/uploads/meetings');
    if ($uploadResult['success']) {
        $upStmt = $conn->prepare(
            "INSERT INTO meeting_attachments (meeting_id, uploaded_by, original_name, stored_name, file_size, mime_type)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $upStmt->bind_param("iissss",
            $meetingId, $organizerId,
            $uploadResult['original_name'], $uploadResult['stored_name'],
            $uploadResult['file_size'], $uploadResult['mime_type']
        );
        $upStmt->execute();
    } elseif ($uploadResult['error'] !== 'no_file') {
        // File was submitted but failed validation — warn but don't block meeting creation
        $_SESSION['success'] = 'Meeting scheduled. Note: attachment was not saved — ' . $uploadResult['error'];
        header('Location: ../modules/meetings/view.php?id=' . $meetingId);
        exit();
    }

    $_SESSION['success'] = 'Meeting scheduled successfully.';
    header('Location: ../modules/meetings/view.php?id=' . $meetingId);
    exit();
} catch (Exception $e) {
    error_log('Meeting creation failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to create meeting right now.';
    header('Location: ../modules/meetings/create.php');
    exit();
}
