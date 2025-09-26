<?php
require_once __DIR__ . '/../config/db.php';

// Get filters from query string
$user = isset($_GET['user']) && $_GET['user'] !== '' ? intval($_GET['user']) : null;
$categoryParam = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Handle multiple categories
$categories = [];
if ($categoryParam) {
    $categories = array_filter(array_map('intval', explode(',', $categoryParam)));
}

// Build SQL
$sql = "SELECT MONTH(t.Date) AS month,
               t.CategoryID,
               c.CategoryName,
               ABS(SUM(t.Price * t.Quantity + t.Tax)) AS total
        FROM transactions t
        LEFT JOIN categories c ON t.CategoryID = c.CategoryID
        WHERE YEAR(t.Date) = :year";

$params = ['year' => $year];

if ($user) {
    $sql .= " AND t.UserID = :user";
    $params['user'] = $user;
}

if (!empty($categories)) {
    $inPlaceholders = [];
    foreach ($categories as $i => $catId) {
        $key = ":cat$i";
        $inPlaceholders[] = $key;
        $params[$key] = $catId;
    }
    $sql .= " AND t.CategoryID IN (" . implode(',', $inPlaceholders) . ")";
}

$sql .= " GROUP BY MONTH(t.Date), t.CategoryID, c.CategoryName
          ORDER BY MONTH(t.Date), t.CategoryID";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data for stacked chart
$data = [];
$categoryIds = [];
$categoryNames = [];

foreach ($results as $row) {
    $month = date('F', mktime(0,0,0,$row['month'],1));
    $catId = $row['CategoryID'];
    $catName = $row['CategoryName'] ?? 'Unknown';

    if (!isset($data[$month])) {
        $data[$month] = [];
    }
    $data[$month][$catId] = floatval($row['total']);

    $categoryIds[$catId] = true;
    $categoryNames[$catId] = $catName;
}

// Prepare final JSON
$finalData = [
    'months' => array_keys($data),
    'categories' => array_map(fn($id) => [
        'id' => $id,
        'name' => $categoryNames[$id]
    ], array_keys($categoryIds)),
    'values' => []
];

foreach ($data as $month => $cats) {
    $row = [];
    foreach ($finalData['categories'] as $cat) {
        $catId = $cat['id'];
        $row[] = $cats[$catId] ?? 0;
    }
    $finalData['values'][] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($finalData);
