<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$name = trim($_POST['name'] ?? '');
if(!$name){ echo json_encode(['status'=>'error','message'=>'Name required']); exit; }
$ok = $pdo->prepare("INSERT INTO rooms (name) VALUES (?)")->execute([$name]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Added':'DB error']);
