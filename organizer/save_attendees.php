<?php
include("../includes/auth_check.php");
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_id']) && isset($_POST['users'])) {
    $meeting_id = intval($_POST['meeting_id']);
    $users = $_POST['users'];

    // Prepare statements for checking and inserting
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM meeting_attendees WHERE meeting_id = ? AND user_id = ?");
    $ins_stmt = mysqli_prepare($conn, "INSERT INTO meeting_attendees (meeting_id, user_id) VALUES (?, ?)");

    // Bind parameters once to the variables $meeting_id and $u_id
    mysqli_stmt_bind_param($check_stmt, "ii", $meeting_id, $u_id);
    mysqli_stmt_bind_param($ins_stmt, "ii", $meeting_id, $u_id);

    foreach ($users as $user_id) {
        $u_id = intval($user_id);

        // Check if attendee already exists for this meeting
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) == 0) {
            // Insert if not already present
            mysqli_stmt_execute($ins_stmt);
        }
        mysqli_stmt_free_result($check_stmt); // Clear result buffer for next iteration
    }

    mysqli_stmt_close($check_stmt);
    mysqli_stmt_close($ins_stmt);

    // Redirect to view_attendees.php as per the requested flow
    header("Location: view_attendees.php?msg=attendees_added");
    exit();
} else {
    header("Location: add_attendees.php?error=missing_data");
    exit();
}
?>