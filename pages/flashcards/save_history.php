<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Decode JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$userId = $_SESSION['user_id'];
$listId = $input['list_id'] ?? null;
$finalTimeSeconds = $input['final_time_seconds'] ?? 0;
$finalTimeDisplay = $input['final_time_display'] ?? '';
$correctTotal = $input['correct_total'] ?? 0;
$totalCards = $input['total_questions'] ?? 0; // new field

if (!$listId) {
    echo json_encode(['success' => false, 'message' => 'Missing list ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO list_history 
        (user_id, list_id, completed_at, final_time_seconds, final_time_display, correct_total, total_questions)
        VALUES (?, ?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $listId, $finalTimeSeconds, $finalTimeDisplay, $correctTotal, $totalCards]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
