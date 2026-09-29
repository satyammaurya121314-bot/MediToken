<?php
// patient/queue.php
// Live Digital Token Queue Tracking for Patients

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];
$appointment_id = (int)($_GET['appointment_id'] ?? 0);

// Find active appointment
if ($appointment_id > 0) {
    $stmt = $pdo->prepare("
        SELECT a.*, d.doctor_name, d.specialization, d.available_time
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id = ? AND a.patient_id = ?
    ");
    $stmt->execute([$appointment_id, $patient_id]);
    $apt = $stmt->fetch();
} else {
    // Pick current active or latest appointment
    $stmt = $pdo->prepare("
        SELECT a.*, d.doctor_name, d.specialization, d.available_time
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.patient_id = ?
        ORDER BY 
            CASE WHEN a.status IN ('Waiting', 'Serving') THEN 1 ELSE 2 END,
            a.appointment_date DESC, a.appointment_time DESC
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $apt = $stmt->fetch();
}

$currently_serving = 'None';
$next_token = 'None';
$patients_ahead = 0;
$estimated_wait = 'N/A';

if ($apt) {
    $doctor_id = (int)$apt['doctor_id'];
    $apt_date = $apt['appointment_date'];

    // 1. Find currently serving token
    $serving_stmt = $pdo->prepare("
        SELECT token_number 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving'
        LIMIT 1
    ");
    $serving_stmt->execute([$doctor_id, $apt_date]);
    $serv = $serving_stmt->fetchColumn();
    if ($serv) {
        $currently_serving = $serv;
    }

    // 2. Find next token in line
    $next_stmt = $pdo->prepare("
        SELECT token_number 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'
        ORDER BY appointment_id ASC
        LIMIT 1
    ");
    $next_stmt->execute([$doctor_id, $apt_date]);
    $nxt = $next_stmt->fetchColumn();
    if ($nxt) {
        $next_token = $nxt;
    }

    // 3. Calculate patients ahead
    if ($apt['status'] === 'Serving') {
        $patients_ahead = 0;
    } elseif ($apt['status'] === 'Waiting') {
        $ahead_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE doctor_id = ? 
              AND appointment_date = ? 
              AND status = 'Waiting' 
              AND appointment_id < ?
        ");
        $ahead_stmt->execute([$doctor_id, $apt_date, $apt['appointment_id']]);
        $patients_ahead = (int)$ahead_stmt->fetchColumn();
    }

    $estimated_wait = calculate_estimated_wait($patients_ahead, $apt['status']);
}

$page_title = "Live Token Queue - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Live Digital Token Queue</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Real-time consultation status synced directly with the doctor's desk.</p>
            </div>
            <div>
                <span class="status-badge badge-serving">
                    <span class="status-pulse"></span> Auto-Syncing (4s)
                </span>
            </div>
        </div>

        <?php if (!$apt): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No appointments found to track.</div>
                <p class="empty-state-desc">You do not have any registered appointments to monitor in the live queue.</p>
                <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary" style="margin-top: 1.25rem;">Book an Appointment</a>
            </div>
        <?php else: ?>

            <div id="queueActiveNotice">
                <?php if ($apt['status'] === 'Serving'): ?>
                    <div class="alert alert-success" style="font-weight: 700;">
                        YOUR TOKEN IS NOW BEING SERVED! Please proceed to Dr. <?= htmlspecialchars($apt['doctor_name']) ?>'s consultation room.
                    </div>
                <?php elseif ($apt['status'] === 'Completed'): ?>
                    <div class="alert alert-info">
                        This appointment was marked as completed. Thank you for consulting with us.
                    </div>
                <?php elseif ($apt['status'] === 'Cancelled'): ?>
                    <div class="alert alert-danger">
                        This appointment has been cancelled and is no longer active in the live queue.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hook element for AJAX real-time polling -->
            <div id="liveQueueTracker" 
                 data-doctor-id="<?= (int)$apt['doctor_id'] ?>" 
                 data-appointment-id="<?= (int)$apt['appointment_id'] ?>" 
                 data-base-url="<?= $base_url ?>">
            </div>

            <!-- Consultation Details Header -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">CONSULTING DOCTOR</div>
                        <h2 style="font-size: 1.25rem; margin-top: 0.2rem;">
                            Dr. <?= htmlspecialchars($apt['doctor_name']) ?>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary); background: var(--primary-light); padding: 0.15rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-border); margin-left: 0.35rem;">Doctor ID: #<?= (int)$apt['doctor_id'] ?></span>
                        </h2>
                        <div style="color: var(--primary); font-size: 0.875rem; font-weight: 600;"><?= htmlspecialchars($apt['specialization']) ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">CONSULTATION SCHEDULE</div>
                        <div style="font-size: 1.05rem; font-weight: 700;"><?= format_date($apt['appointment_date']) ?></div>
                        <div style="color: var(--dark-muted); font-size: 0.875rem;"><?= htmlspecialchars($apt['appointment_time']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Main High-Impact Token Board -->
            <div class="queue-board-card">
                <div class="queue-board-header">
                    <div>
                        <h3 style="font-size: 1.35rem; margin-bottom: 0.25rem;">Doctor's Digital Board</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">All numbers are generated from real MySQL consultation records</p>
                    </div>
                    <div>
                        <span id="myStatusBadgeDisplay">
                            <?= render_status_badge($apt['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="token-highlight-grid">
                    <!-- Currently Serving Box -->
                    <div class="token-box active-serving">
                        <div class="token-box-label">CURRENTLY SERVING</div>
                        <div class="token-box-number" id="servingTokenDisplay"><?= htmlspecialchars($currently_serving) ?></div>
                        <div class="token-box-sub">Inside Consultation</div>
                    </div>

                    <!-- Your Token Box -->
                    <div class="token-box" style="border: 2px solid var(--primary-border); background: #ffffff;">
                        <div class="token-box-label" style="color: var(--primary);">YOUR TOKEN</div>
                        <div class="token-box-number" style="color: var(--primary);"><?= htmlspecialchars($apt['token_number']) ?></div>
                        <div class="token-box-sub">Assigned to you</div>
                    </div>

                    <!-- Next Token Box -->
                    <div class="token-box">
                        <div class="token-box-label">NEXT IN LINE</div>
                        <div class="token-box-number" id="nextTokenDisplay"><?= htmlspecialchars($next_token) ?></div>
                        <div class="token-box-sub">Approaching Next</div>
                    </div>

                    <!-- Patients Ahead Box -->
                    <div class="token-box">
                        <div class="token-box-label">PATIENTS AHEAD</div>
                        <div class="token-box-number" id="patientsAheadDisplay"><?= $patients_ahead ?></div>
                        <div class="token-box-sub">In front of your token</div>
                    </div>

                    <!-- Estimated Wait Box -->
                    <div class="token-box" style="grid-column: 1 / -1;">
                        <div class="token-box-label">ESTIMATED WAITING TIME</div>
                        <div class="token-box-number" id="estimatedWaitDisplay" style="font-size: 2.15rem; color: #0284c7;">
                            <?= htmlspecialchars($estimated_wait) ?>
                        </div>
                        <div class="token-box-sub">Approx. 15 minutes per consultation session</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; flex-wrap: wrap;">
                <a href="<?= $base_url ?>/patient/appointments.php" class="btn btn-secondary">← Back to My Appointments</a>
                <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary">Book Another Appointment</a>
            </div>

        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
