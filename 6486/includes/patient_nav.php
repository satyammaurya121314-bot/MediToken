<?php
// includes/patient_nav.php
$base_url = get_base_url();
$cur = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-profile">
        <div class="profile-name"><?= htmlspecialchars($user['name'] ?? 'Patient') ?></div>
        <div class="profile-role">Patient Portal &bull; <strong style="color: var(--primary);">ID: #<?= (int)($user['id'] ?? 0) ?></strong></div>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-item <?= $cur === 'dashboard.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/dashboard.php">Dashboard</a>
        </li>
        <li class="sidebar-item <?= $cur === 'book_appointment.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/book_appointment.php">Book Appointment</a>
        </li>
        <li class="sidebar-item <?= $cur === 'appointments.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/appointments.php">My Appointments</a>
        </li>
        <li class="sidebar-item <?= $cur === 'queue.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/queue.php">Live Queue</a>
        </li>
        <li class="sidebar-item <?= $cur === 'history.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/history.php">History</a>
        </li>
        <li class="sidebar-item <?= $cur === 'profile.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/patient/profile.php">Profile</a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $base_url ?>/logout.php" style="color: var(--danger);">Logout</a>
        </li>
    </ul>
</aside>
