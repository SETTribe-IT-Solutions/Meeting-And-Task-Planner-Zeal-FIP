<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Create New Meeting";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Include header
include("../includes/header.php");
?>

            <!-- Sidebar Items -->
            <a href="../dashboards/organizer.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="create_meeting.php" class="sidebar-item active" style="background: linear-gradient(135deg, <?php echo $navbar_gradient; ?>); color: white;">
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
                        <i class="fas fa-calendar-plus text-primary me-2"></i> Schedule New Meeting
                    </h2>
                    <a href="view_meetings.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-4">
                        <form action="save_meeting.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Meeting Title -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Meeting Title</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-heading text-primary"></i></span>
                                        <input type="text" name="title" class="form-control bg-light border-start-0" placeholder="e.g. Weekly Strategy Sync-up" required>
                                    </div>
                                </div>

                                <!-- Date and Time -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Date</label>
                                    <input type="date" name="meeting_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Time</label>
                                    <input type="time" name="meeting_time" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Duration (Minutes)</label>
                                    <input type="number" name="duration" class="form-control" placeholder="e.g. 60">
                                </div>

                                <!-- Mode and Location -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Meeting Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="Online">Online</option>
                                        <option value="Offline">Offline</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label fw-bold">Location or Meeting Link</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-map-marker-alt text-primary"></i></span>
                                        <input type="text" name="location_link" class="form-control bg-light border-start-0" placeholder="Room 302 or Zoom Link">
                                    </div>
                                </div>

                                <!-- Department -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Department</label>
                                    <input type="text" name="department" class="form-control" placeholder="IT, HR, Operations, etc.">
                                </div>

                                <!-- Attachment -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Attachment (Optional)</label>
                                    <input type="file" name="attachment" class="form-control">
                                </div>

                                <!-- Employees Selection -->
                                <div class="col-md-12 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">Select Employees (Attendees)</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label small" for="selectAll">Select All</label>
                                        </div>
                                    </div>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" id="empSearch" class="form-control bg-light border-start-0" placeholder="Search employees...">
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-users text-primary"></i></span>
                                        <select name="users[]" id="employeeSelect" class="form-select bg-light border-start-0" multiple style="height: 150px;">
                                            <?php 
                                            $employees_result = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role='employee' ORDER BY name ASC");
                                            while($emp = mysqli_fetch_assoc($employees_result)): 
                                            ?>
                                                <option value="<?= $emp['id'] ?>">
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
                                    <textarea name="agenda" class="form-control" rows="4" placeholder="Briefly describe the meeting goals and discussion points..."></textarea>
                                </div>

                                <div class="col-md-12 text-end">
                                    <hr class="mb-4">
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                        <i class="fas fa-save me-2"></i> Save Meeting
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('empSearch');
    const selectAllCheckbox = document.getElementById('selectAll');
    const employeeSelect = document.getElementById('employeeSelect');
    const options = Array.from(employeeSelect.options);

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        options.forEach(option => {
            const text = option.text.toLowerCase();
            option.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        options.forEach(option => {
            if (option.style.display !== 'none') {
                option.selected = isChecked;
            }
        });
    });
});
</script>
<?php include("../includes/footer.php"); ?>