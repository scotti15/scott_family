<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$id=(int)($_POST['id']??0); if($id<1){ echo json_encode(['status'=>'error']); exit; }
$ok = $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
echo json_encode(['status'=>$ok?'success':'error','message'=>$ok?'Deleted':'DB error']);
