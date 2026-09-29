<?php
// admin/dashboard.php
// Centralized administrator dashboard with real MySQL aggregates

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();
$today = date('Y-m-d');

// 1. Total Doctors
$doc_stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
$total_doctors = (int)$doc_stmt->fetchColumn();

// 2. Total Patients
$pat_stmt = $pdo->query("SELECT COUNT(*) FROM patients");
$total_patients = (int)$pat_stmt->fetchColumn();

// 3. Today's Appointments
$today_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
$today_stmt->execute([$today]);
$today_appointments = (int)$today_stmt->fetchColumn();

// 4. Waiting Patients Today
$wait_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status = 'Waiting'");
$wait_stmt->execute([$today]);
$waiting_patients = (int)$wait_stmt->fetchColumn();

// 5. Completed Appointments (Overall)
$comp_stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'");
$completed_appointments = (int)$comp_stmt->fetchColumn();

// 6. Cancelled Appointments (Overall)
$canc_stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'");
$cancelled_appointments = (int)$canc_stmt->fetchColumn();

// Recent appointments for today
$recent_stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, d.doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    ORDER BY a.created_at DESC
    LIMIT 6
");
$recent_stmt->execute();
$recent_appointments = $recent_stmt->fetchAll();

$page_title = "Admin Dashboard - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Administrator Control Panel</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Clinic metrics calculated in real-time from MySQL database.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="<?= $base_url ?>/admin/doctors.php?action=add" class="btn btn-primary">+ Add Doctor</a>
                <a href="<?= $base_url ?>/admin/queue.php" class="btn btn-secondary">Monitor Live Queues</a>
            </div>
        </div>

        <?php display_flash(); ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'welcome'): ?>
            <div class="alert alert-success">Administrator initialized successfully. Welcome to MediToken Admin Portal!</div>
        <?php endif; ?>

        <!-- Required Real 6 Metric Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-value" style="color: var(--primary);"><?= $total_doctors ?></div>
                </div>
                <div class="stat-icon stat-green" style="font-size:0.85rem; font-weight:700;">DOC</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value" style="color: #0284c7;"><?= $total_patients ?></div>
                </div>
                <div class="stat-icon stat-blue" style="font-size:0.85rem; font-weight:700;">PAT</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Today's Appointments</div>
                    <div class="stat-value"><?= $today_appointments ?></div>
                </div>
                <div class="stat-icon stat-purple" style="font-size:0.85rem; font-weight:700;">APT</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Waiting Patients (Today)</div>
                    <div class="stat-value" style="color: #d97706;"><?= $waiting_patients ?></div>
                </div>
                <div class="stat-icon stat-amber" style="font-size:0.85rem; font-weight:700;">WAI</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Completed Consultations</div>
                    <div class="stat-value" style="color: #059669;"><?= $completed_appointments ?></div>
                </div>
                <div class="stat-icon stat-green" style="font-size:0.85rem; font-weight:700;">CMP</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Cancelled Appointments</div>
                    <div class="stat-value" style="color: #e11d48;"><?= $cancelled_appointments ?></div>
                </div>
                <div class="stat-icon stat-rose" style="font-size:0.85rem; font-weight:700;">CNC</div>
            </div>
        </div>

        <!-- Recent Appointments Activity -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.85rem;">
                <h2 style="font-size: 1.25rem;">Latest Appointment Bookings</h2>
                <a href="<?= $base_url ?>/admin/appointments.php" class="btn btn-sm btn-secondary">View All Records &rarr;</a>
            </div>

            <?php if (empty($recent_appointments)): ?>
                <div class="empty-state" style="padding: 2.5rem 1rem;">
                    
                    <div class="empty-state-title">No appointments found.</div>
                    <p class="empty-state-desc">The database currently has zero appointment records.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_appointments as $ra): ?>
                                <tr>
                                    <td>
                                        <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                                            <?= htmlspecialchars($ra['token_number']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($ra['patient_name']) ?></strong></td>
                                    <td>Dr. <?= htmlspecialchars($ra['doctor_name']) ?></td>
                                    <td><?= format_date($ra['appointment_date']) ?></td>
                                    <td><?= htmlspecialchars($ra['appointment_time']) ?></td>
                                    <td><?= render_status_badge($ra['status']) ?></td>
                                    <td style="text-align: right;">
                                        <a href="<?= $base_url ?>/admin/appointments.php?search=<?= urlencode($ra['token_number']) ?>" class="btn btn-sm btn-secondary">Inspect</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Administration Links -->
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Management Portals</h2>
        <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <a href="<?= $base_url ?>/admin/doctors.php" class="card" style="text-align: center; color: var(--dark);">
                
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Doctor Management</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Add, edit, and toggle availability</div>
            </a>
            <a href="<?= $base_url ?>/admin/patients.php" class="card" style="text-align: center; color: var(--dark);">
                
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Patient Records</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Registered patient directory</div>
            </a>
            <a href="<?= $base_url ?>/admin/schedules.php" class="card" style="text-align: center; color: var(--dark);">
                
                <div style="font-weight: 700; margin-bottom: 0.25rem;">Consultation Schedules</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Hours and availability control</div>
            </a>
            <a href="<?= $base_url ?>/admin/reports.php" class="card" style="text-align: center; color: var(--dark);">
                
                <div style="font-weight: 700; margin-bottom: 0.25rem;">System Analytics</div>
                <div style="font-size: 0.825rem; color: var(--text-muted);">Audit reports & queue counts</div>
            </a>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
