<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$base_url = get_base_url();
$page_title = $page_title ?? 'MediToken - Doctor Appointment & Digital Token Generation System';
$current_page = basename($_SERVER['PHP_SELF']);
$logged_in = is_logged_in();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MediToken is a digital doctor appointment booking and token generation system. Track live queues, avoid long clinic waiting lines, and receive digital tokens in real-time.">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
</head>
<body>

<header class="site-nav">
    <div class="nav-container">
        <a href="<?= $base_url ?>/index.php" class="brand-logo">
            <span class="brand-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </span>
            <span>Medi<span style="color: var(--primary);">Token</span></span>
        </a>

        <button class="mobile-nav-toggle" aria-label="Toggle navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <ul class="nav-links">
            <li><a href="<?= $base_url ?>/index.php" class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a></li>
            <li><a href="<?= $base_url ?>/doctors.php" class="nav-link <?= $current_page === 'doctors.php' ? 'active' : '' ?>">Doctors</a></li>
            <li><a href="<?= $base_url ?>/services.php" class="nav-link <?= $current_page === 'services.php' ? 'active' : '' ?>">Services</a></li>
            <li><a href="<?= $base_url ?>/about.php" class="nav-link <?= $current_page === 'about.php' ? 'active' : '' ?>">About</a></li>
        </ul>

        <div class="nav-actions">
            <?php if ($logged_in): ?>
                <?php
                $dash_link = $base_url . '/login.php';
                if ($user['role'] === 'patient') $dash_link = $base_url . '/patient/dashboard.php';
                elseif ($user['role'] === 'doctor') $dash_link = $base_url . '/doctor/dashboard.php';
                elseif ($user['role'] === 'admin') $dash_link = $base_url . '/admin/dashboard.php';
                ?>
                <a href="<?= $dash_link ?>" class="btn btn-sm btn-secondary">Dashboard (<?= htmlspecialchars($user['name']) ?>)</a>
                <a href="<?= $base_url ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?= $base_url ?>/login.php" class="btn btn-sm btn-secondary">Login</a>
                <a href="<?= $base_url ?>/register.php" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($logged_in && $user['role'] === 'patient'): ?>
    <div id="notificationHook" data-base-url="<?= $base_url ?>"></div>
<?php endif; ?>
