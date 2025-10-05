<?php
require_once '../../config/db.php';
require_once '../../auth/check_login.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$giftId = $_POST['gift_id'] ?? null;
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if (!$giftId) {
    echo json_encode(['success' => false, 'message' => 'No gift specified.']);
    exit;
}

// fetch gift info
$stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ?");
$stmt->execute([$giftId]);
$gift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gift) {
    echo json_encode(['success' => false, 'message' => 'Gift not found.']);
    exit;
}

// check if already claimed (for single gifts)
if (!$gift['allow_multiple']) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE gift_id = ?");
    $stmt->execute([$giftId]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This gift is already claimed.']);
        exit;
    }
}

// insert claim
$stmt = $pdo->prepare("INSERT INTO claims (gift_id, claimer_id, quantity) VALUES (?, ?, ?)");
$stmt->execute([$giftId, $userId, $quantity]);

echo json_encode(['success' => true, 'message' => 'Gift claimed successfully!']);
