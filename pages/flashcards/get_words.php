<?php
require_once __DIR__ . '/../../config/db.php'; // your PDO connection

header('Content-Type: application/json');

// user_id and list_id hard-coded for now; you can adjust or pass via GET/POST
$user_id = 5;
$list_id = 2;

try {
    $stmt = $pdo->prepare("SELECT question, answer FROM flashcards WHERE user_id = ? AND list_id = ?");
    $stmt->execute([$user_id, $list_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode([]);
}
