<?php
require_once __DIR__ . '/../config/db.php';

// Get the transaction ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Invalid transaction ID");

// Fetch existing transaction
$stmt = $pdo->prepare("SELECT * FROM Transactions WHERE IDFinancialTransaction = ?");
$stmt->execute([$id]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tx) die("Transaction not found");

// Fetch options for dropdowns
$places = $pdo->query("SELECT PlaceID, PlaceName FROM Places ORDER BY PlaceName")->fetchAll(PDO::FETCH_ASSOC);
$accounts = $pdo->query("SELECT AccountID, AccountName FROM Accounts ORDER BY AccountName")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT TypeID, TypeName FROM TransactionTypes ORDER BY TypeName")->fetchAll(PDO::FETCH_ASSOC);
$provinces = $pdo->query("SELECT ProvinceID, ProvinceCode FROM Provinces ORDER BY ProvinceCode")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT ItemID, ItemName FROM Items ORDER BY ItemName")->fetchAll(PDO::FETCH_ASSOC);
$units = $pdo->query("SELECT UnitID, UnitName FROM Units ORDER BY UnitName")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE Transactions SET
            Date = ?, PlaceID = ?, AccountID = ?, TypeID = ?, ProvinceID = ?,
            CategoryID = ?, ItemID = ?, Tax = ?, Quantity = ?, Price = ?, UnitID = ?, Comment = ?
        WHERE IDFinancialTransaction = ?
    ");
    $stmt->execute([
        $_POST['Date'], $_POST['PlaceID'], $_POST['AccountID'], $_POST['TypeID'], $_POST['ProvinceID'],
        $_POST['CategoryID'], $_POST['ItemID'], $_POST['Tax'], $_POST['Quantity'], $_POST['Price'],
        $_POST['UnitID'], $_POST['Comment'], $id
    ]);
    header("Location: transactions.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Transaction</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Transaction</h2>
    <form method="post">
        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="Date" class="form-control" value="<?= htmlspecialchars($tx['Date']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Place</label>
            <select name="PlaceID" class="form-select" required>
                <?php foreach ($places as $p): ?>
                    <option value="<?= $p['PlaceID'] ?>" <?= $p['PlaceID'] == $tx['PlaceID'] ? 'selected' : '' ?>><?= htmlspecialchars($p['PlaceName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Account</label>
            <select name="AccountID" class="form-select" required>
                <?php foreach ($accounts as $a): ?>
                    <option value="<?= $a['AccountID'] ?>" <?= $a['AccountID'] == $tx['AccountID'] ? 'selected' : '' ?>><?= htmlspecialchars($a['AccountName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Type</label>
            <select name="TypeID" class="form-select" required>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['TypeID'] ?>" <?= $t['TypeID'] == $tx['TypeID'] ? 'selected' : '' ?>><?= htmlspecialchars($t['TypeName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Province</label>
            <select name="ProvinceID" class="form-select" required>
                <?php foreach ($provinces as $pr): ?>
                    <option value="<?= $pr['ProvinceID'] ?>" <?= $pr['ProvinceID'] == $tx['ProvinceID'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['ProvinceCode']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <select name="CategoryID" class="form-select" required>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>" <?= $c['CategoryID'] == $tx['CategoryID'] ? 'selected' : '' ?>><?= htmlspecialchars($c['CategoryName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Item</label>
            <select name="ItemID" class="form-select" required>
                <?php foreach ($items as $i): ?>
                    <option value="<?= $i['ItemID'] ?>" <?= $i['ItemID'] == $tx['ItemID'] ? 'selected' : '' ?>><?= htmlspecialchars($i['ItemName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Tax</label>
            <input type="number" step="0.01" name="Tax" class="form-control" value="<?= htmlspecialchars($tx['Tax']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" step="0.01" name="Quantity" class="form-control" value="<?= htmlspecialchars($tx['Quantity']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Price</label>
            <input type="number" step="0.01" name="Price" class="form-control" value="<?= htmlspecialchars($tx['Price']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Unit</label>
            <select name="UnitID" class="form-select" required>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['UnitID'] ?>" <?= $u['UnitID'] == $tx['UnitID'] ? 'selected' : '' ?>><?= htmlspecialchars($u['UnitName']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Comment</label>
            <input type="text" name="Comment" class="form-control" value="<?= htmlspecialchars($tx['Comment']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="transactions.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
