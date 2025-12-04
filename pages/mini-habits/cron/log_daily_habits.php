<?php
require_once '../../../config/db.php'; // adjust path to your db.php

if (session_status() == PHP_SESSION_NONE) session_start();

try {
    // 1️⃣ Get all active habits
    $stmt = $pdo->prepare("
        SELECT habit_id, user_id, completed, daily_target
        FROM mini_habits
        WHERE is_active = 1
    ");
    $stmt->execute();
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');

    // 2️⃣ Insert a log entry for each habit
    $insert = $pdo->prepare("
        INSERT INTO mini_habit_log (habit_id, user_id, completed_date, completed, target)
        VALUES (:habit_id, :user_id, :completed_date, :completed, :target)
    ");

    foreach ($habits as $h) {
        $insert->execute([
            ':habit_id' => $h['habit_id'],
            ':user_id' => $h['user_id'],
            ':completed_date' => $today,
            ':completed' => $h['completed'],
            ':target' => $h['daily_target']
        ]);
    }

    // 3️⃣ Reset completed counts for next day
    $pdo->exec("UPDATE mini_habits SET completed = 0 WHERE is_active = 1");

    echo "Daily habits logged and reset successfully.\n";

} catch (Exception $e) {
    error_log("Error in log_daily_habits.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
