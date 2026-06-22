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
                            <label class="form-label small fw-semibold text-secondary">Time</label>
                            <input type="time" name="meeting_time" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Location</label>
                            <input type="text" name="location" class="form-control rounded-3" required>
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
                        <button type="submit" class="btn btn-primary rounded-3" style="background-color: var(--gov-blue);">Save Meeting</button>
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
    const addAttendeesBtn = document.getElementById('add-attendees-btn');
    const employeesSection = document.getElementById('employees-section');
    const employeesList = document.getElementById('employees-list');

    departmentSelect.addEventListener('change', function() {
        addAttendeesBtn.disabled = !this.value;
        renderDepartmentEmployees();
    });

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
