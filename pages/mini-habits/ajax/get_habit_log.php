<?php
// ajax/get_habit_log.php
require_once '../../../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
$month   = $_GET['month'] ?? date('Y-m'); // fallback to current month

try {
    $stmt = $pdo->prepare("
        SELECT
            l.completed_date,
            h.habit_name,
            l.completed,
            l.target,
            ROUND((l.completed / l.target) * 100) AS percent_complete
        FROM mini_habit_log l
        JOIN mini_habits h ON h.habit_id = l.habit_id
        WHERE l.user_id = :user_id
          AND DATE_FORMAT(l.completed_date, '%Y-%m') = :month
        ORDER BY l.completed_date, h.habit_name
    ");

    $stmt->execute([
        ':user_id' => $user_id,
        ':month'   => $month
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'rows' => $rows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
