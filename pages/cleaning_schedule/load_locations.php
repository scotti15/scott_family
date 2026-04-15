<?php
require_once "../../config/db.php";

$stmt = $pdo->query("
    SELECT
        l.location_id,
        l.name,
        p.name AS parent_name,
        l.display_order,
        l.active
    FROM locations l
    LEFT JOIN locations p ON l.parent_id = p.location_id
    ORDER BY COALESCE(p.name, l.name), l.name
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));