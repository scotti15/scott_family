<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT BrandID, BrandName FROM brands ORDER BY BrandName ASC");
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($brands);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
