<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT UnitID, UnitName, UnitType, ConversionToBase FROM units ORDER BY UnitName ASC");
    $stmt->execute();
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($units);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
