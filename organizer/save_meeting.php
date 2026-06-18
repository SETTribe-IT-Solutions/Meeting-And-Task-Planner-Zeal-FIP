<?php
include("../includes/auth_check.php");
include("../config/db.php");

$title = isset($_POST['title']) ? $_POST['title'] : '';
$date = isset($_POST['meeting_date']) ? $_POST['meeting_date'] : '';
$time = isset($_POST['meeting_time']) ? $_POST['meeting_time'] : '';
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$location = isset($_POST['location_link']) ? $_POST['location_link'] : '';
$department = isset($_POST['department']) ? $_POST['department'] : '';
$agenda = isset($_POST['agenda']) ? $_POST['agenda'] : '';
$users = isset($_POST['users']) ? $_POST['users'] : [];

$file = "";

if(!empty($_FILES['attachment']['name']))
{
    $file = time().$_FILES['attachment']['name'];

    move_uploaded_file(
        $_FILES['attachment']['tmp_name'],
        "../uploads/".$file
    );
}

$user_id = $_SESSION['user_id'];

// Modernize with Prepared Statements and handle attendee associations
$sql = "INSERT INTO meetings (title, meeting_date, meeting_time, duration, mode, location_link, department, agenda, attachment, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssisssssi", $title, $date, $time, $duration, $mode, $location, $department, $agenda, $file, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $meeting_id = mysqli_insert_id($conn);

    // Save assigned attendees to the meeting_attendees table
    if (!empty($users)) {
        $att_sql = "INSERT INTO meeting_attendees (meeting_id, user_id) VALUES (?, ?)";
        $att_stmt = mysqli_prepare($conn, $att_sql);

        // Bind parameters once outside the loop for high performance
        mysqli_stmt_bind_param($att_stmt, "ii", $meeting_id, $u_id);

        foreach ($users as $u_id) {
            $u_id = intval($u_id);
            mysqli_stmt_execute($att_stmt);
        }
        mysqli_stmt_close($att_stmt);
    }
    header("Location: view_meetings.php?msg=created");
    exit();
} else {
    echo "Error creating meeting: " . mysqli_error($conn);
}

mysqli_close($conn);