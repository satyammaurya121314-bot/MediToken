<?php
// login.php
// Unified portal login verifying actual MySQL credentials for Patient, Doctor, or Admin

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = $_SESSION['user_role'];
    header("Location: " . get_base_url() . "/{$role}/dashboard.php");
    exit;
}

$selected_role = trim($_GET['role'] ?? 'patient');
if (!in_array($selected_role, ['patient', 'doctor', 'admin'])) {
    $selected_role = 'patient';
}

$error = '';
$setup_needed = is_initial_setup_needed();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'patient');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both credentials.';
    } else {
        $pdo = get_db_connection();

        if ($role === 'patient') {
            $stmt = $pdo->prepare("SELECT patient_id, name, email, password FROM patients WHERE email = ?");
            $stmt->execute([strtolower($identifier)]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user('patient', $user['patient_id'], $user['name'], $user['email']);
                header("Location: " . get_base_url() . "/patient/dashboard.php");
                exit;
            } else {
                $error = 'Invalid email/username or password.';
            }

        } elseif ($role === 'doctor') {
            $stmt = $pdo->prepare("SELECT doctor_id, doctor_name, email, password FROM doctors WHERE email = ?");
            $stmt->execute([strtolower($identifier)]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user('doctor', $user['doctor_id'], $user['doctor_name'], $user['email']);
                header("Location: " . get_base_url() . "/doctor/dashboard.php");
                exit;
            } else {
                $error = 'Invalid email/username or password.';
            }

        } elseif ($role === 'admin') {
            $stmt = $pdo->prepare("SELECT admin_id, username, password FROM admins WHERE username = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user('admin', $user['admin_id'], $user['username'], $user['username'] . '@system.local');
                header("Location: " . get_base_url() . "/admin/dashboard.php");
                exit;
            } else {
                $error = 'Invalid email/username or password.';
            }
        }
    }
}

$page_title = "Sign In - MediToken";
include __DIR__ . '/includes/header.php';
?>

<div class="section" style="max-width: 520px; margin: 1.5rem auto;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div class="brand-icon" style="margin: 0 auto 0.75rem; width: 44px; height: 44px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </div>
            <h2 class="card-title" style="font-size: 1.6rem;">Sign In to MediToken</h2>
            <p class="card-text">Select your role and enter your authorized credentials.</p>
        </div>

        <!-- Role Selector Tabs -->
        <div style="display: flex; gap: 0.5rem; background: var(--bg-main); padding: 0.35rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <a href="?role=patient" class="btn btn-sm <?= $selected_role === 'patient' ? 'btn-primary' : 'btn-secondary' ?>" style="flex: 1;">Patient</a>
            <a href="?role=doctor" class="btn btn-sm <?= $selected_role === 'doctor' ? 'btn-primary' : 'btn-secondary' ?>" style="flex: 1;">Doctor</a>
            <a href="?role=admin" class="btn btn-sm <?= $selected_role === 'admin' ? 'btn-primary' : 'btn-secondary' ?>" style="flex: 1;">Admin</a>
        </div>

        <?php if ($selected_role === 'admin' && $setup_needed): ?>
            <div class="alert alert-warning" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <div><strong>First Time Admin Setup</strong></div>
                <div style="font-size: 0.85rem;">No administrator accounts exist yet in MySQL. You can securely initialize the primary administrator.</div>
                <a href="<?= $base_url ?>/setup.php" class="btn btn-sm btn-primary" style="margin-top: 0.25rem;">Initialize Admin Account</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
            <div class="alert alert-warning">Please sign in to access that page.</div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
            <div class="alert alert-success">You have been logged out successfully.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="role" value="<?= htmlspecialchars($selected_role) ?>">

            <div class="form-group">
                <label class="form-label" for="identifier">
                    <?= $selected_role === 'admin' ? 'Admin Username' : ($selected_role === 'doctor' ? 'Doctor Registered Email' : 'Patient Email Address') ?>
                </label>
                <input type="text" id="identifier" name="identifier" class="form-control" required autofocus placeholder="<?= $selected_role === 'admin' ? 'Enter admin username' : 'name@example.com' ?>" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.75rem;">
                Sign In as <?= ucfirst($selected_role) ?>
            </button>
        </form>

        <?php if ($selected_role === 'patient'): ?>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                New patient? <a href="<?= $base_url ?>/register.php" style="font-weight: 600;">Create an account</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
