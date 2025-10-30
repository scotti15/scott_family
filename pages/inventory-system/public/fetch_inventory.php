<?php
require_once '../config/db.php';

$stmt = $pdo->query("
  SELECT inv.id, i.item_name, r.name AS room_name, l.location_name,
         inv.quantity, inv.entry_date, inv.expiry_date
  FROM inventory inv
  JOIN items i ON inv.item_id = i.id
  JOIN rooms r ON inv.room_id = r.id
  JOIN locations l ON inv.location_id = l.id
  ORDER BY inv.id DESC
");

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['actions'] = '
      <button class="btn btn-sm btn-warning edit-btn">Edit</button>
      <button class="btn btn-sm btn-danger delete-btn">Delete</button>
    ';
    $data[] = $row;
}

echo json_encode(['data' => $data]);
