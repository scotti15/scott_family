<?php
require_once __DIR__ . '/../config/db.php';

$categoryName = trim($_POST['category_name'] ?? '');
$response = ['success' => false];

if($categoryName){
    $stmt = $pdo->prepare("INSERT INTO Categories (CategoryName) VALUES (?)");
    if($stmt->execute([$categoryName])){
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $categoryName;
    }
}

echo json_encode($response);
