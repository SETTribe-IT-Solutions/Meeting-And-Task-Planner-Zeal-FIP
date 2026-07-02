<?php
// controllers/PageController.php — Save portal page content (Organizer only)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isOrganizer()) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: ../index.php'); exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/pages/index.php'); exit();
}

// CSRF
$token = trim($_POST['csrf_token'] ?? '');
if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    $_SESSION['error'] = 'Security token invalid. Please try again.';
    header('Location: ../modules/pages/index.php'); exit();
}

$allowedSlugs = ['about-district','notices','reports','contact','help','administration'];
$slug    = trim($_POST['slug'] ?? '');
$title   = trim($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';

if (!in_array($slug, $allowedSlugs, true)) {
    $_SESSION['error'] = 'Invalid page.';
    header('Location: ../modules/pages/index.php'); exit();
}

if ($title === '' || mb_strlen($title) > 200) {
    $_SESSION['error'] = 'Title must be between 1 and 200 characters.';
    header('Location: ../modules/pages/index.php'); exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE portal_pages SET title = ?, content = ? WHERE slug = ?");
$stmt->bind_param('sss', $title, $content, $slug);
$stmt->execute();

$_SESSION['success'] = 'Page "' . htmlspecialchars($title) . '" updated successfully.';
header('Location: ../modules/pages/index.php'); exit();
