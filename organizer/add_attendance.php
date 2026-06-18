<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Record Attendance";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Fetch meetings for selection
$meetings_result = mysqli_query($conn, "SELECT id, title, meeting_date FROM meetings ORDER BY meeting_date DESC, meeting_time DESC");

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
            <a href="add_attendance.php" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
                <i class="fas fa-clipboard-check"></i> Mark Attendance
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
                        <i class="fas fa-check-double text-primary me-2"></i> Attendance Selection
                    </h2>
                    <a href="attendance_report.php" class="btn btn-outline-primary btn-sm shadow-sm">
                        <i class="fas fa-file-invoice me-1"></i> View Full Report
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <div class="avatar-lg bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                    </div>
                                    <h4>Record Attendance</h4>
                                    <p class="text-muted">Choose a meeting below to start marking attendance for your team members.</p>
                                </div>

                                <form action="attendance_form.php" method="GET">
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Select Meeting</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-calendar-check text-primary"></i>
                                            </span>
                                            <select name="meeting_id" class="form-select bg-light border-start-0" required>
                                                <option value="">Choose a scheduled meeting...</option>
                                                <?php while($meeting = mysqli_fetch_assoc($meetings_result)): ?>
                                                <option value="<?php echo $meeting['id']; ?>">
                                                    <?php echo htmlspecialchars($meeting['title']); ?> (<?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-text small">Only meetings with assigned attendees will show participant lists.</div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <hr class="mb-4">
                                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                            Start Marking <i class="fas fa-chevron-right ms-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Helpful Tip -->
                        <div class="mt-4 p-3 bg-white rounded-3 shadow-sm border-start border-primary border-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lightbulb text-warning me-3 fa-lg"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Pro Tip</h6>
                                    <p class="mb-0 text-muted small">You can also mark attendance directly from the <strong>View Meetings</strong> list or the <strong>Attendees List</strong>.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>