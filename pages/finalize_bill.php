<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Read the JSON POST body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

error_log(print_r($data, true));

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Prepare your insert statement
$stmt = $pdo->prepare("
    INSERT INTO transactions 
    (UserID, Date, PlaceID, AccountID, TypeID, ProvinceID, CategoryID, ItemID, Tax, Quantity, Price, UnitID, Comment)
    VALUES
    (:user, :date, :place, :account, :type, :province, :category, :item, :tax, :quantity, :price, :unit, :comment)
");

try {
    foreach ($data as $line) {

        $typeId = (int)($line['type'] ?? 0);
        $price  = (float)($line['price'] ?? 0);
        $tax    = (float)($line['tax'] ?? 0);
    
        // Replace DB triggers
        if ($typeId === 1) {
            $price = -abs($price);
            $tax   = -abs($tax);
        } else {
            $price = abs($price);
            $tax   = abs($tax);
        }
    
        $stmt->execute([
            ':user'     => $line['user'] ?? null,
            ':date'     => $line['date'] ?? null,
            ':place'    => $line['place'] ?? null,
            ':account'  => $line['account'] ?? null,
            ':type'     => $typeId,
            ':province' => $line['province'] ?? null,
            ':category' => $line['detailCategory'] ?? null,
            ':item'     => $line['item'] ?? null,
            ':tax'      => $tax,
            ':quantity' => $line['quantity'] ?? 0,
            ':price'    => $price,
            ':unit'     => $line['unit'] ?? null,
            ':comment'  => $line['comment'] ?? ''
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Bill saved successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

