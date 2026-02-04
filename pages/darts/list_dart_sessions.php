<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT session_id, name, created_at, updated_at
        FROM dart_sessions
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sessions' => $sessions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
