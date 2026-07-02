<?php
// modules/meetings/mom.php
// Minutes of Meeting (MOM) Module

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/MOMController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../modules/users/login.php');
}

$basePath = APP_URL;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$meeting_id = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Verify meeting exists
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id, title, meeting_date, meeting_time FROM meetings WHERE id = ?");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    $_SESSION['alert'] = ['type' => 'error', 'title' => 'Error', 'message' => 'Meeting not found.'];
    header('Location: index.php');
    exit();
}

// Get MOMs for this meeting
$moms = MOMController::getMOMByMeeting($meeting_id);
$momCount = count($moms);

// Get available tasks for linking
$tasks = [];
$stmt = $conn->prepare("SELECT id, title FROM tasks WHERE meeting_id = ? ORDER BY title");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Notes (MOM) - <?php echo htmlspecialchars($meeting['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $basePath; ?>/assets/css/custom.css" rel="stylesheet">
    <style>
        .mom-header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .mom-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .mom-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .mom-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .mom-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #003366;
            margin: 0;
        }

        .mom-card-meta {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .mom-card-content {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #333;
        }

        .mom-card-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-sm-custom {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn-edit {
            background: #0d6efd;
            color: white;
        }

        .btn-edit:hover {
            background: #0b5ed7;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #bb2d3b;
        }

        .form-section {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-section h5 {
            color: #003366;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .form-group label {
            font-weight: 600;
            color: #003366;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #004080;
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
        }

        .char-count {
            font-size: 12px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .badge-department {
            background: #e7f3ff;
            color: #004080;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }

        .linked-task {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../includes/smart-alert.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Meeting Header -->
        <div class="mom-header">
            <h2><i class="fas fa-file-alt"></i> Meeting Minutes (MOM)</h2>
            <p class="mb-0">
                <strong>Meeting:</strong> <?php echo htmlspecialchars($meeting['title']); ?> 
                <span style="margin-left: 20px;">
                    <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($meeting['meeting_date'])); ?>
                    <i class="fas fa-clock" style="margin-left: 10px;"></i> <?php echo date('h:i A', strtotime($meeting['meeting_time'])); ?>
                </span>
            </p>
        </div>

        <!-- MOM Count -->
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i>
            <strong>Meeting Notes:</strong> Total <strong><?php echo $momCount; ?></strong> note(s) recorded
        </div>

        <!-- Create/Edit MOM Form -->
        <div class="form-section">
            <h5><i class="fas fa-plus-circle"></i> Add New Meeting Note</h5>
            
            <form id="momForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="meeting_id" value="<?php echo htmlspecialchars($meeting_id); ?>">
                <input type="hidden" name="mom_id" id="momId" value="">

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="noteTitle">Note Title <span style="color: red;">*</span></label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="noteTitle" 
                                name="note_title" 
                                placeholder="e.g., Discussion on Budget Allocation"
                                maxlength="200"
                                required
                            >
                            <small class="char-count"><span id="titleCount">0</span>/200</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="noteDepartment">Department</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="noteDepartment" 
                                name="department" 
                                placeholder="e.g., Finance"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="noteDescription">Note Description <span style="color: red;">*</span></label>
                    <textarea 
                        class="form-control" 
                        id="noteDescription" 
                        name="note_description" 
                        rows="6" 
                        placeholder="Describe the key points discussed in this meeting..."
                        maxlength="5000"
                        required
                    ></textarea>
                    <small class="char-count"><span id="descCount">0</span>/5000</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="linkedTask">Link to Task (Optional)</label>
                            <select class="form-control" id="linkedTask" name="linked_task_id">
                                <option value="">-- No Task Linked --</option>
                                <?php foreach ($tasks as $task): ?>
                                    <option value="<?php echo htmlspecialchars($task['id']); ?>">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-save"></i> Save Note
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn" style="display: none;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- MOM List -->
        <div>
            <h5 style="color: #003366; font-weight: 600; margin-bottom: 20px;">
                <i class="fas fa-list"></i> Meeting Notes
            </h5>

            <?php if (empty($moms)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p><strong>No meeting notes yet</strong></p>
                    <p>Start by adding your first meeting note above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($moms as $mom): ?>
                    <div class="mom-card" id="mom-<?php echo $mom['id']; ?>">
                        <div class="mom-card-header">
                            <div>
                                <h6 class="mom-card-title"><?php echo htmlspecialchars($mom['note_title']); ?></h6>
                                <div class="mom-card-meta">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($mom['created_by_name']); ?>
                                    <span style="margin-left: 15px;">
                                        <i class="fas fa-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($mom['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if ($mom['department']): ?>
                                    <div class="badge-department">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($mom['department']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mom-card-footer">
                                <button type="button" class="btn-sm-custom btn-edit" onclick="editMOM(<?php echo $mom['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn-sm-custom btn-delete" onclick="deleteMOM(<?php echo $mom['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>

                        <div class="mom-card-content">
                            <?php echo nl2br(htmlspecialchars($mom['note_description'])); ?>
                        </div>

                        <?php if ($mom['linked_task_id']): ?>
                            <div class="linked-task">
                                <strong><i class="fas fa-link"></i> Linked Task:</strong> 
                                <?php echo htmlspecialchars($mom['linked_task_id']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Back Button -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="view.php?id=<?php echo $meeting_id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Meeting
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Character counters
    document.getElementById('noteTitle').addEventListener('input', function() {
        document.getElementById('titleCount').textContent = this.value.length;
    });

    document.getElementById('noteDescription').addEventListener('input', function() {
        document.getElementById('descCount').textContent = this.value.length;
    });

    // Form submission
    document.getElementById('momForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;

        try {
            const response = await fetch('../../controllers/MOMController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showSmartAlert(data.message, 'success', 'Success');
                document.getElementById('momForm').reset();
                document.getElementById('momId').value = '';
                
                // Reload page after 2 seconds
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showSmartAlert(data.message, 'error', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            showSmartAlert('An error occurred. Please try again.', 'error', 'Error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // Edit MOM
    async function editMOM(momId) {
        try {
            const response = await fetch(`../../controllers/MOMController.php?action=get&id=${momId}`);
            const data = await response.json();

            if (data.success && data.mom) {
                const mom = data.mom;
                document.getElementById('momId').value = mom.id;
                document.getElementById('noteTitle').value = mom.note_title;
                document.getElementById('noteDescription').value = mom.note_description;
                document.getElementById('noteDepartment').value = mom.department || '';
                document.getElementById('linkedTask').value = mom.linked_task_id || '';
                document.getElementById('momForm').action.value = 'update';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check"></i> Update Note';
                document.getElementById('cancelBtn').style.display = 'inline-block';
                
                // Scroll to form
                document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error('Error:', error);
            showSmartAlert('Failed to load note for editing.', 'error', 'Error');
        }
    }

    // Delete MOM
    async function deleteMOM(momId) {
        if (!confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('mom_id', momId);

        try {
            const response = await fetch('../../controllers/MOMController.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showSmartAlert(data.message, 'success', 'Success');
                document.getElementById('mom-' + momId).remove();
            } else {
                showSmartAlert(data.message, 'error', 'Error');
            }
        } catch (error) {
            console.error('Error:', error);
            showSmartAlert('An error occurred while deleting the note.', 'error', 'Error');
        }
    }

    // Cancel edit
    document.getElementById('cancelBtn').addEventListener('click', function() {
        document.getElementById('momForm').reset();
        document.getElementById('momId').value = '';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Note';
        this.style.display = 'none';
    });

    function showSmartAlert(message, type = 'info', title = '') {
        const alertHtml = `
            <div class="alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show" role="alert">
                <strong>${title}:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const alertContainer = document.querySelector('[class*="container"]');
        if (alertContainer) {
            alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
        }

        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) alert.remove();
        }, 5000);
    }
    </script>
</body>
</html>
