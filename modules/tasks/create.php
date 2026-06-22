<?php
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
$meetingRes = $conn->query('SELECT id, title FROM meetings ORDER BY meeting_date DESC');
$meetings = $meetingRes->fetch_all(MYSQLI_ASSOC);

$userRes = $conn->query('SELECT id, name, email FROM users WHERE isDeleted = "No" ORDER BY name');
$users = $userRes->fetch_all(MYSQLI_ASSOC);
?>

<div class="row justify-content-center my-5">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header text-white fw-bold py-3" style="background: linear-gradient(90deg, #003366, #0055aa);">
                Create New Task
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/TaskController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Meeting</label>
                            <select name="meeting_id" class="form-select rounded-3" required>
                                <option value="">Select meeting</option>
                                <?php foreach ($meetings as $meeting): ?>
                                    <option value="<?php echo (int)$meeting['id']; ?>"><?php echo htmlspecialchars($meeting['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Task Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Assigned To</label>
                            <select name="assigned_to" class="form-select rounded-3" required>
                                <option value="">Select user</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo (int)$user['id']; ?>"><?php echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['email']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Due Date</label>
                            <input type="date" name="due_date" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Priority</label>
                            <select name="priority" class="form-select rounded-3" required>
                                <option value="">Select</option>
                                <option>Low</option>
                                <option>Medium</option>
                                <option>High</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Notes</label>
                            <textarea name="notes" class="form-control rounded-3" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3" >Save Task</button>
                        <a href="../../index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
