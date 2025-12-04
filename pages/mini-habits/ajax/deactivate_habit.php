<?php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$habit_id = $_POST['habit_id'] ?? null;

if (!$user_id || !$habit_id) {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

// Only allow owner
$stmt = $pdo->prepare("UPDATE mini_habits SET is_active=0 WHERE habit_id=? AND user_id=?");
$stmt->execute([$habit_id, $user_id]);

echo json_encode(['success'=>true]);
