<?php
require_once '../config/db.php';

// If room_id is provided, return simple location list for dropdowns
if (isset($_GET['room_id']) && is_numeric($_GET['room_id'])) {
    $stmt = $pdo->prepare("SELECT id, location_name FROM locations WHERE room_id = ? ORDER BY location_name");
    $stmt->execute([$_GET['room_id']]);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($locations);
    exit;
}

// Otherwise, return full locations list for DataTables
$sql = "
    SELECT 
        l.id, 
        l.location_name, 
        l.description, 
        r.name AS room_name,
        r.id AS room_id
    FROM locations l
    LEFT JOIN rooms r ON l.room_id = r.id
    ORDER BY l.id DESC
";
$stmt = $pdo->query($sql);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add action buttons
$data = [];
foreach ($locations as $loc) {
    $loc['actions'] = '
        <button class="btn btn-sm btn-warning edit-btn">Edit</button>
        <button class="btn btn-sm btn-danger delete-btn">Delete</button>
    ';
    $data[] = $loc;
}

// DataTables expects { "data": [...] }
header('Content-Type: application/json');
echo json_encode(['data' => $data]);
