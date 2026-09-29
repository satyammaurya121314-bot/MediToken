<?php
// admin/queue.php
// Centralized live queue monitor for administrators across all doctors

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();
$selected_date = trim($_GET['date'] ?? date('Y-m-d'));

// Fetch all doctors
$doc_stmt = $pdo->query("SELECT * FROM doctors ORDER BY doctor_name ASC");
$doctors = $doc_stmt->fetchAll();

// Compile doctor-wise queue metrics from MySQL
$queue_summary = [];

foreach ($doctors as $doc) {
    $doc_id = (int)$doc['doctor_id'];

    // Current Serving Token
    $serv_stmt = $pdo->prepare("SELECT token_number FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving' LIMIT 1");
    $serv_stmt->execute([$doc_id, $selected_date]);
    $curr_serving = $serv_stmt->fetchColumn() ?: 'None';

    // Next Token
    $next_stmt = $pdo->prepare("SELECT token_number FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting' ORDER BY appointment_id ASC LIMIT 1");
    $next_stmt->execute([$doc_id, $selected_date]);
    $next_token = $next_stmt->fetchColumn() ?: 'None';

    // Waiting Patients Count
    $wait_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'");
    $wait_stmt->execute([$doc_id, $selected_date]);
    $waiting_count = (int)$wait_stmt->fetchColumn();

    // Completed Patients Count
    $comp_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'Completed'");
    $comp_stmt->execute([$doc_id, $selected_date]);
    $completed_count = (int)$comp_stmt->fetchColumn();

    // Total Bookings
    $tot_stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    $tot_stmt->execute([$doc_id, $selected_date]);
    $total_today = (int)$tot_stmt->fetchColumn();

    $queue_summary[] = [
        'doctor_id'           => $doc_id,
        'doctor_name'         => $doc['doctor_name'],
        'specialization'      => $doc['specialization'],
        'availability_status' => $doc['availability_status'],
        'current_token'       => $curr_serving,
        'next_token'          => $next_token,
        'waiting_patients'    => $waiting_count,
        'completed_patients'  => $completed_count,
        'total_bookings'      => $total_today,
    ];
}

$page_title = "Clinic Live Queue Monitor - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Live Queue Command Center</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Doctor-wise real-time queue monitor for <?= format_date($selected_date) ?>.</p>
            </div>

            <!-- Date Selector -->
            <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="date" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Queue Date:</label>
                <input type="date" id="date" name="date" class="form-control" style="width: auto; padding: 0.4rem 0.75rem;" value="<?= htmlspecialchars($selected_date) ?>" onchange="this.form.submit();">
            </form>
        </div>

        <?php if (empty($queue_summary)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No doctors registered.</div>
                <p class="empty-state-desc">The system has no active doctor queues to monitor.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Current Serving</th>
                            <th>Next in Line</th>
                            <th>Waiting Patients</th>
                            <th>Completed</th>
                            <th>Total Scheduled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queue_summary as $qs): ?>
                            <tr>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($qs['doctor_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($qs['specialization']) ?></td>
                                <td>
                                    <?php if ($qs['availability_status'] === 'Available'): ?>
                                        <span class="status-badge badge-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-unavailable">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--primary);">
                                        <?= htmlspecialchars($qs['current_token']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--dark);">
                                        <?= htmlspecialchars($qs['next_token']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 800; color: #d97706; font-size: 1.1rem;">
                                        <?= (int)$qs['waiting_patients'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 800; color: #059669; font-size: 1.1rem;">
                                        <?= (int)$qs['completed_patients'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">
                                        <?= (int)$qs['total_bookings'] ?>
                                    </span>
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
