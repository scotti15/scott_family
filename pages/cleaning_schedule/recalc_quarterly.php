<?php
require_once "../../config/db.php";

$today = new DateTime();
$year = (int)$today->format('Y');
$month = (int)$today->format('n');

// Determine the current quarter
$quarterStartMonth = floor(($month - 1) / 3) * 3 + 1;
$startOfQuarter = new DateTime("$year-$quarterStartMonth-01");

// Calculate the week of quarter for today
$daysIntoQuarter = $startOfQuarter->diff($today)->days;
$weekOfQuarter = (int)floor($daysIntoQuarter / 7) + 1;

// Function: return the Y-m-d of the nth weekday in a quarter
function getQuarterlyDueDate($year, $quarterStartMonth, $weekday, $nth) {
    $date = new DateTime("$year-$quarterStartMonth-01");
    $count = 0;

    // Move to first occurrence of the weekday
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

// Check if any quarterly task needs recalculation
$check = $pdo->query("
    SELECT COUNT(*) 
    FROM locations
    WHERE frequency_id = 4
      AND cleanable = 1
      AND active = 1
      AND (due_date IS NULL OR due_date < '$year-$quarterStartMonth-01')
");

$needsUpdate = $check->fetchColumn();

if ($needsUpdate > 0) {
    $stmt = $pdo->query("
        SELECT location_id, schedule_weekday, schedule_nth
        FROM locations
        WHERE frequency_id = 4
          AND cleanable = 1
          AND active = 1
    ");

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare("UPDATE locations SET due_date = ? WHERE location_id = ?");

    foreach ($tasks as $task) {
        $dueDate = getQuarterlyDueDate($year, $quarterStartMonth, $task['schedule_weekday'], $task['schedule_nth']);
        $update->execute([$dueDate, $task['location_id']]);
    }
}

echo json_encode(["status" => "ok"]);