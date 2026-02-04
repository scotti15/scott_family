<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;
if (!$gameId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing game_id']);
    exit;
}

try {
    /* ---------------------------------
       1️⃣ Validate game belongs to user
    --------------------------------- */
    $stmt = $pdo->prepare("
        SELECT g.game_id
        FROM dart_games g
        JOIN dart_sessions s ON g.play_session_id = s.session_id
        WHERE g.game_id = :gid
          AND s.user_id = :uid
    ");
    $stmt->execute([
        ':gid' => $gameId,
        ':uid' => $userId
    ]);

    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized game access']);
        exit;
    }

    /* ---------------------------------
       2️⃣ Load turns
    --------------------------------- */
    $stmt = $pdo->prepare("
        SELECT
            t.turn_id,
            t.turn_number,
            t.end_score
        FROM dart_turns t
        WHERE t.game_id = :gid
          AND t.is_valid = 1
        ORDER BY t.turn_number ASC
    ");
    $stmt->execute([':gid' => $gameId]);
    $turnRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$turnRows) {
        echo json_encode([
            'status' => 'ok',
            'game_id' => $gameId,
            'turns' => []
        ]);
        exit;
    }

    /* ---------------------------------
       3️⃣ Load all dart throws for game
    --------------------------------- */
    $stmt = $pdo->prepare("
        SELECT
            d.turn_id,
            d.throw_number,
            d.hit_score,
            d.ring,
            d.segment,
            d.aimed_ring,
            d.aimed_value,
            d.hit_target,
            d.x,
            d.y
        FROM dart_throws d
        JOIN dart_turns t ON d.turn_id = t.turn_id
        WHERE t.game_id = :gid
          AND d.is_valid = 1
        ORDER BY t.turn_number ASC, d.throw_number ASC
    ");
    $stmt->execute([':gid' => $gameId]);
    $dartRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------------------------------
       4️⃣ Assemble turns → darts
    --------------------------------- */
    $turns = [];
    foreach ($turnRows as $t) {
        $turns[$t['turn_id']] = [
            'turn_id'     => (int)$t['turn_id'],
            'turn_number' => (int)$t['turn_number'],
            'end_score'   => (int)$t['end_score'],
            'darts'       => []
        ];
    }

    foreach ($dartRows as $d) {
        $turnId = $d['turn_id'];
        if (!isset($turns[$turnId])) continue;

        $turns[$turnId]['darts'][] = [
            'throw_number' => (int)$d['throw_number'],
            'hit_score'    => (int)$d['hit_score'],
            'ring'         => $d['ring'],
            'segment'      => $d['segment'],
            'aimed_ring'   => $d['aimed_ring'],
            'aimed_value'  => $d['aimed_value'] !== null ? (int)$d['aimed_value'] : null,
            'hit_target'   => (int)$d['hit_target'],
            'x'            => $d['x'],
            'y'            => $d['y']
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'game_id' => $gameId,
        'turns' => array_values($turns)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
