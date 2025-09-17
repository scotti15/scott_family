<?php
require_once __DIR__ . '/../config/db.php';

$unitName = trim($_POST['unit_name'] ?? '');
$response = ['success' => false];

if($unitName){
    $stmt = $pdo->prepare("INSERT INTO Units (UnitName) VALUES (?)");
    if($stmt->execute([$unitName])){
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $unitName;
    }
}

echo json_encode($response);
