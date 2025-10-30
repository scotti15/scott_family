<?php
require_once '../config/db.php';
include '../includes/header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "Invalid ID.";
    exit;
}

// Fetch existing inventory record
$stmt = $pdo->prepare("
    SELECT * FROM inventory WHERE id = ?
");
$stmt->execute([$id]);
$inventory = $stmt->fetch();

if (!$inventory) {
    echo "Inventory record not found.";
    exit;
}

// Fetch dropdowns
$items = $pdo->query("SELECT id, item_name FROM items")->fetchAll();
$rooms = $pdo->query("SELECT id, name FROM rooms")->fetchAll();
$locations = $pdo->query("SELECT id, location_name FROM locations")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id     = $_POST['item_id'];
    $room_id     = $_POST['room_id'];
    $location_id = $_POST['location_id'] ?: null;
    $quantity    = $_POST['quantity'];
    $expiry_date = $_POST['expiry_date'] ?: null;

    $stmt = $pdo->prepare("
        UPDATE inventory 
        SET item_id = ?, room_id = ?, location_id = ?, quantity = ?, expiry_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$item_id, $room_id, $location_id, $quantity, $expiry_date, $id]);

    echo "<p style='color:green;'>Inventory updated.</p>";
    // Optionally redirect to view page
    // header('Location: view_items.php');
    // exit;
}
?>

<h2>Edit Inventory</h2>

<form method="post">
    <label>Item:</label>
    <select name="item_id" required>
        <?php foreach ($items as $item): ?>
            <option value="<?= $item['id'] ?>" <?= $item['id'] == $inventory['item_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($item['item_name']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Room:</label>
    <select name="room_id" required>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= $room['id'] ?>" <?= $room['id'] == $inventory['room_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($room['name']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Location (optional):</label>
    <select name="location_id">
        <option value="">None</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= $loc['id'] ?>" <?= $loc['id'] == $inventory['location_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc['location_name']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Quantity:</label>
    <input type="number" name="quantity" value="<?= $inventory['quantity'] ?>" min="1" required><br><br>

    <label>Expiry Date:</label>
    <input type="datetime-local" name="expiry_date"
        value="<?= $inventory['expiry_date'] ? date('Y-m-d\TH:i', strtotime($inventory['expiry_date'])) : '' ?>"><br><br>

    <button type="submit">Save Changes</button>
</form>
