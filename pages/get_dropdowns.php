<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$data = [
    'places' => $pdo->query("SELECT PlaceID, PlaceName FROM places ORDER BY PlaceName")->fetchAll(PDO::FETCH_ASSOC),
    'accounts' => $pdo->query("SELECT AccountID, AccountName FROM accounts ORDER BY AccountName")->fetchAll(PDO::FETCH_ASSOC),
    'types' => $pdo->query("SELECT TypeID, TypeName FROM transactiontypes ORDER BY TypeName")->fetchAll(PDO::FETCH_ASSOC),
    'provinces' => $pdo->query("SELECT ProvinceID, ProvinceCode FROM provinces ORDER BY ProvinceCode")->fetchAll(PDO::FETCH_ASSOC),
    'categories' => $pdo->query("SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC),
    'items' => $pdo->query("SELECT ItemID, ItemName FROM items ORDER BY ItemName")->fetchAll(PDO::FETCH_ASSOC),
    'units' => $pdo->query("SELECT UnitID, UnitName FROM units ORDER BY UnitName")->fetchAll(PDO::FETCH_ASSOC)
];

echo json_encode($data);
