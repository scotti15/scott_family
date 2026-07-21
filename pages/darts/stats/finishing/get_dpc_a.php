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

$sql = "
SELECT
    ROUND(AVG(game_darts), 2) AS dpc_a
FROM (
    SELECT
        g.game_id,
        COUNT(dt.throw_id) AS game_darts
    FROM dart_games g

    JOIN dart_sessions s
        ON g.play_session_id = s.session_id

    -- First double attempt in each game
    JOIN (
        SELECT
            t.game_id,
            MIN(dt.throw_id) AS first_double_throw_id
        FROM dart_throws dt
        JOIN dart_turns t
            ON dt.turn_id = t.turn_id
        WHERE dt.aimed_ring = 'D'
        GROUP BY t.game_id
    ) fd
        ON g.game_id = fd.game_id

    -- Successful checkout throw
    JOIN (
        SELECT
            t.game_id,
            MAX(dt.throw_id) AS checkout_throw_id
        FROM dart_throws dt
        JOIN dart_turns t
            ON dt.turn_id = t.turn_id
        WHERE dt.hit_target = 1
          AND dt.aimed_ring = 'D'
        GROUP BY t.game_id
    ) co
        ON g.game_id = co.game_id

    -- All throws between first double attempt and checkout
    JOIN dart_turns t
        ON t.game_id = g.game_id

    JOIN dart_throws dt
        ON dt.turn_id = t.turn_id
        $sessionJoin

    WHERE
        s.user_id = :user_id
        AND dt.throw_id BETWEEN fd.first_double_throw_id
                            AND co.checkout_throw_id
        AND dt.is_valid = 1

    GROUP BY g.game_id
) x
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);