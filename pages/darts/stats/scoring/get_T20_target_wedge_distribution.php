<?php
require_once "../../../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$sessionFilter = $_GET['session'] ?? 'all';

if (!$user_id) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

/* -----------------------------
   SESSION FILTER
----------------------------- */

$limit = null;

if ($sessionFilter === "last1") $limit = 1;
if ($sessionFilter === "last3") $limit = 3;
if ($sessionFilter === "last5") $limit = 5;

$sessionJoin = "";
$params = [':user_id' => $user_id];

if ($limit !== null) {
    $sessionJoin = "
        JOIN (
            SELECT session_id
            FROM dart_sessions
            WHERE user_id = :user_id_inner
            ORDER BY created_at DESC
            LIMIT $limit
        ) recent_sessions
        ON s.session_id = recent_sessions.session_id
    ";

    $params[':user_id_inner'] = $user_id;
}

/* -----------------------------
   QUERY
----------------------------- */

$sql = "
SELECT
    dt.hit_score AS wedge,
    COUNT(*) AS hits,
    ROUND(COUNT(*) * 100.0 / totals.total_attempts, 2) AS pct
FROM dart_throws dt
JOIN dart_turns t 
    ON dt.turn_id = t.turn_id
JOIN dart_games g 
    ON t.game_id = g.game_id
JOIN dart_sessions s 
    ON g.play_session_id = s.session_id
JOIN (
    SELECT 
        COUNT(*) AS total_attempts
    FROM dart_throws dt2
    JOIN dart_turns t2 
        ON dt2.turn_id = t2.turn_id
    JOIN dart_games g2 
        ON t2.game_id = g2.game_id
    JOIN dart_sessions s2 
        ON g2.play_session_id = s2.session_id
    WHERE s2.user_id = :user_id
      AND dt2.is_valid = 1
      AND g2.finished_at IS NOT NULL
      AND dt2.aimed_ring = 'T'
      AND dt2.aimed_value = 20
) AS totals
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