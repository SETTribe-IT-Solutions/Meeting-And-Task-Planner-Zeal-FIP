<?php
// database/backup.php — Full DB export into this folder
// Access: Organizer login required
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

if (!isLoggedIn() || !isOrganizer()) {
    header('Location: ../modules/users/login.php');
    exit();
}

$conn   = getDBConnection();
$outFile = __DIR__ . '/meeting_planner_backup.sql';
$error  = '';
$done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql  = "-- ============================================================\n";
    $sql .= "-- Meeting Planner — Full Data Backup\n";
    $sql .= "-- Generated : " . date('Y-m-d H:i:s') . " (Asia/Kolkata)\n";
    $sql .= "-- By        : " . htmlspecialchars($_SESSION['name'] ?? 'Organizer') . "\n";
    $sql .= "-- ============================================================\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    // Dump each table in a safe order
    $tableOrder = ['departments', 'users', 'meetings', 'attendance', 'tasks', 'task_assignments', 'meeting_translations'];
    $allTables  = [];
    $res = $conn->query("SHOW TABLES");
    while ($r = $res->fetch_row()) { $allTables[] = $r[0]; }

    // Add any extra tables not in the predefined order
    foreach ($allTables as $t) {
        if (!in_array($t, $tableOrder)) $tableOrder[] = $t;
    }

    foreach ($tableOrder as $table) {
        if (!in_array($table, $allTables)) continue;

        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Table: `$table`\n";
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "TRUNCATE TABLE `$table`;\n";

        $rows = $conn->query("SELECT * FROM `$table`");
        if ($rows && $rows->num_rows > 0) {
            while ($row = $rows->fetch_assoc()) {
                $vals = array_map(function ($v) use ($conn) {
                    return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $vals) . ");\n";
            }
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql .= "-- ============================================================\n";
    $sql .= "-- End of backup\n";
    $sql .= "-- ============================================================\n";

    if (file_put_contents($outFile, $sql) === false) {
        $error = 'Could not write backup file. Check folder permissions.';
    } else {
        $done = true;
    }
}

$backupExists = file_exists($outFile);
$backupDate   = $backupExists ? date('d M Y, h:i A', filemtime($outFile)) : null;
$backupSize   = $backupExists ? round(filesize($outFile) / 1024, 1) . ' KB' : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DB Backup — Meeting Planner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body { background: #f0f4f8; font-family: 'Segoe UI', sans-serif; }
.tool-card { max-width: 560px; margin: 60px auto; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
.tool-header { background: linear-gradient(135deg, #0b3d5f, #1a5f7a); color: #fff; padding: 28px 32px; }
.tool-body { background: #fff; padding: 32px; }
</style>
</head>
<body>
<div class="tool-card">
    <div class="tool-header">
        <h4 class="fw-bold mb-1"><i class="fas fa-database me-2 text-warning"></i>Database Backup</h4>
        <p class="mb-0 opacity-75 small">Exports all data into <code>database/meeting_planner_backup.sql</code></p>
    </div>
    <div class="tool-body">
        <?php if ($done): ?>
            <div class="alert alert-success rounded-3">
                <i class="fas fa-check-circle me-2"></i><strong>Backup saved successfully!</strong><br>
                <small class="text-muted">File: <code>database/meeting_planner_backup.sql</code></small>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger rounded-3"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($backupExists): ?>
            <div class="alert alert-info rounded-3 py-2 small mb-4">
                <i class="fas fa-info-circle me-1"></i>
                Last backup: <strong><?php echo $backupDate; ?></strong> &nbsp;|&nbsp; Size: <strong><?php echo $backupSize; ?></strong>
            </div>
        <?php endif; ?>

        <p class="text-muted small mb-4">
            This will dump all tables and data into a single SQL file inside the project folder.
            Copy the <strong>entire project folder</strong> to the new laptop — the backup file travels with it.
        </p>

        <form method="POST">
            <button type="submit" class="btn btn-primary rounded-3 px-4 w-100">
                <i class="fas fa-download me-2"></i>Run Backup Now
            </button>
        </form>

        <div class="mt-3 text-center">
            <a href="../index.php" class="btn btn-sm btn-outline-secondary rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Back to App
            </a>
        </div>
    </div>
</div>
</body>
</html>
