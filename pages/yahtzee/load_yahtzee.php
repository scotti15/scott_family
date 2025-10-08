<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Optionally, allow the frontend to specify a session_id to load
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;

try {
    if ($sessionId) {
        // Load a specific session
        $stmtGames = $pdo->prepare("
            SELECT id, game_number 
            FROM yahtzee_games 
            WHERE user_id = ? AND session_id = ? 
            ORDER BY game_number ASC
        ");
        $stmtGames->execute([$userId, $sessionId]);
    } else {
        // Load the most recent session
        $stmtGames = $pdo->prepare("
            SELECT id, game_number 
            FROM yahtzee_games 
            WHERE user_id = ? 
            ORDER BY session_id DESC, game_number ASC 
            LIMIT 6
        ");
        $stmtGames->execute([$userId]);
    }

    $games = $stmtGames->fetchAll(PDO::FETCH_ASSOC);
    if (!$games) {
        echo json_encode(['scores' => []]);
        exit;
    }

    $response = ['scores' => [], 'session_id' => $sessionId];

    $stmtScores = $pdo->prepare("
        SELECT category, score, is_scratch 
        FROM yahtzee_scores 
        WHERE game_id = ?
    ");

    foreach ($games as $game) {
        $stmtScores->execute([$game['id']]);
        $scores = [];
        while ($row = $stmtScores->fetch(PDO::FETCH_ASSOC)) {
            if ($row['is_scratch']) $scores[$row['category']] = 'X';
            else $scores[$row['category']] = $row['score'];
        }
        $response['scores'][$game['game_number']] = $scores;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
