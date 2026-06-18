<?php
include("../includes/auth_check.php");

// Set page variables
$page_title = "Employee Dashboard";
$navbar_gradient = "linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)";
$primary_color = "#00f2fe";
$secondary_color = "#4facfe";

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="#" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-calendar-alt"></i> My Schedule
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-tasks"></i> My Tasks
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-users"></i> My Team
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
                <p>You're logged in as an Employee. Check your schedule and complete your tasks.</p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card-item">
                    <div class="card-icon" style="background: #e0f7ff; color: #00f2fe;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-title">Scheduled</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">6</div>
                    <div class="card-description">Upcoming meetings</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #e3f2fd; color: #4facfe;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="card-title">My Tasks</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">10</div>
                    <div class="card-description">Assigned to you</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #e0f2f1; color: #26c6da;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="card-title">Completed</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">35</div>
                    <div class="card-description">Total completed</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #f3e5f5; color: #512da8;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="card-title">Progress</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">78%</div>
                    <div class="card-description">Overall completion</div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>