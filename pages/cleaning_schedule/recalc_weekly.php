<?php
require_once "../../config/db.php";

function getThisWeekDate($weekday) {
    $today = new DateTime();
    $todayWeekday = (int)$today->format('N'); // 1–7

    $diff = $weekday - $todayWeekday;

    if ($diff > 0) {
        $diff -= 7;
    }

    $target = clone $today;
    $target->modify("$diff days");

    return $target->format('Y-m-d');
}

// Check if recalculation is needed
$check = $pdo->query("
    SELECT COUNT(*) 
    FROM locations
    WHERE frequency_id = 2
      AND cleanable = 1
      AND active = 1
      AND (due_date IS NULL OR due_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY))
");

$needsUpdate = $check->fetchColumn();

if ($needsUpdate > 0) {

    $stmt = $pdo->prepare("
        SELECT location_id, schedule_weekday 
        FROM locations
        WHERE frequency_id = 2
          AND cleanable = 1
          AND active = 1
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare("
        UPDATE locations 
        SET due_date = ?
        WHERE location_id = ?
    ");

    foreach ($tasks as $task) {
        $dueDate = getThisWeekDate($task['schedule_weekday']);
        $update->execute([$dueDate, $task['location_id']]);
    }
}

echo json_encode(["status" => "ok"]);