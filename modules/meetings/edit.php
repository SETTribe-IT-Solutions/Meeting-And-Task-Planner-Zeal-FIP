<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Organizer', 'Collector'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';
$departments = getDepartments();
$today = date('Y-m-d');
$conn = getDBConnection();

$meetingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($meetingId <= 0) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM meetings WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $meetingId);
$stmt->execute();
$meeting = $stmt->get_result()->fetch_assoc();

if (!$meeting || (int)$meeting['organizer_id'] !== (int)$_SESSION['user_id']) {
    $_SESSION['error'] = 'You are not authorized to edit this meeting.';
    header('Location: index.php');
    exit();
}

$users_result = $conn->query("SELECT id, name, email, department FROM users WHERE role = 'Employee' AND isDeleted = 'No' ORDER BY name ASC");
$all_users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

include_once '../../includes/header.php';

$meetingMode = $meeting['mode'];
$meetingLocation = $meeting['location'];
$meetingUrl = '';
if ($meetingMode === 'Online') {
    $meetingUrl = $meetingLocation;
    $meetingLocation = '';
} elseif ($meetingMode === 'Hybrid') {
    $parts = explode('|', $meetingLocation);
    $meetingLocation = trim($parts[0]);
    $meetingUrl = isset($parts[1]) ? trim($parts[1]) : '';
}
?>

<div class="row justify-content-center animate-on-scroll">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-header text-white fw-bold py-3 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #0b3d5f, #1a5f7a);">
                <i class="fas fa-calendar-edit fs-5"></i>
                <span>Edit Meeting</span>
            </div>
            <div class="card-body p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger rounded-3"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="../../controllers/MeetingController.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="meeting_id" value="<?php echo $meetingId; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Meeting Title</label>
                            <input type="text" name="title" class="form-control rounded-3" required value="<?php echo htmlspecialchars($meeting['title']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="meeting_date" class="form-control rounded-3" min="<?php echo htmlspecialchars($today); ?>" required value="<?php echo htmlspecialchars($meeting['meeting_date']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Time (12-hour)</label>
                            <div class="input-group">
                                <?php
                                $timeParts = explode(':', $meeting['meeting_time']);
                                $hour = (int)($timeParts[0] ?? 0);
                                $minute = (int)($timeParts[1] ?? 0);
                                $ampm = $hour >= 12 ? 'PM' : 'AM';
                                if ($hour === 0) {
                                    $hour = 12;
                                } elseif ($hour > 12) {
                                    $hour -= 12;
                                }
                                ?>
                                <input type="number" id="meeting_time_hour" class="form-control text-center fw-bold rounded-start-3" min="1" max="12" placeholder="12" required value="<?php echo htmlspecialchars($hour); ?>">
                                <span class="input-group-text fw-bold" style="font-size: 1.1rem;">:</span>
                                <input type="number" id="meeting_time_minute" class="form-control text-center fw-bold" min="0" max="59" placeholder="00" required value="<?php echo htmlspecialchars(str_pad($minute, 2, '0', STR_PAD_LEFT)); ?>">
                                <select id="meeting_time_ampm" class="form-select fw-bold rounded-end-3" required>
                                    <option value="AM" <?php echo $ampm === 'AM' ? 'selected' : ''; ?>>AM</option>
                                    <option value="PM" <?php echo $ampm === 'PM' ? 'selected' : ''; ?>>PM</option>
                                </select>
                            </div>
                            <small class="text-muted d-block mt-2">Format: HH:MM AM/PM (e.g., 02:30 PM)</small>
                            <input type="hidden" name="meeting_time" id="meeting_time_hidden">
                        </div>
                        <div class="col-md-6" id="location-field" style="display: none;">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="locationInput" class="form-control rounded-3" placeholder="e.g., Collector Office, Latur" value="<?php echo htmlspecialchars($meetingLocation); ?>">
                        </div>
                        <div class="col-md-6" id="meeting-url-field">
                            <label class="form-label">Meeting URL</label>
                            <input type="url" name="meeting_url" id="meetingUrlInput" class="form-control rounded-3" placeholder="https://" value="<?php echo htmlspecialchars($meetingUrl); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mode</label>
                            <select name="mode" id="modeSelect" class="form-select rounded-3" required>
                                <option value="Offline" <?php echo $meetingMode === 'Offline' ? 'selected' : ''; ?>>Offline</option>
                                <option value="Online" <?php echo $meetingMode === 'Online' ? 'selected' : ''; ?>>Online</option>
                                <option value="Hybrid" <?php echo $meetingMode === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select rounded-3" required>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo htmlspecialchars($department); ?>" <?php echo $meeting['department'] === $department ? 'selected' : ''; ?>><?php echo htmlspecialchars($department); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Agenda</label>
                            <textarea name="agenda" class="form-control rounded-3" rows="4" required><?php echo htmlspecialchars($meeting['agenda']); ?></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fas fa-save me-1"></i> Update Meeting</button>
                        <a href="index.php" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationField = document.getElementById('location-field');
    const meetingUrlField = document.getElementById('meeting-url-field');
    const locationInput = document.getElementById('locationInput');
    const meetingUrlInput = document.getElementById('meetingUrlInput');
    const modeSelect = document.getElementById('modeSelect');
    const hourInput = document.getElementById('meeting_time_hour');
    const minuteInput = document.getElementById('meeting_time_minute');
    const ampmSelect = document.getElementById('meeting_time_ampm');
    const timeHiddenInput = document.getElementById('meeting_time_hidden');
    const form = document.querySelector('form');

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
            hour24 = 0;
        } else if (ampm === 'PM' && hour !== 12) {
            hour24 = hour + 12;
        }

        const formattedTime = String(hour24).padStart(2, '0') + ':' + String(minute).padStart(2, '0') + ':00';
        timeHiddenInput.value = formattedTime;
    }

    function updateModeFields() {
        const mode = modeSelect.value;
        if (mode === 'Offline') {
            locationField.style.display = '';
            meetingUrlField.style.display = 'none';
            locationInput.required = true;
            meetingUrlInput.required = false;
        } else if (mode === 'Hybrid') {
            locationField.style.display = '';
            meetingUrlField.style.display = '';
            locationInput.required = false;
            meetingUrlInput.required = false;
        } else {
            locationField.style.display = 'none';
            meetingUrlField.style.display = '';
            locationInput.required = false;
            meetingUrlInput.required = true;
        }
    }

    modeSelect.addEventListener('change', updateModeFields);
    updateModeFields();
    updateTimeHiddenInput();

    form.addEventListener('submit', function(e) {
        updateTimeHiddenInput();
        const hour = hourInput.value;
        const minute = minuteInput.value;
        const ampm = ampmSelect.value;

        if (!hour || !minute || !ampm) {
            e.preventDefault();
            alert('Please select a valid time (HH:MM AM/PM)');
            return false;
        }
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>