<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT PlaceID, PlaceName FROM places ORDER BY PlaceName ASC");
    $stmt->execute();
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($places);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
