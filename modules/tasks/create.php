<?php
// 1. Database Connection and Session check
require_once '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. BACKEND VALIDATION (The "Strict Guard")
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meetingDate = $_POST['meeting_date'];
    $meetingTime = $_POST['meeting_time'];
    
    // Convert inputs into a timestamp for comparison
    $selectedTimestamp = strtotime($meetingDate . ' ' . $meetingTime);
    $currentTimestamp = time();

    // If the selected date is in the past, stop and show error
    if ($selectedTimestamp < $currentTimestamp) {
        echo "<script>alert('Error: You cannot schedule a meeting for a past date.'); window.history.back();</script>";
        exit();
    }

    // --- Proceed with your Database INSERT query here ---
    // Example:
    // $stmt = $pdo->prepare("INSERT INTO meetings (title, meeting_date, meeting_time, ...) VALUES (?, ?, ?, ...)");
    // $stmt->execute([...]);
    // header("Location: ../../index.php");
    // exit();
}
?>

<form action="create.php" method="POST">
    
    <label>Meeting Date:</label>
    <input type="date" name="meeting_date" required min="<?php echo date('Y-m-d'); ?>">

    <label>Meeting Time:</label>
    <input type="time" name="meeting_time" required>

    <button type="submit">Schedule Meeting</button>
</form>

<script>
    // This script automatically sets the 'min' attribute if the browser doesn't support PHP tags in HTML
    var today = new Date().toISOString().split('T')[0];
    document.getElementsByName("meeting_date")[0].setAttribute('min', today);
</script>