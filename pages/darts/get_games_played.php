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
   RECENT SESSIONS
----------------------------- */
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
    SELECT COUNT(DISTINCT g.game_id) AS games_played
    FROM dart_games g
    JOIN dart_sessions s ON g.play_session_id = s.session_id
    $sessionJoin
    WHERE s.user_id = :user_id
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
echo json_encode([
    "games_played" => (int)($data["games_played"] ?? 0)
]);