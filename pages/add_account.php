<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$accountName = trim($_POST['account_name'] ?? '');
$user_id = $_SESSION['user_id'] ?? 0;

$response = ['success' => false];

if ($accountName && $user_id) {
    $stmt = $pdo->prepare("
        INSERT INTO accounts (AccountName, UserID)
        VALUES (?, ?)
    ");

    if ($stmt->execute([$accountName, $user_id])) {
        $response['success'] = true;
        $response['id'] = $pdo->lastInsertId();
        $response['name'] = $accountName;
    }
}

echo json_encode($response);