<?php
require_once '../../config/db.php'; // adjust path

header('Content-Type: application/json');

$userId = $_POST['userId'] ?? null;
$mealDate = $_POST['mealDate'] ?? null;
$mealType = $_POST['mealType'] ?? null;
$items = $_POST['items'] ?? [];

// Ensure $items is an array
if (!is_array($items)) {
    // If items is a string like "1,2,3" or just a single value
    $items = is_string($items) ? explode(',', $items) : [$items];
}


if (!$userId || !$mealDate || !$mealType) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ensure the meal exists
    $stmt = $pdo->prepare("SELECT meal_id FROM meals WHERE user_id=? AND meal_date=? AND meal_type=?");
    $stmt->execute([$userId, $mealDate, $mealType]);
    $meal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($meal) {
        $mealId = $meal['meal_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO meals (user_id, meal_date, meal_type) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $mealDate, $mealType]);
        $mealId = $pdo->lastInsertId();
    }

    // 2. Clear previous items for this meal
    $stmt = $pdo->prepare("DELETE FROM meal_items WHERE meal_id=?");
    $stmt->execute([$mealId]);

    // 3. Insert all new items
    $stmt = $pdo->prepare("INSERT INTO meal_items (meal_id, food_id) VALUES (?, ?)");
    foreach ($items as $foodId) {
        $stmt->execute([$mealId, $foodId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
