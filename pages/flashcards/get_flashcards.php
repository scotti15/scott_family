<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$listId = isset($_GET['list_id']) && is_numeric($_GET['list_id']) ? (int)$_GET['list_id'] : null;
if (!$listId) {
    echo json_encode(['success' => false, 'message' => 'No list selected']);
    exit;
}

$stmt = $pdo->prepare("SELECT question, answer FROM flashcards WHERE user_id = ? AND list_id = ? ORDER BY id ASC");
$stmt->execute([$userId, $listId]);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'flashcards' => $cards]);
