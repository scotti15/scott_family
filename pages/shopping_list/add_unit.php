<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$name = trim($_POST['unit_name'] ?? '');
$type = trim($_POST['unit_type'] ?? '');
$conv = trim($_POST['conversion_to_base'] ?? '');

if ($name === '' || $type === '' || $conv === '') {
    echo json_encode(['success' => false, 'error' => 'All fields required']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO units (UnitName, UnitType, ConversionToBase) VALUES (?, ?, ?)");

if ($stmt->execute([$name, $type, $conv])) {
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $name
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Insert failed']);
}
