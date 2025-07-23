<?php
/**
 * Main Landing Page - AI Assistant
 * This is the entry point that redirects users to the appropriate section
 */

// Start session to check if user is logged in
session_start();

// If user is logged in, redirect to admin panel
if (isset($_SESSION['user_id'])) {
    header('Location: admin/knowledge/');
    exit;
}

// Otherwise, redirect to the chatbot interface
header('Location: app/index.php');
exit;
?>