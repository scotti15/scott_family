<?php
require_once '../config/db.php';

$id = $_POST['id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if(!$id || $quantity===null){
    echo json_encode(['status'=>'error','message'=>'Missing ID or quantity']);
    exit;
}

$stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
$ok = $stmt->execute([$quantity, $id]);

echo json_encode(['status' => $ok ? 'success' : 'error']);
