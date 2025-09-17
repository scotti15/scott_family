<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM Transactions WHERE IDFinancialTransaction = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
}
