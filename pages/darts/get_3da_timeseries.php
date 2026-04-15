<?php
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION["user_id"] ?? 0;
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
        ) recent_sessions ON s.session_id = recent_sessions.session_id
    ";
    $params[':user_id_inner'] = $user_id;
}

/* -----------------------------
   QUERY
----------------------------- */
$stmt = $pdo->prepare("
SELECT 
    g.game_number,
    g.started_at,
    (SUM(dt.hit_score) / NULLIF(COUNT(dt.throw_id), 0)) * 3 AS three_dart_avg
FROM dart_games g
JOIN dart_turns t ON t.game_id = g.game_id
JOIN dart_throws dt ON dt.turn_id = t.turn_id
JOIN dart_sessions s ON g.play_session_id = s.session_id
$sessionJoin
WHERE s.user_id = :user_id
  AND dt.is_valid = 1
GROUP BY g.game_id
ORDER BY g.started_at ASC
");

/* -----------------------------
   EXECUTE
----------------------------- */
$stmt->execute($params);

/* -----------------------------
   BUILD RESPONSE
----------------------------- */
$labels = [];
$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = "G" . $row["game_number"];
    $data[] = round($row["three_dart_avg"] ?? 0, 1);
}

echo json_encode([
    "labels" => $labels,
    "data" => $data
]);