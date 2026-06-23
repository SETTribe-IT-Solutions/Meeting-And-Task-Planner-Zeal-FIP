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

<div class="row justify-content-center my-5">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header text-white fw-bold py-3" style="background: linear-gradient(90deg, #003366, #0055aa);">
                Create New Meeting
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/MeetingController.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Meeting Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Date</label>
                            <input type="date" name="meeting_date" class="form-control rounded-3" min="<?php echo htmlspecialchars($today); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Time (12-hour)</label>
                            <div class="input-group">
                                <input type="number" id="meeting_time_hour" class="form-control text-center fw-bold" min="1" max="12" placeholder="12" required style="font-size: 1.1rem; letter-spacing: 2px;">
                                <span class="input-group-text fw-bold" style="font-size: 1.1rem;">:</span>
                                <input type="number" id="meeting_time_minute" class="form-control text-center fw-bold" min="0" max="59" placeholder="00" required style="font-size: 1.1rem; letter-spacing: 2px;">
                                <select id="meeting_time_ampm" class="form-select fw-bold" required style="font-size: 1rem; min-width: 95px;">
                                    <option value="">AM/PM</option>
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                            <small class="text-muted d-block mt-2">Format: HH:MM AM/PM (e.g., 02:30 PM)</small>
                            <input type="hidden" name="meeting_time" id="meeting_time_hidden">
                        </div>
                        <div class="col-md-6" id="location-field" style="display: none;">
                            <label class="form-label small fw-semibold text-secondary">Location</label>
                            <input type="text" name="location" id="locationInput" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6" id="meeting-url-field">
                            <label class="form-label small fw-semibold text-secondary">Meeting URL</label>
                            <input type="url" name="meeting_url" id="meetingUrlInput" class="form-control rounded-3" placeholder="https://" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Mode</label>
                            <select name="mode" class="form-select rounded-3" required>
                                <option value="">Select mode</option>
                                <option>Offline</option>
                                <option>Online</option>
                                <option>Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Department</label>
                            <select name="department" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-primary rounded-3" id="add-attendees-btn" disabled>
                                <i class="fas fa-user-plus me-1"></i> Add Attendees
                            </button>
                        </div>
                        <div class="col-12" id="employees-section" style="display: none;">
                            <label class="form-label small fw-semibold text-secondary">Select Attendees from Selected Department</label>
                            <div id="employees-list" class="p-3 border rounded-3 bg-white" style="max-height: 200px; overflow-y: auto;">
                                <!-- Checkboxes will be populated here via JavaScript -->
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Agenda</label>
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3" >Save Meeting</button>
                        <a href="../../index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allUsers = <?php echo json_encode($all_users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const departmentSelect = document.querySelector('select[name="department"]');
    const modeSelect = document.querySelector('select[name="mode"]');
    const locationField = document.getElementById('location-field');
    const meetingUrlField = document.getElementById('meeting-url-field');
    const locationInput = document.getElementById('locationInput');
    const meetingUrlInput = document.getElementById('meetingUrlInput');
    const addAttendeesBtn = document.getElementById('add-attendees-btn');
    const employeesSection = document.getElementById('employees-section');
    const employeesList = document.getElementById('employees-list');

    // 12-hour time picker elements
    const hourInput = document.getElementById('meeting_time_hour');
    const minuteInput = document.getElementById('meeting_time_minute');
    const ampmSelect = document.getElementById('meeting_time_ampm');
    const timeHiddenInput = document.getElementById('meeting_time_hidden');
    const form = document.querySelector('form');

    // Convert 12-hour format to 24-hour format and update hidden input
    function updateTimeHiddenInput() {
        const hour = parseInt(hourInput.value) || 0;
        const minute = parseInt(minuteInput.value) || 0;
        const ampm = ampmSelect.value;

        if (hour < 1 || hour > 12 || !ampm) {
            timeHiddenInput.value = '';
            return;
        }

        let hour24 = hour;
        if (ampm === 'AM' && hour === 12) {
            hour24 = 0; // 12 AM = 00:00
        } else if (ampm === 'PM' && hour !== 12) {
            hour24 = hour + 12; // 1-11 PM = 13-23
        }

        // Format as HH:MM:SS for database storage
        const formattedTime = String(hour24).padStart(2, '0') + ':' + String(minute).padStart(2, '0') + ':00';
        timeHiddenInput.value = formattedTime;
    }

    // Add event listeners for time inputs
    hourInput.addEventListener('change', updateTimeHiddenInput);
    hourInput.addEventListener('input', function() {
        if (this.value > 12) this.value = 12;
        if (this.value < 1 && this.value !== '') this.value = 1;
    });

    minuteInput.addEventListener('change', updateTimeHiddenInput);
    minuteInput.addEventListener('input', function() {
        if (this.value > 59) this.value = 59;
        if (this.value < 0 && this.value !== '') this.value = 0;
    });

    ampmSelect.addEventListener('change', updateTimeHiddenInput);

    // Validate form before submission
    form.addEventListener('submit', function(e) {
        const hour = hourInput.value;
        const minute = minuteInput.value;
        const ampm = ampmSelect.value;

        if (!hour || !minute || !ampm) {
            e.preventDefault();
            alert('Please select a valid time (HH:MM AM/PM)');
            return false;
        }

        if (parseInt(hour) < 1 || parseInt(hour) > 12) {
            e.preventDefault();
            alert('Hour must be between 1 and 12');
            return false;
        }

        if (parseInt(minute) < 0 || parseInt(minute) > 59) {
            e.preventDefault();
            alert('Minute must be between 0 and 59');
            return false;
        }
    });

    departmentSelect.addEventListener('change', function() {
        addAttendeesBtn.disabled = !this.value;
        renderDepartmentEmployees();
    });

    // Toggle location / meeting URL based on selected mode
    function updateModeFields() {
        if (!modeSelect) return;
        const mode = modeSelect.value;
        if (mode === 'Offline') {
            // show location, hide URL
            if (locationField) locationField.style.display = '';
            if (meetingUrlField) meetingUrlField.style.display = 'none';
            if (locationInput) locationInput.required = true;
            if (meetingUrlInput) meetingUrlInput.required = false;
        } else if (mode === 'Hybrid') {
            // show both; neither strictly required so user can provide either or both
            if (locationField) locationField.style.display = '';
            if (meetingUrlField) meetingUrlField.style.display = '';
            if (locationInput) locationInput.required = false;
            if (meetingUrlInput) meetingUrlInput.required = false;
        } else {
            // show URL (default), hide location
            if (locationField) locationField.style.display = 'none';
            if (meetingUrlField) meetingUrlField.style.display = '';
            if (locationInput) locationInput.required = false;
            if (meetingUrlInput) meetingUrlInput.required = true;
        }
    }

    if (modeSelect) {
        modeSelect.addEventListener('change', updateModeFields);
        // set initial state
        updateModeFields();
    }

    addAttendeesBtn.addEventListener('click', function() {
        renderDepartmentEmployees();
    });

    function renderDepartmentEmployees() {
        const dept = departmentSelect.value;
        employeesList.innerHTML = '';
        
        if (!dept) {
            employeesSection.style.display = 'none';
            return;
        }

        const filtered = allUsers.filter(u => u.department === dept);
        
        if (filtered.length === 0) {
            employeesList.innerHTML = '<div class="text-muted small">No employees found in this department.</div>';
        } else {
            filtered.forEach(u => {
                const div = document.createElement('div');
                div.className = 'form-check mb-2';
                div.innerHTML = `
                    <input class="form-check-input" type="checkbox" name="attendees[]" value="${u.id}" id="user-${u.id}">
                    <label class="form-check-label text-dark small" for="user-${u.id}">
                        <strong>${escapeHtml(u.name)}</strong> (${escapeHtml(u.email)})
                    </label>
                `;
                employeesList.appendChild(div);
            });
        }
        employeesSection.style.display = 'block';
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;');
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>
