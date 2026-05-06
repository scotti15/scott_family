<?php
header('Content-Type: application/json');
require_once "../../../../config/db.php";

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM dart_throws
    WHERE aimed_ring = 'T'
    AND aimed_value = 20
");
$stmt->execute();
$totalT20 = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM dart_throws
    WHERE aimed_ring = 'T'
    AND aimed_value = 20
    AND hit_score = 20
");
$stmt->execute();
$t20Hits = $stmt->fetchColumn();

$s20_when_t20_pct = null;
$hasData = false;

if ($totalT20 > 0) {
    $s20_when_t20_pct = ($t20Hits / $totalT20) * 100;
    $s20_when_t20_pct = round($s20_when_t20_pct, 2); // 1 decimal place
    $hasData = true;
}

echo json_encode([
    "s20_when_t20_pct" => $s20_when_t20_pct,
    "s20_when_t20_has_data" => $hasData
]);