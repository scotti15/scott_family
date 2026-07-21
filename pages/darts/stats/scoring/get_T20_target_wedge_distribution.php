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

/* -----------------------------
   QUERY
----------------------------- */

$sql = "
SELECT
    dt.hit_score AS wedge,
    COUNT(*) AS hits
FROM dart_throws dt
JOIN dart_turns t 
    ON dt.turn_id = t.turn_id
JOIN dart_games g 
    ON t.game_id = g.game_id
JOIN dart_sessions s 
    ON g.play_session_id = s.session_id
    $sessionJoin
WHERE s.user_id = :user_id
  AND dt.is_valid = 1
  AND g.finished_at IS NOT NULL
  AND dt.aimed_ring = 'T'
  AND dt.aimed_value = 20
GROUP BY dt.hit_score
ORDER BY FIELD(
    dt.hit_score,
    20, 1, 18, 4, 13,
    6, 10, 15, 2, 17,
    3, 19, 7, 16, 8,
    11, 14, 9, 12, 5
);
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------
   CALCULATE PERCENTAGES
----------------------------- */

$total = array_sum(array_column($results, 'hits'));

foreach ($results as &$row) {
    $row['pct'] = $total > 0
        ? round(($row['hits'] / $total) * 100, 2)
        : 0;
}

$response = [
    "rows" => $results,
    "totalAttempts" => $total
];

echo json_encode($response);