<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$brand = trim($_POST['brand_name'] ?? '');

if ($brand === '') {
    echo json_encode(['success' => false, 'error' => 'Brand required']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO brands (BrandName) VALUES (?)");

if ($stmt->execute([$brand])) {
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $brand
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Insert failed']);
}
