<?php
require_once '../../config/db.php';
require_once '../../middleware/auth.php';
authorize(['Organizer']);

$mId = $_GET['id'];
$meeting = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
$meeting->execute([$mId]);
$m = $meeting->fetch();
?>

<div class="container py-5">
    <h3>Managing: <?php echo $m['title']; ?></h3>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card-gov p-3">
                <h5>Mark Attendance</h5>
                <form action="process_attendance.php" method="POST">
                    <input type="hidden" name="meeting_id" value="<?php echo $mId; ?>">
                    <?php 
                    $users = $pdo->prepare("SELECT u.id, u.name FROM users u JOIN meeting_attendees ma ON u.id = ma.user_id WHERE ma.meeting_id = ?");
                    $users->execute([$mId]);
                    foreach($users as $user): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="present[]" value="<?php echo $user['id']; ?>">
                            <label class="form-check-label"><?php echo $user['name']; ?></label>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn btn-sm btn-dark mt-2">Save Attendance</button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-gov p-3">
                <h5>Log Discussion Points (MoM)</h5>
                <form action="process_mom.php" method="POST">
                    <input type="hidden" name="meeting_id" value="<?php echo $mId; ?>">
                    <input type="text" name="title" class="form-control mb-2" placeholder="Point Title">
                    <textarea name="description" class="form-control mb-2" placeholder="Detail..."></textarea>
                    <button class="btn btn-sm btn-primary">Log Point</button>
                </form>
            </div>
        </div>
    </div>
</div>