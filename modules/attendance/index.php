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

$attendanceStats = [
    'present' => 76,
    'pending' => 14,
    'absent' => 10,
];
?>

        <div class="card p-4 border-0 mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Attendance Records</h3>
                    <p class="text-muted mb-0">Track present, pending, and absent status for official meetings.</p>
                </div>
                <span class="badge bg-success-subtle text-success badge-status">Updated Today</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3 border-0 bg-success text-white stats-card">
                    <small>Present</small>
                    <h3 class="mb-0"><?php echo $attendanceStats['present']; ?>%</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-0 bg-warning text-dark stats-card">
                    <small>Pending</small>
                    <h3 class="mb-0"><?php echo $attendanceStats['pending']; ?>%</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-0 bg-danger text-white stats-card">
                    <small>Absent</small>
                    <h3 class="mb-0"><?php echo $attendanceStats['absent']; ?>%</h3>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card p-4 border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Attendance Trend</h5>
                        <span class="badge bg-success-subtle text-success">This Week</span>
                    </div>
                    <div class="d-flex align-items-end gap-2" style="height: 220px;">
                        <div class="bg-success rounded-top" style="width: 18%; height: 78%;"></div>
                        <div class="bg-success rounded-top" style="width: 18%; height: 88%;"></div>
                        <div class="bg-warning rounded-top" style="width: 18%; height: 58%;"></div>
                        <div class="bg-success rounded-top" style="width: 18%; height: 92%;"></div>
                        <div class="bg-danger rounded-top" style="width: 18%; height: 38%;"></div>
                        <div class="bg-success rounded-top" style="width: 10%; height: 96%;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-4 border-0 shadow-sm text-center">
                    <h5 class="mb-3">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" style="background-color: var(--gov-blue);">Mark Attendance</button>
                        <button class="btn btn-outline-secondary">Export Report</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 border-0 shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <h5 class="mb-0">Attendance Table</h5>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Search employee..." style="width: 220px;">
                    <select class="form-select form-select-sm" style="width: 140px;">
                        <option>All Status</option>
                        <option>Present</option>
                        <option>Pending</option>
                        <option>Absent</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-gov-blue">
                    <thead style="background:#f7fbf8;">
                        <tr>
                            <th>Employee</th>
                            <th>Meeting</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Rahul Patil</strong></td>
                            <td>Weekly Planning Meeting</td>
                            <td><span class="badge bg-success">Present</span></td>
                            <td>Attended on time</td>
                        </tr>
                        <tr>
                            <td><strong>Anita Deshmukh</strong></td>
                            <td>HR Policy Review</td>
                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                            <td>Awaiting confirmation</td>
                        </tr>
                        <tr>
                            <td><strong>Vijay Rao</strong></td>
                            <td>District Review Meeting</td>
                            <td><span class="badge bg-danger">Absent</span></td>
                            <td>Not available</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

<?php include_once '../../includes/footer.php'; ?>
