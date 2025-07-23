<?php
/**
 * api/logout.php
 * ------------------------------------------------------------------
 * Logout endpoint - destroys session and clears cookies
 */

session_start();

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to main app page
header('Location: ../../app/index.php');
exit;