<?php
// admin/doctors.php
// Admin Doctor Management: Add, Edit, Delete, Toggle Availability, View

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pdo = get_db_connection();
$action = trim($_GET['action'] ?? 'list');
$doctor_id = (int)($_GET['id'] ?? 0);

$error = '';
$success = '';

// Handle Delete Doctor
if ($action === 'delete' && $doctor_id > 0) {
    try {
        $del = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
        $del->execute([$doctor_id]);
        set_flash('success', 'Doctor record deleted successfully.');
    } catch (Exception $e) {
        set_flash('error', 'Unable to delete doctor. Associated appointments exist.');
    }
    header("Location: " . get_base_url() . "/admin/doctors.php");
    exit;
}

// Handle Toggle Availability
if ($action === 'toggle' && $doctor_id > 0) {
    $stmt = $pdo->prepare("SELECT availability_status FROM doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $curr_status = $stmt->fetchColumn();

    $new_status = ($curr_status === 'Available') ? 'Unavailable' : 'Available';
    $upd = $pdo->prepare("UPDATE doctors SET availability_status = ? WHERE doctor_id = ?");
    $upd->execute([$new_status, $doctor_id]);

    set_flash('success', "Doctor availability changed to {$new_status}.");
    header("Location: " . get_base_url() . "/admin/doctors.php");
    exit;
}

// Handle Add Doctor POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = trim($_POST['doctor_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $available_time = trim($_POST['available_time'] ?? '09:00 AM - 01:00 PM');
    $availability_status = trim($_POST['availability_status'] ?? 'Available');

    if (empty($name) || empty($email) || empty($password) || empty($specialization) || empty($available_time)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $check = $pdo->prepare("SELECT doctor_id FROM doctors WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'A doctor with this email address already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("
                INSERT INTO doctors (doctor_name, email, password, specialization, available_time, availability_status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$name, $email, $hashed, $specialization, $available_time, $availability_status]);
            set_flash('success', "Dr. {$name} added successfully.");
            header("Location: " . get_base_url() . "/admin/doctors.php");
            exit;
        }
    }
}

