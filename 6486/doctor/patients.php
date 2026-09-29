<?php
// doctor/patients.php
// Patients who have booked appointments with the logged-in doctor

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('doctor');

$pdo = get_db_connection();
$doctor_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT 
        p.patient_id, 
        p.name AS patient_name, 
        p.mobile_no, 
        p.email, 
        COUNT(a.appointment_id) AS total_visits,
        MAX(a.appointment_date) AS last_visit_date
    FROM patients p
    JOIN appointments a ON p.patient_id = a.patient_id
    WHERE a.doctor_id = ?
";
$params = [$doctor_id];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.mobile_no LIKE ? OR p.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY p.patient_id, p.name, p.mobile_no, p.email ORDER BY last_visit_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$page_title = "Doctor Patients - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">My Consulting Patients</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Patients who have attended or scheduled consultations with you.</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" style="flex: 1; min-width: 240px;" placeholder="Search patient name, mobile, or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?= $base_url ?>/doctor/patients.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($patients)): ?>
            <div class="card empty-state">
                
                <div class="empty-state-title">No patient records found.</div>
                <p class="empty-state-desc">No patients have scheduled consultations with you yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Mobile Number</th>
                            <th>Email Address</th>
                            <th>Total Visits</th>
                            <th>Last Visit Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['patient_name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['mobile_no']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary);">
                                        <?= (int)$p['total_visits'] ?>
                                    </span>
                                </td>
                                <td><?= format_date($p['last_visit_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
