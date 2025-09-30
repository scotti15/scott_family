<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// clean any accidental output
if (ob_get_level()) ob_clean();

$userId    = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
$weekStart = $_GET['weekStart'] ?? '';

if (!$userId || !$weekStart) {
    echo json_encode([]);
    exit;
}

// two-week range
$startDate = new DateTime($weekStart);
$endDate   = (clone $startDate)->modify('+13 days');

try {
    // Get meals for the user in the date range
    $stmt = $pdo->prepare("
        SELECT m.meal_date, m.meal_type, i.ItemID, i.ItemName
        FROM meals m
        JOIN meal_items mi ON m.meal_id = mi.meal_id
        JOIN items i ON mi.food_id = i.ItemID
        WHERE m.user_id = ?
          AND m.meal_date BETWEEN ? AND ?
        ORDER BY m.meal_date, m.meal_type
    ");
    $stmt->execute([$userId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

    $meals = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['meal_date'];
        $mealType = strtolower($row['meal_type']);

        if (!isset($meals[$date])) $meals[$date] = [];
        if (!isset($meals[$date][$mealType])) $meals[$date][$mealType] = [];

        $meals[$date][$mealType][] = [
            'id' => $row['ItemID'],
            'name' => $row['ItemName']
        ];
    }

    echo json_encode($meals);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// no closing PHP tag
