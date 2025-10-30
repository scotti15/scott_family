<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

switch($type) {
    case 'items':
        $stmt = $pdo->query("SELECT id, item_name AS name FROM items ORDER BY item_name");
        break;
    case 'categories':
        $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
        break;
    case 'rooms':
        $stmt = $pdo->query("SELECT id, name FROM rooms ORDER BY name");
        break;
    case 'locations':
        $stmt = $pdo->query("SELECT id, location_name AS name FROM locations ORDER BY location_name");
        break;
    default:
        echo json_encode([]);
        exit;
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
