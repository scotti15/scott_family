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

$sql = "
SELECT
    ROUND(AVG(game_darts), 2) AS dpc_b
FROM (
    SELECT
        g.game_id,
        COUNT(dt.throw_id) AS game_darts
    FROM dart_games g
    JOIN dart_sessions s
        ON g.play_session_id = s.session_id

    /* First turn where checkout is possible */
    JOIN (
        SELECT
            game_id,
            MIN(turn_number) AS first_checkout_turn
        FROM dart_turns
        WHERE start_score <= 170
        GROUP BY game_id
    ) fc
        ON g.game_id = fc.game_id

    /* Successful checkout throw */
    JOIN (
        SELECT
            t.game_id,
            MAX(dt.throw_id) AS checkout_throw_id
        FROM dart_throws dt
        JOIN dart_turns t
            ON dt.turn_id = t.turn_id
        WHERE dt.aimed_ring = 'D'
          AND dt.hit_target = 1
          AND dt.is_valid = 1
        GROUP BY t.game_id
    ) co
        ON g.game_id = co.game_id

    JOIN dart_turns t
        ON t.game_id = g.game_id
    JOIN dart_throws dt
        ON dt.turn_id = t.turn_id

    WHERE s.user_id = :user_id
      AND g.finished_at IS NOT NULL
      AND dt.is_valid = 1
      AND t.turn_number >= fc.first_checkout_turn
      AND dt.throw_id <= co.checkout_throw_id

    GROUP BY g.game_id
) x
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user_id' => $user_id
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);
?>