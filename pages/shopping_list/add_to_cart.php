<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
$pdo->query("SET SQL_BIG_SELECTS=1");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Not logged in']);
    exit;
}

$userID = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');

$data = json_decode(file_get_contents('php://input'), true);

// Always require ItemID
if (empty($data['ItemID'])) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'ItemID is required']);
    exit;
}

// Determine if item is a bargain
$isBargain = !empty($data['IsBargain']) ? 1 : 0;

// Optional fields for bargain items
$brandID = $data['BrandID'] ?? null;
$placeID = $data['PlaceID'] ?? null;
$unitID = $data['UnitID'] ?? null;
$price = $data['Price'] ?? null;
$amount = $data['Amount'] ?? null;

// Validation only for bargain items
if ($isBargain) {
    $required = ['BrandID','PlaceID','UnitID','Price','Amount'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>"$field is required for a bargain item"]);
            exit;
        }
    }
}

// Comments and admin flag
$comments = $data['Comments'] ?? '';
$isAdminItem = ($isAdmin && !empty($data['IsAdminItem'])) ? 1 : 0;

// ExpiryDate = next Wednesday
$daysUntilWednesday = (3 - date('N') + 7) % 7; // 1=Mon..7=Sun
$daysUntilWednesday = $daysUntilWednesday === 0 ? 7 : $daysUntilWednesday;
$expiryDate = date('Y-m-d', strtotime("+$daysUntilWednesday days"));

// Insert into shopping_list
$stmt = $pdo->prepare("
    INSERT INTO shopping_list
        (ItemID, BrandID, PlaceID, UnitID, Price, Amount, Comments, IsAdminItem, IsBargain, ExpiryDate)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $stmt->execute([
    $data['ItemID'],
    $brandID,
    $placeID,
    $unitID,
    $price,
    $amount,
    $comments,
    $isAdminItem,
    $isBargain,
    $expiryDate
]);


echo json_encode(['success'=>$success]);
