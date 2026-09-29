<?php
// includes/functions.php
// Core helper functions for MediToken

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize user input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 */
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function display_flash() {
    $flash = get_flash();
    if ($flash) {
        $alertClass = 'alert-' . ($flash['type'] === 'error' ? 'danger' : $flash['type']);
        echo '<div class="alert ' . htmlspecialchars($alertClass) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

/**
 * Generate formatted status badge
 */
function render_status_badge($status) {
    $status = trim($status);
    $badgeClasses = [
        'Waiting'   => 'badge-waiting',
        'Serving'   => 'badge-serving',
        'Completed' => 'badge-completed',
        'Cancelled' => 'badge-cancelled',
        'Skipped'   => 'badge-skipped',
        'No-Show'   => 'badge-noshow',
    ];
    $cls = $badgeClasses[$status] ?? 'badge-secondary';
    $pulse = ($status === 'Serving') ? '<span class="status-pulse"></span>' : '';
    return '<span class="status-badge ' . $cls . '">' . $pulse . htmlspecialchars($status) . '</span>';
}

/**
 * Format date for display
 */
function format_date($date_str) {
    if (!$date_str) return 'N/A';
    $timestamp = strtotime($date_str);
    return date('d M Y', $timestamp);
}

/**
 * Format time for display
 */
function format_time($time_str) {
    if (!$time_str) return 'N/A';
    $timestamp = strtotime($time_str);
    return date('h:i A', $timestamp);
}

/**
 * Generate time slots from doctor's available time string
 * Example: "09:00 AM - 01:00 PM" with 20-min slots
 */
function generate_time_slots($available_time_str, $interval_minutes = 20) {
    $slots = [];
    if (empty($available_time_str) || !strpos($available_time_str, '-')) {
        // Default standard clinic hours
        $start_time = strtotime('09:00 AM');
        $end_time   = strtotime('01:00 PM');
    } else {
        $parts = explode('-', $available_time_str);
        $start_time = strtotime(trim($parts[0]));
        $end_time   = strtotime(trim($parts[1]));
    }

    if (!$start_time || !$end_time || $end_time <= $start_time) {
        $start_time = strtotime('09:00 AM');
        $end_time   = strtotime('01:00 PM');
    }

    $current = $start_time;
    while ($current < $end_time) {
        $slots[] = date('h:i A', $current);
        $current = strtotime("+{$interval_minutes} minutes", $current);
    }

    return $slots;
}

/**
 * Retrieve booked slots for a doctor on a specific date
 */
function get_booked_slots($pdo, $doctor_id, $date) {
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? 
          AND appointment_date = ? 
          AND status != 'Cancelled'
    ");
    $stmt->execute([$doctor_id, $date]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Generate unique token for a doctor on an appointment date
 * Format: Letter prefix (A, B, C...) + 3 digits (e.g. A-001, A-002)
 */
function generate_doctor_token($pdo, $doctor_id, $appointment_date) {
    // Determine letter prefix from doctor_id
    // Doctor 1 -> A, Doctor 2 -> B, Doctor 27 -> AA, etc.
    $prefix = chr(65 + (($doctor_id - 1) % 26));

    // Get all existing tokens for this doctor on this date
    $stmt = $pdo->prepare("
        SELECT token_number 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ?
    ");
    $stmt->execute([$doctor_id, $appointment_date]);
    $existing_tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $highest_num = 0;
    foreach ($existing_tokens as $t) {
        if (preg_match('/(\d+)$/', $t, $matches)) {
            $num = (int)$matches[1];
            if ($num > $highest_num) {
                $highest_num = $num;
            }
        }
    }

    $next_num = $highest_num + 1;
    $token = sprintf("%s-%03d", $prefix, $next_num);

    // Ensure absolutely no duplicate
    while (in_array($token, $existing_tokens, true)) {
        $next_num++;
        $token = sprintf("%s-%03d", $prefix, $next_num);
    }

    return $token;
}

/**
 * Add a notification to the database
 */
function add_notification($pdo, $patient_id, $message, $type = 'info', $doctor_id = null, $appointment_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (patient_id, doctor_id, appointment_id, message, type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$patient_id, $doctor_id, $appointment_id, $message, $type]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to insert notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Estimate waiting time based on patients ahead
 */
function calculate_estimated_wait($patients_ahead, $status) {
    if ($status === 'Serving') {
        return "Now being served";
    }
    if ($status === 'Completed') {
        return "Consultation completed";
    }
    if ($status === 'Cancelled') {
        return "Appointment cancelled";
    }
    if ($patients_ahead <= 0) {
        return "Next in line (< 5 mins)";
    }
    $minutes = $patients_ahead * 15;
    if ($minutes >= 60) {
        $hrs = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "~{$hrs}h {$mins}m wait" : "~{$hrs} hour wait";
    }
    return "~{$minutes} mins wait";
}
