<?php
require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preserve flash messages (if your flash system uses $_SESSION['flash'])
$flash_messages = $_SESSION['flash'] ?? [];

// Clear all session data
session_unset();
session_destroy();

// Start a fresh session for flash
session_start();

// Restore flash messages
if (!empty($flash_messages)) {
    $_SESSION['flash'] = $flash_messages;
}

// Add logout success message
flash('success', 'You have been logged out.');

// Redirect to home
header('Location: ' . BASE_URL . 'index.php');
exit;
