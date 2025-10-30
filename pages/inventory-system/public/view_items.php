<?php
require_once '../config/db.php';
include '../includes/header.php';

// Turn on error reporting (dev only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- FETCH FILTER OPTIONS ---
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();
$locations = $pdo->query("SELECT id, location_name FROM locations ORDER BY location_name")->fetchAll();

// --- HANDLE FILTERS ---
$room_id = $_GET['room_id'] ?? '';
$location_id = $_GET['location_id'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

// Filter: Room
if ($room_id) {
    $where[] = 'inventory.room_id = ?';
    $params[] = $room_id;
}

// Filter: Location
if ($location_id) {
    $where[] = 'inventory.location_id = ?';
    $params[] = $location_id;
}

// Filter: Search
if ($search) {
    $where[] = 'items.item_name LIKE ?';
    $params[] = '%' . $search . '%';
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- FETCH INVENTORY DATA ---
$sql = "
SELECT 
    inventory.id,
    items.item_name,
    rooms.name AS room,
    locations.location_name,
    inventory.quantity,
    inventory.entry_date,
    inventory.expiry_date
FROM inventory
JOIN items ON inventory.item_id = items.id
JOIN rooms ON inventory.room_id = rooms.id
LEFT JOIN locations ON inventory.location_id = locations.id
$whereClause
ORDER BY inventory.entry_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<h2>Inventory List</h2>

<!-- FILTER FORM -->
<form method="get" style="margin-bottom: 20px;">
    <label>Room:</label>
    <select name="room_id">
        <option value="">All</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= $room['id'] ?>" <?= $room_id == $room['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($room['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Location:</label>
    <select name="location_id">
        <option value="">All</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= $loc['id'] ?>" <?= $location_id == $loc['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc['location_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Search Item:</label>
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Item name">

    <button type="submit">Filter</button>
    <a href="view_items.php">Reset</a>
</form>

<!-- RESULTS TABLE -->
<table border="1" cellpadding="6">
    <tr>
        <th>Item</th>
        <th>Room</th>
        <th>Location</th>
        <th>Quantity</th>
        <th>Entry Date</th>
        <th>Expiry Date</th>
        <th>Actions</th>

    </tr>

    <?php if (count($results) > 0): ?>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['item_name']) ?></td>
                <td><?= htmlspecialchars($row['room']) ?></td>
                <td><?= htmlspecialchars($row['location_name'] ?? '—') ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['entry_date'] ?></td>
                <td><?= $row['expiry_date'] ?? '—' ?></td>
                <td><a href="edit_inventory.php?id=<?= $row['id'] ?>">Edit</a> |
                    <a href="delete_inventory.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
                </td>

            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6">No inventory found for the selected filters.</td></tr>
    <?php endif; ?>
</table>
