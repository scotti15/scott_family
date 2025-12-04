<?php
require_once '../../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// read POST safely
$user_id = $_SESSION['user_id'] ?? 0;
$habit_name = trim($_POST['habit_name'] ?? '');
$daily_target = isset($_POST['daily_target']) ? intval($_POST['daily_target']) : 1;

// quick debug output
$debug_post = $_POST;

// validate input
if ($user_id == 0 || $habit_name === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid input',
        'received_post' => $debug_post
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO mini_habits (user_id, habit_name, daily_target, completed)
        VALUES (:uid, :name, :tgt, 0)
    ");

    $stmt->execute([
        ':uid'  => $user_id,
        ':name' => $habit_name,
        ':tgt'  => $daily_target
    ]);

    // return success + echo back what was actually received
    echo json_encode([
        'success' => true,
        'habit_name' => $habit_name,
        'daily_target' => $daily_target,
        'received_post' => $debug_post
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'received_post' => $debug_post
    ]);
}
