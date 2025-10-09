<?php
require_once '../../config/db.php';
require_once '../../auth/check_login.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$giftId = $_POST['gift_id'] ?? null;

if (!$userId || !$giftId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Remove only *this* user's claim
$stmt = $pdo->prepare("DELETE FROM claims WHERE gift_id = ? AND claimer_id = ?");
$stmt->execute([$giftId, $userId]);

// Get updated claim info for tooltip & count
$stmt = $pdo->prepare("
    SELECT u.username, c.quantity
    FROM claims c
    JOIN users u ON c.claimer_id = u.id
    WHERE c.gift_id = ?
");
$stmt->execute([$giftId]);
$claimers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$claimedBy = [];
foreach ($claimers as $c) {
    $claimedBy[] = "{$c['username']} ({$c['quantity']})";
}

echo json_encode([
    'success' => true,
    'message' => 'Your claim has been removed.',
    'claimed_quantity' => count($claimers),
    'claimers_text' => $claimedBy ? 'Claimed by: ' . implode(', ', $claimedBy) : 'Not yet claimed'
]);
exit;
