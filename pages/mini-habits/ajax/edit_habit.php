<?php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$habit_id = intval($_POST['habit_id'] ?? 0);
$habit_name = trim($_POST['habit_name'] ?? '');
$daily_target = intval($_POST['daily_target'] ?? 1);
$user_id = $_SESSION['user_id'] ?? 0;

if ($habit_id === 0 || $habit_name === '' || $daily_target < 1 || $user_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE mini_habits
        SET habit_name = :name, daily_target = :tgt
        WHERE habit_id = :hid AND user_id = :uid
    ");
    $stmt->execute([
        ':name' => $habit_name,
        ':tgt' => $daily_target,
        ':hid' => $habit_id,
        ':uid' => $user_id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
