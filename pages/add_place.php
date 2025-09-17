<?php
require_once __DIR__ . '/../config/db.php';

$placeName = trim($_POST['place_name'] ?? '');
$response = ['success' => false];

if($placeName){
    $stmt = $pdo->prepare("INSERT INTO Places (PlaceName) VALUES (?)");
    if($stmt->execute([$placeName])){
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $placeName;
    }
}

echo json_encode($response);
