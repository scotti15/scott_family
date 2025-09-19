<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$userId = intval($_GET['userId'] ?? 0);
$data = [];

if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT a.AccountName, Cast(SUM(Price*Quantity + Tax)as decimal(10,2)) AS Balance
    FROM Transactions t
    Join accounts a on t.AccountID = a.AccountID
    and t.UserID = a.UserID
    WHERE Date <= NOW()
      AND t.UserID = ?
    GROUP BY t.UserID, a.AccountName;");
    $stmt->execute([$userId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($data);
