<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;

$data = [];
if ($userId > 0) {
    $stmt = $pdo->prepare("
    SELECT 
    t.IDFinancialTransaction AS id,
    t.Date AS date,
    p.PlaceName AS place,
    a.AccountName AS account,
    tt.TypeName AS type,
    pr.ProvinceCode AS province,
    c.CategoryName AS category,
    i.ItemName AS item,
    t.Tax AS tax,
    t.Quantity AS quantity,
    t.Price AS price,
    u.UnitName AS unit,
    t.Comment AS comment,
    (t.Quantity * t.Price + t.Tax) AS total
  FROM Transactions t  
    ");
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Wrap in 'data' key for DataTables
echo json_encode(['data' => $data]);
