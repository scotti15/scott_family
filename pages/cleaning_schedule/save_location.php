<?php
require_once "../../config/db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only admins can add locations
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'user';
if (!$user_id || $role !== 'admin') {
    http_response_code(403);
    echo "Access denied";
    exit();
}

// Collect and sanitize form input
$locationName   = trim($_POST['locationName'] ?? '');
$parentLocation = (int)($_POST['parentLocation'] ?? 0);
$locationType   = $_POST['locationType'] ?? 'room';
$addToSchedule  = isset($_POST['addToSchedule']) ? 1 : 0;
$frequency      = $addToSchedule ? (int)($_POST['frequency'] ?? 0) : null;
$displayOrder   = (int)($_POST['displayOrder'] ?? 0);
$active         = isset($_POST['active']) ? 1 : 0;

// Validate required fields
if (!$locationName) {
    echo "Name is required.";
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO locations 
            (name, parent_id, location_type, display_order, active) 
        VALUES 
            (:name, :parent_id, :location_type, :display_order, :active)
    ");
    $stmt->execute([
        ':name' => $locationName,
        ':parent_id' => $parentLocation ?: null,
        ':location_type' => $locationType,
        ':display_order' => $displayOrder,
        ':active' => $active
    ]);

    // Get the inserted location ID
    $locationId = $pdo->lastInsertId();

    // If part of cleaning schedule, insert task
    if ($addToSchedule && $frequency) {
        $stmt2 = $pdo->prepare("
            INSERT INTO cleaning_tasks (location_id, task_name, frequency_id, cleanable)
            VALUES (:location_id, :task_name, :frequency_id, 1)
        ");
        $stmt2->execute([
            ':location_id' => $locationId,
            ':task_name' => $locationName,  // using location name as task name
            ':frequency_id' => $frequency
        ]);
    }

    // Redirect back to the add_inventory page or show success
    header("Location: add_location.php?success=1");
    exit();

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}