<?php
require_once '../../config/db.php';

$listID   = $_POST['ListID'] ?? 0;
$itemID   = $_POST['ItemID'] ?? null;
$brandID  = ($_POST['BrandID'] === "" ? null : ($_POST['BrandID'] ?? null));
$placeID  = ($_POST['PlaceID'] === "" ? null : ($_POST['PlaceID'] ?? null));
$unitID   = ($_POST['UnitID'] === "" ? null : ($_POST['UnitID'] ?? null));
$price    = ($_POST['Price'] === "" ? null : ($_POST['Price'] ?? null));
$amount   = ($_POST['Amount'] === "" ? null : ($_POST['Amount'] ?? null));
$expiry   = $_POST['ExpiryDate'] ?? null;      // ← FIXED KEY NAME
$comments = $_POST['Comments'] ?? '';

$response = ['success' => false];

// Require only ListID + ItemID
if ($listID && $itemID) {

    $stmt = $pdo->prepare("
        UPDATE shopping_list 
        SET 
            ItemID = ?, 
            BrandID = ?, 
            PlaceID = ?, 
            UnitID = ?, 
            Price = ?, 
            Amount = ?, 
            ExpiryDate = ?, 
            Comments = ?
        WHERE ListID = ?
    ");

    $ok = $stmt->execute([
        $itemID,
        $brandID,
        $placeID,
        $unitID,
        $price,
        $amount,
        $expiry,
        $comments,
        $listID
    ]);

    if ($ok) {
        $response['success'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
