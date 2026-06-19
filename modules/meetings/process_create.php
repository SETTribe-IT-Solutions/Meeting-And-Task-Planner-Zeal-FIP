<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle File Upload
    $filePath = null;
    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = '../../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        $filePath = 'uploads/' . $fileName;
        move_uploaded_file($_FILES['attachment']['tmp_name'], '../../' . $filePath);
    }

    // 2. Insert Meeting (Ensure 'mode' is included here)
    // We use $_POST['mode'] ?? 'Physical' to provide a fallback value
    $sql = "INSERT INTO meetings (title, agenda, meeting_date, meeting_time, mode, createdBy, attachment_path, isDeleted) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'No')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['title'], 
        $_POST['agenda'], 
        $_POST['meeting_date'], 
        $_POST['meeting_time'], 
        $_POST['mode'] ?? 'Physical', // Default if key is missing
        $_SESSION['user_id'], 
        $filePath
    ]);
    
    $mId = $pdo->lastInsertId();

    // 3. Insert Attendees
    if (!empty($_POST['attendees'])) {
        $attStmt = $pdo->prepare("INSERT INTO meeting_attendees (meeting_id, user_id) VALUES (?, ?)");
        foreach ($_POST['attendees'] as $uId) {
            $attStmt->execute([$mId, (int)$uId]);
        }
    }

    header("Location: ../../index.php?msg=success");
    exit();
}