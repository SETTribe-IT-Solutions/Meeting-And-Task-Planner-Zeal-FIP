$id = $_GET['id'];

mysqli_query(
$conn,
"DELETE FROM meetings WHERE id=$id"
);

header("Location:view_meetings.php");