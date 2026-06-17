<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Dynamically determine the base path for relative browser routing
$base_url = "/repo/Meeting-And-Task-Planner-Zeal-FIP/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeal Meeting & Task Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-bottom: 60px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="<?php echo $base_url; ?>index.php">Zeal Planner</a>
    <button class="navbar-collapse-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <?php if (isset($_SESSION['role'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $base_url; ?>index.php">Dashboard</a>
            </li>
            <?php if ($_SESSION['role'] === 'Organizer'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base_url; ?>modules/meetings/create.php">Schedule Meeting</a>
                </li>
            <?php endif; ?>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['role'])): ?>
            <li class="nav-item">
                <span class="navbar-text me-3 text-white-50">
                    Hello, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (<?php echo $_SESSION['role']; ?>)
                </span>
            </li>
            <li class="nav-item">
                <a class="btn btn-sm btn-outline-danger" href="<?php echo $base_url; ?>controllers/LogoutController.php">Sign Out</a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo $base_url; ?>modules/users/login.php">Login</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">