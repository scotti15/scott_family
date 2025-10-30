<?php
require_once '../config/db.php';
header('Content-Type: application/json');
$data = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
foreach($data as &$r){ $r['actions']='<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>'; }
echo json_encode(['data'=>$data]);
