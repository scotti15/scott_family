<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid transaction ID']);
    exit;
}

// Fetch the transaction
$stmt = $pdo->prepare("
SELECT IDFinancialTransaction, Date, PlaceID, AccountID, TypeID,
ProvinceID, CategoryID, ItemID, UnitID, Tax, Quantity, Price, Comment
FROM Transactions
WHERE IDFinancialTransaction = ?
");
$stmt->execute([$id]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    echo json_encode(['error' => 'Transaction not found']);
    exit;
}

echo json_encode($tx);
