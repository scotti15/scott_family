<?php
require_once '../config/db.php';
$action = $_POST['action'] ?? '';

if($action === 'add'){
  $name = trim($_POST['name'] ?? '');
  if(!$name){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }
  $stmt = $pdo->prepare("INSERT INTO rooms (name) VALUES (?)");
  $ok = $stmt->execute([$name]);
  if($ok) echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'name'=>$name]);
  else echo json_encode(['success'=>false]);
  exit;
}

if($action === 'edit'){
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  if(!$id || !$name){ echo json_encode(['success'=>false]); exit; }
  $stmt = $pdo->prepare("UPDATE rooms SET name = ? WHERE id = ?");
  $ok = $stmt->execute([$name,$id]);
  echo json_encode(['success'=>(bool)$ok]);
  exit;
}

if($action === 'delete'){
  $id = (int)($_POST['id'] ?? 0);
  if(!$id){ echo json_encode(['success'=>false]); exit; }
  $ok = $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
  echo json_encode(['success'=> (bool)$ok]);
  exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
