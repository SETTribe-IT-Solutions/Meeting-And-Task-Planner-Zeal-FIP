<?php
include("../includes/auth_check.php");

// Set page variables
$page_title = "Collector Dashboard";
$navbar_gradient = "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)";
$primary_color = "#f5576c";
$secondary_color = "#f093fb";

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="#" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-folder-open"></i> Collections
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-file-alt"></i> Reports
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-users"></i> Team
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <!-- Welcome Section -->
            <div class="welcome-section" style="background: <?php echo $navbar_gradient; ?>;">
                <h1><i class="fas fa-wave-hand"></i> Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
                <p>You're logged in as a Collector. View and manage your collection data.</p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card-item">
                    <div class="card-icon" style="background: #ffe0e6; color: #f5576c;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="card-title">Collections</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">5</div>
                    <div class="card-description">Active collections</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #fce0f3; color: #f093fb;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-title">Reports</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">12</div>
                    <div class="card-description">Submitted reports</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #e3f2fd; color: #2196F3;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-title">Completed</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">28</div>
                    <div class="card-description">This month</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #fff9c4; color: #fbc02d;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="card-title">Pending</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">3</div>
                    <div class="card-description">Awaiting review</div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>