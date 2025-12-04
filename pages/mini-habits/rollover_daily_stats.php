<?php
// rollover_daily_stats.php
require_once '../../config/db.php'; // adjust path as needed
if (session_status() == PHP_SESSION_NONE) session_start();

// header('Content-Type: application/json');

try {
    // Today's date
    $today = date('Y-m-d');

    // 1. Insert into log table all habits not yet rolled over today
    $insertStmt = $pdo->prepare("
        INSERT INTO mini_habit_log (habit_id, user_id, completed_date, completed, target)
        SELECT habit_id, user_id, :today, completed, daily_target
        FROM mini_habits
        WHERE modified < :today AND is_active = 1
    ");
    $insertStmt->execute([':today' => $today]);
    $rolledOver = $insertStmt->rowCount();

    // 2. Reset completed to 0 for those habits
    $updateStmt = $pdo->prepare("
        UPDATE mini_habits
        SET completed = 0
        WHERE modified < :today AND is_active = 1
    ");
    $updateStmt->execute([':today' => $today]);

    // echo json_encode([
    //     'success' => true,
    //     'rolled_over' => $rolledOver
    // ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
