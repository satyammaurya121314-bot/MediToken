<?php
// includes/auth.php
// Authentication and session management for MediToken

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is logged in and sync session user_id with DB
 */
function is_logged_in() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && isset($_SESSION['user_email'])) {
        static $synced = false;
        if (!$synced) {
            $synced = true;
            try {
                $pdo = get_db_connection();
                if ($_SESSION['user_role'] === 'patient') {
                    $chk = $pdo->prepare("SELECT patient_id, name FROM patients WHERE email = ?");
                    $chk->execute([$_SESSION['user_email']]);
                    $row = $chk->fetch();
                    if ($row) {
                        $_SESSION['user_id'] = (int)$row['patient_id'];
                        $_SESSION['user_name'] = $row['name'];
                    }
                } elseif ($_SESSION['user_role'] === 'doctor') {
                    $chk = $pdo->prepare("SELECT doctor_id, doctor_name FROM doctors WHERE email = ?");
                    $chk->execute([$_SESSION['user_email']]);
                    $row = $chk->fetch();
                    if ($row) {
                        $_SESSION['user_id'] = (int)$row['doctor_id'];
                        $_SESSION['user_name'] = $row['doctor_name'];
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
        }
        return true;
    }
    return false;
}

/**
 * Get current user data
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'role'  => $_SESSION['user_role'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

/**
 * Require a specific role to access a page
 * If not authorized, redirect to appropriate login
 */
function require_role($required_role) {
    if (!is_logged_in()) {
        $base_url = get_base_url();
        header("Location: " . $base_url . "/login.php?role=" . urlencode($required_role) . "&msg=login_required");
        exit;
    }

    if ($_SESSION['user_role'] !== $required_role) {
        $base_url = get_base_url();
        // Redirect to their own dashboard if already logged into another role
        if ($_SESSION['user_role'] === 'patient') {
            header("Location: " . $base_url . "/patient/dashboard.php?error=unauthorized");
        } elseif ($_SESSION['user_role'] === 'doctor') {
            header("Location: " . $base_url . "/doctor/dashboard.php?error=unauthorized");
        } elseif ($_SESSION['user_role'] === 'admin') {
            header("Location: " . $base_url . "/admin/dashboard.php?error=unauthorized");
        } else {
            header("Location: " . $base_url . "/login.php?error=unauthorized");
        }
        exit;
    }
}

/**
 * Set user session upon successful login
 */
function login_user($role, $user_id, $user_name, $user_email) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_role'] = $role;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['user_email'] = $user_email;
    session_regenerate_id(true);
}

/**
 * Log out user and destroy session
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Check if initial administrator setup is needed
 */
function is_initial_setup_needed() {
    $pdo = get_db_connection();
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        return (int)$stmt->fetchColumn() === 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate CSRF token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Get base URL for absolute redirection
 */
function get_base_url() {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    // Check if running under /MediToken or /meditoken or root
    if (stripos($script_name, '/MediToken') === 0) {
        return '/MediToken';
    } elseif (stripos($script_name, '/meditoken') === 0) {
        return '/meditoken';
    }
    return '';
}
