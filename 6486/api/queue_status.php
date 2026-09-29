<?php
// api/queue_status.php
// Returns real-time queue status as JSON for live polling

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$appointment_id = (int)($_GET['appointment_id'] ?? 0);

if ($doctor_id <= 0 && $appointment_id <= 0) {
    echo json_encode(['error' => 'Missing doctor_id or appointment_id parameter']);
    exit;
}

// If appointment_id provided, fetch appointment details first
$apt_record = null;
$query_date = date('Y-m-d');

if ($appointment_id > 0) {
    $apt_stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
    $apt_stmt->execute([$appointment_id]);
    $apt_record = $apt_stmt->fetch();
    if ($apt_record) {
        $doctor_id = (int)$apt_record['doctor_id'];
        $query_date = $apt_record['appointment_date'];
    }
}

// 1. Current Serving Token
$serving_stmt = $pdo->prepare("
    SELECT token_number 
    FROM appointments 
    WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving'
    LIMIT 1
");
$serving_stmt->execute([$doctor_id, $query_date]);
$current_token = $serving_stmt->fetchColumn() ?: 'None';

// 2. Next Token
$next_stmt = $pdo->prepare("
    SELECT token_number 
    FROM appointments 
    WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'
    ORDER BY appointment_id ASC
    LIMIT 1
");
$next_stmt->execute([$doctor_id, $query_date]);
$next_token = $next_stmt->fetchColumn() ?: 'None';

// 3. Patients ahead and estimated wait
$patients_ahead = 0;
$your_token = $apt_record['token_number'] ?? 'None';
$status = $apt_record['status'] ?? 'None';

if ($apt_record) {
    if ($status === 'Serving') {
        $patients_ahead = 0;
    } elseif ($status === 'Waiting') {
        $ahead_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE doctor_id = ? 
              AND appointment_date = ? 
              AND status = 'Waiting' 
              AND appointment_id < ?
        ");
        $ahead_stmt->execute([$doctor_id, $query_date, $apt_record['appointment_id']]);
        $patients_ahead = (int)$ahead_stmt->fetchColumn();
    }
} else {
    // Total waiting for doctor today
    $tot_wait_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'
    ");
    $tot_wait_stmt->execute([$doctor_id, $query_date]);
    $patients_ahead = (int)$tot_wait_stmt->fetchColumn();
}

$estimated_wait = calculate_estimated_wait($patients_ahead, $status);

echo json_encode([
    'success'        => true,
    'doctor_id'      => $doctor_id,
    'appointment_id' => $appointment_id,
    'current_token'  => $current_token,
    'next_token'     => $next_token,
    'your_token'     => $your_token,
    'patients_ahead' => $patients_ahead,
    'status'         => $status,
    'status_html'    => render_status_badge($status),
    'estimated_wait' => $estimated_wait,
    'timestamp'      => time(),
]);
exit;
