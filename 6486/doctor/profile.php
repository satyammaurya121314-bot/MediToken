<?php
// doctor/profile.php
// Doctor profile, consultation timing, and availability status management

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('doctor');

$pdo = get_db_connection();
$doctor_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle Profile & Availability Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $available_time = trim($_POST['available_time'] ?? '');
    $availability_status = trim($_POST['availability_status'] ?? 'Available');

    if (empty($doctor_name) || empty($specialization) || empty($available_time)) {
        $error = 'Please fill in all profile fields.';
    } elseif (!in_array($availability_status, ['Available', 'Unavailable'])) {
        $error = 'Invalid availability status.';
    } else {
        $upd = $pdo->prepare("
            UPDATE doctors 
            SET doctor_name = ?, specialization = ?, available_time = ?, availability_status = ? 
            WHERE doctor_id = ?
        ");
        $upd->execute([$doctor_name, $specialization, $available_time, $availability_status, $doctor_id]);
        $_SESSION['user_name'] = $doctor_name;
        $success = 'Doctor profile and availability updated successfully.';
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_new_password'] ?? '';

    if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
        $error = 'All password fields are required.';
    } elseif (strlen($new_pw) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm_pw) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM doctors WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        $stored_hash = $stmt->fetchColumn();

        if (password_verify($current_pw, $stored_hash)) {
            $new_hash = password_hash($new_pw, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE doctors SET password = ? WHERE doctor_id = ?");
            $upd->execute([$new_hash, $doctor_id]);
            $success = 'Password changed successfully.';
        } else {
            $error = 'Incorrect current password.';
        }
    }
}

// Fetch doctor details
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

$page_title = "Doctor Profile & Schedule - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>

    <main class="dashboard-main">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Doctor Profile & Availability</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Configure your consultation hours, live availability status, and credentials.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; max-width: 950px;">
            <!-- Profile & Hours Form -->
            <div class="card">
                <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Consultation Settings</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Doctor ID</label>
                        <input type="text" class="form-control" value="#<?= (int)($doctor['doctor_id'] ?? 0) ?>" readonly style="background: var(--bg-main); font-weight: 700; color: var(--primary);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="doctor_name">Doctor Name</label>
                        <input type="text" id="doctor_name" name="doctor_name" class="form-control" required value="<?= htmlspecialchars($doctor['doctor_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Registered Email (Read Only)</label>
                        <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($doctor['email'] ?? '') ?>" readonly style="background: var(--bg-main);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="specialization">Medical Specialization</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" required value="<?= htmlspecialchars($doctor['specialization'] ?? '') ?>" placeholder="e.g. Cardiologist, General Physician">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="available_time">Available Consultation Timing</label>
                        <input type="text" id="available_time" name="available_time" class="form-control" required value="<?= htmlspecialchars($doctor['available_time'] ?? '09:00 AM - 01:00 PM') ?>" placeholder="e.g. 09:00 AM - 01:00 PM">
                        <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">Format: HH:MM AM - HH:MM PM (used to calculate slots)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="availability_status">Current Availability Status</label>
                        <select id="availability_status" name="availability_status" class="form-select">
                            <option value="Available" <?= ($doctor['availability_status'] ?? '') === 'Available' ? 'selected' : '' ?>>Available (Open for booking)</option>
                            <option value="Unavailable" <?= ($doctor['availability_status'] ?? '') === 'Unavailable' ? 'selected' : '' ?>>Unavailable (Temporarily suspended)</option>
                        </select>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top: 0.5rem;">Update Schedule</button>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="card">
                <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Security Credentials</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password (min 6 chars)</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="Enter new password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required placeholder="Repeat new password">
                    </div>

                    <button type="submit" name="change_password" class="btn btn-secondary" style="margin-top: 0.5rem;">Update Password</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
