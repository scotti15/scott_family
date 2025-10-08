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
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['scores'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

try {
    // Determine current session
    $stmt = $pdo->prepare("
        SELECT MAX(session_id) AS last_session
        FROM yahtzee_games
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $lastSession = (int)$stmt->fetchColumn();
    
    // Check if last session is complete
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM yahtzee_games 
        WHERE user_id = :user_id AND session_id = :session_id
    ");
    $stmt->execute([':user_id' => $userId, ':session_id' => $lastSession]);
    $countGames = (int)$stmt->fetchColumn();
    
    if ($countGames >= 6 || $lastSession === 0) {
        $sessionId = $lastSession + 1; // start new session
    } else {
        $sessionId = $lastSession;
    }

    // Loop through games and upsert
    foreach ($data['scores'] as $gameNumber => $categories) {
        // Upsert game
        $stmtGame = $pdo->prepare("
            INSERT INTO yahtzee_games (user_id, session_id, game_number)
            VALUES (:user_id, :session_id, :game_number)
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stmtGame->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId,
            ':game_number' => $gameNumber
        ]);

        $gameId = $pdo->lastInsertId();
        if (!$gameId) {
            // get existing game_id
            $stmtGet = $pdo->prepare("
                SELECT id FROM yahtzee_games
                WHERE user_id = :user_id AND session_id = :session_id AND game_number = :game_number
            ");
            $stmtGet->execute([
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':game_number' => $gameNumber
            ]);
            $gameId = $stmtGet->fetchColumn();
        }

        // Save scores for this game
        foreach ($categories as $cat => $val) {
            $isScratch = ($val === 'X') ? 1 : 0;
            $numeric = ($val === 'X' || $val === '') ? null : intval($val);

            $stmtScore = $pdo->prepare("
                INSERT INTO yahtzee_scores (game_id, category, score, is_scratch)
                VALUES (:game_id, :category, :score, :is_scratch)
                ON DUPLICATE KEY UPDATE score = :score, is_scratch = :is_scratch, updated_at = NOW()
            ");
            $stmtScore->execute([
                ':game_id' => $gameId,
                ':category' => $cat,
                ':score' => $numeric,
                ':is_scratch' => $isScratch
            ]);
        }
    }

    echo json_encode(['status' => 'ok', 'session_id' => $sessionId]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
