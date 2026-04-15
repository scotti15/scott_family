<?php
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION["user_id"] ?? 0;
if (!$user_id) {
    http_response_code(403);
    exit;
}

$task_id = (int)($_POST['task_id'] ?? 0);
$anchor_date = $_POST['day'] ?? '';
$completed = (int)($_POST['completed'] ?? 0);

if (!$task_id || !$anchor_date) {
    http_response_code(400);
    exit;
}

if ($completed) {
    // INSERT completion
    $stmt = $pdo->prepare(
        "INSERT INTO cleaning_log (task_id, cleaned_date)
         VALUES (?, ?)"
    );
    $stmt->execute([$task_id, $anchor_date]);
    } else {
    // DELETE completion
    $stmt = $pdo->prepare(
        "DELETE FROM cleaning_log
         WHERE task_id = ? AND cleaned_date = ?"
    );
    $stmt->execute([$task_id, $anchor_date]);
}

echo json_encode(['success' => true]);