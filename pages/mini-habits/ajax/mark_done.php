<?php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? null;
$habit_id = $_POST['habit_id'] ?? null;

if (!$user_id || !$habit_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Increment completed, but not beyond target
$sql = "UPDATE mini_habits
        SET completed = LEAST(completed + 1, daily_target)
        WHERE habit_id = ? AND user_id = ? AND is_active = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$habit_id, $user_id]);

echo json_encode(['success' => true]);
