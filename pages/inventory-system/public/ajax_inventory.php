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

    // Inside ajax_inventory.php, in your main switch($action) block:
case 'move':
    $id                = (int)$_POST['id'];
    $new_room_id       = (int)$_POST['room_id'];
    $new_location_id   = (int)$_POST['location_id'];
    $move_qty          = (int)$_POST['move_quantity'];

    if ($move_qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Lock source row to prevent races
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$orig) {
            throw new Exception('Original inventory not found');
        }
        if ($move_qty > (int)$orig['quantity']) {
            throw new Exception('Not enough quantity to move');
        }

        // If destination is the same as source, nothing to do
        if ((int)$orig['room_id'] === $new_room_id && (int)$orig['location_id'] === $new_location_id) {
            $pdo->rollBack();
            echo json_encode(['success' => true, 'message' => 'No change (same destination)']);
            exit;
        }

        // Find/lock destination row with same item and same expiry_date,
        // treating NULL expiry as equal.
        $expiry = $orig['expiry_date'];
        if ($expiry === null || $expiry === '' || $expiry === '0000-00-00' || $expiry === '0000-00-00 00:00:00') {
            $stmt = $pdo->prepare("
                SELECT id FROM inventory
                WHERE item_id = ? AND room_id = ? AND location_id = ? AND expiry_date IS NULL
                FOR UPDATE
            ");
            $stmt->execute([$orig['item_id'], $new_room_id, $new_location_id]);
            $dest = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM inventory
                WHERE item_id = ? AND room_id = ? AND location_id = ? AND expiry_date = ?
                FOR UPDATE
            ");
            $stmt->execute([$orig['item_id'], $new_room_id, $new_location_id, $expiry]);
            $dest = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Reduce quantity at source
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$move_qty, $id]);

        if ($dest) {
            // Merge into existing destination row
            $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$move_qty, $dest['id']]);
        } else {
            // Create destination row (preserve entry/expiry from source)
            $stmt = $pdo->prepare("
                INSERT INTO inventory (item_id, room_id, location_id, quantity, entry_date, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orig['item_id'],
                $new_room_id,
                $new_location_id,
                $move_qty,
                $orig['entry_date'],
                ($expiry === null || $expiry === '' || $expiry === '0000-00-00' || $expiry === '0000-00-00 00:00:00') ? null : $expiry
            ]);
        }

        // Delete source row if it hit zero
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ? AND quantity <= 0");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;



    case 'delete':
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $ok = $stmt->execute([(int)$_POST['id']]);
        echo json_encode(['success' => $ok]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
