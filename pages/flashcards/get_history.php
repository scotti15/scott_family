<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

// Read JSON body
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id"
    ]);
    exit;
}

$user_id = intval($input["user_id"]);

// Correct table + correct column name!
$sql = "
    SELECT 
        h.id,
        h.list_id,
        h.completed_at,
        h.final_time_seconds,
        h.final_time_display,
        h.correct_total,
        h.total_questions,
        l.name AS list_name
    FROM list_history h
    LEFT JOIN flashcard_lists l ON l.id = h.list_id
    WHERE h.user_id = ?
    ORDER BY h.completed_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "history" => $rows
]);
