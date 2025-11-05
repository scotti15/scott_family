<?php
session_set_cookie_params([
    'path' => '/', // must match your login/site settings
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// Auth check
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Active session id for yahtzee (must have been set by your UI)
$sessionId = $_SESSION['yahtzee_session_id'] ?? null;
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'No active yahtzee session selected']);
    exit;
}

// Read JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['scores'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Prepare upsert for yahtzee_scores
    $stmt = $pdo->prepare("
        INSERT INTO yahtzee_scores
          (user_id, session_id, game_number, category, score, is_scratch, created_at, updated_at)
        VALUES
          (:user_id, :session_id, :game_number, :category, :score, :is_scratch, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          score = VALUES(score),
          is_scratch = VALUES(is_scratch),
          updated_at = NOW()
    ");

    // Iterate games
    foreach ($data['scores'] as $gameNumber => $categories) {
        $gameNum = intval($gameNumber);

        foreach ($categories as $category => $val) {
            // Normalize values
            $isScratch = ($val === 'X') ? 1 : 0;
            if ($val === '' || $val === null) {
                $score = null;
            } elseif ($val === 'X') {
                $score = null;
            } else {
                // numeric string -> int
                $score = is_numeric($val) ? intval($val) : null;
            }

            // Bind & execute
            $stmt->execute([
                ':user_id' => $userId,
                ':session_id' => $sessionId,
                ':game_number' => $gameNum,
                ':category' => $category,
                ':score' => $score,
                ':is_scratch' => $isScratch
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['status' => 'ok', 'session_id' => $sessionId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
