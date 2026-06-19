<?php
// dashboards/organizer.php
require_once '../config/db.php';
require_once '../middleware/auth.php';

// Ensure only Organizers access this page 
authorize(['Organizer']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard | Latur Admin Sync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: #1e293b; color: white; min-height: 100vh; }
        .card-gov { border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table-header { background: #f8fafc; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar p-3">
            <h5 class="fw-bold mb-4">LATUR SYNC</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link text-white" href="#">Meetings</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="#">Tasks</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../controllers/LogoutController.php">Logout</a></li>
            </ul>
        </nav>

        <main class="col-md-10 p-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold text-dark">Executive Meeting Log</h3>
                <a href="../modules/meetings/create.php" class="btn btn-dark">+ Create Meeting</a>
            </div>

            <div class="card-gov p-4 bg-white">
                <table class="table align-middle">
                    <thead class="table-header">
                        <tr>
                            <th>Subject</th>
                            <th>Date & Time</th>
                            <th>Mode</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM meetings WHERE isDeleted = 'No' ORDER BY meeting_date DESC");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>
                                    <td class='fw-bold'>{$row['title']}</td>
                                    <td>{$row['meeting_date']} <br><small class='text-muted'>{$row['meeting_time']}</small></td>
                                    <td><span class='badge bg-light text-dark border'>{$row['mode']}</span></td>
                                    <td><button class='btn btn-sm btn-outline-primary' onclick='viewMeeting({$row['id']})'>View Details</button></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

</body>
</html>