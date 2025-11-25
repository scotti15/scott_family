<?php
require_once '../../config/db.php';

$id = $_POST['id'] ?? null;
$response = ['success' => false];

if ($id && is_numeric($id)) {
    $stmt = $pdo->prepare("DELETE FROM shopping_list WHERE ListID = ?");
    if ($stmt->execute([$id])) {
        $response['success'] = true;
    }
}

echo json_encode($response);
