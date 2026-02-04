<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

// ✅ READ JSON PAYLOAD
$data = json_decode(file_get_contents("php://input"), true);
$sessionId = $data['session_id'] ?? null;

if (!$userId || !$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

// Get next game number for this session
$stmt = $pdo->prepare("
    SELECT COALESCE(MAX(game_number), 0) + 1
    FROM dart_games
    WHERE play_session_id = ?
");
$stmt->execute([$sessionId]);
$nextGameNumber = (int)$stmt->fetchColumn();

// Create new game
$stmt = $pdo->prepare("
    INSERT INTO dart_games (
        play_session_id,
        game_number,
        started_at
    ) VALUES (?, ?, NOW())
");
$stmt->execute([
    $sessionId,
    $nextGameNumber
]);

echo json_encode([
    'status'      => 'ok',
    'game_id'     => $pdo->lastInsertId(),
    'game_number' => $nextGameNumber
]);
