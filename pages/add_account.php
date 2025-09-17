<?php
require_once __DIR__ . '/../config/db.php';

$accountName = trim($_POST['account_name'] ?? '');
$response = ['success' => false];

if($accountName){
    $stmt = $pdo->prepare("INSERT INTO Accounts (AccountName) VALUES (?)");
    if($stmt->execute([$accountName])){
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $accountName;
    }
}

echo json_encode($response);
