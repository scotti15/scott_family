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

// Only owner can edit
if ($gift['owner_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'You can only edit your own gifts.']);
    exit;
}

// Update gift
$stmt = $pdo->prepare("UPDATE gifts SET title=?, description=?, link=?, price=?, allow_multiple=? WHERE gift_id=?");
$stmt->execute([
    $_POST['title'],
    $_POST['description'],
    $_POST['link'],
    $_POST['price'] ?: null,
    isset($_POST['allow_multiple']) ? 1 : 0,
    $giftId
]);

echo json_encode(['success' => true, 'message' => 'Gift updated successfully!']);
