<?php
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION["user_id"] ?? 0;
$metric = $_GET['metric'] ?? '3da';
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
   METRIC-BASED SQL
----------------------------- */
$metricSql = "";
$havingSql = "";

$scoreExpr = "(dt.hit_score * dt.segment)";

switch ($metric) {

    case 'scoring3da':
        $metricSql = "(SUM($scoreExpr) / NULLIF(COUNT(dt.throw_id),0)) * 3";
        $havingSql = "AND t.start_score > 160";
        break;

    case 't20':
        $metricSql = "
            (SUM(CASE WHEN dt.hit_target = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)) * 100
        ";
        $havingSql = "
            AND dt.aimed_ring = 'T'
            AND dt.aimed_value = 20
            AND dt.is_valid = 1
        ";
        break;

        case 'wedge20_t20':
            $metricSql = "
                (SUM(CASE WHEN dt.hit_score = 20 THEN 1 ELSE 0 END) 
                / NULLIF(COUNT(*),0)) * 100
            ";
        
            $havingSql = "
                AND dt.aimed_ring = 'T'
                AND dt.aimed_value = 20
                AND dt.is_valid = 1
            ";
            break;
        
    case 'doubleAttempts':
        $metricSql = "COUNT(*)";
        $havingSql = "
            AND dt.aimed_ring = 'D'
            AND dt.is_valid = 1
        ";
        break;

    case 'dpl':
        $metricSql = "COUNT(dt.throw_id)";
        break;

    case '3da':
    default:
        $metricSql = "(SUM($scoreExpr) / NULLIF(COUNT(dt.throw_id),0)) * 3";
        break;
}

/* -----------------------------
   QUERY
----------------------------- */
$stmt = $pdo->prepare("
SELECT 
    g.game_number,
    g.started_at,
    $metricSql AS metric_value
FROM dart_games g
JOIN dart_turns t ON t.game_id = g.game_id
JOIN dart_throws dt ON dt.turn_id = t.turn_id
JOIN dart_sessions s ON g.play_session_id = s.session_id
$sessionJoin
WHERE s.user_id = :user_id
  AND dt.is_valid = 1
  AND g.finished_at IS NOT NULL
  $havingSql
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
    error_log("RAW metric_value: " . $row["metric_value"]);
    error_log($stmt->queryString);
    $labels[] = "G" . $row["game_number"];
    $data[] = ($metric === 'double')
    ? (int)$row["metric_value"]
    : round($row["metric_value"] ?? 0, 2);
}

echo json_encode([
    "labels" => $labels,
    "data" => $data
    
]);