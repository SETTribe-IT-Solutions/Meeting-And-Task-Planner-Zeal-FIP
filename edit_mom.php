<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mom_functions.php';

momRequireManage();

$id = (int)($_GET['id'] ?? 0);
$record = getMomRecordById($id);
if (!$record) {
    $_SESSION['error'] = 'MoM record not found.';
    redirect('mom.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', trim($_POST['csrf_token'] ?? ''))) {
        $_SESSION['error'] = 'Invalid security token.';
        redirect('edit_mom.php?id=' . $id);
    }

    $result = updateMomRecord($_POST + $_FILES + ['id' => $id]);
    $_SESSION['alert'] = ['type' => $result['success'] ? 'success' : 'error', 'title' => $result['success'] ? 'Updated' : 'Error', 'message' => $result['message']];
    redirect('mom.php');
}

$meetings = getMomMeetingsList();
$departments = getMomDepartmentsList();
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrfToken;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/smart-alert.php';
?>
<div class="container-fluid page-shell">
    <div class="page-hero mb-4">
        <div class="hero-badge"><i class="fas fa-edit"></i> Refine your meeting notes</div>
        <h3 class="hero-title">Edit MoM</h3>
        <p class="hero-copy mb-0">Update notes, attachments, and linked tasks while keeping your MoM records consistent.</p>
    </div>
    <div class="card border-0 p-4 form-shell">
        <h4 class="fw-bold mb-3" style="color: var(--gov-blue);">Update MoM Entry</h4>
        <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meeting <span class="text-danger">*</span></label>
                    <select class="form-select" name="meeting_id" required>
                        <option value="">Select meeting</option>
                        <?php foreach ($meetings as $meeting): ?>
                            <option value="<?php echo (int)$meeting['id']; ?>" <?php echo ((int)$record['meeting_id'] === (int)$meeting['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($meeting['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select class="form-select" name="department" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo ($record['department'] === $dept['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Note Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="note_title" required maxlength="255" value="<?php echo htmlspecialchars($record['note_title'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Note Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="6" name="note_description" required><?php echo htmlspecialchars($record['note_description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Linked Task (Optional)</label>
                    <select class="form-select" name="linked_task_id">
                        <option value="">None</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Attachment (Optional)</label>
                    <input type="file" class="form-control" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update MoM</button>
                <a href="mom.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form.needs-validation');
        if (form) {
            form.addEventListener('submit', function (event) {
                const required = Array.from(form.querySelectorAll('[required]'));
                let valid = true;
                required.forEach((field) => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        valid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                if (!valid) {
                    event.preventDefault();
                    window.alert('Please complete all required fields before updating.');
                }
            });
        }
    });
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
