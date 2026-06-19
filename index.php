<?php
// index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role'])) {
    header("Location: modules/users/login.php");
    exit();
}

require_once 'config/db.php';

$userRole = $_SESSION['role'];
$userDept = $_SESSION['department'];
$userId   = $_SESSION['user_id'];

$meetingsList = [];
$tasksList = [];
$totalMeetingsCount = 0;
$scheduledCount = 0;
$completedCount = 0;
$pendingTasksCount = 0;

try {
    if ($userRole === 'Collector') {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM meetings WHERE isDeleted = 'No'");
        $totalMeetingsCount = $countStmt->fetchColumn();
        
        $listStmt = $pdo->query("SELECT m.*, u.name as organizer_name FROM meetings m JOIN users u ON m.createdBy = u.id WHERE m.isDeleted = 'No' ORDER BY m.meeting_date DESC, m.meeting_time DESC");
        $meetingsList = $listStmt->fetchAll();

        $taskStmt = $pdo->query("SELECT t.*, m.title as meeting_title, u.name as assignee_name FROM tasks t JOIN meetings m ON t.meeting_id = m.id JOIN users u ON t.assigned_to = u.id WHERE t.isDeleted = 'No' ORDER BY t.due_date ASC");
        $tasksList = $taskStmt->fetchAll();
    } elseif ($userRole === 'Organizer') {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM meetings WHERE createdBy = :uid AND isDeleted = 'No'");
        $countStmt->execute(['uid' => $userId]);
        $totalMeetingsCount = $countStmt->fetchColumn();
        
        $listStmt = $pdo->prepare("SELECT m.*, u.name as organizer_name FROM meetings m JOIN users u ON m.createdBy = u.id WHERE m.createdBy = :uid AND m.isDeleted = 'No' ORDER BY m.meeting_date DESC, m.meeting_time DESC");
        $listStmt->execute(['uid' => $userId]);
        $meetingsList = $listStmt->fetchAll();

        $taskStmt = $pdo->prepare("SELECT t.*, m.title as meeting_title, u.name as assignee_name FROM tasks t JOIN meetings m ON t.meeting_id = m.id JOIN users u ON t.assigned_to = u.id WHERE t.createdBy = :uid AND t.isDeleted = 'No' ORDER BY t.due_date ASC");
        $taskStmt->execute(['uid' => $userId]);
        $tasksList = $taskStmt->fetchAll();
    } else {
        $listStmt = $pdo->query("SELECT m.*, u.name as organizer_name FROM meetings m JOIN users u ON m.createdBy = u.id WHERE m.isDeleted = 'No' ORDER BY m.meeting_date DESC");
        $meetingsList = $listStmt->fetchAll();

        $taskStmt = $pdo->prepare("SELECT t.*, m.title as meeting_title, u.name as assignee_name FROM tasks t JOIN meetings m ON t.meeting_id = m.id JOIN users u ON t.assigned_to = u.id WHERE t.assigned_to = :uid AND t.isDeleted = 'No' ORDER BY t.due_date ASC");
        $taskStmt->execute(['uid' => $userId]);
        $tasksList = $taskStmt->fetchAll();
    }

    foreach ($meetingsList as $m) {
        if (strtotime($m['meeting_date'] . ' ' . $m['meeting_time']) < time()) {
            $completedCount++;
        } else {
            $scheduledCount++;
        }
    }

    foreach ($tasksList as $t) {
        if ($t['status'] !== 'Completed') {
            $pendingTasksCount++;
        }
    }
} catch (PDOException $e) {
    error_log("Dashboard runtime error: " . $e->getMessage());
}

include_once 'includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

