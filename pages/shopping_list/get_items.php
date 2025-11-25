<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT ItemID, ItemName FROM items ORDER BY ItemName ASC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
