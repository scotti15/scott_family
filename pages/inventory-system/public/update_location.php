<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$id=(int)($_POST['id']??0); $name=trim($_POST['location_name']??''); $desc=trim($_POST['description']??''); $room_id = $_POST['room_id'] ?: null;
if($id<1||!$name||!$room_id){ echo json_encode(['status'=>'error','message'=>'Invalid']); exit; }
$ok=$pdo->prepare("UPDATE locations SET location_name=?, description=?, room_id=? WHERE id=?")->execute([$name,$desc ?: null,$room_id,$id]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Updated':'DB error']);
