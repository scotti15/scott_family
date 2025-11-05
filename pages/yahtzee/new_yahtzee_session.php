<?php
session_start();
require_once '../../config/db.php'; // adjust path if needed

header('Content-Type: application/json');

// Make sure user is logged in
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Determine new session number (increment based on existing sessions)
$stmt = $pdo->prepare("SELECT MAX(id) AS max_session FROM yahtzee_sessions WHERE user_id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$newSessionNumber = ($row && $row['max_session']) ? (int)$row['max_session'] + 1 : 1;

// Optional: you can include a friendly name (like a timestamp)
$sessionName = 'Session ' . $newSessionNumber . ' (' . date('Y-m-d H:i') . ')';

// Insert new session row
$stmt = $pdo->prepare("
    INSERT INTO yahtzee_sessions (user_id, name, created_at, updated_at)
    VALUES (:user_id, :name, NOW(), NOW())
");
$stmt->execute([
    ':user_id' => $userId,
    ':name' => $sessionName
]);

// Get new session id
$newSessionId = $pdo->lastInsertId();

// Save as the active session in PHP session
$_SESSION['yahtzee_session_id'] = $newSessionId;

echo json_encode([
    'status' => 'ok',
    'session_id' => $newSessionId,
    'name' => $sessionName
]);
