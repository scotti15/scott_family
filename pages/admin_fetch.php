<?php
include '../config/db.php';

$table = $_GET['table'];
$allowedTables = [
    'accounts'=>'AccountID',
    'brands'=>'BrandID',
    'categories'=>'CategoryID',
    'items'=>'ItemID',
    'places'=>'PlaceID',
    'units'=>'UnitID',
    'transactiontypes'=>'TypeID',
    'menu_items'=>'id'
];

if(!array_key_exists($table,$allowedTables)) exit('Invalid table');

$pk = $allowedTables[$table];
$stmt = $pdo->query("SELECT *, $pk AS id FROM `$table`");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
