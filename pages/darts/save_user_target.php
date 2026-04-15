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

// Get POST data
$input = json_decode(file_get_contents("php://input"), true);

$metric = $input["metric"] ?? null;
$value = $input["value"] ?? null;

if (!$metric || $value === null) {
    echo json_encode(["error" => "Invalid input"]);
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO user_targets (user_id, domain, metric, target_value)
    VALUES (:user_id, 'darts', :metric, :value)
    ON DUPLICATE KEY UPDATE
        target_value = VALUES(target_value)
");

$stmt->execute([
    ':user_id' => $user_id,
    ':metric' => $metric,
    ':value' => $value
]);

echo json_encode(["success" => true]);