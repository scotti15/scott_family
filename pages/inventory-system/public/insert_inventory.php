<?php
require_once '../config/db.php';

$item_id = $_POST['item_id'] ?? null;
$room_id = $_POST['room_id'] ?? null;
$location_id = $_POST['location_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$entry_date = $_POST['entry_date'] ?? null;
$expiry_date = $_POST['expiry_date'] ?? null;

if(!$item_id || !$room_id || !$location_id || !$quantity){
    echo json_encode(['status'=>'error','message'=>'Missing required fields']);
    exit;
}

$stmt = $pdo->prepare("
  INSERT INTO inventory (item_id, room_id, location_id, quantity, entry_date, expiry_date)
  VALUES (?, ?, ?, ?, ?, ?)
");
$ok = $stmt->execute([$item_id, $room_id, $location_id, $quantity, $entry_date, $expiry_date]);

echo json_encode(['status' => $ok ? 'success' : 'error']);
