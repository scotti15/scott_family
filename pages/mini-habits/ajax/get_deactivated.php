<?php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
$viewer_id = $_SESSION['user_id'] ?? 0;
$isOwner = ($user_id === $viewer_id);

try {
    $stmt = $pdo->prepare("
    SELECT habit_id, habit_name, daily_target, completed
    FROM mini_habits
    WHERE user_id = :uid AND is_active = 0
    ORDER BY habit_name
");
    $stmt->execute([':uid' => $user_id]);
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'isOwner' => $isOwner,
        'habits' => $habits
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
