<?php
require_once '../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();


try {
    $pdo->beginTransaction();

    $today = new DateTime(date('Y-m-d')); // today at 00:00

    $yesterday = (clone $today)->modify('-1 day');

    // 1) Fetch all habits
    $habits = $pdo->query("
        SELECT habit_id, user_id, daily_target, completed, is_active, modified
        FROM mini_habits
    ")->fetchAll(PDO::FETCH_ASSOC);

    $inserted = 0;
    $reset = 0;

    // 2) Process each habit individually
    foreach ($habits as $h) {

        $habitId = $h['habit_id'];
        $userId  = $h['user_id'];
        $target  = $h['daily_target'];
        $completed = $h['completed'];
        $isActive = $h['is_active'];
        $modifiedDate = new DateTime(substr($h['modified'], 0, 10));

        // 2A) Find last logged date (or use modified date)
        $stmt = $pdo->prepare("
            SELECT completed_date 
            FROM mini_habit_log
            WHERE habit_id = ? AND user_id = ?
            ORDER BY completed_date DESC
            LIMIT 1
        ");
        $stmt->execute([$habitId, $userId]);
        $lastLogRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lastLogRow) {
            $lastDate = new DateTime($lastLogRow['completed_date']);
        } else {
            // No log yet → start from modified date
            $lastDate = clone $modifiedDate;
        }

        // 2B) Insert missing days as zeros
        $nextDay = (clone $lastDate)->modify('+1 day');

        while ($nextDay < $today) {

            $dateStr = $nextDay->format('Y-m-d');

            // Insert zero row
            $ins = $pdo->prepare("
                INSERT IGNORE INTO mini_habit_log (habit_id, user_id, completed_date, completed, target)
                VALUES (?, ?, ?, 0, ?)
            ");
            $ins->execute([$habitId, $userId, $dateStr, $target]);

            if ($ins->rowCount()) $inserted++;

            // move forward one day
            $nextDay->modify('+1 day');
        }

// 2C) Log the final real day (only if it's before today)
if ($isActive && $modifiedDate < $today) {

    $realDateStr = $modifiedDate->format('Y-m-d');

    // Only insert if it doesn't already exist
    $ins2 = $pdo->prepare("
        INSERT IGNORE INTO mini_habit_log
        (habit_id, user_id, completed_date, completed, target)
        VALUES (?, ?, ?, ?, ?)
    ");
    $ins2->execute([
        $habitId,
        $userId,
        $realDateStr,
        $completed,
        $target
    ]);

    if ($ins2->rowCount()) {
        $inserted++;
    }

    // Reset ONLY after logging
    $upd = $pdo->prepare("
        UPDATE mini_habits
        SET completed = 0
        WHERE habit_id = ?
    ");
    $upd->execute([$habitId]);
    $reset++;
}

    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
