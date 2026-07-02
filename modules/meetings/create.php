<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Organizer') {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';
$departments = getDepartments();
$today = date('Y-m-d');
$conn = getDBConnection();
$users_result = $conn->query("SELECT id, name, email, department FROM users WHERE role = 'Employee' AND isDeleted = 'No' ORDER BY name ASC");
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

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
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header text-white fw-bold py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                <i class="fas fa-calendar-plus fs-5"></i>
                <span>Schedule New Meeting</span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/MeetingController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <div class="row g-3">

                        <!-- Row 1: Title + Date -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required placeholder="e.g., District Planning Review">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date" class="form-control rounded-3" min="<?php echo htmlspecialchars($today); ?>" required>
                        </div>

                        <!-- Row 2: Time + Mode + Duration -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Time <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" id="meeting_time_hour" class="form-control text-center fw-bold rounded-start-3" min="1" max="12" placeholder="HH" required>
                                <span class="input-group-text fw-bold px-1">:</span>
                                <input type="number" id="meeting_time_minute" class="form-control text-center fw-bold" min="0" max="59" placeholder="00" value="00">
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
                            <select name="duration" class="form-select rounded-3">
                                <option value="">-- Not specified --</option>
                                <option value="15">15 min</option>
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60">1 hr (60 min)</option>
                                <option value="90">1.5 hr (90 min)</option>
                                <option value="120">2 hr (120 min)</option>
                                <option value="180">3 hr (180 min)</option>
                                <option value="240">4 hr (240 min)</option>
                                <option value="300">5 hr (300 min)</option>
                                <option value="360">6 hr (360 min)</option>
                                <option value="420">7 hr (420 min)</option>
                                <option value="480">8 hr (480 min)</option>
                            </select>
                            <div class="form-text">Optional</div>
                        </div>

                        <!-- Row 3: Location (Offline / Hybrid) -->
                        <div class="col-12" id="location-field" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>Location <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="location" id="locationInput" class="form-control rounded-3" placeholder="e.g., Collector Office - Conference Hall, Latur">
                        </div>

                        <!-- Row 3b: Meeting URL (Online / Hybrid) -->
                        <div class="col-12" id="meeting-url-field" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-video text-primary me-1"></i>Meeting URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="meeting_url" id="meetingUrlInput" class="form-control rounded-3" placeholder="https://meet.google.com/... or https://zoom.us/j/...">
                        </div>

                        <!-- Mode hint when nothing selected -->
                        <div class="col-12" id="mode-hint">
                            <div class="text-muted small bg-light rounded-3 p-2 ps-3 border-start border-3 border-secondary">
                                <i class="fas fa-info-circle me-1"></i> Select a meeting mode above — location and/or URL fields will appear accordingly.
                            </div>
                        </div>

                        <!-- Row 4: Department -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                            <select name="department" id="departmentSelect" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Attendees searchable multi-select -->
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
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required placeholder="Meeting agenda points...&#10;• Point 1&#10;• Point 2"></textarea>
                        </div>

                        <!-- Agenda -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Agenda <span class="text-danger">*</span></label>
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required placeholder="Meeting agenda points...&#10;• Point 1&#10;• Point 2"></textarea>
                        </div>

                        <!-- Attachment (optional) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-paperclip text-secondary me-1"></i>Attach File
                                <span class="text-muted fw-normal small">(optional)</span>
                            </label>
                            <input type="file" name="meeting_attachment" class="form-control rounded-3"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg">
                            <div class="form-text">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG &mdash; Max 10 MB</div>
                        </div>

                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fas fa-calendar-check me-1"></i> Schedule Meeting</button>
                        <a href="../../index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allUsers       = <?php echo json_encode($all_users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const modeSelect      = document.getElementById('modeSelect');
    const departmentSelect= document.getElementById('departmentSelect');
    const locationField   = document.getElementById('location-field');
    const meetingUrlField = document.getElementById('meeting-url-field');
    const locationInput   = document.getElementById('locationInput');
    const meetingUrlInput = document.getElementById('meetingUrlInput');
    const modeHint        = document.getElementById('mode-hint');
    const hourInput       = document.getElementById('meeting_time_hour');
    const minuteInput     = document.getElementById('meeting_time_minute');
    const ampmSelect      = document.getElementById('meeting_time_ampm');
    const timeHidden      = document.getElementById('meeting_time_hidden');
    const form            = document.querySelector('form');
    const attendeesControls = document.getElementById('attendeesControls');
    const attendeesHint   = document.getElementById('attendeesHint');
    const attendeesCount  = document.getElementById('attendeesCount');
    let tomSelect = null;

    // ── Time picker ──
    function updateTime() {
        const h = parseInt(hourInput.value) || 0;
        const m = parseInt(minuteInput.value) || 0;
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

    // ── Mode → show/hide Location & URL ──
    function updateModeFields() {
        const mode = modeSelect.value;
        const showLoc = (mode === 'Offline' || mode === 'Hybrid');
        const showUrl = (mode === 'Online'  || mode === 'Hybrid');

        locationField.style.display   = showLoc ? '' : 'none';
        meetingUrlField.style.display = showUrl ? '' : 'none';
        modeHint.style.display        = mode ? 'none' : '';

        locationInput.required   = (mode === 'Offline');
        meetingUrlInput.required = (mode === 'Online');

        // Clear value of the hidden field when not shown
        if (!showLoc) locationInput.value = '';
        if (!showUrl) meetingUrlInput.value = '';
    }
    modeSelect.addEventListener('change', updateModeFields);
    updateModeFields(); // init state

    // ── Attendees — Tom Select searchable multi-select ──
    const attendeesWidget = document.getElementById('attendeesWidget');

    function loadAttendees(dept) {
        if (tomSelect) { tomSelect.destroy(); tomSelect = null; }
        attendeesWidget.style.display = 'none';
        attendeesControls.style.display = 'none';
        attendeesCount.style.display = 'none';

        if (!dept) {
            attendeesHint.innerHTML = '<i class="fas fa-info-circle me-1"></i>Select a department above to load attendees.';
            attendeesHint.style.display = '';
            return;
        }

        const filtered = allUsers.filter(u => u.department === dept);

        if (filtered.length === 0) {
            attendeesHint.innerHTML = '<i class="fas fa-exclamation-circle me-1 text-warning"></i>No employees found in this department.';
            attendeesHint.style.display = '';
            return;
        }

        if (typeof TomSelect === 'undefined') {
            attendeesHint.innerHTML = '<i class="fas fa-exclamation-circle me-1 text-warning"></i>Attendees selector unavailable — check your internet connection. Meeting can still be scheduled without attendees.';
            attendeesHint.style.display = '';
            return;
        }

        attendeesWidget.style.display = '';
        tomSelect = new TomSelect('#attendeesSelect', {
            options: filtered.map(u => ({ value: String(u.id), name: u.name, email: u.email })),
            items: [],
            valueField: 'value',
            labelField: 'name',
            searchField: ['name', 'email'],
            plugins: ['remove_button', 'checkbox_options'],
            placeholder: 'Search by name or email…',
            maxOptions: null,
            onChange: function() {
                const n = this.getValue().length;
                attendeesCount.textContent = n + ' selected';
                attendeesCount.style.display = n > 0 ? '' : 'none';
            },
            render: {
                option: function(data, escape) {
                    return `<div class="d-flex justify-content-between align-items-center gap-3 py-1">
                        <span class="fw-semibold">${escape(data.name)}</span>
                        <small class="text-muted">${escape(data.email)}</small>
                    </div>`;
                },
                item: function(data, escape) {
                    return `<div>${escape(data.name)}</div>`;
                },
                no_results: function() {
                    return '<div class="no-results px-3 py-2 text-muted small">No matching employees found.</div>';
                }
            }
        });

        attendeesHint.style.display = 'none';
        attendeesControls.style.display = '';
    }

    departmentSelect.addEventListener('change', function() { loadAttendees(this.value); });

    document.getElementById('selectAllBtn').addEventListener('click', function() {
        if (!tomSelect) return;
        tomSelect.setValue(Object.keys(tomSelect.options));
    });

    document.getElementById('clearAllBtn').addEventListener('click', function() {
        if (tomSelect) tomSelect.clear();
    });

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
