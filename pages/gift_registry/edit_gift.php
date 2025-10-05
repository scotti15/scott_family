<?php
require_once '../../config/db.php';
//require_once '../../auth/check_login.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: ../../auth/login.php");
    exit;
}

$gift_id = $_GET['gift_id'] ?? null;
if (!$gift_id) {
    die("Gift ID not specified.");
}

// fetch gift info
$stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ? AND owner_id = ?");
$stmt->execute([$gift_id, $userId]);
$gift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gift) {
    die("Gift not found or not owned by you.");
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $link = $_POST['link'] ?? '';
    $price = $_POST['price'] ?: null;
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $expiry_date = $_POST['expiry_date'] ?: null;

    $stmt = $pdo->prepare("
        UPDATE gifts SET 
            title = ?, description = ?, link = ?, price = ?, allow_multiple = ?, expiry_date = ?
        WHERE gift_id = ? AND owner_id = ?
    ");
    $stmt->execute([
        $title, $description, $link, $price, $allow_multiple, $expiry_date, $gift_id, $userId
    ]);

    echo "<div class='alert alert-success'>Gift updated successfully.</div>";

    // refresh gift data
    $stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ? AND owner_id = ?");
    $stmt->execute([$gift_id, $userId]);
    $gift = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
    <h2>Edit Gift</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($gift['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"><?= htmlspecialchars($gift['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Link</label>
            <input type="url" class="form-control" name="link" value="<?= htmlspecialchars($gift['link']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" name="price" value="<?= htmlspecialchars($gift['price']) ?>">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="allow_multiple" id="allow_multiple"
                <?= $gift['allow_multiple'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="allow_multiple">Allow Multiple</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Expiry Date</label>
            <input type="date" class="form-control" name="expiry_date" value="<?= htmlspecialchars($gift['expiry_date']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="my_wishlist.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
