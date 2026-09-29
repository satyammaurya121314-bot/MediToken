<?php
// patient/appointments.php
// Patient's active and upcoming appointments management

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

// Handle appointment cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $apt_id = (int)($_POST['appointment_id'] ?? 0);

    // Verify appointment belongs to this patient and is in 'Waiting' status
    $check_stmt = $pdo->prepare("
        SELECT a.appointment_id, a.token_number, a.appointment_date, d.doctor_name, d.doctor_id
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.appointment_id = ? AND a.patient_id = ? AND a.status = 'Waiting'
    ");
    $check_stmt->execute([$apt_id, $patient_id]);
    $apt = $check_stmt->fetch();

    if ($apt) {
        $cancel_stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
        $cancel_stmt->execute([$apt_id]);

        $msg = "Your appointment (Token: {$apt['token_number']}) with Dr. {$apt['doctor_name']} on {$apt['appointment_date']} has been cancelled.";
        add_notification($pdo, $patient_id, $msg, 'appointment_cancelled', $apt['doctor_id'], $apt_id);

        set_flash('success', 'Your appointment has been cancelled successfully.');
    } else {
        set_flash('error', 'Unable to cancel appointment. Only pending waiting appointments can be cancelled.');
    }

    header("Location: " . get_base_url() . "/patient/appointments.php");
    exit;
}

// Fetch active appointments (Waiting or Serving)
$stmt = $pdo->prepare("
    SELECT a.*, d.doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? 
      AND a.status IN ('Waiting', 'Serving')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();

$page_title = "My Appointments - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">My Active Appointments</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">View and manage your scheduled consultations and digital tokens.</p>
            </div>
            <div>
                <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary">
                    + Book New Appointment
                </a>
            </div>
        </div>

        <?php display_flash(); ?>

        <?php if (empty($appointments)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No active appointments scheduled.</div>
                <p class="empty-state-desc">You do not have any pending or currently serving appointments.</p>
                <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem; justify-content: center;">
                    <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-primary">Book an Appointment</a>
                    <a href="<?= $base_url ?>/patient/history.php" class="btn btn-secondary">View Past History</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                        <?= htmlspecialchars($apt['token_number']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($apt['doctor_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($apt['specialization']) ?></td>
                                <td><?= format_date($apt['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($apt['appointment_time']) ?></td>
                                <td><?= render_status_badge($apt['status']) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= $base_url ?>/patient/queue.php?appointment_id=<?= (int)$apt['appointment_id'] ?>" class="btn btn-sm btn-primary">
                                        Track Queue
                                    </a>

                                    <?php if ($apt['status'] === 'Waiting'): ?>
                                        <form method="POST" action="" style="display: inline-block; margin-left: 0.35rem;" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$apt['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
