<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
error_log("userId received in get_transactions.php: $userId"); // debug

$data = [];
if ($userId > 0) {
    $stmt = $pdo->prepare("
        SELECT t.IDFinancialTransaction, t.Date, p.PlaceName AS Place, a.AccountName AS Account, 
               tt.TypeName AS Type, pr.ProvinceCode AS Province, c.CategoryName AS Category,
               i.ItemName AS Item, t.Tax, t.Quantity, t.Price, u.UnitName AS Unit, t.Comment
        FROM Transactions t
        LEFT JOIN Places p ON t.PlaceID = p.PlaceID
        LEFT JOIN Accounts a ON t.AccountID = a.AccountID
        LEFT JOIN TransactionTypes tt ON t.TypeID = tt.TypeID
        LEFT JOIN Provinces pr ON t.ProvinceID = pr.ProvinceID
        LEFT JOIN Categories c ON t.CategoryID = c.CategoryID
        LEFT JOIN Items i ON t.ItemID = i.ItemID
        LEFT JOIN Units u ON t.UnitID = u.UnitID
        WHERE t.UserID = ?
        ORDER BY t.Date DESC
    ");
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($data);
