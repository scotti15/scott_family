<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$item_name = trim($_POST['item_name'] ?? '');
$description = trim($_POST['description'] ?? null);
$category_id = $_POST['category_id'] ?: null;
$cost = $_POST['cost'] !== '' ? $_POST['cost'] : null;

if(!$item_name){ echo json_encode(['status'=>'error','message'=>'Item name required']); exit; }

$stmt = $pdo->prepare("INSERT INTO items (item_name, description, category_id, cost) VALUES (?, ?, ?, ?)");
$ok = $stmt->execute([$item_name, $description ?: null, $category_id ?: null, $cost ?: null]);

echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? 'Item added':'DB error']);
