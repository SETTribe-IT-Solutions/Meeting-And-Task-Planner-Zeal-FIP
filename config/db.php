<?php

$conn = mysqli_connect(
    'localhost',
    'root',
    '',
    'meeting_task_database'
);

if (!$conn) {
    die('Connection Failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>