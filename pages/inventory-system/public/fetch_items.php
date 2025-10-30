<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$sql = "SELECT i.id, i.item_name, i.description, i.cost, i.category_id, c.name AS category_name
        FROM items i LEFT JOIN categories c ON i.category_id = c.id
        ORDER BY i.item_name";
$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach($data as &$r){
  $r['actions'] = '<button class="btn btn-sm btn-warning edit-btn">Edit</button> <button class="btn btn-sm btn-danger delete-btn">Delete</button>';
}
echo json_encode(['data'=>$data]);
