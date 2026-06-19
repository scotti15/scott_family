<?php
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {

    $pdo->beginTransaction();

    $today = new DateTime('today');
    $yesterday = (clone $today)->modify('-1 day');

    // Fetch all habits
    $habits = $pdo->query("
        SELECT
            habit_id,
            user_id,
            daily_target,
            completed,
            is_active,
            modified
        FROM mini_habits
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($habits as $h) {

        $habitId      = (int)$h['habit_id'];
        $userId       = (int)$h['user_id'];
        $target       = (int)$h['daily_target'];
        $completed    = (int)$h['completed'];
        $isApplicable = (int)$h['is_active'];

        // ----------------------------------
        // Find last logged date
        // ----------------------------------
        $stmt = $pdo->prepare("
            SELECT MAX(completed_date) AS last_date
            FROM mini_habit_log
            WHERE habit_id = ?
              AND user_id = ?
        ");

        $stmt->execute([$habitId, $userId]);

        $lastDateStr = $stmt->fetchColumn();

        if ($lastDateStr) {

            $lastDate = new DateTime($lastDateStr);

        } else {

            // First run for this habit
            $lastDate = new DateTime(substr($h['modified'], 0, 10));
        }

        // ----------------------------------
        // Fill missing days up to yesterday
        // ----------------------------------
        $nextDay = (clone $lastDate)->modify('+1 day');

        while ($nextDay <= $yesterday) {

            $dateStr = $nextDay->format('Y-m-d');

            // Yesterday gets actual values
            if ($dateStr === $yesterday->format('Y-m-d')) {

                $logCompleted = $completed;

            } else {

                // Older missing dates become zeroes
                $logCompleted = 0;
            }

            $ins = $pdo->prepare("
                INSERT IGNORE INTO mini_habit_log
                (
                    habit_id,
                    user_id,
                    completed_date,
                    completed,
                    target,
                    is_applicable
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $ins->execute([
                $habitId,
                $userId,
                $dateStr,
                $logCompleted,
                $target,
                $isApplicable
            ]);

            $nextDay->modify('+1 day');
        }

        // ----------------------------------
        // Reset only after yesterday exists
        // ----------------------------------
        $check = $pdo->prepare("
            SELECT 1
            FROM mini_habit_log
            WHERE habit_id = ?
              AND user_id = ?
              AND completed_date = ?
            LIMIT 1
        ");

        $check->execute([
            $habitId,
            $userId,
            $yesterday->format('Y-m-d')
        ]);

        if ($check->fetch()) {

            $reset = $pdo->prepare("
                UPDATE mini_habits
                SET completed = 0
                WHERE habit_id = ?
            ");

            $reset->execute([$habitId]);
        }
    }

    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}