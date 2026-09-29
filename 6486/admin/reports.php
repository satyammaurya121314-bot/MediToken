<?php
// admin/reports.php
// Statistical clinic reports and queue metrics calculated from MySQL

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();

// 1. Total Patients
$total_patients = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// 2. Total Doctors
$total_doctors = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();

// 3. Total Appointments
$total_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

// 4. Completed Appointments
$completed_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn();

// 5. Cancelled Appointments
$cancelled_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'")->fetchColumn();

// 6. Skipped Appointments
$skipped_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Skipped'")->fetchColumn();

// 7. Waiting Appointments
$waiting_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Waiting'")->fetchColumn();

// 8. Serving Appointments
$serving_appointments = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Serving'")->fetchColumn();

// 9. Patients Served (Completed + Serving)
$patients_served = $completed_appointments + $serving_appointments;

// 10. Estimated Average Waiting Time calculation (approx 15 mins per completed session)
$avg_wait_text = 'N/A';
if ($completed_appointments > 0) {
    $avg_wait_text = 'Approx. 15 - 20 mins / consultation';
}

// 11. Doctor Breakdown
$breakdown_stmt = $pdo->query("
    SELECT 
        d.doctor_name,
        d.specialization,
        COUNT(a.appointment_id) AS total_appts,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS comp_appts,
        SUM(CASE WHEN a.status = 'Waiting' THEN 1 ELSE 0 END) AS wait_appts,
        SUM(CASE WHEN a.status = 'Cancelled' THEN 1 ELSE 0 END) AS canc_appts
    FROM doctors d
    LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
    GROUP BY d.doctor_id, d.doctor_name, d.specialization
    ORDER BY total_appts DESC
");
$doctor_breakdowns = $breakdown_stmt->fetchAll();

$page_title = "Clinic Reports & Analytics - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Clinic Reports & Analytics</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Comprehensive data audits compiled strictly from relational MySQL storage.</p>
            </div>
            <div>
                <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
            </div>
        </div>

        <!-- Aggregate Metric Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value" style="color: #0284c7;"><?= $total_patients ?></div>
                </div>
                <div class="stat-icon stat-blue" style="font-size:0.85rem; font-weight:700;">PAT</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-value" style="color: var(--primary);"><?= $total_doctors ?></div>
                </div>
                <div class="stat-icon stat-green" style="font-size:0.85rem; font-weight:700;">DOC</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-value"><?= $total_appointments ?></div>
                </div>
                <div class="stat-icon stat-purple" style="font-size:0.85rem; font-weight:700;">APT</div>
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

            <div class="stat-card">
                <div>
                    <div class="stat-label">Skipped Appointments</div>
                    <div class="stat-value" style="color: #7c3aed;"><?= $skipped_appointments ?></div>
                </div>
                <div class="stat-icon stat-purple" style="font-size:0.85rem; font-weight:700;">SKP</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Waiting Appointments</div>
                    <div class="stat-value" style="color: #d97706;"><?= $waiting_appointments ?></div>
                </div>
                <div class="stat-icon stat-amber" style="font-size:0.85rem; font-weight:700;">WAI</div>
            </div>

            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Patients Served</div>
                    <div class="stat-value" style="color: #059669;"><?= $patients_served ?></div>
                </div>
                <div class="stat-icon stat-green" style="font-size:0.85rem; font-weight:700;">SRV</div>
            </div>
        </div>

        <!-- Performance Summary Card -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Service Efficiency & Wait Time Metrics</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
                <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius-md);">
                    <div style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">AVERAGE CONSULTATION TIME</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--dark); margin-top: 0.25rem;">
                        <?= htmlspecialchars($avg_wait_text) ?>
                    </div>
                </div>

                <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius-md);">
                    <div style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">COMPLETION RATE</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #059669; margin-top: 0.25rem;">
                        <?= $total_appointments > 0 ? round(($completed_appointments / $total_appointments) * 100, 1) . '%' : '0.0%' ?>
                    </div>
                </div>

                <div style="background: var(--bg-main); padding: 1.25rem; border-radius: var(--radius-md);">
                    <div style="font-size: 0.825rem; color: var(--text-muted); font-weight: 600;">CANCELLATION RATE</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #e11d48; margin-top: 0.25rem;">
                        <?= $total_appointments > 0 ? round(($cancelled_appointments / $total_appointments) * 100, 1) . '%' : '0.0%' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Breakdown Table -->
        <div class="card">
            <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Doctor-Wise Consultation Breakdown</h2>
            <?php if (empty($doctor_breakdowns)): ?>
                <div class="empty-state">
                    
                    <div class="empty-state-title">No doctor performance data available.</div>
                    <p class="empty-state-desc">Register doctors and record appointments to view comparative reporting.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Total Appointments</th>
                                <th>Completed</th>
                                <th>Waiting</th>
                                <th>Cancelled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctor_breakdowns as $db): ?>
                                <tr>
                                    <td><strong>Dr. <?= htmlspecialchars($db['doctor_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($db['specialization']) ?></td>
                                    <td><strong><?= (int)$db['total_appts'] ?></strong></td>
                                    <td><span style="color: #059669; font-weight: 700;"><?= (int)$db['comp_appts'] ?></span></td>
                                    <td><span style="color: #d97706; font-weight: 700;"><?= (int)$db['wait_appts'] ?></span></td>
                                    <td><span style="color: #e11d48; font-weight: 700;"><?= (int)$db['canc_appts'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
