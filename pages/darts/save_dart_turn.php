<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data['user_id'] ?? ($_SESSION['user_id'] ?? null);

if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'No player specified']);
    exit;
}

if (
    empty($data['game_id']) ||
    !isset($data['start_score']) ||
    !isset($data['end_score'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$gameId     = (int)$data['game_id'];
$startScore = (int)$data['start_score'];
$endScore   = (int)$data['end_score'];
$result     = $data['turn_result'] ?? 'normal';
$darts      = $data['darts'] ?? [];

try {
    $pdo->beginTransaction();

    /* -----------------------------
       1️⃣ Get next turn number
    ----------------------------- */
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(turn_number), 0) + 1
        FROM dart_turns
        WHERE game_id = :game_id
    ");
    $stmt->execute([':game_id' => $gameId]);
    $nextTurn = (int)$stmt->fetchColumn();

    /* -----------------------------
       2️⃣ Insert turn
    ----------------------------- */
    $stmt = $pdo->prepare("
        INSERT INTO dart_turns (
            game_id,
            user_id,
            turn_number,
            start_score,
            end_score,
            is_valid,
            turn_result
        ) VALUES (
            :game_id,
            :user_id,
            :turn_number,
            :start_score,
            :end_score,
            1,
            :turn_result
        )
    ");

    $stmt->execute([
        ':game_id'     => $gameId,
        ':user_id'     => $userId,
        ':turn_number' => $nextTurn,
        ':start_score' => $startScore,
        ':end_score'   => $endScore,
        ':turn_result' => $result
    ]);

    $turnId = $pdo->lastInsertId();

/* -----------------------------
   3️⃣ Insert dart throws (updated for ricochet)
----------------------------- */
if (!empty($darts)) {
    $stmt = $pdo->prepare("
    INSERT INTO dart_throws
    (
      turn_id,
      throw_number,
      hit_score,
      ring,
      segment,
      x,
      y,
      hit_target,
      is_implied,
      aimed_ring,
      aimed_value,
      miss_distance,
      miss_angle,
      throw_type
    )
    VALUES
    (
      :turn_id,
      :throw_number,
      :hit_score,
      :ring,
      :segment,
      :x,
      :y,
      :hit_target,
      :is_implied,
      :aimed_ring,
      :aimed_value,
      :miss_distance,
      :miss_angle,
      :throw_type
    )
    
    ");

    foreach ($darts as $dart) {
        $stmt->execute([
            ':turn_id'       => $turnId,
            ':throw_number'  => (int)$dart['throw_number'],
            ':hit_score'     => (int)$dart['hit_score'],
            ':ring'          => $dart['ring'],
            ':segment'       => isset($dart['segment']) ? (int)$dart['segment'] : 1,
            ':x'             => $dart['x'],
            ':y'             => $dart['y'],
            ':hit_target'    => !empty($dart['hit_target']) ? 1 : 0,
            ':is_implied'    => !empty($dart['is_implied']) ? 1 : 0,
            ':aimed_ring'    => $dart['aimed_ring'] ?? null,
            ':aimed_value'   => $dart['aimed_value'] ?? null,
            ':miss_distance' => $dart['miss_distance'] ?? null,
            ':miss_angle'    => $dart['miss_angle'] ?? null,
            ':throw_type'    => $dart['throw_type'] ?? 'normal'
        ]);
        
    }
}


    $pdo->commit();

    echo json_encode([
        'status'       => 'ok',
        'turn_id'      => $turnId,
        'turn_number'  => $nextTurn
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'error'   => 'DB error',
        'message' => $e->getMessage()
    ]);
}
