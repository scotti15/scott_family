<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$item_name = trim($_POST['item_name'] ?? '');
$description = trim($_POST['description'] ?? null);
$category_id = $_POST['category_id'] ?: null;
$cost = $_POST['cost'] !== '' ? $_POST['cost'] : null;

if($id<1 || !$item_name){ echo json_encode(['status'=>'error','message'=>'Invalid input']); exit; }

$stmt = $pdo->prepare("UPDATE items SET item_name=?, description=?, category_id=?, cost=? WHERE id=?");
$ok = $stmt->execute([$item_name, $description ?: null, $category_id ?: null, $cost ?: null, $id]);

echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? 'Updated':'DB error']);
