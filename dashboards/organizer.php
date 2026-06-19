<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Organizer Dashboard";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Fetch dynamic meeting count
$meeting_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM meetings");
$meeting_data = mysqli_fetch_assoc($meeting_query);
$total_meetings = $meeting_data['total'] ?? 0;

// Fetch employee count
$employee_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='employee'");
$employee_data = mysqli_fetch_assoc($employee_query);
$total_employees = $employee_data['total'] ?? 0;

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="organizer.php" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="../organizer/create_meeting.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-plus-circle"></i> Create Meeting
            </a>
            <a href="../organizer/view_meetings.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-calendar-alt"></i> View Meetings
            </a>
            <a href="../organizer/register_employee.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-user-plus"></i> Register Employee
            </a>
            <a href="../organizer/add_attendance.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-clipboard-check"></i> Mark Attendance
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-tasks"></i> Manage Tasks
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
                <p>You're logged in as an Organizer. Plan meetings, manage tasks, and coordinate with your team efficiently.</p>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card-item" onclick="window.location='../organizer/view_meetings.php'">
                    <div class="card-icon" style="background: #e0f7ff; color: #667eea;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="card-title">Meetings</div>
                    <div class="card-number" style="color: <?php echo $primary_color; ?>;"><?php echo $total_meetings; ?></div>
                    <div class="card-description">Total scheduled</div>
                </div>
                
                <div class="card-item" onclick="window.location='../organizer/create_meeting.php'">
                    <div class="card-icon" style="background: #e8f5e9; color: #4caf50;">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="card-title">New Meeting</div>
                    <div class="card-number" style="color: #4caf50;">+</div>
                    <div class="card-description">Create new schedule</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #fff3e0; color: #ff9800;">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="card-title">Pending Tasks</div>
                    <div class="card-number" style="color: #ff9800;">12</div>
                    <div class="card-description">Awaiting assignment</div>
                </div>
                
                <div class="card-item">
                    <div class="card-icon" style="background: #f3e5f5; color: #9c27b0;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="card-title">Reports</div>
                    <div class="card-number" style="color: #9c27b0;">View</div>
                    <div class="card-description">Attendance summary</div>
                </div>

                <div class="card-item" onclick="window.location='../organizer/register_employee.php'">
                    <div class="card-icon" style="background: #e8f5e9; color: #2e7d32;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="card-title">Employees</div>
                    <div class="card-number" style="color: #2e7d32;"><?php echo $total_employees; ?></div>
                    <div class="card-description">Register for meetings</div>
                </div>

                <div class="card-item" onclick="window.location='../organizer/add_attendance.php'">
                    <div class="card-icon" style="background: #e1f5fe; color: #0288d1;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="card-title">Mark Attendance</div>
                    <div class="card-number" style="color: #0288d1;">GO</div>
                    <div class="card-description">Daily records</div>
                </div>
            </div>

            <!-- Recent Meetings Table -->
            <div class="card mt-4 border-0 shadow-sm" style="border-radius: 10px;">
                <div class="card-header bg-white py-3" style="border-radius: 10px 10px 0 0;">
                    <h5 class="mb-0 fw-bold" style="color: <?php echo $secondary_color; ?>;">
                        <i class="fas fa-clock me-2"></i> Recently Created Meetings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Meeting Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Department</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_meetings = mysqli_query($conn, "SELECT * FROM meetings ORDER BY id DESC LIMIT 5");
                                while($meeting = mysqli_fetch_assoc($recent_meetings)):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($meeting['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($meeting['meeting_time'])); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($meeting['department']); ?></span></td>
                                    <td class="text-nowrap">
                                        <a href="../organizer/edit_meeting.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit Meeting">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../organizer/reschedule_meeting.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Reschedule Meeting">
                                            <i class="fas fa-calendar-alt"></i>
                                        </a>
                                        <a href="../organizer/delete_meeting.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Meeting" onclick="return confirm('Are you sure you want to delete this meeting?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>
