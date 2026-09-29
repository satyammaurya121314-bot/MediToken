<?php
// doctors.php
// Public Doctor Directory fetched purely from MySQL

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db_connection();
$search = trim($_GET['search'] ?? '');
$specialization = trim($_GET['specialization'] ?? '');

$sql = "SELECT doctor_id, doctor_name, email, specialization, available_time, availability_status FROM doctors WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (doctor_name LIKE ? OR specialization LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($specialization !== '') {
    $sql .= " AND specialization = ?";
    $params[] = $specialization;
}

$sql .= " ORDER BY doctor_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get unique specializations for filter dropdown
$spec_stmt = $pdo->query("SELECT DISTINCT specialization FROM doctors ORDER BY specialization ASC");
$all_specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Find a Doctor - MediToken";
include __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Medical Experts</div>
            <h2 class="section-title">Our Consulting Doctors</h2>
            <p class="section-desc">View verified specialists, check active consultation hours, and reserve your digital token.</p>
        </div>

        <!-- Filter Bar -->
        <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 220px;">
                    <label class="form-label" for="search">Search Doctor</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search by name or keyword..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div style="flex: 1.5; min-width: 180px;">
                    <label class="form-label" for="specialization">Filter Specialization</label>
                    <select id="specialization" name="specialization" class="form-select">
                        <option value="">All Specializations</option>
                        <?php foreach ($all_specializations as $spec): ?>
                            <option value="<?= htmlspecialchars($spec) ?>" <?= $specialization === $spec ? 'selected' : '' ?>>
                                <?= htmlspecialchars($spec) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search !== '' || $specialization !== ''): ?>
                        <a href="<?= $base_url ?>/doctors.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Doctor Cards -->
        <?php if (empty($doctors)): ?>
            <div class="card empty-state">
                <div class="empty-state-title">No doctors are currently available.</div>
                <p class="empty-state-desc">
                    <?= ($search !== '' || $specialization !== '') ? 'No doctors match your filter criteria. Try resetting your search.' : 'Our clinic administrator has not added any doctors to the system yet.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($doctors as $doc): ?>
                    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.85rem;">
                                <div class="brand-icon" style="width: 44px; height: 44px;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                </div>
                                <?php if ($doc['availability_status'] === 'Available'): ?>
                                    <span class="status-badge badge-available">Available</span>
                                <?php else: ?>
                                    <span class="status-badge badge-unavailable">Unavailable</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="card-title" style="margin-bottom: 0.35rem;">
                                Dr. <?= htmlspecialchars($doc['doctor_name']) ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal; margin-left: 0.35rem;">#<?= (int)$doc['doctor_id'] ?></span>
                            </h3>
                            <p style="color: var(--primary); font-weight: 600; font-size: 0.9rem; margin-bottom: 0.85rem;">
                                <?= htmlspecialchars($doc['specialization']) ?>
                            </p>

                            <div style="background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.85rem;">
                                <div><strong>Available:</strong> <?= htmlspecialchars($doc['available_time']) ?></div>
                                <div style="color: var(--text-muted); margin-top: 0.25rem;"><strong>Email:</strong> <?= htmlspecialchars($doc['email']) ?></div>
                            </div>
                        </div>

                        <div>
                            <?php if ($doc['availability_status'] === 'Available'): ?>
                                <a href="<?= $base_url ?>/patient/book_appointment.php?doctor_id=<?= (int)$doc['doctor_id'] ?>" class="btn btn-primary" style="width: 100%;">
                                    Book Appointment
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" style="width: 100%; opacity: 0.6; cursor: not-allowed;" disabled>
                                    Currently Unavailable
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
