<?php
// api/cancel_appointment.php
// JSON API for cancelling appointments

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$pdo = get_db_connection();
$user = current_user();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$appointment_id = (int)($input['appointment_id'] ?? 0);
if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid appointment ID']);
    exit;
}

// Check appointment permissions
if ($user['role'] === 'patient') {
    $stmt = $pdo->prepare("SELECT appointment_id, patient_id, doctor_id, token_number, status FROM appointments WHERE appointment_id = ? AND patient_id = ?");
    $stmt->execute([$appointment_id, $user['id']]);
} else {
    // Admin or Doctor
    $stmt = $pdo->prepare("SELECT appointment_id, patient_id, doctor_id, token_number, status FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
}

$apt = $stmt->fetch();
if (!$apt) {
    echo json_encode(['success' => false, 'error' => 'Appointment not found or unauthorized']);
    exit;
}

if ($apt['status'] === 'Completed') {
    echo json_encode(['success' => false, 'error' => 'Cannot cancel an already completed appointment']);
    exit;
}

if ($apt['status'] === 'Cancelled') {
    echo json_encode(['success' => false, 'error' => 'Appointment is already cancelled']);
    exit;
}

// Update status to Cancelled
$upd = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
$upd->execute([$appointment_id]);

add_notification($pdo, $apt['patient_id'], "Appointment #{$appointment_id} (Token: {$apt['token_number']}) has been cancelled.", 'appointment_cancelled', $apt['doctor_id'], $appointment_id);

echo json_encode([
    'success'        => true,
    'appointment_id' => $appointment_id,
    'status'         => 'Cancelled',
    'message'        => 'Appointment has been cancelled successfully',
]);
exit;
