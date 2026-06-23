<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

include_once '../../includes/header.php';
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND isDeleted = 'No'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo '<div class="alert alert-danger rounded-3">User not found.</div>';
    include_once '../../includes/footer.php';
    exit();
}

// Get user stats
$role = $user['role'];
$today = date('Y-m-d');

if ($role === 'Collector' || $role === 'Organizer') {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM meetings WHERE organizer_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $totalMeetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks t JOIN meetings m ON t.meeting_id = m.id WHERE m.organizer_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $totalTasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
} else {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT m.id) as total FROM meetings m LEFT JOIN attendance a ON m.id = a.meeting_id WHERE m.department = ? OR a.user_id = ?");
    $stmt->bind_param("si", $user['department'], $user_id);
    $stmt->execute();
    $totalMeetings = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $totalTasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Role badge
$roleBadge = match($role) {
    'Collector' => 'badge-role badge-role-collector',
    'Organizer' => 'badge-role badge-role-organizer',
    'Employee' => 'badge-role badge-role-employee',
    default => 'bg-secondary'
};
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm overflow-hidden animate-on-scroll">
            <!-- Profile Header with Gradient -->
            <div class="profile-header-gradient text-white p-5 text-center position-relative" style="min-height: 200px;">
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('../../assets/image_e15bb67f.png') center/cover; opacity: 0.1;"></div>
                <div class="position-relative" style="z-index: 2;">
                    <div style="width: 90px; height: 90px; border-radius: 50%; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); display: inline-flex; align-items: center; justify-content: center; border: 3px solid rgba(255,255,255,0.5); margin-bottom: 1rem;">
                        <i class="fas fa-user" style="font-size: 2.5rem; color: white;"></i>
                    </div>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <span class="badge <?php echo $roleBadge; ?> px-4 py-2 mb-2" style="font-size: 0.85rem;"><?php echo htmlspecialchars($role); ?></span>
                    <p class="mb-0 mt-2" style="opacity: 0.85; font-size: 0.9rem;"><i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($user['department']); ?></p>
                </div>
            </div>

            <!-- Profile Body -->
            <div class="card-body p-4">
                <!-- Quick Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-4 text-center">
                        <div class="p-3 rounded-3" style="background: #eef6ff;">
                            <div class="fw-bold fs-3 counter-value" data-target="<?php echo $totalMeetings; ?>" style="color: var(--gov-blue);">0</div>
                            <div class="text-muted small fw-semibold">Meetings</div>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-3 rounded-3" style="background: #fef3c7;">
                            <div class="fw-bold fs-3 counter-value" data-target="<?php echo $totalTasks; ?>" style="color: #b45309;">0</div>
                            <div class="text-muted small fw-semibold">Tasks</div>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-3 rounded-3" style="background: #f0fdf4;">
                            <div class="fw-bold fs-3" style="color: #16a34a;">✓</div>
                            <div class="text-muted small fw-semibold">Active</div>
                        </div>
                    </div>
                </div>

                <!-- Info List -->
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                    <i class="fas fa-id-card text-primary me-2"></i> Official Details
                </h5>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0">
                        <span><i class="fas fa-user text-primary me-2" style="width: 20px;"></i> Full Name</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0">
                        <span><i class="fas fa-envelope text-primary me-2" style="width: 20px;"></i> Official Email</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0">
                        <span><i class="fas fa-building text-primary me-2" style="width: 20px;"></i> Department</span>
                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($user['department']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0">
                        <span><i class="fas fa-shield-alt text-primary me-2" style="width: 20px;"></i> Access Role</span>
                        <span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0">
                        <span><i class="fas fa-calendar-alt text-primary me-2" style="width: 20px;"></i> Joined</span>
                        <span class="fw-bold text-dark"><?php echo isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : 'N/A'; ?></span>
                    </div>
                </div>

                <!-- Change Password Section -->
                <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2" style="color: var(--gov-blue);">
                    <i class="fas fa-key text-primary me-2"></i> Change Password
                </h5>
                <form action="../../controllers/ProfileController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control rounded-3" required minlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3" required minlength="8">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-3 mt-3">
                        <i class="fas fa-save me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
