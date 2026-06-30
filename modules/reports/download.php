<?php
// modules/reports/download.php
// Handles two CSV downloads:
//   ?type=all[&search=...]   — all meetings summary (role-filtered)
//   ?type=meeting&id=N       — single meeting attendee detail
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role'])) {
    header('Location: ../users/login.php');
    exit();
}

require_once '../../config/db.php';

$conn      = getDBConnection();
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$department = $_SESSION['department'] ?? '';
$type      = $_GET['type'] ?? '';

// ── helpers ──────────────────────────────────────────────────────────────────
function csv_row(array $fields): string {
    return implode(',', array_map(function ($v) {
        $v = str_replace('"', '""', $v ?? '');
        return '"' . $v . '"';
    }, $fields)) . "\r\n";
}

function send_csv(string $filename): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel opens correctly
}

// ── ALL MEETINGS SUMMARY ──────────────────────────────────────────────────────
if ($type === 'all') {
    $searchQuery    = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $params         = [];
    $types          = '';

    $sql = "SELECT m.id, m.title, m.meeting_date, m.meeting_time, m.location,
                   m.mode, m.department, m.status,
                   u.name AS organizer_name,
                   (SELECT COUNT(*) FROM tasks WHERE meeting_id = m.id) AS total_tasks,
                   (SELECT COUNT(*) FROM tasks WHERE meeting_id = m.id AND status = 'Completed') AS completed_tasks,
                   (SELECT COUNT(*) FROM attendance WHERE meeting_id = m.id) AS total_attendees,
                   (SELECT COUNT(*) FROM attendance WHERE meeting_id = m.id AND status = 'Present') AS present_count,
                   (SELECT COUNT(*) FROM attendance WHERE meeting_id = m.id AND status = 'Absent') AS absent_count
            FROM meetings m
            JOIN users u ON m.organizer_id = u.id";

    if ($role === 'Employee') {
        $sql .= " LEFT JOIN attendance a ON m.id = a.meeting_id
                  WHERE (m.department = ? OR a.user_id = ?)";
        $params[] = $department;
        $params[] = $user_id;
        $types   .= 'si';
    } else {
        $sql .= " WHERE 1=1";
    }

    if (!empty($searchQuery)) {
        $sql    .= " AND (m.title LIKE ? OR m.department LIKE ?)";
        $wild    = '%' . $searchQuery . '%';
        $params[] = $wild;
        $params[] = $wild;
        $types   .= 'ss';
    }

    $sql .= " GROUP BY m.id ORDER BY m.meeting_date DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $filename = 'meetings_report_' . date('Y-m-d') . '.csv';
    send_csv($filename);

    echo csv_row(['#', 'Meeting Title', 'Date', 'Time', 'Location', 'Mode',
                  'Department', 'Organizer', 'Status',
                  'Total Attendees', 'Present', 'Absent', 'Attendance Rate (%)',
                  'Total Tasks', 'Completed Tasks', 'Task Completion Rate (%)']);

    $i = 1;
    foreach ($rows as $r) {
        $attRate  = $r['total_attendees'] > 0
            ? round(($r['present_count'] / $r['total_attendees']) * 100) : 0;
        $taskRate = $r['total_tasks'] > 0
            ? round(($r['completed_tasks'] / $r['total_tasks']) * 100) : 0;

        echo csv_row([
            $i++,
            $r['title'],
            date('d-m-Y', strtotime($r['meeting_date'])),
            date('h:i A', strtotime($r['meeting_time'])),
            $r['location'],
            $r['mode'],
            $r['department'],
            $r['organizer_name'],
            $r['status'],
            $r['total_attendees'],
            $r['present_count'],
            $r['absent_count'],
            $attRate . '%',
            $r['total_tasks'],
            $r['completed_tasks'],
            $taskRate . '%',
        ]);
    }
    exit();
}

// ── SINGLE MEETING DETAIL ────────────────────────────────────────────────────
if ($type === 'meeting') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!$id) { header('Location: index.php'); exit(); }

    // Fetch meeting info
    $stmt = $conn->prepare(
        "SELECT m.*, u.name AS organizer_name
         FROM meetings m JOIN users u ON m.organizer_id = u.id
         WHERE m.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $meeting = $stmt->get_result()->fetch_assoc();

    if (!$meeting) { header('Location: index.php'); exit(); }

    // Role gate: employee can only download meetings they belong to
    if ($role === 'Employee') {
        $chk = $conn->prepare(
            "SELECT 1 FROM meetings m
             LEFT JOIN attendance a ON m.id = a.meeting_id
             WHERE m.id = ? AND (m.department = ? OR a.user_id = ?)
             LIMIT 1"
        );
        $chk->bind_param('isi', $id, $department, $user_id);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            header('Location: index.php');
            exit();
        }
    }

    // Fetch attendee list
    $stmt = $conn->prepare(
        "SELECT u.name, u.email, u.department, u.role, u.designation, u.taluka,
                a.status AS attendance_status, a.arrival_time, a.remarks
         FROM attendance a
         JOIN users u ON a.user_id = u.id
         WHERE a.meeting_id = ?
         ORDER BY a.status, u.name"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Counts
    $total   = count($attendees);
    $present = count(array_filter($attendees, fn($a) => $a['attendance_status'] === 'Present'));
    $absent  = count(array_filter($attendees, fn($a) => $a['attendance_status'] === 'Absent'));
    $pending = $total - $present - $absent;

    $safe    = preg_replace('/[^a-zA-Z0-9_-]/', '_', $meeting['title']);
    $filename = 'meeting_' . $safe . '_' . date('Y-m-d') . '.csv';
    send_csv($filename);

    // Meeting header block
    echo csv_row(['MEETING ATTENDANCE REPORT']);
    echo csv_row(['Title',        $meeting['title']]);
    echo csv_row(['Date',         date('d-m-Y', strtotime($meeting['meeting_date']))]);
    echo csv_row(['Time',         date('h:i A', strtotime($meeting['meeting_time']))]);
    echo csv_row(['Location',     $meeting['location']]);
    echo csv_row(['Mode',         $meeting['mode']]);
    echo csv_row(['Department',   $meeting['department']]);
    echo csv_row(['Organizer',    $meeting['organizer_name']]);
    echo csv_row(['Status',       $meeting['status']]);
    echo csv_row([]);
    echo csv_row(['Total Invited', $total, 'Present', $present, 'Absent', $absent, 'Pending', $pending]);
    echo csv_row([]);

    // Attendee table
    echo csv_row(['#', 'Name', 'Email', 'Department', 'Role', 'Designation', 'Taluka',
                  'Attendance Status', 'Arrival Time', 'Remarks']);

    if (empty($attendees)) {
        echo csv_row(['', 'No attendance records found for this meeting.']);
    } else {
        $i = 1;
        foreach ($attendees as $a) {
            echo csv_row([
                $i++,
                $a['name'],
                $a['email'],
                $a['department'],
                $a['role'],
                $a['designation'] ?? '',
                $a['taluka'] ?? '',
                $a['attendance_status'],
                $a['arrival_time'] ? date('h:i A', strtotime($a['arrival_time'])) : '',
                $a['remarks'] ?? '',
            ]);
        }
    }
    exit();
}

// fallback
header('Location: index.php');
exit();
