<?php
// admin/patients.php
// Admin Patient Management: Search, View, and Delete from MySQL patients table

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();
$action = trim($_GET['action'] ?? 'list');
$patient_id = (int)($_GET['id'] ?? 0);

// Handle Delete Patient
if ($action === 'delete' && $patient_id > 0) {
    try {
        $del = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
        $del->execute([$patient_id]);
        set_flash('success', 'Patient record deleted successfully.');
    } catch (Exception $e) {
        set_flash('error', 'Unable to delete patient. Database error occurred.');
    }
    header("Location: " . get_base_url() . "/admin/patients.php");
    exit;
}

// Data for List / Search View
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT 
        p.*, 
        COUNT(a.appointment_id) AS total_appointments,
        MAX(a.appointment_date) AS last_appointment
    FROM patients p
    LEFT JOIN appointments a ON p.patient_id = a.patient_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.email LIKE ? OR p.mobile_no LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY p.patient_id, p.name, p.mobile_no, p.email, p.created_at ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$page_title = "Patient Management - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Patient Directory</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">View registered patient accounts, contact info, and consultation volume.</p>
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" style="flex: 1; min-width: 240px;" placeholder="Search by name, email, or mobile..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?= $base_url ?>/admin/patients.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($patients)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No patient records found.</div>
                <p class="empty-state-desc">The database currently has no patient records matching your query.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Full Name</th>
                            <th>Mobile Number</th>
                            <th>Email Address</th>
                            <th>Total Bookings</th>
                            <th>Registered On</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td>#<?= (int)$p['patient_id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['mobile_no']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary);">
                                        <?= (int)$p['total_appointments'] ?>
                                    </span>
                                </td>
                                <td><?= format_date($p['created_at']) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= $base_url ?>/admin/appointments.php?search=<?= urlencode($p['name']) ?>" class="btn btn-sm btn-secondary">Appointments</a>
                                    <a href="?action=delete&id=<?= (int)$p['patient_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this patient account? All their appointments will be removed.');">Delete</a>
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
