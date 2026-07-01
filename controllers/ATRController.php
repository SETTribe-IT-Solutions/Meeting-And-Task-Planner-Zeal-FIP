<?php
// controllers/ATRController.php
// Handles Action Taken Report (ATR) CRUD, approval workflow, and history logging

class ATRController {
    private $db;
    public function __construct() {
        $this->db = getDBConnection();
    }

    // Create a new ATR entry for a given task (pre-fills some fields)
    public function createReport($taskId) {
        // fetch task and employee info
        $stmt = $this->db->prepare("SELECT assigned_to, due_date FROM tasks WHERE id = ?");
        $stmt->bind_param('i', $taskId);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        if (!$task) return false;
        $employeeId = $task['assigned_to'];
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("INSERT INTO atr_reports (task_id, meeting_id, employee_id, assigned_date, due_date, status) VALUES (?,?,?,NOW(),?, 'Pending')");
        $stmt->bind_param('iii', $taskId, $meetingId = null, $employeeId);
        $stmt->execute();
        $atrId = $stmt->insert_id;
        $this->addHistory($atrId, 'Created', 'ATR created for task', $_SESSION['user_id']);
        return $atrId;
    }

    // Retrieve ATR details
    public function getReport($atrId) {
        $stmt = $this->db->prepare("SELECT * FROM atr_reports WHERE id = ?");
        $stmt->bind_param('i', $atrId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update ATR progress, description, evidence, remarks
    public function updateReport($atrId, $data) {
        $fields = [];
        $params = [];
        $types = '';
        if (isset($data['progress_percent'])) { $fields[] = 'progress_percent = ?'; $params[] = $data['progress_percent']; $types .= 'i'; }
        if (isset($data['action_description'])) { $fields[] = 'action_description = ?'; $params[] = $data['action_description']; $types .= 's'; }
        if (isset($data['evidence_path'])) { $fields[] = 'evidence_path = ?'; $params[] = $data['evidence_path']; $types .= 's'; }
        if (isset($data['remarks'])) { $fields[] = 'remarks = ?'; $params[] = $data['remarks']; $types .= 's'; }
        if (empty($fields)) return false;
        $sql = "UPDATE atr_reports SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $atrId; $types .= 'i';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $this->addHistory($atrId, 'Progress Update', json_encode($data), $_SESSION['user_id']);
        return true;
    }

    // Submit for approval (sets approval_status to Pending)
    public function submitForApproval($atrId) {
        $stmt = $this->db->prepare("UPDATE atr_reports SET approval_status = 'Pending' WHERE id = ?");
        $stmt->bind_param('i', $atrId);
        $stmt->execute();
        $this->addHistory($atrId, 'Submitted', 'Submitted for approval', $_SESSION['user_id']);
    }

    // Approve or reject an ATR
    public function decideReport($atrId, $approverId, $decision, $comments = '') {
        $status = $decision === 'approve' ? 'Approved' : 'Rejected';
        $approvalStatus = $decision === 'approve' ? 'Approved' : 'Rejected';
        $stmt = $this->db->prepare("UPDATE atr_reports SET approval_status = ?, approved_by = ?, status = ? WHERE id = ?");
        $stmt->bind_param('sisi', $approvalStatus, $approverId, $status, $atrId);
        $stmt->execute();
        $detail = $decision . ' by user ' . $approverId . ($comments ? ': ' . $comments : '');
        $this->addHistory($atrId, $status, $detail, $approverId);
    }

    // Add an entry to atr_history
    private function addHistory($atrId, $eventType, $details, $performedBy) {
        $stmt = $this->db->prepare("INSERT INTO atr_history (atr_id, event_type, details, performed_by) VALUES (?,?,?,?)");
        $stmt->bind_param('issi', $atrId, $eventType, $details, $performedBy);
        $stmt->execute();
    }
}
?>
