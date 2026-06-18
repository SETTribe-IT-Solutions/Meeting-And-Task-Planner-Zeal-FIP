<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Add Attendees";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Get meeting_id from GET request if available
$preselected_meeting_id = isset($_GET['meeting_id']) ? intval($_GET['meeting_id']) : 0;

// Fetch meetings and employees (using distinct variable names for results)
$meetings_result = mysqli_query($conn, "SELECT * FROM meetings ORDER BY meeting_date DESC, meeting_time DESC");
$employees_result = mysqli_query($conn, "SELECT * FROM users WHERE role='employee' ORDER BY name ASC");

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
                        <i class="fas fa-user-plus text-primary me-2"></i> Add Meeting Attendees
                    </h2>
                    <a href="view_meetings.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Meetings
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <form action="save_attendees.php" method="POST">
                                    <?php if ($preselected_meeting_id > 0): ?>
                                        <input type="hidden" name="meeting_id" value="<?= $preselected_meeting_id ?>">
                                    <?php endif; ?>
                                    <!-- Meeting Selection -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Select Meeting</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-calendar-check text-primary"></i>
                                            </span>
                                            <select name="<?= ($preselected_meeting_id > 0) ? 'meeting_id_display' : 'meeting_id' ?>" class="form-select bg-light border-start-0" <?php echo ($preselected_meeting_id > 0) ? 'disabled' : ''; ?> required>
                                                <option value="">Choose a scheduled meeting...</option>
                                                <?php while($meeting = mysqli_fetch_assoc($meetings_result)): ?>
                                                <option value="<?php echo $meeting['id']; ?>" <?php echo ($preselected_meeting_id == $meeting['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($meeting['title']); ?> (<?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-text">Choose the meeting you want to assign people to.</div>
                                    </div>

                                    <!-- Employee Selection -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0">Select Employees</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                                <label class="form-check-label small" for="selectAll">Select All</label>
                                            </div>
                                        </div>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" id="empSearch" class="form-control bg-light border-start-0" placeholder="Search employees by name or email...">
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-users text-primary"></i>
                                            </span>
                                            <select name="users[]" id="employeeSelect" multiple class="form-select bg-light border-start-0" style="height: 200px;" required>
                                                <?php while($emp = mysqli_fetch_assoc($employees_result)): ?>
                                                <option value="<?php echo $emp['id']; ?>">
                                                    <?php echo htmlspecialchars($emp['name']); ?> (<?php echo htmlspecialchars($emp['email']); ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-text">Hold down Ctrl (Windows) or Command (Mac) to select multiple employees.</div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="text-center mt-4">
                                        <hr class="mb-4">
                                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                            <i class="fas fa-save me-2"></i> Save Attendees
                                        </button>
                                        <div class="mt-3">
                                            <a href="view_attendees.php" class="text-decoration-none text-muted small">
                                                <i class="fas fa-eye me-1"></i> View existing attendee assignments
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
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

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        options.forEach(option => {
            const text = option.text.toLowerCase();
            option.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Select All functionality (only affects visible/filtered options)
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