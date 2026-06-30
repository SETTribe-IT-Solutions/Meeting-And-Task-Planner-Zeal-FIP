<?php
// database/restore.php — Restores DB from backup file in this folder
// Safe to run on a fresh machine (DB may be empty — no login required in that case)
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

$backupFile = __DIR__ . '/meeting_planner_backup.sql';
$error      = '';
$done       = false;
$lines      = 0;

// Auth: if DB already has users, require Organizer login
$conn = getDBConnection();
$userCount = (int)($conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0);
$requireAuth = $userCount > 0;

if ($requireAuth && (!isLoggedIn() || !isOrganizer())) {
    header('Location: ../modules/users/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    if (!file_exists($backupFile)) {
        $error = 'Backup file not found. Run backup.php on the source machine first.';
    } else {
        $sql  = file_get_contents($backupFile);

        // Strip comment lines and blank lines, split by semicolon
        $raw      = preg_replace('/^[ \t]*--.*$/m', '', $sql);
        $raw      = preg_replace('/\/\*.*?\*\//s', '', $raw);
        $queries  = array_filter(array_map('trim', explode(';', $raw)));

        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($queries as $q) {
            if ($q === '') continue;
            if (!$conn->query($q)) {
                $error = 'Error at query: ' . htmlspecialchars(substr($q, 0, 120)) . '<br>MySQL: ' . htmlspecialchars($conn->error);
                break;
            }
            $lines++;
        }
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        if (!$error) $done = true;
    }
}

$backupExists = file_exists($backupFile);
$backupDate   = $backupExists ? date('d M Y, h:i A', filemtime($backupFile)) : null;
$backupSize   = $backupExists ? round(filesize($backupFile) / 1024, 1) . ' KB' : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DB Restore — Meeting Planner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
.tool-card { max-width: 560px; margin: 60px auto; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
.tool-header { background: linear-gradient(135deg, #7c3aed, #5b21b6); color: #fff; padding: 28px 32px; }
.tool-body { background: #fff; padding: 32px; }
</style>
</head>
<body>
<div class="tool-card">
    <div class="tool-header">
        <h4 class="fw-bold mb-1"><i class="fas fa-upload me-2 text-warning"></i>Database Restore</h4>
        <p class="mb-0 opacity-75 small">Imports data from <code>database/meeting_planner_backup.sql</code></p>
    </div>
    <div class="tool-body">
        <?php if ($done): ?>
            <div class="alert alert-success rounded-3">
                <i class="fas fa-check-circle me-2"></i><strong>Database restored successfully!</strong><br>
                <small class="text-muted"><?php echo $lines; ?> statements executed.</small>
            </div>
            <a href="../index.php" class="btn btn-primary rounded-3 px-4 w-100 mt-2">
                <i class="fas fa-home me-2"></i>Go to App
            </a>
        <?php elseif ($error): ?>
            <div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <a href="restore.php" class="btn btn-outline-secondary rounded-3 w-100 mt-2">Try Again</a>
        <?php elseif (!$backupExists): ?>
            <div class="alert alert-warning rounded-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>No backup file found.</strong><br>
                <small>Run <code>backup.php</code> on your source laptop first, then copy the entire project folder here.</small>
            </div>
        <?php else: ?>
            <div class="alert alert-info rounded-3 py-2 small mb-3">
                <i class="fas fa-file-alt me-1"></i>
                Backup found: <strong><?php echo $backupDate; ?></strong> &nbsp;|&nbsp; <strong><?php echo $backupSize; ?></strong>
            </div>
            <div class="alert alert-warning rounded-3 py-2 small mb-4">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>This will overwrite all existing data</strong> in the database. This cannot be undone.
            </div>
            <form method="POST" onsubmit="return confirm('Overwrite all current data with this backup?')">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-danger rounded-3 px-4 w-100">
                    <i class="fas fa-upload me-2"></i>Restore Database Now
                </button>
            </form>
            <div class="mt-3 text-center">
                <a href="../index.php" class="btn btn-sm btn-outline-secondary rounded-3">
                    <i class="fas fa-arrow-left me-1"></i> Back to App
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
