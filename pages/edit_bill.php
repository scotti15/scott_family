<?php
require_once __DIR__ . '/../config/db.php';

// Get the Bill ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Invalid bill ID");

// Fetch existing bill
$stmt = $pdo->prepare("SELECT * FROM CurrentBill WHERE IDBill = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bill) die("Bill not found");

// Fetch dropdown options
$categories = $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT ItemID, ItemName FROM Items ORDER BY ItemName")->fetchAll(PDO::FETCH_ASSOC);
$units = $pdo->query("SELECT UnitID, UnitName FROM Units ORDER BY UnitName")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE CurrentBill SET
            CategoryID = ?, ItemID = ?, Tax = ?, Quantity = ?, Price = ?, UnitID = ?, Comment = ?
        WHERE IDBill = ?
    ");
    $stmt->execute([
        $_POST['CategoryID'], $_POST['ItemID'], $_POST['Tax'],
        $_POST['Quantity'], $_POST['Price'], $_POST['UnitID'],
        $_POST['Comment'], $id
    ]);
    header("Location: current_bill.php"); // go back to your main bill page
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Bill</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Bill</h2>
    <form method="post">

        <div class="mb-3">
            <label>Category</label>
            <select name="CategoryID" class="form-select" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>" <?= $c['CategoryID'] == $bill['CategoryID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['CategoryName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Item</label>
            <select name="ItemID" class="form-select" required>
                <?php foreach ($items as $i): ?>
                    <option value="<?= $i['ItemID'] ?>" <?= $i['ItemID'] == $bill['ItemID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($i['ItemName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Tax</label>
            <input type="number" step="0.01" name="Tax" class="form-control" value="<?= htmlspecialchars($bill['Tax']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" step="0.01" name="Quantity" class="form-control" value="<?= htmlspecialchars($bill['Quantity']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Price</label>
            <input type="number" step="0.01" name="Price" class="form-control" value="<?= htmlspecialchars($bill['Price']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Unit</label>
            <select name="UnitID" class="form-select" required>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['UnitID'] ?>" <?= $u['UnitID'] == $bill['UnitID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['UnitName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Comment</label>
            <input type="text" name="Comment" class="form-control" value="<?= htmlspecialchars($bill['Comment']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="current_bill.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
