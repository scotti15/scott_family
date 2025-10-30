<?php
require_once '../config/db.php';

$id = $_POST['id'] ?? null;
if(!$id){
    echo json_encode(['status'=>'error','message'=>'Missing ID']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
$ok = $stmt->execute([$id]);

echo json_encode(['status' => $ok ? 'success' : 'error']);
