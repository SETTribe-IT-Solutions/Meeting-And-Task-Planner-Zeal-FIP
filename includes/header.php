<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$base_url = "/repo/Meeting-And-Task-Planner-Zeal-FIP/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting & Task Monitoring System | District Latur, Government of Maharashtra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gov-navy: #0a2240;
            --gov-gold: #b8860b;
            --gov-light-gold: #fdfaf2;
        }
        body { 
            font-family: 'Open Sans', sans-serif; 
            background-color: #f4f6f9; 
            padding-bottom: 70px; 
        }
        .gov-top-bar {
            background-color: #111;
            color: #ccc;
            font-size: 11px;
            font-weight: 600;
        }
        .gov-header-brand {
            background-color: #ffffff;
            border-bottom: 3px solid var(--gov-gold);
        }
        .gov-title-font {
            font-family: 'Marcellus', serif;
            color: var(--gov-navy);
            letter-spacing: 0.5px;
        }
        .navbar-gov {
            background-color: var(--gov-navy) !important;
            border-bottom: 4px solid var(--gov-gold);
        }
        .nav-link {
            font-weight: 600;
            font-size: 14px;
            color: #f8f9fa !important;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--gov-gold) !important;
        }
        .card-gov-header {
            background-color: var(--gov-navy);
            color: white;
            border-bottom: 2px solid var(--gov-gold);
            font-weight: 700;
        }
        .btn-gov-primary {
            background-color: var(--gov-navy);
            color: white;
            border: 1px solid var(--gov-gold);
        }
        .btn-gov-primary:hover {
            background-color: #11335c;
            color: var(--gov-gold);
        }
        
        /* Interactive Map Highlight Badges */
        .header-emblem-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .gov-svg-badge {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .gov-svg-badge:hover {
            transform: scale(1.05);
            border-color: var(--gov-gold);
        }
    </style>
</head>
<body>

<div class="gov-top-bar py-1 px-3 d-flex justify-content-between align-items-center">
    <div>GOVERNMENT OF MAHARASHTRA | DISTRICT LATUR ADMINISTRATION</div>
    <div class="d-none d-md-block">डिजिटल भारत | Digital India</div>
</div>

<div class="gov-header-brand py-3 shadow-sm">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            
            <div class="header-emblem-container me-3">
                <div class="gov-svg-badge" title="Government of Maharashtra Official Emblem Framework">
                    <svg viewBox="0 0 100 100" width="100%" height="100%">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="#b8860b" stroke-width="3"/>
                        <circle cx="50" cy="50" r="38" fill="none" stroke="#0a2240" stroke-width="1" stroke-dasharray="3,3"/>
                        <path d="M35 70 L50 30 L65 70 Z" fill="#0a2240"/>
                        <circle cx="50" cy="45" r="8" fill="#b8860b"/>
                        <text x="50" y="82" font-size="9" font-family="sans-serif" font-weight="bold" fill="#0a2240" text-anchor="middle">सत्यमेव जयते</text>
                    </svg>
                </div>
                
                <div class="gov-svg-badge" title="Geographic Regional Border Mapping - Latur Sector">
                    <svg viewBox="0 0 100 100" width="100%" height="100%">
                        <path d="M20 30 Q35 15 55 25 T85 35 T75 75 T45 85 T15 65 Z" fill="#e9ecef" stroke="#ccc" stroke-width="1.5"/>
                        <path d="M52 50 Q58 45 68 52 T62 68 T48 60 Z" fill="#0a2240" stroke="#b8860b" stroke-width="1.5"/>
                        <circle cx="58" cy="56" r="3" fill="#ffc107" class="animate-pulse"/>
                        <text x="58" y="78" font-size="8" font-family="sans-serif" font-weight="bold" fill="#0a2240" text-anchor="middle">LATUR</text>
                    </svg>
                </div>
            </div>

            <div>
                <h4 class="mb-0 fw-bold gov-title-font">जिल्हा लातूर, महाराष्ट्र शासन</h4>
                <h5 class="mb-0 text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">District Latur, Government of Maharashtra</h5>
                <span class="text-secondary font-monospace" style="font-size: 11px;">Collectorate Institutional Inter-Departmental Monitoring Portal</span>
            </div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-gov shadow-sm">
  <div class="container">
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#govNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="govNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (isset($_SESSION['role'])): ?>
            <li class="nav-item">
                <a class="nav-link px-3" href="<?php echo $base_url; ?>index.php">🏛️ Official Dashboard</a>
            </li>
            <?php if ($_SESSION['role'] === 'Organizer'): ?>
                <li class="nav-item">
                    <a class="nav-link px-3" href="<?php echo $base_url; ?>modules/meetings/create.php">📝 Schedule Action Review</a>
                </li>
            <?php endif; ?>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (isset($_SESSION['role'])): ?>
            <li class="nav-item">
                <span class="badge bg-warning text-dark px-3 py-2 me-3 font-monospace shadow-sm">
                    OFFICER: <?php echo htmlspecialchars($_SESSION['user_name']); ?> [<?php echo $_SESSION['role']; ?>]
                </span>
            </li>
            <li class="nav-item">
                <a class="btn btn-sm btn-danger fw-bold border-0" href="<?php echo $base_url; ?>controllers/LogoutController.php">Secure Exit</a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="btn btn-sm btn-warning fw-bold text-dark" href="<?php echo $base_url; ?>modules/users/login.php">Portal Login</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">