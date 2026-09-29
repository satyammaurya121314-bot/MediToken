<?php
// patient/history.php
// Patient consultation and appointment history from MySQL

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

// Fetch all appointment records for this patient
$stmt = $pdo->prepare("
    SELECT a.*, d.doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$patient_id]);
$history = $stmt->fetchAll();

$page_title = "Appointment History - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Appointment History</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Complete log of all past and current consultations.</p>
            </div>
            <div>
                <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary">
                    + Book Appointment
                </a>
            </div>
        </div>

        <?php if (empty($history)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No records found.</div>
                <p class="empty-state-desc">You have no appointment records recorded in the database yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Token</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><strong>Dr. <?= htmlspecialchars($h['doctor_name']) ?></strong></td>
                                <td><?= htmlspecialchars($h['specialization']) ?></td>
                                <td><?= format_date($h['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($h['appointment_time']) ?></td>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-weight: 700; color: var(--primary);">
                                        <?= htmlspecialchars($h['token_number']) ?>
                                    </span>
                                </td>
                                <td><?= render_status_badge($h['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
