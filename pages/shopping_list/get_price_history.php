<?php
require_once __DIR__ . '/../../config/db.php';

$itemID = (int)($_GET['item_id'] ?? 0);
$response = [];

if ($itemID) {
    $stmt = $pdo->prepare("
        SELECT s.ListID, s.Price, s.Amount, u.UnitName, u.UnitType, u.ConversionToBase, 
               b.BrandName, p.PlaceName, s.ExpiryDate
        FROM shopping_list s
        LEFT JOIN brands b ON s.BrandID = b.BrandID
        LEFT JOIN places p ON s.PlaceID = p.PlaceID
        LEFT JOIN units u ON s.UnitID = u.UnitID
        WHERE s.ItemID = ?
        ORDER BY (s.Price * 100 / (s.Amount * u.ConversionToBase)) ASC
    ");
    $stmt->execute([$itemID]);
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($response);
