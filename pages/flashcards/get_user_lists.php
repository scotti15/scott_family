<?php
// get_user_lists.php
// Returns JSON list of flashcard lists for the logged-in user.
// Replace the path below if required.

require_once __DIR__ . '/../../config/db.php';

// start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON response header
header('Content-Type: application/json');

// determine user id from session
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Use PDO if available, otherwise fall back to mysqli ($conn)
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT id, name, description, created_at FROM flashcard_lists WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$lists) $lists = [];
        echo json_encode(['success' => true, 'lists' => $lists]);
        exit;
    }

    // fallback for mysqli ($conn)
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT id, name, description, created_at FROM flashcard_lists WHERE user_id = ? ORDER BY created_at DESC");
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $lists = [];
        while ($row = $res->fetch_assoc()) {
            $lists[] = $row;
        }
        echo json_encode(['success' => true, 'lists' => $lists]);
        exit;
    }

    // neither DB handle found
    echo json_encode(['success' => false, 'message' => 'No DB connection available (expected $pdo or $conn).']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}
