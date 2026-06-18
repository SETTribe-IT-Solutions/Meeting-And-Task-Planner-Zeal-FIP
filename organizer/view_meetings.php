<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "View Meetings";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Fetch meetings sorted by date
$result = mysqli_query($conn, "SELECT * FROM meetings ORDER BY meeting_date DESC, meeting_time DESC");

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
            <a href="view_meetings.php" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
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
                        <i class="fas fa-calendar-check text-primary me-2"></i> Scheduled Meetings
                    </h2>
                    <a href="create_meeting.php" class="btn btn-primary btn-sm shadow-sm" style="background: <?php echo $navbar_gradient; ?>; border: none;">
                        <i class="fas fa-plus me-1"></i> Create New Meeting
                    </a>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Meeting Details</th>
                                        <th>Date & Time</th>
                                        <th>Department</th>
                                        <th>Mode</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(substr($row['agenda'], 0, 60)) ?><?= strlen($row['agenda']) > 60 ? '...' : '' ?></small>
                                        </td>
                                        <td>
                                            <div class="text-dark"><i class="far fa-calendar-alt me-1 text-primary"></i> <?= date('M d, Y', strtotime($row['meeting_date'])) ?></div>
                                            <div class="small text-muted"><i class="far fa-clock me-1 text-primary"></i> <?= date('h:i A', strtotime($row['meeting_time'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle fw-medium px-3">
                                                <?= htmlspecialchars($row['department']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['mode'] == 'Online'): ?>
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle fw-medium px-3">
                                                    <i class="fas fa-video me-1"></i> Online
                                                </span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle fw-medium px-3">
                                                    <i class="fas fa-users me-1"></i> Offline
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="add_attendees.php?meeting_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary border-0" title="Add Attendees">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <a href="attendance_form.php?meeting_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success border-0" title="Mark Attendance">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
                                                <a href="edit_meeting.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning border-0" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="reschedule_meeting.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info border-0" title="Reschedule">
                                                    <i class="fas fa-calendar-day"></i>
                                                </a>
                                                <a href="delete_meeting.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0" title="Delete" onclick="return confirm('Are you sure you want to delete this meeting?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-calendar-times fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0">No meetings found. Start by creating one!</p>
                                        </td>
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