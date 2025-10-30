<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode([ "success" => false, "message" => "No ID provided." ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode([ "success" => true, "data" => $data ]);
} else {
    echo json_encode([ "success" => false, "message" => "Record not found." ]);
}
