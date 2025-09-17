<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form values safely
    $id       = intval($_POST['IDFinancialTransaction'] ?? 0);
    $date     = $_POST['Date'] ?? null;
    $placeId  = intval($_POST['PlaceID'] ?? 0);
    $accountId= intval($_POST['AccountID'] ?? 0);
    $typeId   = intval($_POST['TypeID'] ?? 0);
    $provinceId = intval($_POST['ProvinceID'] ?? 0);
    $categoryId = intval($_POST['CategoryID'] ?? 0);
    $itemId   = intval($_POST['ItemID'] ?? 0);
    $tax      = $_POST['Tax'] ?? null;
    $quantity = $_POST['Quantity'] ?? null;
    $price    = $_POST['Price'] ?? null;
    $unitId   = intval($_POST['UnitID'] ?? 0);
    $comment  = $_POST['Comment'] ?? null;

    if ($id > 0) {
        try {
            // Update the transaction
            $stmt = $pdo->prepare("
                UPDATE Transactions
                SET Date = ?, PlaceID = ?, AccountID = ?, TypeID = ?, ProvinceID = ?,
                    CategoryID = ?, ItemID = ?, Tax = ?, Quantity = ?, Price = ?, UnitID = ?, Comment = ?
                WHERE IDFinancialTransaction = ?
            ");
            $success = $stmt->execute([
                $date, $placeId, $accountId, $typeId, $provinceId,
                $categoryId, $itemId, $tax, $quantity, $price, $unitId, $comment, $id
            ]);

            if ($success) {
                // Fetch the updated row with all joined info (for frontend refresh)
                $stmt = $pdo->prepare("
                    SELECT t.IDFinancialTransaction, t.Date,
                           p.PlaceName AS Place, a.AccountName AS Account, tt.TypeName AS Type,
                           pr.ProvinceCode AS Province, c.CategoryName AS Category,
                           i.ItemName AS Item, t.Tax, t.Quantity, t.Price,
                           u.UnitName AS Unit, t.Comment
                    FROM Transactions t
                    LEFT JOIN Places p ON t.PlaceID = p.PlaceID
                    LEFT JOIN Accounts a ON t.AccountID = a.AccountID
                    LEFT JOIN TransactionTypes tt ON t.TypeID = tt.TypeID
                    LEFT JOIN Provinces pr ON t.ProvinceID = pr.ProvinceID
                    LEFT JOIN Categories c ON t.CategoryID = c.CategoryID
                    LEFT JOIN Items i ON t.ItemID = i.ItemID
                    LEFT JOIN Units u ON t.UnitID = u.UnitID
                    WHERE t.IDFinancialTransaction = ?
                    LIMIT 1
                ");
                $stmt->execute([$id]);
                $updatedTx = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    "success" => true,
                    "updatedTx" => $updatedTx
                ]);
                exit;
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Update failed"
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
            exit;
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid transaction ID"
        ]);
        exit;
    }
}

echo json_encode([
    "success" => false,
    "message" => "Invalid request method"
]);
