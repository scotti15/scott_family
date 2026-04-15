<?php
require_once "../../config/db.php";

$today = new DateTime();
$year = (int)$today->format('Y');
$month = (int)$today->format('n');

// Function: return the Y-m-d of the nth weekday in a month
function getMonthlyDueDate($year, $month, $weekday, $nth) {
    // weekday: 1=Monday ... 7=Sunday
    $date = new DateTime("$year-$month-01");
    $count = 0;

    // Move to the first occurrence of the weekday
    while ((int)$date->format('N') != $weekday) {
        $date->modify('+1 day');
    }
    $count++;

    // Move to nth occurrence
    while ($count < $nth) {
        $date->modify('+7 days');
        $count++;
    }

    return $date->format('Y-m-d');
}

// Check if any monthly task needs recalculation
$check = $pdo->query("
    SELECT COUNT(*) 
    FROM locations
    WHERE frequency_id = 3
      AND cleanable = 1
      AND active = 1
      AND (due_date IS NULL OR MONTH(due_date) != $month OR YEAR(due_date) != $year)
");

$needsUpdate = $check->fetchColumn();

if ($needsUpdate > 0) {

    $stmt = $pdo->query("
        SELECT location_id, schedule_weekday, schedule_nth
        FROM locations
        WHERE frequency_id = 3
          AND cleanable = 1
          AND active = 1
    ");

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare("
        UPDATE locations 
        SET due_date = ?
        WHERE location_id = ?
    ");

    foreach ($tasks as $task) {
        $dueDate = getMonthlyDueDate($year, $month, $task['schedule_weekday'], $task['schedule_nth']);
        $update->execute([$dueDate, $task['location_id']]);
    }
}

echo json_encode(["status" => "ok"]);