<?php
// api/book_appointment.php
// JSON API for booking appointments with slot check and token generation

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || $_SESSION['user_role'] !== 'patient') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Patient login required']);
    exit;
}

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

// Decode JSON input or POST fields
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$doctor_id = (int)($input['doctor_id'] ?? 0);
$appointment_date = trim($input['appointment_date'] ?? '');
$appointment_time = trim($input['appointment_time'] ?? '');

if ($doctor_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
    echo json_encode(['success' => false, 'error' => 'Missing doctor_id, appointment_date, or appointment_time']);
    exit;
}

if ($appointment_date < date('Y-m-d')) {
    echo json_encode(['success' => false, 'error' => 'Cannot book appointments for past dates']);
    exit;
}

// Doctor verification
$doc_stmt = $pdo->prepare("SELECT doctor_name, specialization, availability_status FROM doctors WHERE doctor_id = ?");
$doc_stmt->execute([$doctor_id]);
$doctor = $doc_stmt->fetch();

if (!$doctor) {
    echo json_encode(['success' => false, 'error' => 'Doctor not found']);
    exit;
}

if ($doctor['availability_status'] !== 'Available') {
    echo json_encode(['success' => false, 'error' => 'Doctor is currently unavailable for consultations']);
    exit;
}

$pdo->beginTransaction();
try {
    // 1. Check slot conflict
    $slot_stmt = $pdo->prepare("
        SELECT appointment_id 
        FROM appointments 
        WHERE doctor_id = ? 
          AND appointment_date = ? 
          AND appointment_time = ? 
          AND status != 'Cancelled' 
        FOR UPDATE
    ");
    $slot_stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
    if ($slot_stmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'This appointment slot is already booked']);
        exit;
    }

    // 2. Generate unique token
    $token_number = generate_doctor_token($pdo, $doctor_id, $appointment_date);

    // 3. Insert appointment
    $ins = $pdo->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, token_number, status)
        VALUES (?, ?, ?, ?, ?, 'Waiting')
    ");
    $ins->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $token_number]);
    $appointment_id = $pdo->lastInsertId();

    $pdo->commit();

    // 4. Notification
    add_notification($pdo, $patient_id, "Your appointment is confirmed with Dr. {$doctor['doctor_name']} on {$appointment_date} at {$appointment_time}. Token: {$token_number}", 'appointment_confirmed', $doctor_id, $appointment_id);

    echo json_encode([
        'success'          => true,
        'appointment_id'   => $appointment_id,
        'doctor_name'      => $doctor['doctor_name'],
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'token_number'     => $token_number,
        'status'           => 'Waiting',
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("API Booking Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error while booking appointment']);
}
exit;
