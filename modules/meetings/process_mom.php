<?php
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO meeting_notes (meeting_id, note_title, note_description) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['meeting_id'], $_POST['title'], $_POST['description']]);
    header("Location: manage_meeting.php?id=" . $_POST['meeting_id'] . "&success=1");
}