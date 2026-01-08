<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
error_log("userId received in get_transactions.php: $userId"); // debug

$data = [];
if ($userId > 0) {
    $stmt = $pdo->prepare("
        SELECT t.IDFinancialTransaction, t.Date, 
        p.PlaceName AS Place, p.logoPath AS PlaceLogo,
        a.AccountName AS Account, 
        tt.TypeName AS Type, pr.ProvinceCode AS Province, 
        c.CategoryName AS Category,
        i.ItemName AS Item, t.Tax, t.Quantity, t.Price, 
        u.UnitName AS Unit, t.Comment
        FROM transactions t
        LEFT JOIN places p ON t.PlaceID = p.PlaceID
        LEFT JOIN accounts a ON t.AccountID = a.AccountID
        LEFT JOIN transactiontypes tt ON t.TypeID = tt.TypeID
        LEFT JOIN provinces pr ON t.ProvinceID = pr.ProvinceID
        LEFT JOIN categories c ON t.CategoryID = c.CategoryID
        LEFT JOIN items i ON t.ItemID = i.ItemID
        LEFT JOIN units u ON t.UnitID = u.UnitID
        WHERE t.UserID = ?
        ORDER BY t.Date DESC
    ");
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($data);
