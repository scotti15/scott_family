<?php
require_once '../../config/db.php';
require_once '../../auth/check_login.php';

header('Content-Type: application/json');

$userId   = $_SESSION['user_id'];
$giftId   = $_POST['gift_id'] ?? null;
$quantity = max(1, intval($_POST['quantity'] ?? 1));
$action   = $_POST['action'] ?? 'claim'; // "claim" or "unclaim"

if (!$giftId) {
    echo json_encode(['success' => false, 'message' => 'No gift specified.']);
    exit;
}

// Fetch gift info
$stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ?");
$stmt->execute([$giftId]);
$gift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gift) {
    echo json_encode(['success' => false, 'message' => 'Gift not found.']);
    exit;
}

// --- CLAIM ACTION ---
if ($action === 'claim') {
    if (!$gift['allow_multiple']) {
        // Single claim gift
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE gift_id = ?");
        $stmt->execute([$giftId]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'This gift is already claimed.']);
            exit;
        }
    }

    // Add a claim record
    $stmt = $pdo->prepare("INSERT INTO claims (gift_id, claimer_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$giftId, $userId, $quantity]);

    $msg = 'Claim registered successfully.';

// --- UNCLAIM ACTION ---
} elseif ($action === 'unclaim') {
    $stmt = $pdo->prepare("DELETE FROM claims WHERE gift_id = ? AND claimer_id = ?");
    $stmt->execute([$giftId, $userId]);
    $msg = 'Claim removed successfully.';
}

// Always return updated claim info
$stmt = $pdo->prepare("
    SELECT u.username, c.quantity
    FROM claims c
    JOIN users u ON c.claimer_id = u.id
    WHERE c.gift_id = ?
");
$stmt->execute([$giftId]);
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);

$claimedBy = [];
foreach ($claims as $row) {
    $claimedBy[] = [
        'username' => $row['username'],
        'qty' => (int)$row['quantity']
    ];
}

echo json_encode([
    'success' => true,
    'message' => $msg,
    'claimed_by' => $claimedBy
]);
exit;
