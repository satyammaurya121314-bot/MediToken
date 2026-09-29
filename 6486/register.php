<?php
// register.php
// Real patient registration inserting into MySQL patients table

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    $role = $_SESSION['user_role'];
    header("Location: " . get_base_url() . "/{$role}/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile_no'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Backend Validations
    if (empty($name) || empty($mobile) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', preg_replace('/[^\d]/', '', $mobile))) {
        $error = 'Please enter a valid mobile number (10 to 15 digits).';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters in length.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password confirmation does not match.';
    } else {
        $pdo = get_db_connection();

        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $error = 'An account with this email address already exists. Please login instead.';
        } else {
            // Hash password securely with BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            try {
                $ins_stmt = $pdo->prepare("
                    INSERT INTO patients (name, mobile_no, email, password)
                    VALUES (?, ?, ?, ?)
                ");
                $ins_stmt->execute([$name, $mobile, $email, $hashed_password]);
                $patient_id = $pdo->lastInsertId();

                // Automatically log the patient in and redirect to dashboard
                login_user('patient', $patient_id, $name, $email);

                // Add welcome notification
                add_notification($pdo, $patient_id, "Welcome to MediToken, {$name}! You can now book doctor appointments and receive digital tokens.", "welcome");

                header("Location: " . get_base_url() . "/patient/dashboard.php?msg=reg_success");
                exit;
            } catch (Exception $e) {
                error_log("Patient registration error: " . $e->getMessage());
                $error = 'An error occurred during registration. Please try again.';
            }
        }
    }
}

$page_title = "Patient Registration - MediToken";
include __DIR__ . '/includes/header.php';
?>

<div class="section" style="max-width: 580px; margin: 1.5rem auto;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 1.75rem;">
            <div class="brand-icon" style="margin: 0 auto 0.75rem; width: 44px; height: 44px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </div>
            <h2 class="card-title" style="font-size: 1.6rem;">Create Patient Account</h2>
            <p class="card-text">Register to book doctor appointments and receive live digital tokens.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div class="form-group">
                <label class="form-label" for="name">Full Name <span style="color: var(--danger);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="mobile_no">Mobile Number <span style="color: var(--danger);">*</span></label>
                <input type="tel" id="mobile_no" name="mobile_no" class="form-control" required placeholder="e.g. 9876543210" value="<?= htmlspecialchars($_POST['mobile_no'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address <span style="color: var(--danger);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password (min 6 characters) <span style="color: var(--danger);">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Create a strong password">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password <span style="color: var(--danger);">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Repeat your password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.75rem;">Register Account</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? <a href="<?= $base_url ?>/login.php?role=patient" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
