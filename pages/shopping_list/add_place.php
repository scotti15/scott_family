<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$place = trim($_POST['place_name'] ?? '');

if ($place === '') {
    echo json_encode(['success' => false, 'error' => 'Place required']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO places (PlaceName) VALUES (?)");

if ($stmt->execute([$place])) {
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $place
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Insert failed']);
}
