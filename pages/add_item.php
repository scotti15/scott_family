<?php
require_once __DIR__ . '/../config/db.php';

$itemName = $_POST['item_name'] ?? '';
$response = ['success' => false];

if($itemName){
    $stmt = $pdo->prepare("INSERT INTO Items (ItemName) VALUES (?)");
    if($stmt->execute([$itemName])){
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $itemName;
    }
}

echo json_encode($response);
