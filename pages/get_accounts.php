<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
error_log("userId received in get_accounts.php: $userId"); // logs to PHP error log

if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT AccountID, AccountName FROM Accounts WHERE UserID = ? ORDER BY AccountName");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $accounts = [];
}

echo json_encode($accounts);
