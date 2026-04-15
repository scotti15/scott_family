<?php
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION["user_id"] ?? 0;

if (!$user_id) {
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT metric, target_value
    FROM user_targets
    WHERE user_id = :user_id
      AND domain = 'darts'
");

$stmt->execute([
    ':user_id' => $user_id
]);

$targets = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $targets[$row['metric']] = (float)$row['target_value'];
}

echo json_encode($targets);