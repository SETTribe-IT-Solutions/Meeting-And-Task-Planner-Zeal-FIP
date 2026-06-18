<?php
// Header component with navbar and opening structure
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f5f7fa;
            margin: 0;
        }
        
        #navbar-style {
            background: <?php echo isset($navbar_gradient) ? $navbar_gradient : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?> !important;
        }
        
        .navbar {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3em;
        }
        
        .sidebar {
            background: white;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            min-height: 100vh;
            padding-top: 20px;
            position: sticky;
            top: 0;
        }
        
        .sidebar-item {
            padding: 15px 20px;
            margin: 10px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
            text-decoration: none;
            display: block;
        }
        
        .sidebar-item:hover {
            background: #f0f0f0;
            padding-left: 30px;
        }
        
        .sidebar-item.active {
            color: white;
        }
        
        .sidebar-item i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .welcome-section {
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        .welcome-section h1 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            font-size: 1.1em;
            opacity: 0.95;
            margin: 0;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card-item {
            background: white;
            border: none;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        
        .card-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .card-number {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .card-description {
            color: #999;
            font-size: 0.9em;
        }
        
        .footer {
            background: white;
            border-top: 1px solid #e0e0e0;
            padding: 20px 30px;
            margin-top: 40px;
            text-align: center;
            color: #999;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: relative;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark" id="navbar-style">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-calendar-check"></i> Meeting & Task Planner
            </span>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                </span>
                <a href="<?php echo isset($logout_path) ? htmlspecialchars($logout_path) : '../auth/logout.php'; ?>" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
