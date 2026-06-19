<?php
require_once '../config/db.php';
require_once '../middleware/auth.php';
authorize(['Employee']); // Restrict to employees only

$userId = $_SESSION['user_id'];
$tasks = $pdo->prepare("SELECT t.*, m.title as meeting_title FROM tasks t 
                        JOIN meetings m ON t.meeting_id = m.id 
                        WHERE t.assigned_to = ?");
$tasks->execute([$userId]);
?>

<div class="container py-5">
    <h3 class="fw-bold mb-4">My Assigned Tasks</h3>
    <table class="table card-gov bg-white">
        <thead><tr><th>Task</th><th>Deadline</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach($tasks as $t): ?>
            <tr>
                <td><?php echo $t['title']; ?> <br><small class="text-muted">Meeting: <?php echo $t['meeting_title']; ?></small></td>
                <td><?php echo $t['deadline']; ?></td>
                <td><span class="badge bg-info"><?php echo $t['status']; ?></span></td>
                <td><a href="../modules/tasks/update.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-dark">Update Status</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>