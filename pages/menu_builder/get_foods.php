<?php
require_once __DIR__ . '/../../config/db.php'; // adjust path to match your structure

try {
    // Fetch items marked as food
    $stmt = $pdo->prepare("SELECT ItemID, ItemName FROM items WHERE is_food = 1 ORDER BY ItemName");
    $stmt->execute();
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($foods);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
