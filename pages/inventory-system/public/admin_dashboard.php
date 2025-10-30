<?php
require_once '../config/db.php';
include '../includes/header.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
  admin_dashboard.php
  - One file with Edit + AJAX Delete for Items, Categories, Rooms, Locations
*/

/* =========== Utility =========== */
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/* =========== HANDLE POSTS (EDIT/UPDATE) =========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ITEMS
    if (isset($_POST['edit_item'])) {
        $stmt = $pdo->prepare("UPDATE items SET item_name = ?, description = ?, category = ?, cost = ? WHERE id = ?");
        $stmt->execute([
            $_POST['item_name'],
            $_POST['description'],
            $_POST['category'],
            $_POST['cost'],
            $_POST['id']
        ]);
        header("Location: admin_dashboard.php?success_item=1");
        exit;
    }

    // CATEGORIES
    if (isset($_POST['edit_category'])) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['id']]);
        header("Location: admin_dashboard.php?success_category=1");
        exit;
    }

    // ROOMS
    if (isset($_POST['edit_room'])) {
        $stmt = $pdo->prepare("UPDATE rooms SET name = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['id']]);
        header("Location: admin_dashboard.php?success_room=1");
        exit;
    }

    // LOCATIONS
    if (isset($_POST['edit_location'])) {
        $stmt = $pdo->prepare("UPDATE locations SET location_name = ?, description = ?, room_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['location_name'],
            $_POST['description'],
            $_POST['room_id'],
            $_POST['id']
        ]);
        header("Location: admin_dashboard.php?success_location=1");
        exit;
    }
}

/* =========== HANDLE DELETES (AJAX-aware) =========== */
if (isset($_GET['delete']) && isset($_GET['type']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $type = $_GET['type'];

    // whitelist mapping from type to table
    $tableMap = [
        'item' => 'items',
        'category' => 'categories',
        'room' => 'rooms',
        'location' => 'locations'
    ];

    if (isset($tableMap[$type])) {
        $table = $tableMap[$type];

        // Prepare and execute using a validated table name (can't param table name)
        $sql = "DELETE FROM `$table` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$id]);

        if (is_ajax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => (bool)$ok]);
            exit;
        } else {
            // Non-AJAX fallback: redirect with flag
            header("Location: admin_dashboard.php?deleted_{$type}=1");
            exit;
        }
    } else {
        if (is_ajax()) { echo json_encode(['success' => false, 'error' => 'invalid_type']); exit; }
        header("Location: admin_dashboard.php?error=invalid_type");
        exit;
    }
}

/* =========== FETCH DATA (after edits/deletes) =========== */
$items = $pdo->query("SELECT i.id, i.item_name, i.description, i.category, i.cost, c.name AS category_name 
                      FROM items i LEFT JOIN categories c ON i.category = c.id")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$locations = $pdo->query("SELECT l.id, l.location_name, l.description, l.room_id, r.name AS room_name 
                          FROM locations l LEFT JOIN rooms r ON l.room_id = r.id")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Admin Dashboard — Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      /* small fade transition for removed rows */
      tr.removing { opacity: 0; transition: opacity .45s ease; }
    </style>
</head>
<body class="container py-4">

<?php
// Alerts for non-AJAX actions
$alerts = [
    'success_item' => '✅ Item updated successfully.',
    'deleted_item' => '🗑️ Item deleted successfully.',
    'success_category' => '✅ Category updated successfully.',
    'deleted_category' => '🗑️ Category deleted successfully.',
    'success_room' => '✅ Room updated successfully.',
    'deleted_room' => '🗑️ Room deleted successfully.',
    'success_location' => '✅ Location updated successfully.',
    'deleted_location' => '🗑️ Location deleted successfully.'
];
foreach ($alerts as $key => $msg) {
    if (isset($_GET[$key])) {
        echo "<div class='alert alert-info'>$msg</div>";
    }
}
if (isset($_GET['error'])) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($_GET['error']) . "</div>";
}
?>

