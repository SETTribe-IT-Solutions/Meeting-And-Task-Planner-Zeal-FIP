<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Mark Attendance";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

$meeting_id = isset($_GET['meeting_id']) ? intval($_GET['meeting_id']) : 0;

// Fetch meeting info
$meeting_stmt = mysqli_prepare($conn, "SELECT title, meeting_date FROM meetings WHERE id = ?");
mysqli_stmt_bind_param($meeting_stmt, "i", $meeting_id);
mysqli_stmt_execute($meeting_stmt);
$meeting_info = mysqli_fetch_assoc(mysqli_stmt_get_result($meeting_stmt));

// Fetch attendees
$query = "SELECT u.id, u.name, a.status 
          FROM meeting_attendees ma 
          INNER JOIN users u ON ma.user_id = u.id 
          LEFT JOIN attendance a ON ma.meeting_id = a.meeting_id AND u.id = a.user_id 
          WHERE ma.meeting_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $meeting_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

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
                        <i class="fas fa-clipboard-check text-primary me-2"></i> Mark Attendance
                    </h2>
                    <div class="d-flex align-items-center">
                        <div class="text-end me-3">
                            <h5 class="mb-0 text-muted"><?= htmlspecialchars($meeting_info['title']) ?></h5>
                            <small class="text-primary fw-bold"><?= date('M d, Y', strtotime($meeting_info['meeting_date'])) ?></small>
                        </div>
                        <a href="add_attendees.php?meeting_id=<?= $meeting_id ?>" class="btn btn-primary btn-sm shadow-sm" style="background: <?php echo $navbar_gradient; ?>; border: none;">
                            <i class="fas fa-user-plus me-1"></i> Add Attendee
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <form action="save_attendance.php" method="POST">
                            <input type="hidden" name="meeting_id" value="<?php echo $meeting_id; ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Employee Name</th>
                                            <th class="text-center">
                                                Present
                                                <button type="button" class="btn btn-xs btn-link p-0 ms-1 text-success" id="markAllPresent" title="Mark All Present"><i class="fas fa-check-double"></i></button>
                                            </th>
                                            <th class="text-center">Absent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user text-muted"></i>
                                                    </div>
                                                    <span class="fw-medium"><?php echo htmlspecialchars($row['name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="status[<?php echo $row['id']; ?>]" value="Present" <?= (isset($row['status']) && $row['status'] === 'Present') ? 'checked' : '' ?> required>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="status[<?php echo $row['id']; ?>]" value="Absent" <?= (isset($row['status']) && $row['status'] === 'Absent') ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if(mysqli_num_rows($result) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5">
                                                <i class="fas fa-user-slash fa-3x mb-3 text-muted opacity-25"></i>
                                                <p class="text-muted mb-0">No attendees assigned to this meeting yet.</p>
                                                <a href="add_attendees.php?meeting_id=<?= $meeting_id ?>" class="btn btn-sm btn-outline-primary mt-3">Assign Attendees Now</a>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if(mysqli_num_rows($result) > 0): ?>
                            <div class="p-4 text-center border-top">
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                    <i class="fas fa-save me-2"></i> Save Attendance
                                </button>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<script>
document.getElementById('markAllPresent').addEventListener('click', function() {
    const presentRadios = document.querySelectorAll('input[type="radio"][value="Present"]');
    presentRadios.forEach(radio => {
        radio.checked = true;
    });
});
</script>
<?php include("../includes/footer.php"); ?>