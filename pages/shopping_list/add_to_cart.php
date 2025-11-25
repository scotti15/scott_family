<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userID = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['ItemID', 'BrandID', 'PlaceID', 'UnitID', 'Price', 'Amount'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit;
    }
}

// Optional
$comments = $data['Comments'] ?? '';
// Only allow admin flag if user is admin
$isAdminItem = ($isAdmin && isset($data['IsAdminItem']) && $data['IsAdminItem'] == 1) ? 1 : 0;

// Expiry date: next Thursday
$today = new DateTime();
$today->modify('next thursday');
$expiryDate = $today->format('Y-m-d');

try {
    $sql = "INSERT INTO shopping_list 
            (UserID, ItemID, BrandID, PlaceID, UnitID, Price, Amount, Comments, IsAdminItem, ExpiryDate) 
            VALUES 
            (:UserID, :ItemID, :BrandID, :PlaceID, :UnitID, :Price, :Amount, :Comments, :IsAdminItem, :ExpiryDate)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':UserID' => $userID,
        ':ItemID' => $data['ItemID'],
        ':BrandID' => $data['BrandID'],
        ':PlaceID' => $data['PlaceID'],
        ':UnitID' => $data['UnitID'],
        ':Price' => $data['Price'],
        ':Amount' => $data['Amount'],
        ':Comments' => $comments,
        ':IsAdminItem' => $isAdminItem,
        ':ExpiryDate' => $expiryDate
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
