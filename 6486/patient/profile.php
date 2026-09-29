<?php
// patient/profile.php
// Patient account profile and credentials update

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('patient');

$pdo = get_db_connection();
$patient_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile_no'] ?? '');

    if (empty($name) || empty($mobile)) {
        $error = 'Please complete all required fields.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', preg_replace('/[^\d]/', '', $mobile))) {
        $error = 'Please enter a valid mobile number (10 to 15 digits).';
    } else {
        $upd = $pdo->prepare("UPDATE patients SET name = ?, mobile_no = ? WHERE patient_id = ?");
        $upd->execute([$name, $mobile, $patient_id]);
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully.';
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
        $stmt = $pdo->prepare("SELECT password FROM patients WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $stored_hash = $stmt->fetchColumn();

        if (password_verify($current_pw, $stored_hash)) {
            $new_hash = password_hash($new_pw, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE patients SET password = ? WHERE patient_id = ?");
            $upd->execute([$new_hash, $patient_id]);
            $success = 'Password changed successfully.';
        } else {
            $error = 'Incorrect current password.';
        }
    }
}

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

$page_title = "My Profile - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/patient_nav.php'; ?>

    <main class="dashboard-main">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">Patient Profile</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Manage your contact information and account security.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; max-width: 900px;">
            <!-- Profile Info Form -->
            <div class="card">
                <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Personal Details</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Patient ID</label>
                        <input type="text" class="form-control" value="#<?= (int)($patient['patient_id'] ?? 0) ?>" readonly style="background: var(--bg-main); font-weight: 700; color: var(--primary);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($patient['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Registered Email (Read Only)</label>
                        <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($patient['email'] ?? '') ?>" readonly style="background: var(--bg-main);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mobile_no">Mobile Number</label>
                        <input type="tel" id="mobile_no" name="mobile_no" class="form-control" required value="<?= htmlspecialchars($patient['mobile_no'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Registered Since</label>
                        <input type="text" class="form-control" value="<?= format_date($patient['created_at'] ?? '') ?>" readonly style="background: var(--bg-main);">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top: 0.5rem;">Save Changes</button>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="card">
                <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Security & Password</h2>
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
