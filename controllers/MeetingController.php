<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';

$action = isset($_POST['action']) ? trim($_POST['action']) : 'create';
$organizerId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!isset($_SESSION['user_id']) || !isOrganizer()) {
    $_SESSION['error'] = 'Only organizers can manage meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

// CSRF verification for delete and update actions
if (in_array($action, ['delete', 'update'])) {
    $submitted_token = trim($_POST['csrf_token'] ?? '');
    if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
        $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
        header('Location: ../modules/meetings/index.php');
        exit();
    }
}

$meetingId = isset($_POST['meeting_id']) ? (int) $_POST['meeting_id'] : 0;

if ($action === 'delete') {
    if ($meetingId <= 0) {
        $_SESSION['error'] = 'Invalid meeting selected for deletion.';
        header('Location: ../modules/meetings/index.php');
        exit();
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT organizer_id FROM meetings WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $meetingId);
    $stmt->execute();
    $meetingOwner = $stmt->get_result()->fetch_assoc();

    if (!$meetingOwner || (int)$meetingOwner['organizer_id'] !== $organizerId) {
        $_SESSION['error'] = 'You are not authorized to delete this meeting.';
        header('Location: ../modules/meetings/index.php');
        exit();
    }

    // Cascading delete: remove related records explicitly for safety
    // 1. Delete task_assignments for tasks under this meeting
    $conn->query("DELETE ta FROM task_assignments ta INNER JOIN tasks t ON ta.task_id = t.id WHERE t.meeting_id = " . (int)$meetingId);
    // 2. Delete tasks (also handled by FK CASCADE but explicit for clarity)
    $delTasks = $conn->prepare('DELETE FROM tasks WHERE meeting_id = ?');
    $delTasks->bind_param('i', $meetingId);
    $delTasks->execute();
    // 3. Delete attendance records
    $delAtt = $conn->prepare('DELETE FROM attendance WHERE meeting_id = ?');
    $delAtt->bind_param('i', $meetingId);
    $delAtt->execute();
    // 4. Delete meeting translations
    $delTrans = $conn->prepare('DELETE FROM meeting_translations WHERE meeting_id = ?');
    $delTrans->bind_param('i', $meetingId);
    $delTrans->execute();
    // 5. Delete the meeting itself
    $deleteStmt = $conn->prepare('DELETE FROM meetings WHERE id = ? AND organizer_id = ?');
    $deleteStmt->bind_param('ii', $meetingId, $organizerId);
    if ($deleteStmt->execute()) {
        $_SESSION['success'] = 'Meeting and all related records deleted successfully.';
    } else {
        $_SESSION['error'] = 'Unable to delete meeting right now.';
    }

    header('Location: ../modules/meetings/index.php');
    exit();
}

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
$mode = trim($_POST['mode']);
$agenda = trim($_POST['agenda']);
$department = trim($_POST['department']);
$location = trim($_POST['location'] ?? '');
$meetingUrl = trim($_POST['meeting_url'] ?? '');

if ($mode === 'Offline' && empty($location)) {
    $_SESSION['error'] = 'Please provide a meeting location for offline mode.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

if ($mode === 'Online' && empty($meetingUrl)) {
    $_SESSION['error'] = 'Please provide a meeting URL for online mode.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

if ($mode === 'Hybrid' && empty($location) && empty($meetingUrl)) {
    $_SESSION['error'] = 'Please provide a location or URL for hybrid mode.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

if ($mode === 'Online') {
    $location = $meetingUrl;
} elseif ($mode === 'Hybrid') {
    if (!empty($location) && !empty($meetingUrl)) {
        $location = $location . ' | ' . $meetingUrl;
    } elseif (!empty($meetingUrl)) {
        $location = $meetingUrl;
    }
}

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

    if ($action === 'update') {
        if ($meetingId <= 0) {
            $_SESSION['error'] = 'Invalid meeting selected for update.';
            header('Location: ../modules/meetings/index.php');
            exit();
        }

        $ownershipStmt = $conn->prepare('SELECT organizer_id FROM meetings WHERE id = ? LIMIT 1');
        $ownershipStmt->bind_param('i', $meetingId);
        $ownershipStmt->execute();
        $owner = $ownershipStmt->get_result()->fetch_assoc();

        if (!$owner || (int)$owner['organizer_id'] !== $organizerId) {
            $_SESSION['error'] = 'You are not authorized to update this meeting.';
            header('Location: ../modules/meetings/index.php');
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE meetings SET title = ?, meeting_date = ?, meeting_time = ?, location = ?, mode = ?, agenda = ?, department = ? WHERE id = ? AND organizer_id = ?"
        );
        $stmt->bind_param("ssssssiii", $title, $meetingDate, $meetingTime, $location, $mode, $agenda, $department, $meetingId, $organizerId);
        $stmt->execute();

        // Update or insert Marathi translation for the agenda if needed
        $transStmt = $conn->prepare("SELECT id FROM meeting_translations WHERE meeting_id = ? AND language_code = 'mr' LIMIT 1");
        $transStmt->bind_param('i', $meetingId);
        $transStmt->execute();
        $transRow = $transStmt->get_result()->fetch_assoc();

        $translationService = new TranslationService();
        $translatedAgenda = $translationService->translateText($agenda, 'mr', 'en');

        if ($transRow) {
            $updateTrans = $conn->prepare("UPDATE meeting_translations SET translated_agenda = ? WHERE id = ?");
            $updateTrans->bind_param('si', $translatedAgenda, $transRow['id']);
            $updateTrans->execute();
        } else {
            $insertTrans = $conn->prepare("INSERT INTO meeting_translations (meeting_id, language_code, translated_agenda) VALUES (?, 'mr', ?)");
            $insertTrans->bind_param('is', $meetingId, $translatedAgenda);
            $insertTrans->execute();
        }

        $_SESSION['success'] = 'Meeting updated successfully.';
        header('Location: ../modules/meetings/view.php?id=' . $meetingId);
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO meetings (title, meeting_date, meeting_time, location, meeting_url, mode, duration, agenda, department, organizer_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
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