// Handle Edit Doctor POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doctor'])) {
    $name = trim($_POST['doctor_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $available_time = trim($_POST['available_time'] ?? '');
    $availability_status = trim($_POST['availability_status'] ?? 'Available');

    if (empty($name) || empty($email) || empty($specialization) || empty($available_time)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $pdo->prepare("SELECT doctor_id FROM doctors WHERE email = ? AND doctor_id != ?");
        $check->execute([$email, $doctor_id]);
        if ($check->fetch()) {
            $error = 'Another doctor already uses this email.';
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("
                    UPDATE doctors 
                    SET doctor_name = ?, email = ?, password = ?, specialization = ?, available_time = ?, availability_status = ? 
                    WHERE doctor_id = ?
                ");
                $upd->execute([$name, $email, $hashed, $specialization, $available_time, $availability_status, $doctor_id]);
            } else {
                $upd = $pdo->prepare("
                    UPDATE doctors 
                    SET doctor_name = ?, email = ?, specialization = ?, available_time = ?, availability_status = ? 
                    WHERE doctor_id = ?
                ");
                $upd->execute([$name, $email, $specialization, $available_time, $availability_status, $doctor_id]);
            }
            set_flash('success', "Dr. {$name} updated successfully.");
            header("Location: " . get_base_url() . "/admin/doctors.php");
            exit;
        }
    }
}

// Data for Edit View
$edit_doctor = null;
if ($action === 'edit' && $doctor_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $edit_doctor = $stmt->fetch();
}

// Data for List View
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM doctors WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (doctor_name LIKE ? OR specialization LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$sql .= " ORDER BY doctor_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$page_title = "Doctor Management - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Doctor Management</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Register, configure, and maintain consulting medical staff in MySQL.</p>
            </div>
            <div>
                <?php if ($action !== 'add'): ?>
                    <a href="<?= $base_url ?>/admin/doctors.php?action=add" class="btn btn-primary">+ Add New Doctor</a>
                <?php else: ?>
                    <a href="<?= $base_url ?>/admin/doctors.php" class="btn btn-secondary">← Back to List</a>
                <?php endif; ?>
            </div>
        </div>

        <?php display_flash(); ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($action === 'add'): ?>
            <!-- Add Doctor Form -->
            <div class="card" style="max-width: 680px; margin: 0 auto;">
                <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem;">Register New Doctor</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="doctor_name">Doctor Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="doctor_name" name="doctor_name" class="form-control" required placeholder="e.g. Sarah Jenkins" value="<?= htmlspecialchars($_POST['doctor_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Doctor Email (Login Username) <span style="color: var(--danger);">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="doctor@meditoken.local" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Initial Password (min 6 chars) <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Create doctor login password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="specialization">Specialization <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="specialization" name="specialization" class="form-control" required placeholder="e.g. Cardiologist, Dermatologist, Pediatrician" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="available_time">Available Consultation Timing <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="available_time" name="available_time" class="form-control" required placeholder="09:00 AM - 01:00 PM" value="<?= htmlspecialchars($_POST['available_time'] ?? '09:00 AM - 01:00 PM') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="availability_status">Initial Availability Status</label>
                        <select id="availability_status" name="availability_status" class="form-select">
                            <option value="Available">Available (Accepting bookings)</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" name="add_doctor" class="btn btn-primary">Create Doctor Record</button>
                        <a href="<?= $base_url ?>/admin/doctors.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'edit' && $edit_doctor): ?>
            <!-- Edit Doctor Form -->
            <div class="card" style="max-width: 680px; margin: 0 auto;">
                <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem;">Edit Doctor: Dr. <?= htmlspecialchars($edit_doctor['doctor_name']) ?></h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="doctor_name">Doctor Full Name</label>
                        <input type="text" id="doctor_name" name="doctor_name" class="form-control" required value="<?= htmlspecialchars($edit_doctor['doctor_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Doctor Email</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($edit_doctor['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Change Password (leave blank to keep unchanged)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Optional new password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" required value="<?= htmlspecialchars($edit_doctor['specialization']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="available_time">Available Consultation Timing</label>
                        <input type="text" id="available_time" name="available_time" class="form-control" required value="<?= htmlspecialchars($edit_doctor['available_time']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="availability_status">Availability Status</label>
                        <select id="availability_status" name="availability_status" class="form-select">
                            <option value="Available" <?= $edit_doctor['availability_status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Unavailable" <?= $edit_doctor['availability_status'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" name="edit_doctor" class="btn btn-primary">Save Changes</button>
                        <a href="<?= $base_url ?>/admin/doctors.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- Doctors List -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <input type="text" name="search" class="form-control" style="flex: 1; min-width: 240px;" placeholder="Search doctor name, specialization, or email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= $base_url ?>/admin/doctors.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($doctors)): ?>
                <div class="card empty-state">
                    
                    <div class="empty-state-title">No doctors found.</div>
                    <p class="empty-state-desc">The doctors table in MySQL has zero matching records.</p>
                    <a href="<?= $base_url ?>/admin/doctors.php?action=add" class="btn btn-primary" style="margin-top: 1.25rem;">Add First Doctor</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Doctor ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Timing</th>
                                <th>Availability</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $d): ?>
                                <tr>
                                    <td>#<?= (int)$d['doctor_id'] ?></td>
                                    <td><strong>Dr. <?= htmlspecialchars($d['doctor_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($d['email']) ?></td>
                                    <td><span style="color: var(--primary); font-weight: 600;"><?= htmlspecialchars($d['specialization']) ?></span></td>
                                    <td><?= htmlspecialchars($d['available_time']) ?></td>
                                    <td>
                                        <a href="?action=toggle&id=<?= (int)$d['doctor_id'] ?>" title="Click to toggle status" style="text-decoration: none;">
                                            <?php if ($d['availability_status'] === 'Available'): ?>
                                                <span class="status-badge badge-available">● Available</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-unavailable">● Unavailable</span>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="?action=edit&id=<?= (int)$d['doctor_id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <a href="?action=delete&id=<?= (int)$d['doctor_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this doctor?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
