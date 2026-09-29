<?php
// api/notifications.php
// Returns unread notifications for logged in patient and marks them read

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || $_SESSION['user_role'] !== 'patient') {
    echo json_encode(['notifications' => []]);
    exit;
}

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

// Fetch unread notifications
$stmt = $pdo->prepare("
    SELECT notification_id, message, type, created_at 
    FROM notifications 
    WHERE patient_id = ? AND is_read = 0 
    ORDER BY created_at ASC
");
$stmt->execute([$patient_id]);
$unread = $stmt->fetchAll();

if (!empty($unread)) {
    // Mark as read
    $upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE patient_id = ? AND is_read = 0");
    $upd->execute([$patient_id]);
}

echo json_encode([
    'success'       => true,
    'count'         => count($unread),
    'notifications' => $unread,
]);
exit;
