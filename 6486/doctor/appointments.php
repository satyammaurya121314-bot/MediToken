<?php
// doctor/appointments.php
// All appointments assigned to the logged-in doctor

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('doctor');

$pdo = get_db_connection();
$doctor_id = $_SESSION['user_id'];

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_filter = trim($_GET['date'] ?? '');

$sql = "
    SELECT a.*, p.name AS patient_name, p.mobile_no, p.email AS patient_email
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ?
";
$params = [$doctor_id];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR a.token_number LIKE ? OR p.mobile_no LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status_filter !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if ($date_filter !== '') {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$page_title = "Doctor Appointments - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Doctor Consultation Registry</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Complete historical and scheduled appointment records.</p>
            </div>
            <div>
                <a href="<?= $base_url ?>/doctor/queue.php" class="btn btn-primary">Go to Live Queue &rarr;</a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label class="form-label" for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Patient name, token, or mobile..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Waiting" <?= $status_filter === 'Waiting' ? 'selected' : '' ?>>Waiting</option>
                        <option value="Serving" <?= $status_filter === 'Serving' ? 'selected' : '' ?>>Serving</option>
                        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Skipped" <?= $status_filter === 'Skipped' ? 'selected' : '' ?>>Skipped</option>
                        <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search !== '' || $status_filter !== '' || $date_filter !== ''): ?>
                        <a href="<?= $base_url ?>/doctor/appointments.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No appointments found.</div>
                <p class="empty-state-desc">No appointment records match your criteria in the database.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Patient Name</th>
                            <th>Mobile</th>
                            <th>Date</th>
                            <th>Slot Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                                        <?= htmlspecialchars($apt['token_number']) ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($apt['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($apt['mobile_no']) ?></td>
                                <td><?= format_date($apt['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($apt['appointment_time']) ?></td>
                                <td><?= render_status_badge($apt['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
