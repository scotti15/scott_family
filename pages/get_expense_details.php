<?php
require_once __DIR__ . '/../config/db.php';

$categoryId = $_GET['categoryId'] ?? null;
$month = $_GET['month'] ?? null;
$year = $_GET['year'] ?? null;
$userId = $_GET['userId'] ?? null;

if (!$categoryId || !$month || !$year || !is_numeric($categoryId)) {
    echo "<div class='alert alert-warning'>Invalid parameters</div>";
    exit;
}

// Convert month name to number
$monthNum = date('m', strtotime($month));

// Query with all joins from your main transactions page
$sql = "SELECT t.IDFinancialTransaction, t.Date, 
               p.PlaceName AS Place, 
               a.AccountName AS Account, 
               tt.TypeName AS Type, 
               pr.ProvinceCode AS Province, 
               c.CategoryName AS Category, 
               i.ItemName AS Item, 
               t.Tax, t.Quantity, t.Price, 
               u.UnitName AS Unit, 
               t.Comment
        FROM Transactions t
        LEFT JOIN Places p ON t.PlaceID = p.PlaceID
        LEFT JOIN Accounts a ON t.AccountID = a.AccountID
        LEFT JOIN TransactionTypes tt ON t.TypeID = tt.TypeID
        LEFT JOIN Provinces pr ON t.ProvinceID = pr.ProvinceID
        LEFT JOIN Categories c ON t.CategoryID = c.CategoryID
        LEFT JOIN Items i ON t.ItemID = i.ItemID
        LEFT JOIN Units u ON t.UnitID = u.UnitID
        WHERE t.CategoryID = :categoryId
          AND YEAR(t.Date) = :year
          AND MONTH(t.Date) = :month";

$params = [
    'categoryId' => $categoryId,
    'year' => $year,
    'month' => $monthNum
];

if ($userId) {
    $sql .= " AND t.UserID = :userId";
    $params['userId'] = $userId;
}

$sql .= " ORDER BY t.Date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<div class='alert alert-info'>No transactions found for this category in $month $year</div>";
    exit;
}

// Calculate totals for footer
$totalQuantity = 0;
$totalPrice = 0;
$totalTax = 0;
$totalOverall = 0;

echo "<h5>Details for {$rows[0]['Category']} — $month $year</h5>";
echo "<table id='expenseDetailsTable' class='table table-striped table-bordered table-sm'>";
echo "<thead>
        <tr>
            <th>ID</th> <!-- hidden by DataTables -->
            <th>Date</th>
            <th>Place</th>
            <th>Account</th>
            <th>Type</th>
            <th>Province</th>
            <th>Category</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Unit</th>
            <th>Price</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Comment</th>
        </tr>
      </thead>";
echo "<tbody>";

foreach ($rows as $r) {
    $total = ($r['Price'] * $r['Quantity']) + $r['Tax'];

    $totalQuantity += $r['Quantity'];
    $totalPrice += $r['Price'] * $r['Quantity'];
    $totalTax += $r['Tax'];
    $totalOverall += $total;

    echo "<tr>
            <td>{$r['IDFinancialTransaction']}</td>
            <td>{$r['Date']}</td>
            <td>" . htmlspecialchars($r['Place']) . "</td>
            <td>" . htmlspecialchars($r['Account']) . "</td>
            <td>" . htmlspecialchars($r['Type']) . "</td>
            <td>" . htmlspecialchars($r['Province']) . "</td>
            <td>" . htmlspecialchars($r['Category']) . "</td>
            <td>" . htmlspecialchars($r['Item']) . "</td>
            <td>{$r['Quantity']}</td>
            <td>" . htmlspecialchars($r['Unit']) . "</td>
            <td>$" . number_format($r['Price'], 2) . "</td>
            <td>$" . number_format($r['Tax'], 2) . "</td>
            <td>$" . number_format($total, 2) . "</td>
            <td>" . htmlspecialchars($r['Comment']) . "</td>
          </tr>";
}

echo "</tbody>";

// Optional footer totals row
echo "<tfoot>
        <tr>
            <th colspan='8'>Totals</th>
            <th>{$totalQuantity}</th>
            <th></th>
            <th>$" . number_format($totalPrice, 2) . "</th>
            <th>$" . number_format($totalTax, 2) . "</th>
            <th>$" . number_format($totalOverall, 2) . "</th>
            <th></th>
        </tr>
      </tfoot>";

echo "</table>";
