<?php
require_once "../../config/db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
        AVG(game_darts) AS darts_per_leg
    FROM (
        SELECT 
            g.game_id,
            COUNT(dt.throw_id) AS game_darts
        FROM dart_games g
        JOIN dart_turns t ON t.game_id = g.game_id
        JOIN dart_throws dt ON dt.turn_id = t.turn_id
        JOIN dart_sessions s ON g.play_session_id = s.session_id
        $sessionJoin
        WHERE s.user_id = :user_id
          AND g.finished_at IS NOT NULL
          AND dt.is_valid = 1
        GROUP BY g.game_id
    ) AS per_game
");

$stmt->execute($params);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

/* -----------------------------
   OUTPUT
----------------------------- */
if (!$data || $data["darts_per_leg"] === null) {
    echo json_encode([
        "darts_per_leg" => 0,
        "message" => "No finished games"
    ]);
    exit();
}

echo json_encode([
    "darts_per_leg" => round($data["darts_per_leg"], 1)
]);