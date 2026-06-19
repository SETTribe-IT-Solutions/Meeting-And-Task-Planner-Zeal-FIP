<?php
// modules/users/profile.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';
include_once '../../includes/header.php';

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    echo "<div class='alert alert-danger'>User data not found.</div>";
    include_once '../../includes/footer.php';
    exit();
}
?>

<div class="row justify-content-center my-5">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="text-white p-4 text-center" style="background: linear-gradient(135deg, #0b3d5f 0%, #1e5c83 100%);">
                <i class="fas fa-user-circle fa-4x mb-2 text-warning"></i>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($userData['name']); ?></h4>
                <small class="text-white-50"><?php echo htmlspecialchars($userData['role']); ?> | Latur Admin</small>
            </div>
            <div class="card-body p-4 bg-white">
                <div class="mb-4">
                    <h5 class="fw-bold text-gov-blue border-bottom pb-2 mb-3">Official Credentials</h5>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-0">Official Email</label>
                            <div class="fw-semibold text-dark fs-6"><?php echo htmlspecialchars($userData['email']); ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-0">Department / Wing</label>
                            <div class="fw-semibold text-dark fs-6"><?php echo htmlspecialchars($userData['department']); ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary mb-0">Security Role</label>
                            <div class="fw-semibold text-dark fs-6">
                                <span class="badge bg-primary-subtle text-primary"><?php echo htmlspecialchars($userData['role']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <a href="../../index.php" class="btn btn-outline-secondary rounded-3 w-100">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
