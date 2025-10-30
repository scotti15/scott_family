<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $stmt = $pdo->query("
            SELECT inv.id, i.item_name, r.name AS room_name, l.location_name,
                   inv.quantity, inv.entry_date, inv.expiry_date,
                   inv.item_id, inv.room_id, inv.location_id
            FROM inventory inv
            LEFT JOIN items i ON inv.item_id = i.id
            LEFT JOIN rooms r ON inv.room_id = r.id
            LEFT JOIN locations l ON inv.location_id = l.id
            ORDER BY inv.id DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['data' => $data]);
        break;

    case 'get_locations':
        $room_id = (int)$_POST['room_id'];
        $stmt = $pdo->prepare("SELECT id, location_name FROM locations WHERE room_id = ?");
        $stmt->execute([$room_id]);
        echo json_encode(['success' => true, 'locations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'add':
        $stmt = $pdo->prepare("
            INSERT INTO inventory (item_id, room_id, location_id, quantity, entry_date, expiry_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ok = $stmt->execute([
            (int)$_POST['item_id'],
            (int)$_POST['room_id'],
            (int)$_POST['location_id'],
            (int)$_POST['quantity'],
            $_POST['entry_date'] ?: null,
            $_POST['expiry_date'] ?: null
        ]);
        echo json_encode(['success' => $ok]);
        break;

    case 'edit':
        $stmt = $pdo->prepare("
            UPDATE inventory
            SET quantity = ?, expiry_date = ?,
                room_id = COALESCE(?, room_id),
                location_id = COALESCE(?, location_id)
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            (int)$_POST['quantity'],
            $_POST['expiry_date'] ?: null,
            isset($_POST['room_id']) ? (int)$_POST['room_id'] : null,
            isset($_POST['location_id']) ? (int)$_POST['location_id'] : null,
            (int)$_POST['id']
        ]);
        echo json_encode(['success' => $ok]);
        break;

    case 'move':
        $stmt = $pdo->prepare("
            UPDATE inventory
            SET room_id = ?, location_id = ?
            WHERE id = ?
        ");
        $ok = $stmt->execute([
            (int)$_POST['room_id'],
            (int)$_POST['location_id'],
            (int)$_POST['id']
        ]);
        echo json_encode(['success' => $ok]);
        break;

    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $ok = $stmt->execute([(int)$_POST['id']]);
        echo json_encode(['success' => $ok]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
