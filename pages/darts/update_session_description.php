<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

try {

    $sessionId = $_POST['session_id'] ?? null;
    $description = $_POST['description'] ?? '';

    if (!$sessionId) {
        throw new Exception("Missing session ID");
    }

    $stmt = $pdo->prepare("
        UPDATE dart_sessions
        SET description = :description
        WHERE session_id = :session_id
    ");

    $stmt->execute([
        ':description' => $description,
        ':session_id' => $sessionId
    ]);

    echo json_encode([
        'status' => 'ok'
    ]);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}