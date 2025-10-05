<?php
include '../config/db.php';

$table = $_POST['table'];
$id = $_POST['id'];

$allowedTables = [
    'accounts'=>'AccountID',
    'categories'=>'CategoryID',
    'items'=>'ItemID',
    'places'=>'PlaceID',
    'units'=>'UnitID',
    'transactiontypes'=>'TypeID',
    'menu_items'=>'id'
];

if(!array_key_exists($table,$allowedTables)) exit('Invalid table');

$pk = $allowedTables[$table];
$stmt = $pdo->prepare("DELETE FROM `$table` WHERE $pk=:id");
$stmt->execute(['id'=>$id]);

echo json_encode(['success'=>true]);
?>
