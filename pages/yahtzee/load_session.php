<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get session_id from GET
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session_id']);
    exit;
}

try {
    // Fetch all games and scores for this session
    $stmtGames = $pdo->prepare("
        SELECT id AS game_id, game_number
        FROM yahtzee_games
        WHERE user_id = :user_id AND session_id = :session_id
        ORDER BY game_number ASC
    ");
    $stmtGames->execute([
        ':user_id' => $userId,
        ':session_id' => $sessionId
    ]);
    $games = $stmtGames->fetchAll(PDO::FETCH_ASSOC);

    $scoresResult = [];

    foreach ($games as $game) {
        $gameId = $game['game_id'];

        $stmtScores = $pdo->prepare("
            SELECT category, score, is_scratch
            FROM yahtzee_scores
            WHERE game_id = :game_id
        ");
        $stmtScores->execute([':game_id' => $gameId]);
        $scores = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

        // Build associative array: category => value
        $scoresAssoc = [];
        foreach ($scores as $s) {
            $val = $s['is_scratch'] ? 'X' : $s['score'];
            $scoresAssoc[$s['category']] = $val;
        }

        $scoresResult[$game['game_number']] = $scoresAssoc;
    }

        // After fetching the session data successfully:
    $_SESSION['yahtzee_session_id'] = $sessionId;

    echo json_encode([
        'status' => 'ok',
        'session_id' => $sessionId,
        'scores' => $scoresResult  // <--- alias as 'scores' for your JS
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
