<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/TranslationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/meetings/create.php');
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organizer') {
    $_SESSION['error'] = 'Only organizers can create meetings.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

/* Required Fields Validation */

$requiredFields = [
    'title',
    'meeting_date',
    'meeting_time',
    'location',
    'mode',
    'agenda',
    'department'
];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = 'Please fill in all meeting details.';
        header('Location: ../modules/meetings/create.php');
        exit();
    }
}

/* Get Form Data */

$title = trim($_POST['title']);
$meetingDate = trim($_POST['meeting_date']);
$meetingTime = trim($_POST['meeting_time']);
$location = trim($_POST['location']);
$mode = trim($_POST['mode']);
$agenda = trim($_POST['agenda']);
$department = trim($_POST['department']);
$organizerId = (int) $_SESSION['user_id'];

/* Prevent Previous Date */

$today = date('Y-m-d');

if ($meetingDate < $today) {
    $_SESSION['error'] = 'Previous dates are not allowed.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

/* Prevent Previous Time For Today */

if ($meetingDate === $today) {

    $currentTime = date('H:i');

    if ($meetingTime < $currentTime) {
        $_SESSION['error'] = 'Past time is not allowed for today.';
        header('Location: ../modules/meetings/create.php');
        exit();
    }
}

/* Department Validation */

if (!in_array($department, getDepartments(), true)) {
    $_SESSION['error'] = 'Please select a valid department.';
    header('Location: ../modules/meetings/create.php');
    exit();
}

try {

    $conn = getDBConnection();

    /* Create Meeting */

    $stmt = $conn->prepare(
        "INSERT INTO meetings
        (
            title,
            meeting_date,
            meeting_time,
            location,
            mode,
            agenda,
            department,
            organizer_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sssssssi",
        $title,
        $meetingDate,
        $meetingTime,
        $location,
        $mode,
        $agenda,
        $department,
        $organizerId
    );

    $stmt->execute();

    $meetingId = $conn->insert_id;

    $_SESSION['success'] = 'Meeting created successfully.';

    /* Translation Logic */

    $targetLanguages = ['en', 'mr'];
    $sourceLanguage = 'en';

    try {

        $translationService = new TranslationService();

        foreach ($targetLanguages as $langCode) {

            if ($langCode === $sourceLanguage) {
                continue;
            }

            $translatedAgenda = $translationService->translateText(
                $agenda,
                $langCode,
                $sourceLanguage
            );

            $translationStmt = $conn->prepare(
                "INSERT INTO meeting_translations
                (
                    meeting_id,
                    language_code,
                    translated_agenda
                )
                VALUES (?, ?, ?)"
            );

            $translationStmt->bind_param(
                "iss",
                $meetingId,
                $langCode,
                $translatedAgenda
            );

            $translationStmt->execute();
        }

    } catch (Exception $e) {

        error_log(
            'Failed to translate meeting agenda: ' .
            $e->getMessage()
        );
    }

    header('Location: ../index.php?status=success');
    exit();

} catch (Exception $e) {

    error_log(
        'Meeting creation failed: ' .
        $e->getMessage()
    );

    $_SESSION['error'] = 'Unable to create meeting right now.';

    header('Location: ../modules/meetings/create.php');
    exit();
}