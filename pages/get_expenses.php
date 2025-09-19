<?php
require_once __DIR__ . '/../config/db.php';

// Get filters from query string
$user = isset($_GET['user']) && $_GET['user'] !== '' ? intval($_GET['user']) : null;
$categoryParam = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Handle multiple categories
$categories = [];
if ($categoryParam) {
    // Assume comma-separated values
    $categories = array_filter(array_map('intval', explode(',', $categoryParam)));
}

// Build SQL
$sql = "SELECT MONTH(t.Date) AS month, t.CategoryID, c.CategoryName, 
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
    // Build placeholders for IN clause
    $inPlaceholders = [];
    foreach ($categories as $i => $catId) {
        $key = ":cat$i";
        $inPlaceholders[] = $key;
        $params[$key] = $catId;
    }
    $sql .= " AND t.CategoryID IN (" . implode(',', $inPlaceholders) . ")";
}

$sql .= " GROUP BY MONTH(t.Date), t.CategoryID
          ORDER BY MONTH(t.Date), t.CategoryID";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data for stacked chart
$data = [];
$categoryNames = [];

foreach($results as $row){
    $month = date('F', mktime(0,0,0,$row['month'],1));
    $catName = $row['CategoryName'] ?? 'Unknown';

    if (!isset($data[$month])) {
        $data[$month] = [];
    }
    $data[$month][$catName] = floatval($row['total']);
    $categoryNames[$catName] = true;
}

// Prepare final JSON structure
$finalData = [
    'months' => array_keys($data),
    'categories' => array_keys($categoryNames),
    'values' => []
];

foreach ($data as $month => $cats) {
    $row = [];
    foreach ($finalData['categories'] as $cat) {
        $row[] = $cats[$cat] ?? 0;
    }
    $finalData['values'][] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($finalData);
