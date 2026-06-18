<?php
include("../includes/auth_check.php");

// Set page variables
$page_title = "Organizer Dashboard";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="#" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-calendar-alt"></i> Meetings
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-tasks"></i> Tasks
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
                <p>You're logged in as an Organizer. Manage your meetings and tasks effectively.</p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card-item">
                    <div class="card-icon" style="background: #e3f2fd; color: #2196F3;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-title">Meetings</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">8</div>
                    <div class="card-description">Scheduled this month</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #f3e5f5; color: #9c27b0;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="card-title">Tasks</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">15</div>
                    <div class="card-description">Pending completion</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #e8f5e9; color: #4caf50;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Team Members</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">12</div>
                    <div class="card-description">Active users</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #fff3e0; color: #ff9800;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="card-title">Completed</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;">42</div>
                    <div class="card-description">This quarter</div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>