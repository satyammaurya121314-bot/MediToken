<?php
// patient/dashboard.php
// Patient Dashboard querying actual MySQL records

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

// Fetch the most immediate active appointment for this patient
$stmt = $pdo->prepare("
    SELECT a.*, d.doctor_name, d.specialization, d.available_time
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? 
      AND a.status IN ('Waiting', 'Serving')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 1
");
$stmt->execute([$patient_id]);
$active_apt = $stmt->fetch();

$current_serving_token = 'None';
$patients_ahead = 0;
$estimated_wait = 'No active queue';

if ($active_apt) {
    // Find currently serving token for this doctor and date
    $serving_stmt = $pdo->prepare("
        SELECT token_number 
        FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving' 
        LIMIT 1
    ");
    $serving_stmt->execute([$active_apt['doctor_id'], $active_apt['appointment_date']]);
    $serving_token = $serving_stmt->fetchColumn();
    if ($serving_token) {
        $current_serving_token = $serving_token;
    }

    // Count waiting patients before this patient's token/appointment
    if ($active_apt['status'] === 'Serving') {
        $patients_ahead = 0;
        $estimated_wait = 'Now being served';
    } else {
        $ahead_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE doctor_id = ? 
              AND appointment_date = ? 
              AND status = 'Waiting' 
              AND appointment_id < ?
        ");
        $ahead_stmt->execute([$active_apt['doctor_id'], $active_apt['appointment_date'], $active_apt['appointment_id']]);
        $patients_ahead = (int)$ahead_stmt->fetchColumn();
        $estimated_wait = calculate_estimated_wait($patients_ahead, $active_apt['status']);
    }
}

// Fetch recent notifications for this patient
$notif_stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE patient_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$notif_stmt->execute([$patient_id]);
$notifications = $notif_stmt->fetchAll();

$page_title = "Patient Dashboard - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">
                    Patient Dashboard
                    <span style="font-size: 0.95rem; color: var(--primary); font-weight: 700; background: var(--primary-light); padding: 0.25rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-border); vertical-align: middle; margin-left: 0.5rem;">Patient ID: #<?= (int)$patient_id ?></span>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here is your live appointment summary.</p>
            </div>
            <div>
                <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary">
                    + Book New Appointment
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'reg_success'): ?>
            <div class="alert alert-success">Registration successful! Welcome to MediToken.</div>
        <?php endif; ?>

        <!-- Queue Status Overview Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">My Current Token</div>
                    <div class="stat-value" style="color: var(--primary);">
                        <?= $active_apt ? htmlspecialchars($active_apt['token_number']) : '-' ?>
                    </div>
                </div>
                <div class="stat-icon stat-green" style="font-size: 0.85rem; font-weight: 700;">TKN</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Current Serving Token</div>
                    <div class="stat-value" style="color: #0284c7;">
                        <?= htmlspecialchars($current_serving_token) ?>
                    </div>
                </div>
                <div class="stat-icon stat-blue" style="font-size: 0.85rem; font-weight: 700;">SRV</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Patients Ahead</div>
                    <div class="stat-value" style="color: #d97706;">
                        <?= $active_apt ? $patients_ahead : '-' ?>
                    </div>
                </div>
                <div class="stat-icon stat-amber" style="font-size: 0.85rem; font-weight: 700;">QUE</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Appointment Status</div>
                    <div style="margin-top: 0.4rem;">
                        <?= $active_apt ? render_status_badge($active_apt['status']) : '<span style="color: var(--text-muted); font-size: 0.9rem;">None Active</span>' ?>
                    </div>
                </div>
                <div class="stat-icon stat-purple" style="font-size: 0.85rem; font-weight: 700;">STS</div>
            </div>
        </div>

        <!-- Upcoming Appointment Details -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
                <h2 style="font-size: 1.25rem;">Upcoming Appointment</h2>
                <?php if ($active_apt): ?>
                    <a href="<?= $base_url ?>/patient/queue.php?appointment_id=<?= (int)$active_apt['appointment_id'] ?>" class="btn btn-sm btn-primary">
                        Track Live Queue &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$active_apt): ?>
                <div class="empty-state" style="padding: 2.5rem 1rem;">
                    <div class="empty-state-title">No upcoming appointments.</div>
                    <p class="empty-state-desc">You currently do not have any waiting or serving appointments scheduled.</p>
                    <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary" style="margin-top: 1.25rem;">Book an Appointment</a>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Consulting Doctor</div>
                        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.2rem;">Dr. <?= htmlspecialchars($active_apt['doctor_name']) ?></div>
                        <div style="color: var(--primary); font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($active_apt['specialization']) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Date & Time Slot</div>
                        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.2rem;"><?= format_date($active_apt['appointment_date']) ?></div>
                        <div style="color: var(--dark-muted); font-size: 0.9rem;"><?= htmlspecialchars($active_apt['appointment_time']) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Your Token Number</div>
                        <div style="font-size: 1.85rem; font-weight: 800; color: var(--primary); font-family: 'Space Grotesk', sans-serif;">
                            <?= htmlspecialchars($active_apt['token_number']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($estimated_wait) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Current Status</div>
                        <div style="margin-top: 0.35rem;">
                            <?= render_status_badge($active_apt['status']) ?>
                        </div>
                        <div style="margin-top: 0.75rem;">
                            <a href="<?= $base_url ?>/patient/appointments.php" class="btn btn-sm btn-secondary">Manage</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Grid -->
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Quick Actions</h2>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
            <a href="<?= $base_url ?>/patient/book_appointment.php" class="card" style="text-align: center; color: var(--dark);">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Book Appointment</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Choose doctor & time slot</div>
            </a>

            <a href="<?= $base_url ?>/patient/appointments.php" class="card" style="text-align: center; color: var(--dark);">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">My Appointments</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">View schedule & cancel</div>
            </a>

            <a href="<?= $base_url ?>/patient/queue.php" class="card" style="text-align: center; color: var(--dark);">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Track Live Queue</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Monitor token calling</div>
            </a>

            <a href="<?= $base_url ?>/patient/history.php" class="card" style="text-align: center; color: var(--dark);">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Appointment History</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Past consultations log</div>
            </a>

            <a href="<?= $base_url ?>/patient/profile.php" class="card" style="text-align: center; color: var(--dark);">
                <div style="font-weight: 700; margin-bottom: 0.25rem;">My Profile</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Personal information</div>
            </a>
        </div>

        <!-- Recent Notifications -->
        <?php if (!empty($notifications)): ?>
            <div class="card">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem;">Recent Queue & System Notifications</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($notifications as $n): ?>
                        <div style="padding: 0.75rem; background: var(--bg-main); border-radius: var(--radius-sm); border-left: 3px solid var(--primary); display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">
                            <div><?= htmlspecialchars($n['message']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; margin-left: 1rem;">
                                <?= date('d M, h:i A', strtotime($n['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
