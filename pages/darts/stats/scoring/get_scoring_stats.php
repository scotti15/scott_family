<?php
header('Content-Type: application/json');
require_once "../../../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

require_once "../../../../includes/session_filter.php";


$sql = "
SELECT
    COUNT(*) AS attempts,
    SUM(
        CASE
            WHEN dt.hit_score = 20 THEN 1
            ELSE 0
        END
    ) AS hits
FROM dart_throws dt
JOIN dart_turns t ON dt.turn_id = t.turn_id
JOIN dart_games g ON t.game_id = g.game_id
JOIN dart_sessions s ON g.play_session_id = s.session_id
$sessionJoin
WHERE s.user_id = :user_id
  AND dt.aimed_ring = 'T'
  AND dt.aimed_value = 20
  AND dt.is_valid = 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

$totalT20 = (int)$result['attempts'];
$t20Hits  = (int)$result['hits'];

$s20_when_t20_pct = null;
$hasData = false;

if ($totalT20 > 0) {
    $s20_when_t20_pct = ($t20Hits / $totalT20) * 100;
    $s20_when_t20_pct = round($s20_when_t20_pct, 2); // 1 decimal place
    $hasData = true;
}

echo json_encode([
    "s20_when_t20_pct" => $s20_when_t20_pct,
    "s20_when_t20_has_data" => $hasData
]);