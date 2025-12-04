<?php
require_once '../../../config/db.php';

if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$habit_id = intval($_POST['habit_id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

if ($habit_id === 0 || $user_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    // Only the owner can delete
    $stmt = $pdo->prepare("DELETE FROM mini_habits WHERE habit_id = :hid AND user_id = :uid");
    $stmt->execute([
        ':hid' => $habit_id,
        ':uid' => $user_id
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
