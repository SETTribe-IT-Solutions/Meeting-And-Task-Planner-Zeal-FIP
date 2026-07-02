<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/upload_helper.php';

function momCanManage(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Organizer';
}

function momCanView(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['Organizer', 'Employee', 'Collector'], true);
}

function momRequireAccess(): void {
    if (!isLoggedIn()) {
        redirect('../modules/users/login.php');
    }

    if (!momCanView()) {
        $_SESSION['error'] = 'You do not have permission to view MoM records.';
        redirect('../index.php');
    }
}

function momRequireManage(): void {
    momRequireAccess();
    if (!momCanManage()) {
        $_SESSION['error'] = 'Only organizers can manage MoM records.';
        redirect('../mom.php');
    }
}

function getMomMeetingsList(): array {
    $conn = getDBConnection();
    $userRole = $_SESSION['role'] ?? '';
    $userDepartment = $_SESSION['department'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($userRole === 'Employee') {
        $stmt = $conn->prepare("SELECT DISTINCT m.id, m.title FROM meetings m LEFT JOIN attendance a ON a.meeting_id = m.id WHERE (m.department = ? OR a.user_id = ?) ORDER BY m.meeting_date DESC");
        $stmt->bind_param('si', $userDepartment, $userId);
    } else {
        $stmt = $conn->query("SELECT id, title FROM meetings ORDER BY meeting_date DESC");
        return $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMomDepartmentsList(): array {
    $conn = getDBConnection();
    $result = $conn->query("SELECT name FROM departments WHERE is_active = 'Yes' ORDER BY name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getMomTasksList(int $meetingId): array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, title FROM tasks WHERE meeting_id = ? ORDER BY title ASC");
    $stmt->bind_param('i', $meetingId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMomRecords(array $filters = []): array {
    $conn = getDBConnection();
    $userRole = $_SESSION['role'] ?? '';
    $userDepartment = $_SESSION['department'] ?? '';
    $userId = (int)($_SESSION['user_id'] ?? 0);

    $sql = "SELECT mr.*, m.title AS meeting_title, u.name AS created_by_name, t.title AS linked_task_title
            FROM mom_records mr
            LEFT JOIN meetings m ON mr.meeting_id = m.id
            LEFT JOIN users u ON mr.created_by = u.id
            LEFT JOIN tasks t ON mr.linked_task_id = t.id";

    $where = [];
    $params = [];
    $types = '';

    if ($userRole === 'Employee') {
        $where[] = "(m.department = ? OR EXISTS (SELECT 1 FROM attendance a WHERE a.meeting_id = m.id AND a.user_id = ?))";
        $params[] = $userDepartment;
        $params[] = $userId;
        $types .= 'si';
    }

    if (!empty($filters['department'])) {
        $where[] = 'mr.department = ?';
        $params[] = $filters['department'];
        $types .= 's';
    }

    if (!empty($filters['meeting_id'])) {
        $where[] = 'mr.meeting_id = ?';
        $params[] = (int)$filters['meeting_id'];
        $types .= 'i';
    }

    if (!empty($filters['search'])) {
        $search = '%' . trim($filters['search']) . '%';
        $where[] = '(mr.note_title LIKE ? OR mr.note_description LIKE ? OR m.title LIKE ?)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY mr.created_at DESC';

    $stmt = $conn->prepare($sql);
    if ($stmt && $params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getMomRecordById(int $id): ?array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT mr.*, m.title AS meeting_title, u.name AS created_by_name, t.title AS linked_task_title
        FROM mom_records mr
        LEFT JOIN meetings m ON mr.meeting_id = m.id
        LEFT JOIN users u ON mr.created_by = u.id
        LEFT JOIN tasks t ON mr.linked_task_id = t.id
        WHERE mr.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    return $record ?: null;
}

function createMomRecord(array $data): array {
    momRequireManage();

    $meetingId = (int)($data['meeting_id'] ?? 0);
    $noteTitle = trim($data['note_title'] ?? '');
    $noteDescription = trim($data['note_description'] ?? '');
    $department = trim($data['department'] ?? '');
    $linkedTaskId = !empty($data['linked_task_id']) ? (int)$data['linked_task_id'] : null;
    $createdBy = (int)($_SESSION['user_id'] ?? 0);

    if ($meetingId <= 0 || $noteTitle === '' || $noteDescription === '' || $department === '') {
        return ['success' => false, 'message' => 'Meeting, title, description, and department are required.'];
    }

    $attachmentName = null;
    if (!empty($_FILES['attachment']['name'])) {
        $upload = validateAndStoreUpload('attachment', dirname(__DIR__) . '/uploads/moms');
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['error']];
        }
        $attachmentName = $upload['stored_name'];
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO mom_records (meeting_id, note_title, note_description, department, linked_task_id, attachment, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssiss', $meetingId, $noteTitle, $noteDescription, $department, $linkedTaskId, $attachmentName, $createdBy);
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'MoM saved successfully.'];
    }

    return ['success' => false, 'message' => 'Unable to save MoM.'];
}

function updateMomRecord(array $data): array {
    momRequireManage();

    $id = (int)($data['id'] ?? 0);
    $meetingId = (int)($data['meeting_id'] ?? 0);
    $noteTitle = trim($data['note_title'] ?? '');
    $noteDescription = trim($data['note_description'] ?? '');
    $department = trim($data['department'] ?? '');
    $linkedTaskId = !empty($data['linked_task_id']) ? (int)$data['linked_task_id'] : null;

    if ($id <= 0 || $meetingId <= 0 || $noteTitle === '' || $noteDescription === '' || $department === '') {
        return ['success' => false, 'message' => 'All required fields must be provided.'];
    }

    $conn = getDBConnection();
    $current = getMomRecordById($id);
    $attachmentName = $current['attachment'] ?? null;

    if (!empty($_FILES['attachment']['name'])) {
        $upload = validateAndStoreUpload('attachment', dirname(__DIR__) . '/uploads/moms');
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['error']];
        }
        $attachmentName = $upload['stored_name'];
        if (!empty($current['attachment'])) {
            deleteUploadFile(dirname(__DIR__) . '/uploads/moms', $current['attachment']);
        }
    }

    $stmt = $conn->prepare("UPDATE mom_records SET meeting_id = ?, note_title = ?, note_description = ?, department = ?, linked_task_id = ?, attachment = ? WHERE id = ?");
    $stmt->bind_param('isssisi', $meetingId, $noteTitle, $noteDescription, $department, $linkedTaskId, $attachmentName, $id);
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'MoM updated successfully.'];
    }

    return ['success' => false, 'message' => 'Unable to update MoM.'];
}

function deleteMomRecord(int $id): array {
    momRequireManage();
    $conn = getDBConnection();
    $record = getMomRecordById($id);
    if (!$record) {
        return ['success' => false, 'message' => 'MoM record not found.'];
    }

    $stmt = $conn->prepare('DELETE FROM mom_records WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        if (!empty($record['attachment'])) {
            deleteUploadFile(dirname(__DIR__) . '/uploads/moms', $record['attachment']);
        }
        return ['success' => true, 'message' => 'MoM deleted successfully.'];
    }

    return ['success' => false, 'message' => 'Unable to delete MoM.'];
}