<style>
    body, html, table, div, p, span {
        font-family: 'Inter', sans-serif;
        -webkit-font-smoothing: antialiased;
        letter-spacing: -0.1px;
    }

    h1, h2, h3, h4, h5, h6, .tracking-tight-custom {
        font-family: 'Plus Jakarta Sans', sans-serif;
        letter-spacing: -0.4px !important;
    }
    
    .dashboard-viewport-wrapper {
        position: relative;
        min-height: 100vh;
        width: 100%;
        padding: 10px 0;
    }
    
    /* MAP WATERMARK LAYER */
    .dashboard-viewport-wrapper::before {
        content: "";
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1;
        background-image: url('image.png?v=refresh_map_asset');
        background-repeat: no-repeat;
        background-position: center center;
        background-size: contain;
        opacity: 0.38; 
        filter: contrast(120%) saturate(110%);
        pointer-events: none;
    }
    
    .tracking-tight-custom {
        font-weight: 800 !important;
    }

    .font-monospace-custom {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        letter-spacing: 0.8px !important;
        font-size: 15px !important;
        font-weight: 600 !important;
    }

    /* THE MAIN BIG UNIFIED FROSTED GLASS BOX CONTAINER */
    .unified-workspace-card {
        background: rgb(10 10 10 / 28%) !important;
        /* backdrop-filter: blur(15px) saturate(130%); */
        -webkit-backdrop-filter: blur(15px) saturate(130%);
        border: 1px solid rgba(191, 204, 218, 0.7);
        border-radius: 20px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
    }

    /* METRIC SUB-CARDS INSIDE MAIN BOX */
    .dashboard-card-premium {
        background: #ffffff !important;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        transition: all 0.22s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }
    
    .dashboard-card-premium::before {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 100%; height: 4px;
        background: transparent;
    }
    .card-total::before { background: linear-gradient(90deg, #475569, #64748b); }
    .card-active::before { background: linear-gradient(90deg, #1d4ed8, #3b82f6); }
    .card-pending::before { background: linear-gradient(90deg, #b91c1c, #ef4444); }

    .dashboard-card-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.06) !important;
        border-color: #94a3b8;
    }

    /* OPAQUE STABLE TABLE FORMATTING */
    .table-responsive-clean table {
        border-collapse: separate;
        border-spacing: 0 6px;
    }
    .table-responsive-clean tr {
        background-color: transparent !important;
    }
    .table-responsive-clean td {
        background: #ffffff !important;
        border-top: 1px solid #cbd5e1 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding: 12px 14px !important;
        transition: all 0.1s ease;
    }
    .table-responsive-clean tr:hover td {
        background-color: #f8fafc !important;
    }
    .table-responsive-clean td:first-child {
        border-left: 1px solid #cbd5e1 !important;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    .table-responsive-clean td:last-child {
        border-right: 1px solid #cbd5e1 !important;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .badge-pill-custom {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 5px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .badge-pill-active { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .badge-pill-archived { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
</style>

<div class="dashboard-viewport-wrapper">
    
    <div class="row align-items-center justify-content-between my-4 g-3">
        <div class="col-md-7">
            <h3 class="text-dark mb-1 tracking-tight-custom" style="font-size: 28px; font-weight: 700;">Desk of <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            <p class="text-dark small mb-0 font-monospace-custom text-uppercase">Workspace Directory Panel • Latur Administration Sync</p>
        </div>
        <div class="col-md-auto d-flex gap-2 align-items-center">
            <?php if ($userRole === 'Organizer'): ?>
                <a href="modules/meetings/create.php" class="btn btn-sm btn-outline-dark bg-white px-3 py-2 fw-semibold" style="border-radius: 8px; font-size: 13px; border-color: #cbd5e1;">+ Create Meeting</a>
                <a href="modules/tasks/create.php" class="btn btn-sm btn-dark px-3 py-2 fw-semibold" style="background-color: #0f172a; border-radius: 8px; font-size: 13px; border: none;">Assign Task</a>
            <?php endif; ?>
            <a href="modules/users/logout.php" class="text-danger small font-monospace-custom text-uppercase text-decoration-none ms-2" style="font-weight: 700; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 8px; background: #fff5f5;">Exit Session ↩</a>
        </div>
    </div>

    <div class="unified-workspace-card p-4 p-md-5 mb-5">
        
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="dashboard-card-premium card-total p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase font-monospace-custom text-muted">Total Directives</span>
                            <h2 class="text-dark mt-1 mb-0 tracking-tight-custom" style="font-size: 34px; font-weight: 700;"><?php echo count($meetingsList); ?></h2>
                        </div>
                        <span style="font-size: 24px;">📂</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card-premium card-active p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase font-monospace-custom text-primary">Active Tracks</span>
                            <h2 class="text-primary mt-1 mb-0 tracking-tight-custom" style="font-size: 34px; font-weight: 700;"><?php echo $scheduledCount; ?></h2>
                        </div>
                        <span style="font-size: 24px;">⚡</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card-premium card-pending p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-uppercase font-monospace-custom text-danger">Open Tasks</span>
                            <h2 class="text-danger mt-1 mb-0 tracking-tight-custom" style="font-size: 34px; font-weight: 700;"><?php echo $pendingTasksCount; ?></h2>
                        </div>
                        <span style="font-size: 24px;">🚨</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-5">
            <h5 class="text-dark mb-3 tracking-tight-custom" style="font-size: 18px; font-weight: 700;">🏛️ Executive Meeting Log</h5>
            <div class="table-responsive-clean">
                <table class="table align-middle border-0 mb-0">
                    <thead>
                        <tr class="bg-transparent shadow-none font-monospace-custom text-dark text-uppercase" style="font-size: 11px;">
                            <th class="ps-3 border-0">Subject / Core Identifier</th>
                            <th class="border-0">Timeline Frame</th>
                            <th class="border-0">Channel Mode</th>
                            <th class="border-0">Department</th>
                            <th class="border-0">Current State</th>
                            <th class="pe-3 border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetingsList)): ?>
                            <tr class="shadow-none"><td colspan="6" class="text-center py-5 text-muted border-0 bg-transparent font-monospace-custom">Empty database registry.</td></tr>
                        <?php else: ?>
                            <?php foreach ($meetingsList as $meeting): 
                                $isPast = (strtotime($meeting['meeting_date'] . ' ' . $meeting['meeting_time']) < time());
                            ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark mb-0.5" style="font-size: 15px;"><code><?php echo htmlspecialchars($meeting['title']); ?></code></div>
                                        <span class="text-muted font-monospace-custom">Ref ID: LTR-M-00<?php echo $meeting['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small" style="font-size: 13.5px;"><?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?></div>
                                        <div class="text-muted small"><?php echo date('h:i A', strtotime($meeting['meeting_time'])); ?></div>
                                    </td>
                                    <td><span class="badge border bg-light text-secondary font-monospace-custom px-2 py-1"><?php echo $meeting['mode']; ?></span></td>
                                    <td class="font-monospace-custom text-uppercase text-secondary"><?php echo htmlspecialchars($meeting['department']); ?></td>
                                    <td>
                                        <span class="badge-pill-custom <?php echo $isPast ? 'badge-pill-archived' : 'badge-pill-active'; ?>">
                                            <span style="width: 5px; height: 5px; border-radius: 50%; background-color: currentColor;"></span>
                                            <?php echo $isPast ? 'Archived' : 'Active Call'; ?>
                                        </span>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <button type="button" class="btn btn-sm btn-white border fw-semibold px-3 py-1.5" style="font-size: 12px; border-radius: 6px; background: white;" onclick="viewMeetingModal('<?php echo htmlspecialchars(addslashes($meeting['title'])); ?>', '<?php echo htmlspecialchars(addslashes($meeting['agenda'])); ?>', '<?php echo htmlspecialchars(addslashes($meeting['organizer_name'])); ?>', '<?php echo htmlspecialchars($meeting['location_or_link']); ?>')">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h5 class="text-dark mb-3 tracking-tight-custom" style="font-size: 18px; font-weight: 700;">📋 Action Directives Queue</h5>
            <div class="table-responsive-clean">
                <table class="table align-middle border-0 mb-0">
                    <thead>
                        <tr class="bg-transparent shadow-none font-monospace-custom text-dark text-uppercase" style="font-size: 11px;">
                            <th class="ps-3 border-0">Objective Mandate</th>
                            <th class="border-0">Parent Reference</th>
                            <th class="border-0">Assigned Official</th>
                            <th class="border-0">Target Deadline</th>
                            <th class="pe-3 border-0 text-end">Compliance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasksList)): ?>
                            <tr class="shadow-none"><td colspan="5" class="text-center py-5 text-muted border-0 bg-transparent font-monospace-custom">No open assignments found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tasksList as $task): ?>
                                <tr>
                                    <td class="ps-3" style="max-width: 320px;">
                                        <div class="fw-semibold text-dark mb-0.5" style="font-size: 15px;"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <span class="text-muted d-block small text-truncate" style="font-size: 12.5px;"><?php echo htmlspecialchars($task['description']); ?></span>
                                    </td>
                                    <td class="text-secondary text-truncate small fw-medium" style="max-width: 180px;"><?php echo htmlspecialchars($task['meeting_title']); ?></td>
                                    <td class="text-dark fw-medium" style="font-size: 14px;">👤 <?php echo htmlspecialchars($task['assignee_name']); ?></td>
                                    <td class="text-danger fw-semibold" style="font-size: 13.5px;">⏰ <?php echo date('d M Y', strtotime($task['due_date'])); ?></td>
                                    <td class="pe-3 text-end">
                                        <span class="badge border <?php echo $task['status'] === 'Completed' ? 'bg-success-subtle text-success border-success' : 'bg-warning-subtle text-warning border-warning'; ?> px-3 py-1.5" style="border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> </div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);">
            <div class="modal-header py-3 bg-transparent border-bottom">
                <h6 class="modal-title text-dark tracking-tight-custom" id="modalTitle" style="font-size: 16px; font-weight: 700;">File Parameters</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="transform: scale(0.8);"></button>
            </div>
            <div class="modal-body p-4 bg-transparent" style="font-size: 14px;">
                <div class="mb-3">
                    <span class="text-uppercase font-monospace-custom text-muted d-block mb-1">Nodal Officer Assigned</span>
                    <div id="modalConvener" class="fw-semibold text-dark"></div>
                </div>
                <div class="mb-3">
                    <span class="text-uppercase font-monospace-custom text-muted d-block mb-1">Destination Pointer / URL</span>
                    <div id="modalLocation" class="font-monospace-custom text-break text-primary"></div>
                </div>
                <hr class="my-3" style="border-color: rgba(203, 213, 225, 0.5);">
                <div class="mb-0">
                    <span class="text-uppercase font-monospace-custom text-muted d-block mb-1">Agenda Memorandum Objectives</span>
                    <p id="modalAgenda" class="text-secondary mb-0" style="white-space: pre-wrap; line-height: 1.6; font-size: 13.5px;"></p>
                </div>
            </div>
            <div class="modal-footer bg-transparent py-2 border-top">
                <button type="button" class="btn btn-sm btn-dark px-3 py-1.5 fw-semibold" data-bs-dismiss="modal" style="font-size: 12px; border-radius: 6px; background-color: #0f172a; border: none;">Dismiss Context</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMeetingModal(title, agenda, convener, location) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalAgenda').innerText = agenda;
    document.getElementById('modalConvener').innerText = convener;
    
    var locBox = document.getElementById('modalLocation');
    if(location.startsWith('http')) {
        locBox.innerHTML = `<a href="${location}" target="_blank" class="text-decoration-none fw-semibold">${location}</a>`;
    } else {
        locBox.innerText = location;
    }
    
    var myModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    myModal.show();
}
</script>

<?php include_once 'includes/footer.php'; ?>
<div class="modal fade" id="meetingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 p-4">
            <h4 id="mTitle" class="fw-bold text-dark"></h4>
            <p id="mAgenda" class="text-muted small"></p>
            <hr>
            <div id="mAttendees" class="small text-primary"></div>
        </div>
    </div>
</div>

