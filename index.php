<?php
// index.php

// 1. Initialize sessions to manage authenticated user states
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. Access Protection Interceptor: If no session token exists, redirect to login page immediately
if (!isset($_SESSION['role'])) {
    header("Location: modules/users/login.php");
    exit();
}

// 3. Extract authenticated context variables safely
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = $_SESSION['role'];
$userDept = htmlspecialchars($_SESSION['department']);

// 4. Inject global layout header component
include_once 'includes/header.php';
?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>Success!</strong> Your action items/meetings have been saved and updated across systems successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 bg-white border rounded-3 shadow-sm">
            <h1 class="display-6 fw-bold text-dark">Welcome back, <?php echo $userName; ?>!</h1>
            <p class="text-muted mb-0">
                Authorized System Tier: <strong class="text-primary"><?php echo $userRole; ?></strong> 
                <?php if ($userRole !== 'Collector'): ?>
                    | Institutional Domain: <strong class="text-secondary"><?php echo $userDept; ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="row g-4">

    <?php if ($userRole === 'Collector'): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
                <div class="card-header bg-dark text-white fw-bold py-3">Hon. Collector - Operational Insights Overview</div>
                <div class="card-body p-4">
                    <p class="card-text text-muted">Accessing complete institutional analytics. You have global read-only access to review all scheduled meetings, logged Minutes of Meetings (MoM), and cross-departmental task completion rates.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm p-4 rounded-3">
                <h6 class="text-uppercase small opacity-75 fw-bold">Total Running Meetings</h6>
                <h1 class="fw-bold mb-0 mt-2">--</h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark border-0 shadow-sm p-4 rounded-3">
                <h6 class="text-uppercase small opacity-75 fw-bold">Pending Department Actions</h6>
                <h1 class="fw-bold mb-0 mt-2">--</h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow-sm p-4 rounded-3">
                <h6 class="text-uppercase small opacity-75 fw-bold">Completed Deliverables</h6>
                <h1 class="fw-bold mb-0 mt-2">--</h1>
            </div>
        </div>


    <?php elseif ($userRole === 'Organizer'): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-white rounded-3">
                <div class="card-header bg-primary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span class="mb-0">Meeting Organizer Dashboard Console</span>
                    <a href="modules/meetings/create.php" class="btn btn-sm btn-light fw-bold text-primary shadow-sm">+ Plan New Event</a>
                </div>
                <div class="card-body p-4">
                    <p class="card-text text-muted">You hold structural administrative permissions. Use the console below to organize scheduling frameworks, log Minutes of Meetings (MoM), and assign action-item deliverables.</p>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-hover border align-middle rounded-3 overflow-hidden">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3">Meeting Details</th>
                                    <th class="py-3">Schedule Window</th>
                                    <th class="py-3">Mode</th>
                                    <th class="py-3">Department Target</th>
                                    <th class="py-3 text-center">Control Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted bg-light-subtle">
                                        No upcoming scheduled records found in active catalog. Click <a href="modules/meetings/create.php" class="fw-semibold">here</a> to record your first scheduled event.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    <?php elseif ($userRole === 'Employee'): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-white rounded-3">
                <div class="card-header bg-secondary text-white fw-bold py-3">My Event Calendar Invitations</div>
                <div class="card-body p-4">
                    <p class="small text-muted">Listing authorized agendas requiring your physical or online session attendance.</p>
                    <div class="alert alert-light border text-center text-muted mb-0 py-5 bg-light-subtle rounded-3">
                        No incoming invitations pending at your desk.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-white rounded-3">
                <div class="card-header bg-secondary text-white fw-bold py-3">My Personal Task Assignments</div>
                <div class="card-body p-4">
                    <p class="small text-muted">Pending action items assigned directly to your desk during strategic department briefings.</p>
                    <div class="alert alert-light border text-center text-muted mb-0 py-5 bg-light-subtle rounded-3">
                        All clear! You have no pending tracking tasks assigned.
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php
// 6. Inject global layout footer component
include_once 'includes/footer.php';
?>