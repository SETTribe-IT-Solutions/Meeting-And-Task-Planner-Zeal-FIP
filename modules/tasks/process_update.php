<?php
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE tasks SET status = ?, progress_notes = ?, last_updated = NOW() WHERE id = ?";
    $pdo->prepare($sql)->execute([$_POST['status'], $_POST['notes'], $_POST['id']]);
    header("Location: ../../dashboards/employee.php?updated=1");
}