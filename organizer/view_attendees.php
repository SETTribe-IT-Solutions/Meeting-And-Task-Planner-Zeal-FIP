<?php
include("../includes/auth_check.php");
include("../config/db.php");

// Set page variables
$page_title = "Meeting Attendees List";
$navbar_gradient = "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
$primary_color = "#667eea";
$secondary_color = "#764ba2";

// Fetch attendee list with meeting details and user info
$query = "
SELECT
    ma.id,
    m.title,
    m.id AS meeting_id,
    m.meeting_date,
    u.name,
    u.email
FROM meeting_attendees ma
INNER JOIN meetings m
ON ma.meeting_id = m.id
INNER JOIN users u
ON ma.user_id = u.id
ORDER BY ma.id DESC
";

$result = mysqli_query($conn,$query);

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
            <a href="register_employee.php" class="sidebar-item" style="color: <?php echo $primary_color; ?>;">
                <i class="fas fa-user-plus"></i> Register Employee
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
                        <i class="fas fa-users text-primary me-2"></i> Meeting Attendees
                    </h2>
                    <div>
                        <a href="../dashboards/organizer.php" class="btn btn-outline-secondary btn-sm shadow-sm me-2">
                            <i class="fas fa-arrow-left me-1"></i> Dashboard
                        </a>
                        <a href="register_employee.php" class="btn btn-outline-primary btn-sm shadow-sm me-2">
                            <i class="fas fa-user-plus me-1"></i> Register Employee
                        </a>
                        <a href="add_attendees.php" class="btn btn-primary btn-sm shadow-sm" style="background: <?php echo $navbar_gradient; ?>; border: none;">
                            <i class="fas fa-user-plus me-1"></i> Add New Attendees
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'attendees_added'): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i> Attendees added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Meeting</th>
                                        <th>Attendee Name</th>
                                        <th>Email Address</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($row['meeting_date'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user text-muted small"></i>
                                                </div>
                                                <span><?php echo htmlspecialchars($row['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="text-end pe-4">
                                            <a href="attendance_form.php?meeting_id=<?php echo $row['meeting_id']; ?>" class="btn btn-sm btn-outline-success border-0" title="Mark Attendance">
                                                <i class="fas fa-clipboard-check"></i>
                                            </a>
                                            <a href="delete_attendee.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger border-0" title="Remove Attendee" onclick="return confirm('Are you sure you want to remove this attendee from the meeting?')">
                                                <i class="fas fa-user-minus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0">No attendee assignments found.</p>
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
