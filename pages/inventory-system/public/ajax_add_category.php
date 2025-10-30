<?php
require_once '../config/db.php';

if (!empty($_POST['name'])) {
    $name = trim($_POST['name']);

    // Check duplicate
    $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Category already exists.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);

    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'name' => $name
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No category name provided.']);
}
