<?php
include '../config/db.php';

$table = $_POST['table'];
$action = $_POST['action'];
$id = $_POST['id'] ?? null;
$fields = $_POST['fields'];

if ($table === 'menu_items' && isset($fields['parent_id'])) {
    if ($fields['parent_id'] === '' || $fields['parent_id'] === 'null') {
        $fields['parent_id'] = null;
    }
}

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

if($action === 'add'){
    $cols = implode(',', array_keys($fields));
    $placeholders = implode(',', array_map(fn($k)=>":$k", array_keys($fields)));
    $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($placeholders)");
    $stmt->execute($fields);
} elseif($action==='edit' && $id){
    $set = implode(',', array_map(fn($k)=>"$k=:$k", array_keys($fields)));
    $fields['id']=$id;
    $stmt = $pdo->prepare("UPDATE `$table` SET $set WHERE {$allowedTables[$table]}=:id");
    $stmt->execute($fields);
}

echo json_encode(['success'=>true]);
?>
