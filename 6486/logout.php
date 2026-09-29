<?php
// logout.php
// Cleans session and redirects safely to login page

require_once __DIR__ . '/includes/auth.php';

logout_user();
$base_url = get_base_url();
header("Location: " . $base_url . "/login.php?msg=logged_out");
exit;
