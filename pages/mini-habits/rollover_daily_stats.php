<?php
require_once '../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

try {
    $pdo->beginTransaction();

    $today = new DateTime(date('Y-m-d'));        // today 00:00
    $yesterday = (clone $today)->modify('-1 day');

    // 1) Fetch all habits
    $habits = $pdo->query("
        SELECT habit_id, user_id, daily_target, completed, is_active, modified
        FROM mini_habits
    ")->fetchAll(PDO::FETCH_ASSOC);

    $inserted = 0;
    $reset = 0;

    foreach ($habits as $h) {

        $habitId  = $h['habit_id'];
        $userId   = $h['user_id'];
        $target   = $h['daily_target'];
        $completed = $h['completed'];
        $isActive = $h['is_active'];

        $yesterdayStr = $yesterday->format('Y-m-d');

        // -----------------------------------
        // 1) LOG YESTERDAY FIRST (REAL DATA)
        // -----------------------------------
        if ($isActive) {

            $insReal = $pdo->prepare("
                INSERT IGNORE INTO mini_habit_log
                (habit_id, user_id, completed_date, completed, target)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insReal->execute([
                $habitId,
                $userId,
                $yesterdayStr,
                $completed,
                $target
            ]);

            // Only reset if we successfully logged yesterday
            if ($insReal->rowCount()) {
                $inserted++;

                $upd = $pdo->prepare("
                    UPDATE mini_habits
                    SET completed = 0
                    WHERE habit_id = ?
                ");
                $upd->execute([$habitId]);
                $reset++;
            }
        }

        // -----------------------------------
        // 2) FIND LAST LOGGED DATE
        // -----------------------------------
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
            // fallback: use modified date ONLY for history start
            $lastDate = new DateTime(substr($h['modified'], 0, 10));
        }

        // -----------------------------------
        // 3) BACKFILL MISSING DAYS (EXCLUDE YESTERDAY)
        // -----------------------------------
        $nextDay = (clone $lastDate)->modify('+1 day');

        while ($nextDay < $yesterday) {

            $dateStr = $nextDay->format('Y-m-d');

            $insZero = $pdo->prepare("
                INSERT IGNORE INTO mini_habit_log
                (habit_id, user_id, completed_date, completed, target)
                VALUES (?, ?, ?, 0, ?)
            ");

            $insZero->execute([
                $habitId,
                $userId,
                $dateStr,
                $target
            ]);

            if ($insZero->rowCount()) {
                $inserted++;
            }

            $nextDay->modify('+1 day');
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