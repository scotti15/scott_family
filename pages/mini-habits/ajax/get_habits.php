<?php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

$userId = $_GET['user_id'] ?? null;
$loggedInUser = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false]);
    exit;
}

// Pending habits
$stmt = $pdo->prepare("
    SELECT habit_id, habit_name, daily_target, completed
    FROM mini_habits
    WHERE user_id = ? AND is_active = 1 AND completed < daily_target
    ORDER BY habit_name
");
$stmt->execute([$userId]);
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Completed habits
$stmt = $pdo->prepare("
    SELECT habit_id, habit_name, daily_target, completed
    FROM mini_habits
    WHERE user_id = ? AND is_active = 1 AND completed = daily_target
    ORDER BY habit_name
");
$stmt->execute([$userId]);
$completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "isOwner" => ($loggedInUser == $userId),
    "pending" => $pending,
    "completed" => $completed
]);
