<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Register Employee";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

$preselected_meeting_id = isset($_GET['meeting_id']) ? intval($_GET['meeting_id']) : 0;
$meetings_result = mysqli_query($conn, "SELECT id, title, meeting_date, meeting_time FROM meetings ORDER BY meeting_date DESC, meeting_time DESC");

$success_messages = [
    'employee_added' => 'Employee registered successfully.',
    'employee_added_to_meeting' => 'Employee registered and added to the selected meeting successfully.'
];

$error_messages = [
    'invalid_request' => 'Invalid request. Please submit the form again.',
    'missing_data' => 'Please fill in all required fields.',
    'invalid_name' => 'Employee name must be at least 2 characters.',
    'invalid_email' => 'Please enter a valid email address.',
    'invalid_department' => 'Department must be 100 characters or less.',
    'duplicate_email' => 'An employee with this email already exists.',
    'invalid_password' => 'Password must be at least 6 characters.',
    'password_mismatch' => 'Password and confirm password do not match.',
    'invalid_meeting' => 'Selected meeting was not found.',
    'save_failed' => 'Unable to register employee right now. Please try again.'
];

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
            <a href="register_employee.php" class="sidebar-item active" style="background: <?php echo $navbar_gradient; ?>; color: white;">
                <i class="fas fa-user-plus"></i> Register Employee
            </a>
            <a href="add_attendance.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
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
                        <i class="fas fa-user-plus text-primary me-2"></i> Employee Registration
                    </h2>
                    <a href="add_attendees.php<?php echo $preselected_meeting_id > 0 ? '?meeting_id=' . $preselected_meeting_id : ''; ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="fas fa-users me-1"></i> Add Attendees
                    </a>
                </div>

                <?php if (isset($_GET['msg']) && isset($success_messages[$_GET['msg']])): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_messages[$_GET['msg']]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && isset($error_messages[$_GET['error']])): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_messages[$_GET['error']]); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="card-body p-4">
                                <form action="save_employee.php" method="POST" id="employeeForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Employee Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-user text-primary"></i>
                                                </span>
                                                <input type="text" name="name" class="form-control bg-light border-start-0" placeholder="Enter full name" minlength="2" maxlength="100" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-envelope text-primary"></i>
                                                </span>
                                                <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="employee@example.com" maxlength="100" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Department</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-building text-primary"></i>
                                                </span>
                                                <input type="text" name="department" class="form-control bg-light border-start-0" placeholder="IT, HR, Operations, etc." maxlength="100">
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Add to Meeting</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-calendar-check text-primary"></i>
                                                </span>
                                                <select name="meeting_id" class="form-select bg-light border-start-0">
                                                    <option value="0">Register employee only</option>
                                                    <?php while($meeting = mysqli_fetch_assoc($meetings_result)): ?>
                                                        <option value="<?php echo $meeting['id']; ?>" <?php echo ($preselected_meeting_id === intval($meeting['id'])) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($meeting['title']); ?> (<?php echo date('M d, Y', strtotime($meeting['meeting_date'])); ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-lock text-primary"></i>
                                                </span>
                                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0" placeholder="Minimum 6 characters" minlength="6" required>
                                                <button type="button" class="btn btn-outline-secondary" id="togglePassword" title="Show password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Confirm Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0">
                                                    <i class="fas fa-shield-alt text-primary"></i>
                                                </span>
                                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control bg-light border-start-0" placeholder="Repeat password" minlength="6" required>
                                            </div>
                                        </div>

                                        <div class="col-md-12 text-end">
                                            <hr class="mb-4">
                                            <a href="view_meetings.php" class="btn btn-outline-secondary px-4 me-2">
                                                Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 8px; background: <?php echo $navbar_gradient; ?>; border: none;">
                                                <i class="fas fa-save me-2"></i> Register Employee
                                            </button>
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
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');

    togglePassword.addEventListener('click', function() {
        const isPassword = password.type === 'password';
        password.type = isPassword ? 'text' : 'password';
        confirmPassword.type = isPassword ? 'text' : 'password';
        this.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        this.title = isPassword ? 'Hide password' : 'Show password';
    });
});
</script>
<?php include("../includes/footer.php"); ?>
