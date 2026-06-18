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

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare(
        "INSERT INTO meetings (title, meeting_date, meeting_time, location, mode, agenda, department, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("sssssssi", $title, $meetingDate, $meetingTime, $location, $mode, $agenda, $department, $organizerId);
    $stmt->execute();

    $_SESSION['success'] = 'Meeting created successfully.';

    // --- Start Translation Logic ---
    $meetingId = $conn->insert_id; // Get the ID of the newly inserted meeting
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
