<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mom_functions.php';

momRequireAccess();

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'department' => trim($_GET['department'] ?? ''),
    'meeting_id' => (int)($_GET['meeting_id'] ?? 0),
];

$activeFilters = array_filter($filters, function ($value) {
    return $value !== '' && $value !== null && $value !== 0;
});

if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'], true)) {
    downloadMomReport(getMomRecords($filters), $_GET['export']);
}

$records = getMomRecords($filters);
$stats = getMomStats();
$departments = getMomDepartmentsList();
$meetings = getMomMeetingsList();
$canManage = momCanManage();
$selectedMeetingTitle = '';
foreach ($meetings as $meeting) {
    if ($filters['meeting_id'] === (int)$meeting['id']) {
        $selectedMeetingTitle = $meeting['title'];
        break;
    }
}

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrfToken;
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/smart-alert.php';
?>

<div class="container-fluid page-shell">
    <div class="page-hero mb-4">
        <div class="hero-badge"><i class="fas fa-file-alt"></i> Smart meeting minutes</div>
        <h2 class="hero-title">MoM (Minutes of Meeting)</h2>
        <p class="hero-copy mb-0">Capture important discussion points, link follow-up tasks, and keep every meeting outcome vivid and easy to review.</p>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--gov-blue);">Meeting records at a glance</h3>
            <p class="text-muted mb-0">A colorful dashboard for searching, reviewing, and exporting MoMs quickly.</p>
        </div>
        <?php if ($canManage): ?>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#momModal">
                <i class="fas fa-plus-circle me-1"></i> Add MoM
            </button>
            <a class="btn btn-outline-secondary rounded-3" href="?export=excel<?php echo $activeFilters ? '&' . http_build_query($activeFilters) : ''; ?>">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a class="btn btn-outline-secondary rounded-3" href="?export=pdf<?php echo $activeFilters ? '&' . http_build_query($activeFilters) : ''; ?>">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-outline-dark rounded-3" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 h-100 p-4 soft-panel">
                <div class="metric-pill mb-2"><i class="fas fa-calendar-check"></i> Total meetings</div>
                <div class="fw-bold fs-3 text-primary"><?php echo (int)$stats['total_meetings']; ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 h-100 p-4 soft-panel">
                <div class="metric-pill mb-2"><i class="fas fa-file-alt"></i> Total MoMs</div>
                <div class="fw-bold fs-3 text-success"><?php echo (int)$stats['total_moms']; ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 h-100 p-4 soft-panel">
                <div class="metric-pill mb-2"><i class="fas fa-star"></i> Latest MoM</div>
                <div class="fw-bold fs-6 text-info"><?php echo htmlspecialchars($stats['latest_mom']); ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 h-100 p-4 soft-panel">
                <div class="metric-pill mb-2"><i class="fas fa-list-check"></i> Pending linked tasks</div>
                <div class="fw-bold fs-3 text-warning"><?php echo (int)$stats['pending_tasks']; ?></div>
            </div>
        </div>
    </div>

    <div class="card border-0 p-4 form-shell">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search title or description">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select class="form-select" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['name']); ?>" <?php echo ($filters['department'] === $dept['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Meeting</label>
                <select class="form-select" name="meeting_id">
                    <option value="">All Meetings</option>
                    <?php foreach ($meetings as $meeting): ?>
                        <option value="<?php echo (int)$meeting['id']; ?>" <?php echo $filters['meeting_id'] === (int)$meeting['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($meeting['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100" type="submit"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
        </form>

        <?php if (!empty($filters['search']) || !empty($filters['department']) || $filters['meeting_id']): ?>
        <div class="filter-chips-wrap">
            <?php if (!empty($filters['search'])): ?>
                <span class="filter-chip filter-chip-search"><i class="fas fa-search"></i> Search: <?php echo htmlspecialchars($filters['search']); ?></span>
            <?php endif; ?>
            <?php if (!empty($filters['department'])): ?>
                <span class="filter-chip filter-chip-department"><i class="fas fa-building"></i> Dept: <?php echo htmlspecialchars($filters['department']); ?></span>
            <?php endif; ?>
            <?php if (!empty($filters['meeting_id']) && $selectedMeetingTitle): ?>
                <span class="filter-chip filter-chip-meeting"><i class="fas fa-calendar-day"></i> Meeting: <?php echo htmlspecialchars($selectedMeetingTitle); ?></span>
            <?php endif; ?>
            <span class="filter-chip filter-chip-count"><i class="fas fa-list"></i> Showing <?php echo count($records); ?> record<?php echo count($records) === 1 ? '' : 's'; ?></span>
        </div>
        <?php endif; ?>

        <div class="table-responsive mt-4 table-shell">
            <table id="momTable" class="table table-hover align-middle table-enhanced">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Meeting Title</th>
                        <th>Note Title</th>
                        <th>Note Description</th>
                        <th>Department</th>
                        <th>Linked Task</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $index => $record): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($record['meeting_title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($record['note_title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(substr($record['note_description'] ?? '', 0, 120)); ?><?php echo strlen($record['note_description'] ?? '') > 120 ? '…' : ''; ?></td>
                        <td><?php echo htmlspecialchars($record['department'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($record['linked_task_title'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($record['created_by_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($record['created_at']))); ?></td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-outline-primary" href="view_mom.php?id=<?php echo (int)$record['id']; ?>"><i class="fas fa-eye"></i></a>
                                <?php if ($canManage): ?>
                                <a class="btn btn-sm btn-outline-warning" href="edit_mom.php?id=<?php echo (int)$record['id']; ?>"><i class="fas fa-edit"></i></a>
                                <a class="btn btn-sm btn-outline-danger delete-mom" href="delete_mom.php?id=<?php echo (int)$record['id']; ?>" data-id="<?php echo (int)$record['id']; ?>"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<div class="modal fade" id="momModal" tabindex="-1" aria-labelledby="momModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="momModalLabel">Add MoM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="add_mom.php" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Meeting <span class="text-danger">*</span></label>
              <select class="form-select" name="meeting_id" required>
                <option value="">Select meeting</option>
                <?php foreach ($meetings as $meeting): ?>
                  <option value="<?php echo (int)$meeting['id']; ?>"><?php echo htmlspecialchars($meeting['title']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Department <span class="text-danger">*</span></label>
              <select class="form-select" name="department" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo htmlspecialchars($dept['name']); ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label">Note Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="note_title" required maxlength="255">
            </div>
            <div class="col-md-12">
              <label class="form-label">Note Description <span class="text-danger">*</span></label>
              <textarea class="form-control" name="note_description" rows="5" required></textarea>
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save MoM</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function validateMomForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let valid = true;
        requiredFields.forEach((field) => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        return valid;
    }

    $(function () {
        $('#momTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            order: [[0, 'desc']]
        });

        $('form.needs-validation').on('submit', function (e) {
            if (!validateMomForm(this)) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Please complete the highlighted fields.' });
            }
        });

        $('.delete-mom').on('click', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');
            Swal.fire({
                title: 'Delete MoM?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
