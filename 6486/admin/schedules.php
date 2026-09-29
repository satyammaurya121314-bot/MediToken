<?php
// admin/schedules.php
// Admin Doctor Schedule & Availability Management

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();

// Handle quick inline schedule update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $doc_id = (int)($_POST['doctor_id'] ?? 0);
    $timing = trim($_POST['available_time'] ?? '');
    $status = trim($_POST['availability_status'] ?? 'Available');

    if ($doc_id > 0 && !empty($timing) && in_array($status, ['Available', 'Unavailable'])) {
        $upd = $pdo->prepare("UPDATE doctors SET available_time = ?, availability_status = ? WHERE doctor_id = ?");
        $upd->execute([$timing, $status, $doc_id]);
        set_flash('success', 'Doctor schedule updated successfully in database.');
    } else {
        set_flash('error', 'Please provide valid timing and availability status.');
    }
    header("Location: " . get_base_url() . "/admin/schedules.php");
    exit;
}

// Fetch all doctors with their current schedules
$doctors = $pdo->query("SELECT * FROM doctors ORDER BY doctor_name ASC")->fetchAll();

$page_title = "Doctor Schedules - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Doctor Schedule & Availability</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Changes made here immediately govern slot generation and booking availability.</p>
            </div>
            <div>
                <a href="<?= $base_url ?>/admin/doctors.php?action=add" class="btn btn-primary">+ Register Doctor</a>
            </div>
        </div>

        <?php display_flash(); ?>

        <?php if (empty($doctors)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No doctors found.</div>
                <p class="empty-state-desc">There are no doctors registered in the database to configure schedules for.</p>
                <a href="<?= $base_url ?>/admin/doctors.php?action=add" class="btn btn-primary" style="margin-top: 1.25rem;">Add Doctor Now</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th style="min-width: 220px;">Available Consultation Timing</th>
                            <th style="min-width: 170px;">Live Status</th>
                            <th style="text-align: right;">Save</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doc): ?>
                            <tr>
                                <form method="POST" action="">
                                    <input type="hidden" name="doctor_id" value="<?= (int)$doc['doctor_id'] ?>">
                                    <td>
                                        <strong>Dr. <?= htmlspecialchars($doc['doctor_name']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($doc['email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($doc['specialization']) ?></td>
                                    <td>
                                        <input type="text" name="available_time" class="form-control" style="font-size: 0.875rem;" value="<?= htmlspecialchars($doc['available_time']) ?>" required>
                                    </td>
                                    <td>
                                        <select name="availability_status" class="form-select" style="font-size: 0.875rem;">
                                            <option value="Available" <?= $doc['availability_status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Unavailable" <?= $doc['availability_status'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                        </select>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="submit" name="update_schedule" class="btn btn-sm btn-primary">Update</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
