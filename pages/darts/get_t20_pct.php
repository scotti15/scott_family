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
$rangeWhere = "";
$sessionJoin = "";

$params = [
    ':user_id' => $user_id
];

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
   SESSION RANGE
----------------------------- */
if ($sessionFilter === "range") {

    $from = (int)($_GET['from'] ?? 0);
    $to   = (int)($_GET['to'] ?? 0);

    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    $rangeWhere = "
        AND s.session_id BETWEEN :from_session AND :to_session
    ";

    $params[':from_session'] = $from;
    $params[':to_session'] = $to;
}

/* -----------------------------
   QUERY
----------------------------- */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS attempts,
        SUM(CASE WHEN dt.hit_target = 1 THEN 1 ELSE 0 END) AS successes,
        (SUM(CASE WHEN dt.hit_target = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100 AS t20_pct
    FROM dart_throws dt
    JOIN dart_turns t ON dt.turn_id = t.turn_id
    JOIN dart_games g ON t.game_id = g.game_id
    JOIN dart_sessions s ON g.play_session_id = s.session_id
    $sessionJoin
    WHERE s.user_id = :user_id
      AND dt.aimed_ring = 'T'
      AND dt.aimed_value = 20
      AND dt.is_valid = 1
      $rangeWhere
");

/* -----------------------------
   EXECUTE
----------------------------- */
$stmt->execute($params);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

/* -----------------------------
   OUTPUT
----------------------------- */
if (!$data || $data["attempts"] == 0) {
    echo json_encode([
        "t20_pct" => 0,
        "attempts" => 0,
        "successes" => 0
    ]);
    exit();
}

echo json_encode([
    "t20_pct" => round($data["t20_pct"], 1),
    "attempts" => (int)$data["attempts"],
    "successes" => (int)$data["successes"]
]);