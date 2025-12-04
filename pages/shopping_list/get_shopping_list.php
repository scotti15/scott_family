<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userID = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');

$today = date('Y-m-d');

try {
    if ($isAdmin) {
        // Admin sees all non-expired items
        $sql = "SELECT sl.ListID, sl.Price, sl.Amount, sl.Comments, sl.IsAdminItem, 
                    sl.ExpiryDate, sl.IsBargain,
                    i.ItemName,
                    b.BrandName,
                    p.PlaceName,
                    u.UnitName,
                    u.UnitType,
                    u.ConversionToBase
                FROM shopping_list sl
                LEFT JOIN items i ON sl.ItemID = i.ItemID
                LEFT JOIN brands b ON sl.BrandID = b.BrandID
                LEFT JOIN places p ON sl.PlaceID = p.PlaceID
                LEFT JOIN units u ON sl.UnitID = u.UnitID
                WHERE sl.ExpiryDate >= :today
                ORDER BY sl.ExpiryDate DESC;
 ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':today' => $today]);
    } else {
        // Non-admin sees only non-admin, non-expired items
        $sql = "SELECT sl.ListID, sl.Price, sl.Amount, sl.Comments, sl.IsAdminItem,
                    sl.ExpiryDate, sl.IsBargain,
                    i.ItemName,
                    b.BrandName,
                    p.PlaceName,
                    u.UnitName,
                    u.UnitType,
                    u.ConversionToBase
                FROM shopping_list sl
                LEFT JOIN items i ON sl.ItemID = i.ItemID
                LEFT JOIN brands b ON sl.BrandID = b.BrandID
                LEFT JOIN places p ON sl.PlaceID = p.PlaceID
                LEFT JOIN units u ON sl.UnitID = u.UnitID
                WHERE sl.IsAdminItem = 0
                AND sl.ExpiryDate >= :today
                ORDER BY sl.ExpiryDate DESC;
 ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':today' => $today]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
