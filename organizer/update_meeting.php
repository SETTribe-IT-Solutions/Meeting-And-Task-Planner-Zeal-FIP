<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $title = $_POST['title'];
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $duration = $_POST['duration'];
    $mode = $_POST['mode'];
    $location = $_POST['location_link'];
    $department = $_POST['department'];
    $agenda = $_POST['agenda'];
    $users = isset($_POST['users']) ? $_POST['users'] : [];

    // Fetch existing attachment in case no new one is uploaded
    $current_stmt = mysqli_prepare($conn, "SELECT attachment FROM meetings WHERE id = ?");
    mysqli_stmt_bind_param($current_stmt, "i", $id);
    mysqli_stmt_execute($current_stmt);
    $current_res = mysqli_stmt_get_result($current_stmt);
    $current_data = mysqli_fetch_assoc($current_res);
    $file = $current_data['attachment'];

    if(!empty($_FILES['attachment']['name'])) {
        $file = time().$_FILES['attachment']['name'];
        move_uploaded_file($_FILES['attachment']['tmp_name'], "../uploads/".$file);
    }

    // Safe update using Prepared Statements
    $sql = "UPDATE meetings SET 
            title = ?, meeting_date = ?, meeting_time = ?, 
            duration = ?, mode = ?, location_link = ?, 
            department = ?, agenda = ?, attachment = ?
            WHERE id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssisssssi", $title, $date, $time, $duration, $mode, $location, $department, $agenda, $file, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Sync Attendees: Remove current assignments and add new selection
        $del_stmt = mysqli_prepare($conn, "DELETE FROM meeting_attendees WHERE meeting_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);

        if (!empty($users)) {
            $ins_stmt = mysqli_prepare($conn, "INSERT INTO meeting_attendees (meeting_id, user_id) VALUES (?, ?)");

            // Bind parameters once for efficiency
            mysqli_stmt_bind_param($ins_stmt, "ii", $id, $uid);

            foreach ($users as $user_id) {
                $uid = intval($user_id);
                mysqli_stmt_execute($ins_stmt);
            }
            mysqli_stmt_close($ins_stmt);
        }
        header("Location: view_meetings.php?msg=updated");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>