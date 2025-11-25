<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$item = trim($_POST['item_name'] ?? '');

if ($item === '') {
    echo json_encode(['success' => false, 'error' => 'Item required']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO items (ItemName) VALUES (?)");

if ($stmt->execute([$item])) {
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $item
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Insert failed']);
}
