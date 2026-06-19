<?php
require_once '../../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['attachment'])) {
    $taskId = $_POST['task_id'];
    $uploadDir = '../../uploads/'; // Ensure this folder exists and is writable
    
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = basename($_FILES['attachment']['name']);
    $targetPath = $uploadDir . time() . '_' . $fileName;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO task_attachments (task_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$taskId, $fileName, $targetPath]);
        header("Location: ../../index.php?status=success");
    }
}
?>