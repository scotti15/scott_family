<?php
require_once '../../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['game_id'])) {
  http_response_code(400);
  echo json_encode(["status" => "error", "error" => "Missing game_id"]);
  exit;
}

$stmt = $pdo->prepare("
  UPDATE dart_games
  SET
    game_result = :result,
    finished_at = NOW()
  WHERE game_id = :game_id
");

$stmt->execute([
  ":result" => $data['game_result'] ?? 'finished',
  ":game_id" => $data['game_id']
]);

echo json_encode(["status" => "ok"]);
