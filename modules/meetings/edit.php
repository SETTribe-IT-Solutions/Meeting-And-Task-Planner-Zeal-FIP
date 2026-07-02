<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Organizer') {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';

$conn      = getDBConnection();
$meetingId = (int)($_GET['id'] ?? 0);

if ($meetingId <= 0) {
    $_SESSION['error'] = 'Invalid meeting.';
    header('Location: index.php');
    exit();
}

// Fetch meeting
$stmt = $conn->prepare("SELECT * FROM meetings WHERE id = ? AND status = 'Scheduled'");
$stmt->bind_param('i', $meetingId);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting) {
    $_SESSION['error'] = 'Meeting not found or cannot be edited.';
    header('Location: index.php');
    exit();
}

// Current attachment
$atchStmt = $conn->prepare("SELECT id, original_name, file_size FROM meeting_attachments WHERE meeting_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$atchStmt->bind_param('i', $meetingId);
$atchStmt->execute();
$currentAttachment = $atchStmt->get_result()->fetch_assoc();

// Current attendees
$attStmt = $conn->prepare("SELECT user_id FROM attendance WHERE meeting_id = ?");
$attStmt->bind_param('i', $meetingId);
$attStmt->execute();
$currentAttendeeIds = array_column($attStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

// All employees for Tom Select
$usersResult = $conn->query("SELECT id, name, email, department FROM users WHERE role = 'Employee' AND isDeleted = 'No' ORDER BY name ASC");
$all_users   = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];

$departments = getDepartments();

// Convert stored 24-hour time → 12-hour for the time picker
$timeStr = $meeting['meeting_time'] ?? '09:00:00';
$h24     = (int)date('H', strtotime('2000-01-01 ' . $timeStr));
$timeMin = (int)date('i', strtotime('2000-01-01 ' . $timeStr));
$timeAmpm = $h24 >= 12 ? 'PM' : 'AM';
$timeH12  = $h24 % 12 ?: 12;

include_once '../../includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $basePath; ?>/assets/vendor/tom-select/css/tom-select.bootstrap5.min.css">
<style>
.ts-wrapper.multi .ts-control { min-height: 42px; border-radius: 0.5rem; }
.ts-wrapper.multi .ts-control input { color: #212529; }
.ts-dropdown .option { padding: 6px 10px; }
</style>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Meetings
            </a>
        </div>

        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header text-white fw-bold py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                <i class="fas fa-pencil-alt fs-5"></i>
                <span>Edit Meeting</span>
                <span class="badge bg-warning text-dark ms-auto fw-normal small"><?php echo htmlspecialchars($meeting['title']); ?></span>
            </div>
            <div class="card-body p-4">

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/MeetingUpdateController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="meeting_id"  value="<?php echo $meetingId; ?>">
                    <div class="row g-3">

                        <!-- Row 1: Title + Date -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required
                                   value="<?php echo htmlspecialchars($meeting['title']); ?>"
                                   placeholder="e.g., District Planning Review">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date" class="form-control rounded-3" required
                                   value="<?php echo htmlspecialchars($meeting['meeting_date']); ?>">
                        </div>

                        <!-- Row 2: Time + Mode + Duration -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Time <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="meeting_time_hour"   class="form-control text-center fw-bold rounded-start-3" min="1" max="12" placeholder="HH" required>
                                <span class="input-group-text fw-bold px-1">:</span>
                                <input type="number" id="meeting_time_minute" class="form-control text-center fw-bold" min="0" max="59" placeholder="00">
                                <select id="meeting_time_ampm" class="form-select fw-bold rounded-end-3" required style="min-width: 80px;">
                                    <option value="">--</option>
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                            <input type="hidden" name="meeting_time" id="meeting_time_hidden">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mode <span class="text-danger">*</span></label>
                            <select name="mode" id="modeSelect" class="form-select rounded-3" required>
                                <option value="">Select mode</option>
                                <option value="Offline">🏢 Offline — In-person</option>
                                <option value="Online">💻 Online — Virtual</option>
                                <option value="Hybrid">🔄 Hybrid — Both</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duration (minutes)</label>
                            <?php
                            $durationOptions = [15=>'15 min',30=>'30 min',45=>'45 min',60=>'1 hr (60 min)',90=>'1.5 hr (90 min)',120=>'2 hr (120 min)',180=>'3 hr (180 min)',240=>'4 hr (240 min)',300=>'5 hr (300 min)',360=>'6 hr (360 min)',420=>'7 hr (420 min)',480=>'8 hr (480 min)'];
                            $savedDuration = (int)($meeting['duration'] ?? 0);
                            ?>
                            <select name="duration" class="form-select rounded-3">
                                <option value="">-- Not specified --</option>
                                <?php foreach ($durationOptions as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($savedDuration === $val) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Optional</div>
                        </div>

                        <!-- Location (Offline / Hybrid) -->
                        <div class="col-12" id="location-field" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>Location <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="location" id="locationInput" class="form-control rounded-3"
                                   placeholder="e.g., Collector Office - Conference Hall, Latur">
                        </div>

                        <!-- Meeting URL (Online / Hybrid) -->
                        <div class="col-12" id="meeting-url-field" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-video text-primary me-1"></i>Meeting URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="meeting_url" id="meetingUrlInput" class="form-control rounded-3"
                                   placeholder="https://meet.google.com/... or https://zoom.us/j/...">
                        </div>

                        <!-- Mode hint -->
                        <div class="col-12" id="mode-hint" style="display: none;">
                            <div class="text-muted small bg-light rounded-3 p-2 ps-3 border-start border-3 border-secondary">
                                <i class="fas fa-info-circle me-1"></i> Select a meeting mode above.
                            </div>
                        </div>

                        <!-- Department -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select name="department" id="departmentSelect" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"
                                        <?php echo ($meeting['department'] === $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Attendees -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="fas fa-users text-primary me-1"></i>Attendees
                                </label>
                                <div id="attendeesControls" class="d-flex align-items-center gap-2" style="display:none;">
                                    <span id="attendeesCount" class="badge bg-primary rounded-pill px-3 py-1" style="display:none; font-size:0.78rem;"></span>
                                    <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary rounded-3 px-3">
                                        <i class="fas fa-check-double me-1"></i>Select All
                                    </button>
                                    <button type="button" id="clearAllBtn" class="btn btn-sm btn-outline-secondary rounded-3 px-3">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                            <div id="attendeesWidget" style="display:none;">
                                <select name="attendees[]" id="attendeesSelect" multiple></select>
                            </div>
                            <div id="attendeesHint" class="text-muted small mt-1">
                                <i class="fas fa-info-circle me-1"></i>Select a department above to load attendees.
                            </div>
                        </div>

                        <!-- Agenda -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Agenda <span class="text-danger">*</span></label>
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required
                                      placeholder="Meeting agenda points..."><?php echo htmlspecialchars($meeting['agenda']); ?></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip text-secondary me-1"></i>Attachment
                                <span class="text-muted fw-normal small">(optional)</span>
                            </label>
                            <?php if ($currentAttachment): ?>
                            <div class="alert alert-info py-2 rounded-3 mb-2 d-flex align-items-center gap-2">
                                <i class="fas fa-file-alt text-primary"></i>
                                <div class="flex-fill">
                                    <strong><?php echo htmlspecialchars($currentAttachment['original_name']); ?></strong>
                                    <span class="text-muted small ms-2">(<?php echo round($currentAttachment['file_size'] / 1024, 1); ?> KB)</span>
                                </div>
                                <a href="download_attachment.php?meeting_id=<?php echo $meetingId; ?>"
                                   class="btn btn-sm btn-outline-primary rounded-3">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </div>
                            <div class="form-text mb-1">Upload a new file below to <strong>replace</strong> the current attachment.</div>
                            <?php endif; ?>
                            <input type="file" name="meeting_attachment" class="form-control rounded-3"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                            <div class="form-text">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG &mdash; Max 10 MB</div>
                        </div>

                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-warning rounded-3 px-4 fw-semibold">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3">Discard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allUsers          = <?php echo json_encode($all_users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const preloadDept       = <?php echo json_encode($meeting['department']); ?>;
    const preloadItems      = <?php echo json_encode(array_map('strval', $currentAttendeeIds)); ?>;
    const preloadLocation   = <?php echo json_encode($meeting['location'] ?? ''); ?>;
    const preloadUrl        = <?php echo json_encode($meeting['meeting_url'] ?? ''); ?>;
    const preloadMode       = <?php echo json_encode($meeting['mode']); ?>;

    const modeSelect        = document.getElementById('modeSelect');
    const departmentSelect  = document.getElementById('departmentSelect');
    const locationField     = document.getElementById('location-field');
    const meetingUrlField   = document.getElementById('meeting-url-field');
    const locationInput     = document.getElementById('locationInput');
    const meetingUrlInput   = document.getElementById('meetingUrlInput');
    const modeHint          = document.getElementById('mode-hint');
    const hourInput         = document.getElementById('meeting_time_hour');
    const minuteInput       = document.getElementById('meeting_time_minute');
    const ampmSelect        = document.getElementById('meeting_time_ampm');
    const timeHidden        = document.getElementById('meeting_time_hidden');
    const form              = document.querySelector('form');
    const attendeesControls = document.getElementById('attendeesControls');
    const attendeesHint     = document.getElementById('attendeesHint');
    const attendeesCount    = document.getElementById('attendeesCount');
    const attendeesWidget   = document.getElementById('attendeesWidget');
    let tomSelect = null;

    // ── Time picker ──
    function updateTime() {
        const h  = parseInt(hourInput.value) || 0;
        const m  = parseInt(minuteInput.value) || 0;
        const ap = ampmSelect.value;
        if (h < 1 || h > 12 || !ap) { timeHidden.value = ''; return; }
        let h24 = h;
        if (ap === 'AM' && h === 12) h24 = 0;
        else if (ap === 'PM' && h !== 12) h24 = h + 12;
        timeHidden.value = String(h24).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':00';
    }
    hourInput.addEventListener('input', function() {
        if (this.value > 12) this.value = 12;
        if (this.value < 1 && this.value !== '') this.value = 1;
        updateTime();
    });
    minuteInput.addEventListener('input', function() {
        if (this.value > 59) this.value = 59;
        if (this.value < 0 && this.value !== '') this.value = 0;
        updateTime();
    });
    ampmSelect.addEventListener('change', updateTime);

    // Pre-fill time
    hourInput.value   = <?php echo $timeH12; ?>;
    minuteInput.value = String(<?php echo $timeMin; ?>).padStart(2, '0');
    ampmSelect.value  = '<?php echo $timeAmpm; ?>';
    updateTime();

    // ── Mode → show/hide Location & URL ──
    function updateModeFields() {
        const mode   = modeSelect.value;
        const showLoc = (mode === 'Offline' || mode === 'Hybrid');
        const showUrl = (mode === 'Online'  || mode === 'Hybrid');
        locationField.style.display   = showLoc ? '' : 'none';
        meetingUrlField.style.display = showUrl ? '' : 'none';
        modeHint.style.display        = mode ? 'none' : '';
        locationInput.required        = (mode === 'Offline');
        meetingUrlInput.required      = (mode === 'Online');
        if (!showLoc) locationInput.value  = '';
        if (!showUrl) meetingUrlInput.value = '';
    }
    modeSelect.addEventListener('change', updateModeFields);

    // Pre-fill mode (then restore values cleared by updateModeFields)
    if (preloadMode) {
        modeSelect.value = preloadMode;
        updateModeFields();
        locationInput.value  = preloadLocation;
        meetingUrlInput.value = preloadUrl;
    }

    // ── Attendees — Tom Select ──
    function loadAttendees(dept, preItems = []) {
        if (tomSelect) { tomSelect.destroy(); tomSelect = null; }
        attendeesWidget.style.display   = 'none';
        attendeesControls.style.display = 'none';
        attendeesCount.style.display    = 'none';

        if (!dept) {
            attendeesHint.innerHTML    = '<i class="fas fa-info-circle me-1"></i>Select a department above to load attendees.';
            attendeesHint.style.display = '';
            return;
        }

        const filtered = allUsers.filter(u => u.department === dept);
        if (filtered.length === 0) {
            attendeesHint.innerHTML    = '<i class="fas fa-exclamation-circle me-1 text-warning"></i>No employees found in this department.';
            attendeesHint.style.display = '';
            return;
        }

        if (typeof TomSelect === 'undefined') {
            attendeesHint.innerHTML    = '<i class="fas fa-exclamation-circle me-1 text-warning"></i>Attendees selector unavailable — check your internet connection. Changes can still be saved without modifying attendees.';
            attendeesHint.style.display = '';
            return;
        }

        attendeesWidget.style.display = '';
        tomSelect = new TomSelect('#attendeesSelect', {
            options:     filtered.map(u => ({ value: String(u.id), name: u.name, email: u.email })),
            items:       preItems,
            valueField:  'value',
            labelField:  'name',
            searchField: ['name', 'email'],
            plugins:     ['remove_button', 'checkbox_options'],
            placeholder: 'Search by name or email…',
            maxOptions:  null,
            onChange: function() {
                const n = this.getValue().length;
                attendeesCount.textContent  = n + ' selected';
                attendeesCount.style.display = n > 0 ? '' : 'none';
            },
            render: {
                option: function(data, escape) {
                    return `<div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <span class="fw-semibold">${escape(data.name)}</span>
                        <small class="text-muted">${escape(data.email)}</small>
                    </div>`;
                },
                item: function(data, escape) { return `<div>${escape(data.name)}</div>`; },
                no_results: function() { return '<div class="no-results px-3 py-2 text-muted small">No matching employees found.</div>'; }
            }
        });

        attendeesHint.style.display     = 'none';
        attendeesControls.style.display = '';
    }

    departmentSelect.addEventListener('change', function() { loadAttendees(this.value); });

    document.getElementById('selectAllBtn').addEventListener('click', function() {
        if (tomSelect) tomSelect.setValue(Object.keys(tomSelect.options));
    });
    document.getElementById('clearAllBtn').addEventListener('click', function() {
        if (tomSelect) tomSelect.clear();
    });

    // Pre-load existing department + attendees (wrapped so a CDN error cannot block the submit handler below)
    if (preloadDept) {
        try { loadAttendees(preloadDept, preloadItems); }
        catch (err) { console.warn('Attendees widget failed to load:', err); }
    }

    // ── Form submit validation ──
    form.addEventListener('submit', function(e) {
        updateTime();
        if (!hourInput.value || !ampmSelect.value || !timeHidden.value) {
            e.preventDefault();
            hourInput.focus();
            hourInput.classList.add('is-invalid');
            return;
        }
        hourInput.classList.remove('is-invalid');
    });
});
</script>

<script src="<?php echo $basePath; ?>/assets/vendor/tom-select/js/tom-select.complete.min.js"></script>
<?php include_once '../../includes/footer.php'; ?>
