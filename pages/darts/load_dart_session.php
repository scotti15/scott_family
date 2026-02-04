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

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
if (!$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session_id']);
    exit;
}

try {

    /* -----------------------------
       1️⃣ Load session
    ----------------------------- */
    $stmt = $pdo->prepare("
        SELECT session_id, name, created_at
        FROM dart_sessions
        WHERE session_id = :sid AND user_id = :uid
    ");
    $stmt->execute([
        ':sid' => $sessionId,
        ':uid' => $userId
    ]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        throw new Exception("Session not found");
    }

    /* -----------------------------
       2️⃣ Load games
    ----------------------------- */
    $gameId = isset($_GET['game_id']) ? intval($_GET['game_id']) : null;

    $stmt = $pdo->prepare("
        SELECT game_id, game_number, started_at
        FROM dart_games
        WHERE play_session_id = :sid
        ORDER BY game_number ASC
    ");
    $stmt->execute([':sid' => $sessionId]);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* -----------------------------
   3️⃣ Select game
----------------------------- */
$game = null;

if ($gameId) {
    foreach ($games as $g) {
        if ((int)$g['game_id'] === $gameId) {
            $game = $g;
            break;
        }
    }
} else {
    $game = $games ? end($games) : null; // default = latest
}


    /* -----------------------------
       4️⃣ Load turns + darts
    ----------------------------- */
    $turns = [];

    if ($game) {
        $stmt = $pdo->prepare("
        SELECT
        t.turn_id,
        t.turn_number,
        t.start_score,
        t.end_score,
        t.turn_result,
        d.throw_number,
        d.hit_score,
        d.ring,
        d.segment,
        d.hit_target,
        d.x,
        d.y,
        d.throw_type
            FROM dart_turns t
            LEFT JOIN dart_throws d
                ON d.turn_id = t.turn_id
            WHERE t.game_id = :gid
            ORDER BY t.turn_number ASC, d.throw_number ASC
        ");
        $stmt->execute([':gid' => $game['game_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $tid = $row['turn_id'];

            if (!isset($turns[$tid])) {
                $turns[$tid] = [
                    'turn_id'     => $tid,
                    'turn_number' => (int)$row['turn_number'],
                    'start_score' => (int)$row['start_score'],
                    'end_score'   => (int)$row['end_score'],
                    'turn_result' => $row['turn_result'],
                    'darts'       => []
                ];
            }

            if ($row['throw_number']) {
                $turns[$tid]['darts'][] = [
                    'throw_number' => (int)$row['throw_number'],
                    'score'        => (int)$row['hit_score'],
                    'ring'         => $row['ring'],
                    'segment'      => $row['segment'],
                    'hit_target'   => (bool)$row['hit_target'],
                    'x'            => $row['x'] !== null ? (float)$row['x'] : null,
                    'y'            => $row['y'] !== null ? (float)$row['y'] : null,
                    'throw_type'   => $row['throw_type'],
                ];
                
            }
        }

        $turns = array_values($turns);
    }

    /* -----------------------------
       5️⃣ Output
    ----------------------------- */
    echo json_encode([
        'status'  => 'ok',
        'session' => $session,
        'games'   => $games,
        'game'    => $game,
        'turns'   => $turns
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
