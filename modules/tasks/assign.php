<?php
require_once '../../config/db.php';
require_once '../../middleware/auth.php';
authorize(['Organizer']);

$mId = $_GET['meeting_id'];
?>

<div class="container py-4">
    <div class="card-gov p-4 shadow-sm">
        <h4 class="fw-bold mb-3">Assign New Task</h4>
        <form action="process_task.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="meeting_id" value="<?php echo $mId; ?>">
            
            <div class="mb-3">
                <label class="small fw-bold">Task Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="small fw-bold">Assign To</label>
                <select name="assigned_to" class="form-select">
                    <?php 
                    $users = $pdo->query("SELECT id, name FROM users");
                    foreach($users as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>";
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="small fw-bold">Deadline</label>
                <input type="date" name="deadline" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-dark w-100">Commit Task</button>
        </form>
    </div>
</div>