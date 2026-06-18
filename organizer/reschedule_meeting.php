<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Reschedule Meeting";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Get Meeting ID safely
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Meeting Details using Prepared Statements
$stmt = mysqli_prepare($conn, "SELECT * FROM meetings WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$meeting = mysqli_fetch_assoc($result);

if (!$meeting) {
    header("Location: view_meetings.php");
    exit();
}

// Update Meeting Schedule
if (isset($_POST['reschedule'])) {
    $new_date = $_POST['meeting_date'];
    $new_time = $_POST['meeting_time'];

    $update_stmt = mysqli_prepare($conn, "UPDATE meetings SET meeting_date = ?, meeting_time = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "ssi", $new_date, $new_time, $id);

    if (mysqli_stmt_execute($update_stmt)) {
        echo "<script>
                alert('Meeting Rescheduled Successfully');
                window.location='view_meetings.php';
              </script>";
        exit();
    }
}

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
                        <i class="fas fa-calendar-day text-primary me-2"></i> Reschedule Meeting
                    </h2>
                    <a href="view_meetings.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <div class="alert alert-info border-0 shadow-sm mb-4" style="border-radius: 10px;">
                                    <h5 class="alert-heading fw-bold mb-2">
                                        <i class="fas fa-info-circle me-2"></i> Current Schedule
                                    </h5>
                                    <p class="mb-1"><strong>Meeting:</strong> <?= htmlspecialchars($meeting['title']) ?></p>
                                    <p class="mb-1"><strong>Current Date:</strong> <?= date('M d, Y', strtotime($meeting['meeting_date'])) ?></p>
                                    <p class="mb-0"><strong>Current Time:</strong> <?= date('h:i A', strtotime($meeting['meeting_time'])) ?></p>
                                </div>

                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">New Meeting Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="far fa-calendar-alt text-primary"></i></span>
                                                <input type="date" name="meeting_date" class="form-control bg-light border-start-0" min="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">New Meeting Time</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="far fa-clock text-primary"></i></span>
                                                <input type="time" name="meeting_time" class="form-control bg-light border-start-0" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <hr class="mb-4">
                                        <button type="submit" name="reschedule" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                            <i class="fas fa-save me-2"></i> Update Schedule
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>