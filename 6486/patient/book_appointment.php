<?php
// patient/book_appointment.php
// Real appointment booking with slot collision prevention and unique doctor-date token generation

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

$error = '';
$confirmed_appointment = null;

// Get available doctors
$doc_stmt = $pdo->query("
    SELECT doctor_id, doctor_name, specialization, available_time, availability_status 
    FROM doctors 
    ORDER BY doctor_name ASC
");
$all_doctors = $doc_stmt->fetchAll();

$selected_doctor_id = (int)($_POST['doctor_id'] ?? $_GET['doctor_id'] ?? ($all_doctors[0]['doctor_id'] ?? 0));
$selected_date = trim($_POST['appointment_date'] ?? $_GET['date'] ?? date('Y-m-d'));

// Prevent past dates
if ($selected_date < date('Y-m-d')) {
    $selected_date = date('Y-m-d');
}

// Find chosen doctor details
$current_doctor = null;
foreach ($all_doctors as $doc) {
    if ((int)$doc['doctor_id'] === $selected_doctor_id) {
        $current_doctor = $doc;
        break;
    }
}

// Time slots and booked slots
$time_slots = [];
$booked_slots = [];
if ($current_doctor) {
    $time_slots = generate_time_slots($current_doctor['available_time'], 20);
    $booked_slots = get_booked_slots($pdo, $current_doctor['doctor_id'], $selected_date);
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $doc_id = (int)($_POST['doctor_id'] ?? 0);
    $apt_date = trim($_POST['appointment_date'] ?? '');
    $apt_time = trim($_POST['appointment_time'] ?? '');

    if (!$doc_id || empty($apt_date) || empty($apt_time)) {
        $error = 'Please select a doctor, appointment date, and available time slot.';
    } elseif ($apt_date < date('Y-m-d')) {
        $error = 'You cannot book an appointment for a past date.';
    } else {
        // Verify doctor availability status
        $doc_check = $pdo->prepare("SELECT doctor_name, specialization, availability_status FROM doctors WHERE doctor_id = ?");
        $doc_check->execute([$doc_id]);
        $doc_info = $doc_check->fetch();

        if (!$doc_info) {
            $error = 'Selected doctor does not exist.';
        } elseif ($doc_info['availability_status'] !== 'Available') {
            $error = 'Doctor is currently unavailable for consultations.';
        } else {
            // Begin transaction for slot safety
            $pdo->beginTransaction();

            try {
                // Check if slot is already booked (Real slot management)
                $slot_stmt = $pdo->prepare("
                    SELECT appointment_id 
                    FROM appointments 
                    WHERE doctor_id = ? 
                      AND appointment_date = ? 
                      AND appointment_time = ? 
                      AND status != 'Cancelled' 
                    FOR UPDATE
                ");
                $slot_stmt->execute([$doc_id, $apt_date, $apt_time]);
                $already_booked = $slot_stmt->fetch();

                if ($already_booked) {
                    $pdo->rollBack();
                    $error = 'This appointment slot is already booked. Please choose another available slot.';
                } else {
                    // Check if this patient already has a Waiting or Serving appointment with this doctor on the same date
                    $patient_check = $pdo->prepare("
                        SELECT appointment_id 
                        FROM appointments 
                        WHERE patient_id = ? 
                          AND doctor_id = ? 
                          AND appointment_date = ? 
                          AND status IN ('Waiting', 'Serving')
                    ");
                    $patient_check->execute([$patient_id, $doc_id, $apt_date]);
                    if ($patient_check->fetch()) {
                        $pdo->rollBack();
                        $error = 'You already have an active appointment scheduled with this doctor on this date.';
                    } else {
                        // Generate real doctor token for this date
                        $token_number = generate_doctor_token($pdo, $doc_id, $apt_date);

                        // Insert the appointment
                        $ins = $pdo->prepare("
                            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, token_number, status)
                            VALUES (?, ?, ?, ?, ?, 'Waiting')
                        ");
                        $ins->execute([$patient_id, $doc_id, $apt_date, $apt_time, $token_number]);
                        $new_apt_id = $pdo->lastInsertId();

                        // Commit transaction
                        $pdo->commit();

                        // Create notification
                        $notif_msg = "Your appointment is confirmed with Dr. {$doc_info['doctor_name']} on {$apt_date} at {$apt_time}. Token: {$token_number}.";
                        add_notification($pdo, $patient_id, $notif_msg, 'appointment_confirmed', $doc_id, $new_apt_id);

                        // Set confirmation record
                        $confirmed_appointment = [
                            'appointment_id' => $new_apt_id,
                            'doctor_name'    => $doc_info['doctor_name'],
                            'specialization' => $doc_info['specialization'],
                            'date'           => $apt_date,
                            'time'           => $apt_time,
                            'token'          => $token_number,
                            'status'         => 'Waiting',
                        ];
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Booking error: " . $e->getMessage());
                $error = 'Unable to complete appointment booking. Please try again.';
            }
        }
    }
}

$page_title = "Book Doctor Appointment - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <?php if ($confirmed_appointment): ?>
            <!-- Appointment Confirmation Card -->
            <div class="card" style="max-width: 650px; margin: 2rem auto; text-align: center; border-top: 5px solid var(--primary); padding: 2.5rem;">
                <div style="display: inline-block; padding: 0.5rem 1rem; background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-full); margin-bottom: 1rem;">
                    BOOKING CONFIRMED
                </div>
                <h1 style="color: var(--primary); font-size: 1.85rem; margin-bottom: 0.5rem;">APPOINTMENT CONFIRMED</h1>
                <p style="color: var(--text-muted); margin-bottom: 1.75rem;">Your digital consultation token has been generated successfully.</p>

                <div class="token-box active-serving" style="max-width: 320px; margin: 0 auto 2rem;">
                    <div class="token-box-label">YOUR TOKEN NUMBER</div>
                    <div class="token-box-number" style="font-size: 3.2rem;"><?= htmlspecialchars($confirmed_appointment['token']) ?></div>
                    <div class="token-box-sub">Keep this token ready for calling</div>
                </div>

                <div style="background: var(--bg-main); border-radius: var(--radius-md); padding: 1.25rem; text-align: left; margin-bottom: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">DOCTOR</div>
                        <div style="font-size: 1.05rem; font-weight: 700;">Dr. <?= htmlspecialchars($confirmed_appointment['doctor_name']) ?></div>
                        <div style="color: var(--primary); font-size: 0.85rem;"><?= htmlspecialchars($confirmed_appointment['specialization']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">SCHEDULE</div>
                        <div style="font-size: 1.05rem; font-weight: 700;"><?= format_date($confirmed_appointment['date']) ?></div>
                        <div style="color: var(--dark-muted); font-size: 0.9rem;"><?= htmlspecialchars($confirmed_appointment['time']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">INITIAL STATUS</div>
                        <div style="margin-top: 0.25rem;"><?= render_status_badge($confirmed_appointment['status']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">APPOINTMENT ID</div>
                        <div style="font-weight: 700; color: var(--dark);">#<?= (int)$confirmed_appointment['appointment_id'] ?></div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?= $base_url ?>/patient/queue.php?appointment_id=<?= (int)$confirmed_appointment['appointment_id'] ?>" class="btn btn-primary btn-lg">
                        Track Live Queue &rarr;
                    </a>
                    <a href="<?= $base_url ?>/patient/appointments.php" class="btn btn-secondary btn-lg">
                        View My Appointments
                    </a>
                </div>
            </div>

        <?php else: ?>

            <div style="margin-bottom: 1.5rem;">
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Book Doctor Appointment</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Select a certified doctor, appointment date, and choose an available consultation slot.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($all_doctors)): ?>
                <div class="card empty-state">
                    
                    <div class="empty-state-title">No doctors available.</div>
                    <p class="empty-state-desc">The clinic has not registered any doctors yet. Please check back later.</p>
                </div>
            <?php else: ?>

                <div class="card" style="max-width: 780px;">
                    <!-- Step 1 & 2: Doctor and Date Selection (Auto updates slots) -->
                    <form method="GET" action="" id="doctorDateForm" style="margin-bottom: 1.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="doctor_select">1. Select Consulting Doctor</label>
                                <select id="doctor_select" name="doctor_id" class="form-select" onchange="document.getElementById('doctorDateForm').submit();">
                                    <?php foreach ($all_doctors as $doc): ?>
                                        <option value="<?= (int)$doc['doctor_id'] ?>" <?= $selected_doctor_id === (int)$doc['doctor_id'] ? 'selected' : '' ?>>
                                            Dr. <?= htmlspecialchars($doc['doctor_name']) ?> (ID: #<?= (int)$doc['doctor_id'] ?> - <?= htmlspecialchars($doc['specialization']) ?>) - [<?= htmlspecialchars($doc['availability_status']) ?>]
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="date_select">2. Select Appointment Date</label>
                                <input type="date" id="date_select" name="date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($selected_date) ?>" onchange="document.getElementById('doctorDateForm').submit();">
                            </div>
                        </div>
                    </form>

                    <!-- Doctor Info Summary -->
                    <?php if ($current_doctor): ?>
                        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: gap; gap: 1rem;">
                            <div>
                                <div style="font-weight: 700; font-size: 1.05rem;">
                                    Dr. <?= htmlspecialchars($current_doctor['doctor_name']) ?>
                                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 0.15rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-border); margin-left: 0.35rem;">Doctor ID: #<?= (int)$current_doctor['doctor_id'] ?></span>
                                </div>
                                <div style="color: var(--primary); font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($current_doctor['specialization']) ?></div>
                                <div style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.25rem;">
                                    Consulting Hours: <strong><?= htmlspecialchars($current_doctor['available_time']) ?></strong>
                                </div>
                            </div>
                            <div>
                                <?php if ($current_doctor['availability_status'] === 'Available'): ?>
                                    <span class="status-badge badge-available">Doctor Available</span>
                                <?php else: ?>
                                    <span class="status-badge badge-unavailable">Doctor Unavailable</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Step 3: Slot Selection & Confirmation -->
                    <?php if ($current_doctor && $current_doctor['availability_status'] === 'Available'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="doctor_id" value="<?= (int)$selected_doctor_id ?>">
                            <input type="hidden" name="appointment_date" value="<?= htmlspecialchars($selected_date) ?>">
                            <input type="hidden" name="appointment_time" id="selected_time_slot" value="<?= htmlspecialchars($_POST['appointment_time'] ?? '') ?>" required>

                            <div class="form-group">
                                <label class="form-label">
                                    3. Choose Available Time Slot for <?= format_date($selected_date) ?>
                                </label>
                                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                                    Click any active slot below to reserve your appointment time.
                                </p>

                                <?php if (empty($time_slots)): ?>
                                    <div class="alert alert-warning">No consultation slots could be calculated for this doctor's hours.</div>
                                <?php else: ?>
                                    <div class="slots-grid">
                                        <?php foreach ($time_slots as $slot): ?>
                                            <?php $is_booked = in_array($slot, $booked_slots, true); ?>
                                            <button type="button" 
                                                    class="slot-btn <?= $is_booked ? 'slot-booked' : '' ?> <?= (isset($_POST['appointment_time']) && $_POST['appointment_time'] === $slot) ? 'slot-selected' : '' ?>"
                                                    data-time="<?= htmlspecialchars($slot) ?>"
                                                    <?= $is_booked ? 'disabled title="Slot already booked"' : '' ?>>
                                                <?= htmlspecialchars($slot) ?>
                                                <?php if ($is_booked): ?>
                                                    <span style="display: block; font-size: 0.65rem; color: #94a3b8;">Booked</span>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    A unique digital token will be automatically generated upon confirmation.
                                </div>
                                <button type="submit" name="confirm_booking" class="btn btn-primary btn-lg">
                                    Confirm Appointment & Get Token
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Dr. <?= htmlspecialchars($current_doctor['doctor_name'] ?? 'This doctor') ?> is currently marked as unavailable. Please select another doctor or another date.
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
