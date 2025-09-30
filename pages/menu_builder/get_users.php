<?php
require_once __DIR__ . '/../../config/db.php'; // adjust path if needed

try {
    $stmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($users);

} catch (PDOException $e) {
    // Return error as JSON for easier debugging
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
