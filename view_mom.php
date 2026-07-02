<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mom_functions.php';

momRequireAccess();

$id = (int)($_GET['id'] ?? 0);
$record = getMomRecordById($id);
if (!$record) {
    $_SESSION['error'] = 'MoM record not found.';
    redirect('mom.php');
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/smart-alert.php';
?>
<div class="container-fluid page-shell">
    <div class="page-hero mb-4">
        <div class="hero-badge"><i class="fas fa-eye"></i> Review report details</div>
        <h3 class="hero-title">MoM Details</h3>
        <p class="hero-copy mb-0">Inspect every detail, attachment, and follow-up item from this meeting note.</p>
    </div>
    <div class="card border-0 p-4 form-shell">
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">MoM Details</h3>
                <p class="text-muted mb-0">View the meeting minute details and attached document.</p>
            </div>
            <a href="mom.php" class="btn btn-outline-secondary rounded-3"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><strong>Meeting:</strong> <?php echo htmlspecialchars($record['meeting_title'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Department:</strong> <?php echo htmlspecialchars($record['department'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Note Title:</strong> <?php echo htmlspecialchars($record['note_title'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Linked Task:</strong> <?php echo htmlspecialchars($record['linked_task_title'] ?? '—'); ?></div>
            <div class="col-md-6"><strong>Created By:</strong> <?php echo htmlspecialchars($record['created_by_name'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Created At:</strong> <?php echo htmlspecialchars($record['created_at'] ?? ''); ?></div>
            <div class="col-12"><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($record['note_description'] ?? '')); ?></div>
            <?php if (!empty($record['attachment'])): ?>
            <div class="col-12">
                <strong>Attachment:</strong>
                <a class="btn btn-outline-primary btn-sm ms-2" href="uploads/moms/<?php echo htmlspecialchars($record['attachment']); ?>" target="_blank">Download</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
