<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session ID']);
    exit;
}

try {
    // Fetch all scores for this session and user
    $stmt = $pdo->prepare("
        SELECT g.game_number, s.category, s.score, s.is_scratch
        FROM yahtzee_games g
        JOIN yahtzee_scores s ON s.game_id = g.id
        WHERE g.user_id = :user_id AND g.session_id = :session_id
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':session_id' => $sessionId
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize by game_number -> category -> value
    $scores = [];
    foreach ($results as $row) {
        $game = $row['game_number'];
        $cat = $row['category'];
        $val = $row['is_scratch'] ? 'X' : $row['score'];
        if (!isset($scores[$game])) $scores[$game] = [];
        $scores[$game][$cat] = $val;
    }

    echo json_encode(['scores' => $scores]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
