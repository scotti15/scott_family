<?php
require_once '../../config/db.php';
require_once '../../auth/check_login.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$giftId = $_POST['gift_id'] ?? null;

if (!$giftId) {
    echo json_encode(['success' => false, 'message' => 'No gift specified.']);
    exit;
}

// Fetch gift to confirm ownership
$stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ?");
$stmt->execute([$giftId]);
$gift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gift) {
    echo json_encode(['success' => false, 'message' => 'Gift not found.']);
    exit;
}

// Only owner can delete
if ($gift['owner_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'You can only delete your own gifts.']);
    exit;
}

// Delete gift (optional: also delete claims for this gift)
$stmt = $pdo->prepare("DELETE FROM gifts WHERE gift_id = ?");
$stmt->execute([$giftId]);

echo json_encode(['success' => true, 'message' => 'Gift deleted successfully!']);
