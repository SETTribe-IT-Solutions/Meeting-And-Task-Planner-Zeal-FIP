<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Attendance Report";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

$query = "SELECT m.title, u.name, a.status, a.arrival_time FROM attendance a 
          INNER JOIN meetings m ON a.meeting_id=m.id 
          INNER JOIN users u ON a.user_id=u.id 
          ORDER BY a.arrival_time DESC";
$result = mysqli_query($conn, $query);

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="../dashboards/organizer.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="create_meeting.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-plus-circle"></i> Create Meeting
            </a>
            <a href="view_meetings.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-calendar-alt"></i> View Meetings
            </a>
            <a href="#" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-tasks"></i> Manage Tasks
            </a>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark">
                        <i class="fas fa-file-invoice text-primary me-2"></i> Attendance Report
                    </h2>
                    <a href="../dashboards/organizer.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Meeting Title</th>
                                        <th>Employee</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Arrival/Marked Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['title']); ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle text-muted me-2"></i>
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'Present'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 rounded-pill">Present</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 rounded-pill">Absent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4 text-muted">
                                            <small><?php echo date('M d, Y h:i A', strtotime($row['arrival_time'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No attendance data recorded yet.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>