<?php
session_start();
require_once '../../config/db.php'; // adjust path if needed

// Make sure user is logged in
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Find the user's current highest session_id
$stmt = $pdo->prepare("SELECT MAX(session_id) AS max_session FROM yahtzee_games WHERE user_id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$lastSession = $row['max_session'] ? (int)$row['max_session'] : 0;

// Increment for new session
$newSession = $lastSession + 1;

// Save this as the active session for this user
$_SESSION['yahtzee_session_id'] = $newSession;

// Return to the frontend
echo json_encode([
    'status' => 'ok',
    'session_id' => $newSession
]);