function getMomStats(): array {
    $conn = getDBConnection();
    $stats = ['total_meetings' => 0, 'total_moms' => 0, 'latest_mom' => 'No MoM yet', 'pending_tasks' => 0];

    $meetingsResult = $conn->query('SELECT COUNT(*) AS total FROM meetings');
    $stats['total_meetings'] = (int)($meetingsResult->fetch_assoc()['total'] ?? 0);

    $momsResult = $conn->query('SELECT COUNT(*) AS total FROM mom_records');
    $stats['total_moms'] = (int)($momsResult->fetch_assoc()['total'] ?? 0);

    $latestResult = $conn->query('SELECT note_title FROM mom_records ORDER BY created_at DESC LIMIT 1');
    $stats['latest_mom'] = $latestResult->fetch_assoc()['note_title'] ?? 'No MoM yet';

    $pendingResult = $conn->query("SELECT COUNT(*) AS total FROM tasks t JOIN mom_records mr ON mr.linked_task_id = t.id WHERE t.status IN ('Pending', 'In Progress')");
    $stats['pending_tasks'] = (int)($pendingResult->fetch_assoc()['total'] ?? 0);

    return $stats;
}

function downloadMomReport(array $records, string $format): void {
    $filename = 'mom_report_' . date('Ymd_His');
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Meeting', 'Note Title', 'Department', 'Linked Task', 'Created By', 'Created At']);
        foreach ($records as $record) {
            fputcsv($out, [
                $record['meeting_title'] ?? '',
                $record['note_title'] ?? '',
                $record['department'] ?? '',
                $record['linked_task_title'] ?? '',
                $record['created_by_name'] ?? '',
                $record['created_at'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }

    if ($format === 'pdf') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>MoM Report</title><style>body{font-family:Arial,sans-serif;padding:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:8px}</style></head><body><h2>MoM Report</h2><p>Generated on ' . date('d M Y H:i') . '</p><table><thead><tr><th>Meeting</th><th>Note Title</th><th>Department</th><th>Created By</th><th>Created At</th></tr></thead><tbody>';
        foreach ($records as $record) {
            echo '<tr><td>' . htmlspecialchars($record['meeting_title'] ?? '') . '</td><td>' . htmlspecialchars($record['note_title'] ?? '') . '</td><td>' . htmlspecialchars($record['department'] ?? '') . '</td><td>' . htmlspecialchars($record['created_by_name'] ?? '') . '</td><td>' . htmlspecialchars($record['created_at'] ?? '') . '</td></tr>';
        }
        echo '</tbody></table><script>window.print();</script></body></html>';
        exit;
    }
}
