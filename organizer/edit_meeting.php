<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Edit Meeting";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Get Meeting ID safely and fetch data using Prepared Statements
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = mysqli_prepare($conn, "SELECT * FROM meetings WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: view_meetings.php");
    exit();
}

// Fetch current attendees
$att_stmt = mysqli_prepare($conn, "SELECT user_id FROM meeting_attendees WHERE meeting_id = ?");
mysqli_stmt_bind_param($att_stmt, "i", $id);
mysqli_stmt_execute($att_stmt);
$att_res = mysqli_stmt_get_result($att_stmt);
$current_users = [];
while($att_row = mysqli_fetch_assoc($att_res)) {
    $current_users[] = $att_row['user_id'];
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
                        <i class="fas fa-edit text-primary me-2"></i> Edit Meeting Details
                    </h2>
                    <a href="view_meetings.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Cancel & Back
                    </a>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <form action="update_meeting.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $data['id'] ?>">
                            
                            <div class="row">
                                <!-- Meeting Title -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Meeting Title</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-heading text-primary"></i></span>
                                        <input type="text" name="title" class="form-control bg-light border-start-0" value="<?= htmlspecialchars($data['title']) ?>" required>
                                    </div>
                                </div>

                                <!-- Date and Time -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Date</label>
                                    <input type="date" name="meeting_date" class="form-control" value="<?= $data['meeting_date'] ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Time</label>
                                    <input type="time" name="meeting_time" class="form-control" value="<?= $data['meeting_time'] ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Duration (Minutes)</label>
                                    <input type="number" name="duration" class="form-control" value="<?= $data['duration'] ?>">
                                </div>

                                <!-- Mode and Location -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Meeting Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="Online" <?= $data['mode'] == 'Online' ? 'selected' : '' ?>>Online</option>
                                        <option value="Offline" <?= $data['mode'] == 'Offline' ? 'selected' : '' ?>>Offline</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label fw-bold">Location or Meeting Link</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                        <input type="text" name="location_link" class="form-control bg-light border-start-0" value="<?= htmlspecialchars($data['location_link']) ?>">
                                    </div>
                                </div>

                                <!-- Department -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Department</label>
                                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($data['department']) ?>">
                                </div>

                                <!-- Attachment -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Attachment (Optional)</label>
                                    <input type="file" name="attachment" class="form-control">
                                    <?php if(!empty($data['attachment'])): ?>
                                        <div class="form-text text-success"><i class="fas fa-paperclip me-1"></i> Current: <?= htmlspecialchars($data['attachment']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Employees Selection -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Select Employees (Attendees)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-users text-primary"></i></span>
                                        <select name="users[]" class="form-select bg-light border-start-0" multiple style="height: 150px;">
                                            <?php 
                                            $employees_result = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role='employee' ORDER BY name ASC");
                                            while($emp = mysqli_fetch_assoc($employees_result)): 
                                            ?>
                                                <option value="<?= $emp['id'] ?>" <?= in_array($emp['id'], $current_users) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['email']) ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple employees.</div>
                                </div>

                                <!-- Agenda -->
                                <div class="col-md-12 mb-4">
                                    <label class="form-label fw-bold">Agenda</label>
                                    <textarea name="agenda" class="form-control" rows="4"><?= htmlspecialchars($data['agenda']) ?></textarea>
                                </div>

                                <div class="col-md-12 text-end">
                                    <hr class="mb-4">
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                        <i class="fas fa-sync-alt me-2"></i> Update Meeting
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<?php include("../includes/footer.php"); ?>