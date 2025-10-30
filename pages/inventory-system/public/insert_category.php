<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$name = trim($_POST['name'] ?? '');
if(!$name){ echo json_encode(['status'=>'error','message'=>'Name required']); exit; }
$check = $pdo->prepare("SELECT id FROM categories WHERE name = ?"); $check->execute([$name]); if($check->fetch()){ echo json_encode(['status'=>'error','message'=>'Already exists']); exit; }
$ok = $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Added':'DB error']);
