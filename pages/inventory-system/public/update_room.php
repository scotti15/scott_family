<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$id=(int)($_POST['id']??0); $name=trim($_POST['name']??'');
if($id<1||!$name){ echo json_encode(['status'=>'error','message'=>'Invalid']); exit; }
$ok=$pdo->prepare("UPDATE rooms SET name=? WHERE id=?")->execute([$name,$id]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Updated':'DB error']);
