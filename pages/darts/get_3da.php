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
   SESSION FILTER SETUP
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
    SUM(dt.hit_score * dt.segment) AS total_score,
    COUNT(*) AS total_darts,
    (SUM(dt.hit_score * dt.segment) / COUNT(*)) * 3 AS three_dart_avg
FROM dart_throws dt
JOIN dart_turns t ON dt.turn_id = t.turn_id
JOIN dart_games g ON t.game_id = g.game_id
JOIN dart_sessions s ON g.play_session_id = s.session_id
$sessionJoin
WHERE s.user_id = :user_id
  AND dt.is_valid = 1;
");

$stmt->execute($params);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

/* -----------------------------
   OUTPUT
----------------------------- */
echo json_encode([
    "three_dart_avg" => round($data["three_dart_avg"] ?? 0, 2),
    "total_darts" => (int)($data["total_darts"] ?? 0),
    "total_score" => (int)($data["total_score"] ?? 0)
]);