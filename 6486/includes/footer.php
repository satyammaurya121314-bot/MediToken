<?php
// includes/footer.php
$base_url = get_base_url();
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="brand-logo" style="margin-bottom: 0.5rem;">
                <span class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </span>
                <span>Medi<span style="color: var(--primary);">Token</span></span>
            </div>
            <p>Smart Doctor Appointment Booking & Live Digital Token Queue Management System for clinics and hospitals.</p>
        </div>

        <div class="footer-col">
            <h5>Quick Links</h5>
            <ul class="footer-links">
                <li><a href="<?= $base_url ?>/index.php">Home</a></li>
                <li><a href="<?= $base_url ?>/doctors.php">Doctor Directory</a></li>
                <li><a href="<?= $base_url ?>/services.php">Clinical Services</a></li>
                <li><a href="<?= $base_url ?>/about.php">About MediToken</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h5>Portals</h5>
            <ul class="footer-links">
                <li><a href="<?= $base_url ?>/login.php?role=patient">Patient Portal</a></li>
                <li><a href="<?= $base_url ?>/login.php?role=doctor">Doctor Portal</a></li>
                <li><a href="<?= $base_url ?>/login.php?role=admin">Admin Portal</a></li>
                <li><a href="<?= $base_url ?>/register.php">Patient Registration</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div>&copy; <?= date('Y') ?> MediToken Healthcare System. All rights reserved.</div>
        <div>Real-time Appointment & Queue Management</div>
    </div>
</footer>

<script src="<?= $base_url ?>/assets/js/script.js"></script>
</body>
</html>
