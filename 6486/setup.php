<?php
// setup.php
// Secure initial administrator setup (Only accessible when 0 admins exist in MySQL)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_db_connection();
$stmt = $pdo->query("SELECT COUNT(*) FROM admins");
$admin_count = (int)$stmt->fetchColumn();

// If admin already exists, permanently lock setup
if ($admin_count > 0) {
    header("Location: " . get_base_url() . "/login.php?role=admin&msg=setup_completed");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash password securely with BCRYPT
        $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
        
        $ins = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        if ($ins->execute([$username, $hashed_pw])) {
            $new_admin_id = $pdo->lastInsertId();
            login_user('admin', $new_admin_id, $username, $username . '@system.local');
            header("Location: " . get_base_url() . "/admin/dashboard.php?msg=welcome");
            exit;
        } else {
            $error = 'Failed to create administrator account.';
        }
    }
}

$page_title = "Initial Administrator Setup - MediToken";
include __DIR__ . '/includes/header.php';
?>

<div class="section" style="max-width: 540px; margin: 2rem auto;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </div>
            <h2 class="card-title" style="font-size: 1.5rem;">Initial Administrator Setup</h2>
            <p class="card-text">Create your primary administrative account for MediToken. This initial setup is locked permanently once created.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Administrator Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="e.g. admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (min 6 characters)</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Choose a strong password">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Repeat password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Complete Setup & Enter Admin Portal</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
