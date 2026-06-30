<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'Organizer') {
    header('Location: login.php');
    exit();
}

$format = $_GET['format'] ?? 'csv'; // 'csv' or 'excel'
$conn   = getDBConnection();

$result = $conn->query(
    "SELECT name, department, email, role, isDeleted
     FROM users
     ORDER BY isDeleted ASC, role ASC, name ASC"
);
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$headers = ['User Name', 'Department', 'Email ID', 'Role', 'Status'];
$date    = date('Y-m-d');

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="users_export_' . $date . '.xls"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    // HTML table — Excel opens this natively as .xls
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<thead><tr>';
    foreach ($headers as $h) {
        echo '<th style="background:#0b3d5f;color:#ffffff;font-weight:bold;padding:6px 10px;">'
             . htmlspecialchars($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($users as $u) {
        $status = $u['isDeleted'] === 'Yes' ? 'Disabled' : 'Active';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($u['name'])       . '</td>';
        echo '<td>' . htmlspecialchars($u['department'])  . '</td>';
        echo '<td>' . htmlspecialchars($u['email'])       . '</td>';
        echo '<td>' . htmlspecialchars($u['role'])        . '</td>';
        echo '<td>' . $status                             . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit();
}

// Default: CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="users_export_' . $date . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel opens correctly

$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($users as $u) {
    fputcsv($out, [
        $u['name'],
        $u['department'],
        $u['email'],
        $u['role'],
        $u['isDeleted'] === 'Yes' ? 'Disabled' : 'Active',
    ]);
}
fclose($out);
exit();
