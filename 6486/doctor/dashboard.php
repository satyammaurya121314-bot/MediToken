<?php
// doctor/dashboard.php
// Doctor Dashboard with live statistics from MySQL

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('doctor');

$pdo = get_db_connection();
$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Doctor details
$doc_stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$doc_stmt->execute([$doctor_id]);
$doctor = $doc_stmt->fetch();

// 1. Today's Appointments Count
$tot_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
$tot_stmt->execute([$doctor_id, $today]);
$today_total = (int)$tot_stmt->fetchColumn();

// 2. Waiting Patients Count
$wait_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'");
$wait_stmt->execute([$doctor_id, $today]);
$today_waiting = (int)$wait_stmt->fetchColumn();

// 3. Current Serving Token
$serv_stmt = $pdo->prepare("SELECT token_number FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving' LIMIT 1");
$serv_stmt->execute([$doctor_id, $today]);
$current_serving = $serv_stmt->fetchColumn() ?: 'None';

// 4. Completed Patients Count
$comp_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Completed'");
$comp_stmt->execute([$doctor_id, $today]);
$today_completed = (int)$comp_stmt->fetchColumn();

// Today's upcoming queue snapshot
$queue_stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.mobile_no
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'Serving' THEN 1
            WHEN a.status = 'Waiting' THEN 2
            WHEN a.status = 'Skipped' THEN 3
            ELSE 4
        END,
        a.appointment_id ASC
    LIMIT 6
");
$queue_stmt->execute([$doctor_id, $today]);
$today_queue = $queue_stmt->fetchAll();

$page_title = "Doctor Dashboard - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">
                    Dr. <?= htmlspecialchars($doctor['doctor_name'] ?? 'Doctor') ?>
                    <span style="font-size: 0.95rem; color: var(--primary); font-weight: 700; background: var(--primary-light); padding: 0.25rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-border); vertical-align: middle; margin-left: 0.5rem;">Doctor ID: #<?= (int)$doctor_id ?></span>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Specialization: <strong><?= htmlspecialchars($doctor['specialization'] ?? '') ?></strong> | 
                    Today is <strong><?= date('l, d F Y') ?></strong>
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <?php if (($doctor['availability_status'] ?? '') === 'Available'): ?>
                    <span class="status-badge badge-available">● Available</span>
                <?php else: ?>
                    <span class="status-badge badge-unavailable">● Unavailable</span>
                <?php endif; ?>
                <a href="<?= $base_url ?>/doctor/queue.php" class="btn btn-primary">Open Live Queue &rarr;</a>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Doctor Stat Cards (All Real MySQL Numbers) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value"><?= $today_total ?></div>
                </div>
                <div class="stat-icon stat-blue" style="font-size:0.85rem; font-weight:700;">APT</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Waiting Patients</div>
                    <div class="stat-value" style="color: #d97706;"><?= $today_waiting ?></div>
                </div>
                <div class="stat-icon stat-amber" style="font-size:0.85rem; font-weight:700;">WAI</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Current Serving Token</div>
                    <div class="stat-value" style="color: var(--primary);"><?= htmlspecialchars($current_serving) ?></div>
                </div>
                <div class="stat-icon stat-green" style="font-size:0.85rem; font-weight:700;">TKN</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Completed Consultations</div>
                    <div class="stat-value" style="color: #0284c7;"><?= $today_completed ?></div>
                </div>
                <div class="stat-icon stat-purple" style="font-size:0.85rem; font-weight:700;">CMP</div>
            </div>
        </div>

        <!-- Today's Queue Overview -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
                <div>
                    <h2 style="font-size: 1.25rem;">Today's Patient Queue Overview</h2>
                    <p style="color: var(--text-muted); font-size: 0.825rem;">Real-time consultation list for <?= date('d M Y') ?></p>
                </div>
                <a href="<?= $base_url ?>/doctor/queue.php" class="btn btn-sm btn-primary">Manage Full Queue &rarr;</a>
            </div>

            <?php if (empty($today_queue)): ?>
                <div class="empty-state" style="padding: 2.5rem 1rem;">
                    
                    <div class="empty-state-title">No appointments scheduled for today.</div>
                    <p class="empty-state-desc">No patients have booked appointments with you for today's date yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Patient Name</th>
                                <th>Slot Time</th>
                                <th>Mobile</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_queue as $q): ?>
                                <tr>
                                    <td>
                                        <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                            <?= htmlspecialchars($q['token_number']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($q['patient_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($q['appointment_time']) ?></td>
                                    <td><?= htmlspecialchars($q['mobile_no']) ?></td>
                                    <td><?= render_status_badge($q['status']) ?></td>
                                    <td style="text-align: right;">
                                        <a href="<?= $base_url ?>/doctor/queue.php" class="btn btn-sm btn-secondary">Desk</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Consultation Hours & Info -->
        <div class="card">
            <h3 style="font-size: 1.15rem; margin-bottom: 0.75rem;">Consultation Schedule Configuration</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                <div>
                    <span style="color: var(--text-muted);">Configured Hours:</span> 
                    <strong><?= htmlspecialchars($doctor['available_time'] ?? 'Not set') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Current Status:</span> 
                    <strong><?= htmlspecialchars($doctor['availability_status'] ?? 'Available') ?></strong>
                </div>
                <div>
                    <a href="<?= $base_url ?>/doctor/profile.php" class="btn btn-sm btn-outline">Update Timing or Availability</a>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
