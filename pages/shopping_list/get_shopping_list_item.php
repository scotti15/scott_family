<?php
require_once '../../config/db.php';

$listID = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM shopping_list WHERE ListID = ?");
$stmt->execute([$listID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row);
