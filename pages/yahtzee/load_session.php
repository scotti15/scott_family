<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// --- Check login ---
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// --- Get session_id from GET ---
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session_id']);
    exit;
}

try {
    // --- Fetch all scores for this user + session ---
    $stmt = $pdo->prepare("
        SELECT game_number, category, score, is_scratch
        FROM yahtzee_scores
        WHERE user_id = :user_id AND session_id = :session_id
        ORDER BY game_number ASC, FIELD(category,
            'ones','twos','threes','fours','fives','sixes',
            'three_kind','four_kind','full_house','small_straight',
            'large_straight','yahtzee','chance'
        )
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':session_id' => $sessionId
    ]);

    $scoresResult = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gameNumber = $row['game_number'];
        $category   = $row['category'];
        $value      = $row['is_scratch'] ? 'X' : $row['score'];

        if (!isset($scoresResult[$gameNumber])) {
            $scoresResult[$gameNumber] = [];
        }

        $scoresResult[$gameNumber][$category] = $value;
    }

    // --- Remember current session for subsequent saves ---
    $_SESSION['yahtzee_session_id'] = $sessionId;

    echo json_encode([
        'status' => 'ok',
        'session_id' => $sessionId,
        'scores' => $scoresResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
