<?php
// includes/admin_nav.php
$base_url = get_base_url();
$cur = basename($_SERVER['PHP_SELF']);
$user = current_user();
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-profile">
        <div class="profile-name"><?= htmlspecialchars($user['name'] ?? 'Administrator') ?></div>
        <div class="profile-role">Clinic Administration</div>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-item <?= $cur === 'dashboard.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/dashboard.php">Overview</a>
        </li>
        <li class="sidebar-item <?= $cur === 'doctors.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/doctors.php">Doctors</a>
        </li>
        <li class="sidebar-item <?= $cur === 'patients.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/patients.php">Patients</a>
        </li>
        <li class="sidebar-item <?= $cur === 'appointments.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/appointments.php">Appointments</a>
        </li>
        <li class="sidebar-item <?= $cur === 'schedules.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/schedules.php">Schedules</a>
        </li>
        <li class="sidebar-item <?= $cur === 'queue.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/queue.php">Live Queues</a>
        </li>
        <li class="sidebar-item <?= $cur === 'reports.php' ? 'active' : '' ?>">
            <a href="<?= $base_url ?>/admin/reports.php">Reports</a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $base_url ?>/logout.php" style="color: var(--danger);">Logout</a>
        </li>
    </ul>
</aside>
