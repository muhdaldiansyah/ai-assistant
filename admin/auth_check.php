<?php
/**
 * Authentication check for admin pages
 * Include this file at the top of any admin page that requires authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Get the root path for redirect
    $currentPath = $_SERVER['REQUEST_URI'];
    $depth = substr_count($currentPath, '/') - 2; // Adjust based on your structure
    $rootPath = str_repeat('../', $depth);
    
    // Redirect to login page
    header('Location: ' . $rootPath . 'auth/login/');
    exit;
}

// Optional: Add additional security checks
// For example, check if session is expired, validate user agent, etc.

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Optional: Get user information for use in the page
$userId = $_SESSION['user_id'];
$userName = $_SESSION['username'] ?? 'User';

// Optional: Check if user is still active in database
// This would require including config.php and checking the database
// Uncomment if you want this extra security check:
/*
require_once __DIR__ . '/../config.php';
$db = getDB();
if ($db) {
    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_active']) {
        session_destroy();
        header('Location: ' . $rootPath . 'auth/login/');
        exit;
    }
}
*/

// Function to check if user has specific permission (for future use)
function hasPermission($permission) {
    // Placeholder for permission system
    // For now, all logged-in users have all permissions
    return true;
}

// Function to safely get session variable
function getSessionVar($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}