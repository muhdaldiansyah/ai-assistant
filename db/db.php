<?php
/**
 * admin/api/db.php
 * ------------------------------------------------------------------
 * Centralized database connection handler for MariaDB 10.6.
 * Updated to use the central config.php file.
 */

// Ensure this script is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die('Access denied.');
}

// Include the central configuration file
require_once __DIR__ . '/../../config.php';

// Get the database connection using the central function
$pdo = getDB();

// If database connection failed, handle the error
if ($pdo === null) {
    // Only send JSON error if this is an API request
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    }
    exit;
}
