<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mom_functions.php';

momRequireManage();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $result = deleteMomRecord($id);
    $_SESSION['alert'] = ['type' => $result['success'] ? 'success' : 'error', 'title' => $result['success'] ? 'Deleted' : 'Error', 'message' => $result['message']];
}

redirect('mom.php');
