<?php
require_once '../../config/db.php';

// Mapping of exact target coordinates
$targetCoords = [
    // Triples
    'T1' => ['x'=>0,'y'=>103],    'T2'=>['x'=>106,'y'=>75],    'T3'=>['x'=>125,'y'=>43],
    'T4' => ['x'=>103,'y'=>0],    'T5'=>['x'=>125,'y'=>-43],   'T6'=>['x'=>106,'y'=>-75],
    'T7' => ['x'=>0,'y'=>-103],   'T8'=>['x'=>-106,'y'=>-75],  'T9'=>['x'=>-125,'y'=>-43],
    'T10'=>['x'=>-103,'y'=>0],    'T11'=>['x'=>-125,'y'=>43],  'T12'=>['x'=>-106,'y'=>75],
    'T13'=>['x'=>0,'y'=>103],     'T14'=>['x'=>106,'y'=>75],   'T15'=>['x'=>125,'y'=>43],
    'T16'=>['x'=>103,'y'=>0],     'T17'=>['x'=>125,'y'=>-43],  'T18'=>['x'=>106,'y'=>-75],
    'T19'=>['x'=>0,'y'=>-103],    'T20'=>['x'=>-106,'y'=>-75],

    // Doubles
    'D1' => ['x'=>0,'y'=>170],    'D2'=>['x'=>106,'y'=>125.6], 'D3'=>['x'=>125,'y'=>73],
    'D4' => ['x'=>140,'y'=>0],    'D5'=>['x'=>125,'y'=>-73],   'D6'=>['x'=>106,'y'=>-126],
    'D7' => ['x'=>0,'y'=>-170],   'D8'=>['x'=>-106,'y'=>-126], 'D9'=>['x'=>-125,'y'=>-73],
    'D10'=>['x'=>-140,'y'=>0],    'D11'=>['x'=>-125,'y'=>73],  'D12'=>['x'=>-106,'y'=>126],
    'D13'=>['x'=>0,'y'=>170],     'D14'=>['x'=>106,'y'=>125.6],'D15'=>['x'=>125,'y'=>73],
    'D16'=>['x'=>140,'y'=>0],     'D17'=>['x'=>125,'y'=>-73],  'D18'=>['x'=>106,'y'=>-126],
    'D19'=>['x'=>0,'y'=>-170],    'D20'=>['x'=>-106,'y'=>-126],

    // Singles
    'S1' => ['x'=>0,'y'=>140],    'S2'=>['x'=>106,'y'=>125.6], 'S3'=>['x'=>125,'y'=>73],
    'S4' => ['x'=>140,'y'=>0],    'S5'=>['x'=>125,'y'=>-73],   'S6'=>['x'=>106,'y'=>-126],
    'S7' => ['x'=>0,'y'=>-140],   'S8'=>['x'=>-106,'y'=>-126], 'S9'=>['x'=>-125,'y'=>-73],
    'S10'=>['x'=>-140,'y'=>0],    'S11'=>['x'=>-125,'y'=>73],  'S12'=>['x'=>-106,'y'=>126],
    'S13'=>['x'=>0,'y'=>140],     'S14'=>['x'=>106,'y'=>125.6],'S15'=>['x'=>125,'y'=>73],
    'S16'=>['x'=>140,'y'=>0],     'S17'=>['x'=>125,'y'=>-73],  'S18'=>['x'=>106,'y'=>-126],
    'S19'=>['x'=>0,'y'=>-140],    'S20'=>['x'=>-106,'y'=>-126],

    // Bulls
    'S25'=>['x'=>0,'y'=>0],
    'D25'=>['x'=>0,'y'=>0],
];

// Prepare queries
$stmt = $pdo->query("
    SELECT throw_id, x, y, aimed_ring, aimed_value, throw_type
    FROM dart_throws
    WHERE aimed_value IS NOT NULL
      AND throw_type='normal'
");

$update = $pdo->prepare("
    UPDATE dart_throws
    SET miss_distance=?, miss_angle=?
    WHERE throw_id=?
");

foreach ($stmt as $row) {
    $throwId = $row['throw_id'];
    $x = $row['x'];
    $y = $row['y'];
    $ring = $row['aimed_ring'];
    $value = $row['aimed_value'];

    $key = $ring.$value;
    if (!isset($targetCoords[$key])) {
        echo "Missing coordinates for $key, skipping throw $throwId\n";
        continue;
    }

    $target = $targetCoords[$key];
    $dx = $x - $target['x'];
    $dy = $y - $target['y'];

    $miss_distance = sqrt($dx*$dx + $dy*$dy);
    $miss_angle = rad2deg(atan2($dy,$dx));

    $update->execute([$miss_distance,$miss_angle,$throwId]);
}

echo "Backfill complete for all normal throws.\n";
