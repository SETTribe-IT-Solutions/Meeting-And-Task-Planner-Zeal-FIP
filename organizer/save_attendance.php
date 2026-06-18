<?php
include("../includes/auth_check.php");
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_id = intval($_POST['meeting_id']);
    $status_data = $_POST['status']; // array of user_id => status

    // Prepare a single UPSERT statement for atomic database operations
    $stmt = mysqli_prepare($conn, "INSERT INTO attendance (meeting_id, user_id, status, arrival_time) 
                                  VALUES (?, ?, ?, NOW()) 
                                  ON DUPLICATE KEY UPDATE status = VALUES(status), arrival_time = NOW()");

    foreach ($status_data as $user_id => $status) {
        $user_id = intval($user_id);
        mysqli_stmt_bind_param($stmt, "iis", $meeting_id, $user_id, $status);
        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header("Location: attendance_report.php?msg=saved");
    exit();
}
?>