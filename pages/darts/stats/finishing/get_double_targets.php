<?php
require_once "../../../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$sessionFilter = $_GET['filter'] ?? 'all';

if (!$user_id) {
  echo json_encode(["error" => "Not logged in"]);
  exit();
}


require_once "../../../../includes/session_filter.php";

$sql = "
SELECT 
    dt.aimed_value AS target,
    COUNT(*) AS attempts,
    SUM(dt.hit_target) AS hits,
    ROUND((SUM(dt.hit_target) / COUNT(*)) * 100, 2) AS pct
FROM dart_throws dt
JOIN dart_turns t ON dt.turn_id = t.turn_id
JOIN dart_games g ON t.game_id = g.game_id
JOIN dart_sessions s ON g.play_session_id = s.session_id
$sessionJoin
WHERE dt.aimed_ring = 'D'
  AND dt.is_valid = 1
  AND s.user_id = :user_id
GROUP BY dt.aimed_value
ORDER BY dt.aimed_value
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>