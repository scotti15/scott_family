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

$stmt = $pdo->prepare("
    SELECT
        ROUND(
            (SUM(hit_target) / COUNT(*)) * 100,
            2
        ) AS pure_double_pct
    FROM dart_throws dt
    JOIN dart_turns t ON dt.turn_id = t.turn_id
    JOIN dart_games g ON t.game_id = g.game_id
    JOIN dart_sessions s ON g.play_session_id = s.session_id
    WHERE
        s.user_id = :user_id
        AND dt.aimed_ring = 'D'
        AND dt.is_valid = 1
        AND dt.is_implied = 0
");

$stmt->execute([
    ':user_id' => $user_id
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);