<?php
require_once '../../config/db.php';
//require_once '../../auth/check_login.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: ../../auth/login.php");
    exit;
}

$gift_id = $_GET['gift_id'] ?? null;
if (!$gift_id) {
    die("Gift ID not specified.");
}

// delete gift only if owned by this user
$stmt = $pdo->prepare("DELETE FROM gifts WHERE gift_id = ? AND owner_id = ?");
$stmt->execute([$gift_id, $userId]);

header("Location: my_wishlist.php");
exit;
