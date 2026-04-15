<?php
require_once "../../config/db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION["user_id"] ?? 0;
if (!$user_id) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$frequency = $_GET['frequency'] ?? 'daily';

$map = [
    'daily' => 1,
    'weekly' => 2,
    'monthly' => 3,
    'quarterly' => 4
];

$frequency_id = $map[$frequency] ?? 1;

/* ✅ UPDATED QUERY */
$sql = "
SELECT
    l.location_id AS task_id,
    CONCAT(
        COALESCE(p.name, l.name),
        CASE WHEN p.name IS NULL THEN '' ELSE ' → ' END,
        CASE WHEN p.name IS NULL THEN '' ELSE l.name END
    ) AS location_path,

    l.schedule_weekday,
    l.schedule_nth,
    l.due_date

FROM locations l
LEFT JOIN locations p ON l.parent_id = p.location_id
WHERE l.frequency_id = ?
  AND l.active = 1
ORDER BY COALESCE(p.name, l.name), l.name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$frequency_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Get all completed entries */
$logStmt = $pdo->query("
  SELECT task_id, cleaned_date
  FROM cleaning_log
");
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

/* Build lookup */
$completed = [];
foreach ($logs as $row) {
    $completed[$row['task_id']][] = $row['cleaned_date'];
}

/* Attach completed dates */
foreach ($tasks as &$task) {
    $task['completed_dates'] = $completed[$task['task_id']] ?? [];
}

foreach ($tasks as &$task) {
    $task['completed_dates'] = $completed[$task['task_id']] ?? [];
    $task['DEBUG'] = 'CORRECT FILE';
}

echo json_encode($tasks);
exit;