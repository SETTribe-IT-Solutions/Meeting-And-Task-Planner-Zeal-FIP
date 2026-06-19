<?php
require_once '../../config/db.php';
$tId = $_GET['id'];
$task = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$task->execute([$tId]);
$t = $task->fetch();
?>

<div class="container py-5">
    <div class="card-gov p-4" style="max-width: 500px;">
        <h4 class="mb-3">Update: <?php echo $t['title']; ?></h4>
        <form action="process_update.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $tId; ?>">
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="Pending" <?php if($t['status']=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="In Progress" <?php if($t['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
                    <option value="Completed" <?php if($t['status']=='Completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Progress Notes</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-dark w-100">Save Update</button>
        </form>
    </div>
</div>