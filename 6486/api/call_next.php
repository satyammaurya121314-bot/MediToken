<?php
// api/call_next.php
// API endpoint for doctors to call the next waiting token

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo = get_db_connection();
$doctor_id = ($_SESSION['user_role'] === 'doctor') ? $_SESSION['user_id'] : (int)($_POST['doctor_id'] ?? $_GET['doctor_id'] ?? 0);
$date = trim($_POST['date'] ?? $_GET['date'] ?? date('Y-m-d'));

if ($doctor_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid doctor ID']);
    exit;
}

// Fetch doctor details
$doc_stmt = $pdo->prepare("SELECT doctor_name FROM doctors WHERE doctor_id = ?");
$doc_stmt->execute([$doctor_id]);
$doctor_name = $doc_stmt->fetchColumn() ?: 'Doctor';

// 1. Mark previous Serving as Completed
$curr_stmt = $pdo->prepare("SELECT appointment_id, patient_id, token_number FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving'");
$curr_stmt->execute([$doctor_id, $date]);
$curr_serving = $curr_stmt->fetch();

if ($curr_serving) {
    $upd_comp = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
    $upd_comp->execute([$curr_serving['appointment_id']]);
    add_notification($pdo, $curr_serving['patient_id'], "Your consultation with Dr. {$doctor_name} (Token {$curr_serving['token_number']}) has been completed.", 'completed', $doctor_id, $curr_serving['appointment_id']);
}

// 2. Find next Waiting appointment
$next_stmt = $pdo->prepare("
    SELECT appointment_id, patient_id, token_number 
    FROM appointments 
    WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'
    ORDER BY appointment_id ASC 
    LIMIT 1
");
$next_stmt->execute([$doctor_id, $date]);
$next_patient = $next_stmt->fetch();

if ($next_patient) {
    $upd_serv = $pdo->prepare("UPDATE appointments SET status = 'Serving' WHERE appointment_id = ?");
    $upd_serv->execute([$next_patient['appointment_id']]);

    // Send notification to the patient
    $notif = "Your token {$next_patient['token_number']} is now being served! Please enter Dr. {$doctor_name}'s room.";
    add_notification($pdo, $next_patient['patient_id'], $notif, 'token_called', $doctor_id, $next_patient['appointment_id']);

    // Find token following this one
    $after_stmt = $pdo->prepare("
        SELECT token_number 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting' AND appointment_id > ?
        ORDER BY appointment_id ASC 
        LIMIT 1
    ");
    $after_stmt->execute([$doctor_id, $date, $next_patient['appointment_id']]);
    $following_token = $after_stmt->fetchColumn() ?: 'None';

    echo json_encode([
        'success'        => true,
        'current_token'  => $next_patient['token_number'],
        'next_token'     => $following_token,
        'appointment_id' => $next_patient['appointment_id'],
        'message'        => "Called Token {$next_patient['token_number']}",
    ]);
} else {
    echo json_encode([
        'success'       => true,
        'current_token' => 'None',
        'next_token'    => 'None',
        'message'       => 'No waiting patients remain in queue for this date.',
    ]);
}
exit;
