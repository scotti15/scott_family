<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$name=trim($_POST['location_name']??''); $desc=trim($_POST['description']??''); $room_id = $_POST['room_id'] ?: null;
if(!$name || !$room_id){ echo json_encode(['status'=>'error','message'=>'Name and room required']); exit; }
$ok=$pdo->prepare("INSERT INTO locations (location_name, description, room_id) VALUES (?,?,?)")->execute([$name,$desc ?: null,$room_id]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Added':'DB error']);
