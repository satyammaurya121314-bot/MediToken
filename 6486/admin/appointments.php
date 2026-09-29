<?php
// admin/appointments.php
// Admin Appointments Management: Search, Filter by Doctor, Date, Status, and Cancel

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();

// Handle Cancel Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $apt_id = (int)($_POST['appointment_id'] ?? 0);
    $check = $pdo->prepare("SELECT patient_id, doctor_id, token_number FROM appointments WHERE appointment_id = ?");
    $check->execute([$apt_id]);
    $apt = $check->fetch();

    if ($apt) {
        $upd = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
        $upd->execute([$apt_id]);
        add_notification($pdo, $apt['patient_id'], "Your appointment (Token {$apt['token_number']}) was cancelled by the clinic administrator.", 'appointment_cancelled', $apt['doctor_id'], $apt_id);
        set_flash('success', "Appointment #{$apt_id} (Token {$apt['token_number']}) has been cancelled.");
    }
    header("Location: " . get_base_url() . "/admin/appointments.php");
    exit;
}

// Fetch Doctors list for filter dropdown
$doc_list = $pdo->query("SELECT doctor_id, doctor_name FROM doctors ORDER BY doctor_name ASC")->fetchAll();

// Filters
$search = trim($_GET['search'] ?? '');
$doctor_filter = (int)($_GET['doctor_id'] ?? 0);
$status_filter = trim($_GET['status'] ?? '');
$date_filter = trim($_GET['date'] ?? '');

$sql = "
    SELECT 
        a.*, 
        p.name AS patient_name, 
        p.mobile_no AS patient_mobile,
        p.email AS patient_email,
        d.doctor_name, 
        d.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR a.token_number LIKE ? OR d.doctor_name LIKE ? OR p.mobile_no LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($doctor_filter > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctor_filter;
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

$page_title = "Manage Appointments - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Appointment Registry</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Comprehensive clinic booking log from MySQL database.</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Filter Bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form method="GET" action="" style="display: flex; gap: 0.85rem; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 200px;">
                    <label class="form-label" for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Patient name, doctor, or token..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="flex: 1.5; min-width: 170px;">
                    <label class="form-label" for="doctor_id">Doctor</label>
                    <select id="doctor_id" name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        <?php foreach ($doc_list as $doc): ?>
                            <option value="<?= (int)$doc['doctor_id'] ?>" <?= $doctor_filter === (int)$doc['doctor_id'] ? 'selected' : '' ?>>
                                Dr. <?= htmlspecialchars($doc['doctor_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 130px;">
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

                <div style="flex: 1; min-width: 140px;">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search !== '' || $doctor_filter > 0 || $status_filter !== '' || $date_filter !== ''): ?>
                        <a href="<?= $base_url ?>/admin/appointments.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No appointments found.</div>
                <p class="empty-state-desc">The database has no appointments matching the specified query filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Token</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td>#<?= (int)$apt['appointment_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($apt['patient_name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($apt['patient_mobile']) ?></div>
                                </td>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($apt['doctor_name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($apt['specialization']) ?></div>
                                </td>
                                <td><?= format_date($apt['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($apt['appointment_time']) ?></td>
                                <td>
                                    <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                        <?= htmlspecialchars($apt['token_number']) ?>
                                    </span>
                                </td>
                                <td><?= render_status_badge($apt['status']) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($apt['status'] === 'Waiting' || $apt['status'] === 'Serving'): ?>
                                        <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Cancel this appointment?');">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$apt['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">Archived</span>
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
