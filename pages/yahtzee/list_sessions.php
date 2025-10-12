<?php
session_set_cookie_params([
    'path' => '/', // must match login.php
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// Accept either user_id or id
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}



$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
    SELECT DISTINCT session_id, MIN(created_at) AS created_at
    FROM yahtzee_games
    WHERE user_id = :user_id
    GROUP BY session_id
    ORDER BY session_id DESC
");
    $stmt->execute([':user_id' => $userId]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sessions' => $sessions]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
