<?php
require_once '../../config/db.php';

$listID = $_POST['ListID'] ?? 0;
$itemID = $_POST['ItemID'] ?? 0;
$brandID = $_POST['BrandID'] ?? 0;
$placeID = $_POST['PlaceID'] ?? 0;
$unitID = $_POST['UnitID'] ?? 0;
$price = $_POST['Price'] ?? 0;
$amount = $_POST['Amount'] ?? 0;
$comments = $_POST['Comments'] ?? '';

$response = ['success'=>false];

if($listID && $itemID && $brandID && $placeID && $unitID){
    $stmt = $pdo->prepare("UPDATE shopping_list SET ItemID=?, BrandID=?, PlaceID=?, UnitID=?, Price=?, Amount=?, Comments=? WHERE ListID=?");
    if($stmt->execute([$itemID,$brandID,$placeID,$unitID,$price,$amount,$comments,$listID])){
        $response['success'] = true;
    }
}

echo json_encode($response);
