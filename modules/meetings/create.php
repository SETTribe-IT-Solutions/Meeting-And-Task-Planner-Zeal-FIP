<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Organizer') {
    header('Location: ../users/login.php');
    exit();
}

include_once '../../includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header text-white fw-bold py-3" style="background: linear-gradient(90deg, #003366, #0055aa);">
                Create New Meeting
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/MeetingController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Meeting Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Date</label>
                            <input type="date" name="meeting_date" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Time</label>
                            <input type="time" name="meeting_time" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Location</label>
                            <input type="text" name="location" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Mode</label>
                            <select name="mode" class="form-select rounded-3" required>
                                <option value="">Select mode</option>
                                <option>Offline</option>
                                <option>Online</option>
                                <option>Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Department</label>
                            <input type="text" name="department" class="form-control rounded-3" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Agenda</label>
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3" style="background-color: var(--gov-blue);">Save Meeting</button>
                        <a href="../../index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
