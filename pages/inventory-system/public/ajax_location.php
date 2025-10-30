<?php
require_once __DIR__ . '/../includes/auth.php'; // must be FIRST
require_once '../config/db.php';
$action = $_POST['action'] ?? '';

if($action === 'add'){
  $name = trim($_POST['location_name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $room_id = $_POST['room_id'] ? (int)$_POST['room_id'] : null;
  if(!$name){ echo json_encode(['success'=>false,'message'=>'Name required']); exit; }

  // fetch room name if provided
  $room_name = '';
  if($room_id){
    $r = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
    $r->execute([$room_id]);
    $rr = $r->fetch();
    if($rr) $room_name = $rr['name'];
  }

  $stmt = $pdo->prepare("INSERT INTO locations (location_name, description, room_id) VALUES (?,?,?)");
  $ok = $stmt->execute([$name, $desc ?: null, $room_id]);
  if($ok) echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'location_name'=>$name,'description'=>$desc,'room_id'=>$room_id,'room_name'=>$room_name]);
  else echo json_encode(['success'=>false]);
  exit;
}

if($action === 'get'){
  $id = (int)($_POST['id'] ?? 0);
  $stmt = $pdo->prepare("SELECT l.*, r.name AS room_name FROM locations l LEFT JOIN rooms r ON l.room_id = r.id WHERE l.id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row) echo json_encode(array_merge(['success'=>true], $row));
  else echo json_encode(['success'=>false,'message'=>'Not found']);
  exit;
}

if($action === 'edit'){
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['location_name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $room_id = $_POST['room_id'] ? (int)$_POST['room_id'] : null;
  if(!$id || !$name){ echo json_encode(['success'=>false]); exit; }

  $stmt = $pdo->prepare("UPDATE locations SET location_name=?, description=?, room_id=? WHERE id=?");
  $ok = $stmt->execute([$name, $desc ?: null, $room_id, $id]);

  // get room name
  $room_name = '';
  if($room_id){
    $r = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
    $r->execute([$room_id]);
    $rr = $r->fetch();
    if($rr) $room_name = $rr['name'];
  }

  echo json_encode(['success'=>(bool)$ok,'id'=>$id,'location_name'=>$name,'description'=>$desc,'room_id'=>$room_id,'room_name'=>$room_name]);
  exit;
}

if($action === 'delete'){
  $id = (int)($_POST['id'] ?? 0);
  if(!$id){ echo json_encode(['success'=>false]); exit; }
  $ok = $pdo->prepare("DELETE FROM locations WHERE id = ?")->execute([$id]);
  echo json_encode(['success'=> (bool)$ok]);
  exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);
