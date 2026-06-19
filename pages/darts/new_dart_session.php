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
    $pdo->beginTransaction();

    date_default_timezone_set('America/Montreal');
    // 1️⃣ Create new session
    $sessionName = 'Session ' . date('Y-m-d H:i');
    $stmt = $pdo->prepare("
        INSERT INTO dart_sessions (user_id, name)
        VALUES (:user_id, :name)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':name' => $sessionName
    ]);

    $sessionId = $pdo->lastInsertId();

    // 2️⃣ Create Game #1 for this session
    $stmt = $pdo->prepare("
        INSERT INTO dart_games (
            play_session_id,
            player_1_id,
            game_number,
            game_type,
            starting_score,
            game_result,
            is_valid,
            target_profile_id
        ) VALUES (
            :session_id,
            :player_1_id,
            1,
            '501',
            501,
            'in_progress',
            1,
            1
        )
    ");
    $stmt->execute([
        ':session_id' => $sessionId,
        ':player_1_id' => $userId
    ]);

    $gameId = $pdo->lastInsertId();

    $pdo->commit();

    // 3️⃣ Store in PHP session
    $_SESSION['dart_session_id'] = $sessionId;
    $_SESSION['dart_game_id'] = $gameId;

    echo json_encode([
        'status' => 'ok',
        'session_id' => $sessionId,
        'game_id' => $gameId,
        'game_number' => 1,
        'name' => $sessionName
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
