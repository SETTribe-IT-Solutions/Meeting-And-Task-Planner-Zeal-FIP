<?php
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO tasks (meeting_id, title, assigned_to, deadline, status) VALUES (?, ?, ?, ?, 'Pending')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['meeting_id'], 
        $_POST['title'], 
        $_POST['assigned_to'], 
        $_POST['deadline']
    ]);
    header("Location: ../meetings/manage_meeting.php?id=" . $_POST['meeting_id'] . "&task=assigned");
}