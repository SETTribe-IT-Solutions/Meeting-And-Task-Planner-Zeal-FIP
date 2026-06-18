<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';
include_once '../../includes/header.php';
?>

        <div class="card p-4 border-0 mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Reports</h3>
                    <p class="text-muted mb-0">Review official meeting and task summaries.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary badge-status">Quarterly Overview</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card p-4 border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Meeting Summary</h5>
                        <button class="btn btn-sm btn-outline-primary" style="color: var(--gov-blue); border-color: var(--gov-blue);">Export Report</button>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Total Meetings</span><strong>12</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Completed</span><strong>9</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Upcoming</span><strong>3</strong></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Task Summary</h5>
                        <button class="btn btn-sm btn-primary" style="background-color: var(--gov-blue);">Generate PDF</button>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Assigned Tasks</span><strong>18</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Pending</span><strong>6</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Completed</span><strong>12</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Report Filter</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Search report..." style="width: 220px;">
                    <button class="btn btn-sm btn-success">Apply</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-gov-blue">
                    <thead style="background:#f7fbf8;">
                        <tr>
                            <th>Report Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Weekly Meeting Review</td>
                            <td>18 Jun 2026</td>
                            <td><span class="badge bg-success">Ready</span></td>
                            <td><button class="btn btn-sm btn-outline-primary">View</button></td>
                        </tr>
                        <tr>
                            <td>Employee Task Progress</td>
                            <td>15 Jun 2026</td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td><button class="btn btn-sm btn-outline-secondary">Review</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

<?php include_once '../../includes/footer.php'; ?>
