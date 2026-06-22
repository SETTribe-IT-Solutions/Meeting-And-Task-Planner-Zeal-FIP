<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/TranslationService.php'; // Include the new service

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'Only organizers can create meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

$requiredFields = ['title', 'meeting_date', 'meeting_time', 'location', 'mode', 'agenda', 'department'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Please fill in all meeting details.';
        header('Location: ../modules/meetings/create.php');
        exit();
    }
}

$title = trim($_POST['title']);
$meetingDate = trim($_POST['meeting_date']);
$meetingTime = trim($_POST['meeting_time']);
$location = trim($_POST['location']);
$mode = trim($_POST['mode']);
$agenda = trim($_POST['agenda']);
$department = trim($_POST['department']);
$organizerId = (int) $_SESSION['user_id'];

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
        "INSERT INTO meetings (title, meeting_date, meeting_time, location, mode, agenda, department, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("sssssssi", $title, $meetingDate, $meetingTime, $location, $mode, $agenda, $department, $organizerId);
    $stmt->execute();

    $meetingId = $conn->insert_id; // Get the ID of the newly inserted meeting

    // Insert selected employees from the selected department into the attendance table
    if (!empty($_POST['attendees']) && is_array($_POST['attendees'])) {
        $validAttendeeStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'Employee' AND department = ? AND isDeleted = 'No' LIMIT 1");
        $stmt_att = $conn->prepare("INSERT INTO attendance (meeting_id, user_id, status) VALUES (?, ?, 'Pending')");
        foreach ($_POST['attendees'] as $attendeeId) {
            $attendeeId = (int)$attendeeId;
            $validAttendeeStmt->bind_param("is", $attendeeId, $department);
            $validAttendeeStmt->execute();
            if ($validAttendeeStmt->get_result()->num_rows === 0) {
                continue;
            }
            $stmt_att->bind_param("ii", $meetingId, $attendeeId);
            $stmt_att->execute();
        }
    }

    $_SESSION['success'] = 'Meeting created successfully.';

    // --- Start Translation Logic ---
    $targetLanguages = ['en', 'mr']; // Languages to translate into
    $sourceLanguage = 'en'; // Assuming agenda is primarily written in English

    try {
        $translationService = new TranslationService();
        foreach ($targetLanguages as $langCode) {
            if ($langCode === $sourceLanguage) {
                continue; // Skip translating to the source language
            }
            $translatedAgenda = $translationService->translateText($agenda, $langCode, $sourceLanguage);
            $stmt = $conn->prepare("INSERT INTO meeting_translations (meeting_id, language_code, translated_agenda) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $meetingId, $langCode, $translatedAgenda);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log('Failed to translate meeting agenda: ' . $e->getMessage());
        // Continue execution even if translation fails, don't block meeting creation
    }
    // --- End Translation Logic ---

    header('Location: ../index.php?status=success');
    exit();
} catch (Exception $e) {
    error_log('Meeting creation failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to create meeting right now.';
    header('Location: ../modules/meetings/create.php');
    exit();
}
