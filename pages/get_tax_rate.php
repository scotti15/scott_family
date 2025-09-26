<?php
require_once __DIR__ . '/../config/db.php';

$provinceId = $_GET['provinceId'] ?? null;
$response = ['success' => false, 'taxRate' => null];

if ($provinceId) {
    $stmt = $pdo->prepare("SELECT TaxRate FROM Provinces WHERE ProvinceID = :id");
    $stmt->execute([':id' => $provinceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $response = [
            'success' => true,
            'taxRate' => (float) $row['TaxRate']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
