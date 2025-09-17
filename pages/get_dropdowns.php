<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = [
    'places' => $pdo->query("SELECT PlaceID, PlaceName FROM Places ORDER BY PlaceName")->fetchAll(PDO::FETCH_ASSOC),
    'accounts' => $pdo->query("SELECT AccountID, AccountName FROM Accounts ORDER BY AccountName")->fetchAll(PDO::FETCH_ASSOC),
    'types' => $pdo->query("SELECT TypeID, TypeName FROM TransactionTypes ORDER BY TypeName")->fetchAll(PDO::FETCH_ASSOC),
    'provinces' => $pdo->query("SELECT ProvinceID, ProvinceCode FROM Provinces ORDER BY ProvinceCode")->fetchAll(PDO::FETCH_ASSOC),
    'categories' => $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC),
    'items' => $pdo->query("SELECT ItemID, ItemName FROM Items ORDER BY ItemName")->fetchAll(PDO::FETCH_ASSOC),
    'units' => $pdo->query("SELECT UnitID, UnitName FROM Units ORDER BY UnitName")->fetchAll(PDO::FETCH_ASSOC)
];

echo json_encode($data);
