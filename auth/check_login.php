<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Optional: fetch user info from database if needed
// require_once '../config/db.php';
// $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
// $stmt->execute([$_SESSION['user_id']]);
// $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
