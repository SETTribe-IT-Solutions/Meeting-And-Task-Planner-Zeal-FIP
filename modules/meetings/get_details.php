<?php
require_once '../../config/db.php';
$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT m.*, u.name as organizer 
    FROM meetings m 
    JOIN users u ON m.createdBy = u.id 
    WHERE m.id = ?");
$stmt->execute([$id]);
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);

$attStmt = $pdo->prepare("
    SELECT u.name FROM meeting_attendees ma 
    JOIN users u ON ma.user_id = u.id 
    WHERE ma.meeting_id = ?");
$attStmt->execute([$id]);
$attendees = $attStmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['meeting' => $meeting, 'attendees' => $attendees]);