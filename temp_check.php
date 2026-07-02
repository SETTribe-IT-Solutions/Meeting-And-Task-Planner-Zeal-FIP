<?php
require 'config/db.php';
$conn = getDBConnection();
$res = $conn->query('SHOW COLUMNS FROM meetings');
if (!$res) { echo 'show columns failed: ' . $conn->error . PHP_EOL; exit(1); }
while ($row = $res->fetch_assoc()) { echo $row['Field'] . PHP_EOL; }