<h3>Items</h3>
<table class="table table-bordered" id="table-items">
    <thead>
        <tr>
            <th>Name</th><th>Description</th><th>Category</th><th>Cost</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr data-type="item" data-id="<?= $item['id'] ?>">
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['description']) ?></td>
                <td><?= htmlspecialchars($item['category_name']) ?></td>
                <td>$<?= number_format($item['cost'], 2) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editItemModal<?= $item['id'] ?>">Edit</button>
                    <a href="?delete=1&type=item&id=<?= $item['id'] ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                </td>
            </tr>

            <!-- Edit Item Modal -->
            <div class="modal fade" id="editItemModal<?= $item['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Item</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="edit_item" value="1">
                            <label>Name:</label>
                            <input type="text" name="item_name" class="form-control" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                            <label>Description:</label>
                            <textarea name="description" class="form-control"><?= htmlspecialchars($item['description']) ?></textarea>
                            <label>Category:</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $item['category'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Cost:</label>
                            <input type="number" step="0.01" name="cost" class="form-control" value="<?= $item['cost'] ?>">
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                            <button class="btn btn-success" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Categories</h3>
<table class="table table-bordered" id="table-categories">
    <thead><tr><th>Name</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr data-type="category" data-id="<?= $cat['id'] ?>">
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>">Edit</button>
                    <a href="?delete=1&type=category&id=<?= $cat['id'] ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                </td>
            </tr>

            <!-- Edit Category Modal -->
            <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Category</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="edit_category" value="1">
                            <label>Name:</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($cat['name']) ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                            <button class="btn btn-success" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Rooms</h3>
<table class="table table-bordered" id="table-rooms">
    <thead><tr><th>Name</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach ($rooms as $room): ?>
            <tr data-type="room" data-id="<?= $room['id'] ?>">
                <td><?= htmlspecialchars($room['name']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editRoomModal<?= $room['id'] ?>">Edit</button>
                    <a href="?delete=1&type=room&id=<?= $room['id'] ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                </td>
            </tr>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editRoomModal<?= $room['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Room</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $room['id'] ?>">
                            <input type="hidden" name="edit_room" value="1">
                            <label>Name:</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($room['name']) ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                            <button class="btn btn-success" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Locations</h3>
<table class="table table-bordered" id="table-locations">
    <thead><tr><th>Name</th><th>Description</th><th>Room</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach ($locations as $loc): ?>
            <tr data-type="location" data-id="<?= $loc['id'] ?>">
                <td><?= htmlspecialchars($loc['location_name']) ?></td>
                <td><?= htmlspecialchars($loc['description']) ?></td>
                <td><?= htmlspecialchars($loc['room_name']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editLocationModal<?= $loc['id'] ?>">Edit</button>
                    <a href="?delete=1&type=location&id=<?= $loc['id'] ?>" class="btn btn-sm btn-danger btn-delete">Delete</a>
                </td>
            </tr>

            <!-- Edit Location Modal -->
            <div class="modal fade" id="editLocationModal<?= $loc['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Location</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                            <input type="hidden" name="edit_location" value="1">
                            <label>Name:</label>
                            <input type="text" name="location_name" class="form-control" value="<?= htmlspecialchars($loc['location_name']) ?>" required>
                            <label>Description:</label>
                            <textarea name="description" class="form-control"><?= htmlspecialchars($loc['description']) ?></textarea>
                            <label>Room:</label>
                            <select name="room_id" class="form-select">
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $loc['room_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                            <button class="btn btn-success" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/*
  AJAX delete handler (progressive enhancement)
  - Buttons are normal links so non-JS users still work.
  - JS intercepts clicks on .btn-delete to do an AJAX GET (with X-Requested-With header)
  - On success: fade the row out and remove the modal element that belongs to that id (if any).
*/
document.addEventListener('DOMContentLoaded', function () {
    function handleDeleteClick(e) {
        // keep native behaviour for non-left clicks / modifier keys
        if (e.which && e.which !== 1) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        e.preventDefault();
        const link = e.currentTarget;
        if (!confirm('Are you sure you want to delete this?')) return;

        const href = link.getAttribute('href');
        // determine row and id/type from DOM attributes for nicer removal
        const row = link.closest('tr');
        const type = row ? row.getAttribute('data-type') : null;
        const id = row ? row.getAttribute('data-id') : null;

        fetch(href, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.success) {
                if (row) {
                    // animate fade and remove
                    row.classList.add('removing');
                    setTimeout(function () {
                        // Also remove any associated modal for that id
                        if (type && id) {
                            const modalId = {
                                'item': '#editItemModal' + id,
                                'category': '#editCategoryModal' + id,
                                'room': '#editRoomModal' + id,
                                'location': '#editLocationModal' + id
                            }[type];
                            if (modalId) {
                                const modalEl = document.querySelector(modalId);
                                if (modalEl) modalEl.remove();
                            }
                        }
                        row.remove();
                    }, 450);
                } else {
                    // fallback: reload if no row found
                    location.reload();
                }
            } else {
                alert('Delete failed. Try again.');
            }
        }).catch(function () {
            alert('Error deleting. Try again.');
        });
    }

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', handleDeleteClick);
    });
});
</script>

</body>
</html>
