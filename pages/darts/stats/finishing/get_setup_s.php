<?php
require_once "../../../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

require_once "../../../../includes/session_filter.php";

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS attempts,
        SUM(
            CASE
                WHEN dt.ring = 'S'
                 AND dt.segment = dt.aimed_value
                THEN 1 ELSE 0
            END
        ) AS hits,
        ROUND(
            (SUM(
                CASE
                    WHEN dt.ring = 'S'
                     AND dt.segment = dt.aimed_value
                    THEN 1 ELSE 0
                END
            ) / COUNT(*)) * 100
        , 2) AS setup_s_pct
    FROM dart_throws dt
    JOIN dart_turns t ON dt.turn_id = t.turn_id
    JOIN dart_games g ON t.game_id = g.game_id
    JOIN dart_sessions s ON g.play_session_id = s.session_id
    $sessionJoin
    WHERE
        s.user_id = :user_id
        AND dt.is_valid = 1
        AND dt.aimed_ring = 'S'
");

$stmt->execute($params);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);