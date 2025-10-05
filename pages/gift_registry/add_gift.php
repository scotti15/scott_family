<?php
require_once '../../config/db.php';
//require_once '../../auth/login.php';
require_once '../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

// Fetch users for recipient dropdown
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id   = $_POST['recipient_id'] ?? null;
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $link           = trim($_POST['link'] ?? '');
    $price          = $_POST['price'] ?? null;
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
    $expiry_date    = $_POST['expiry_date'] ?: null;

    // Determine if giver-added or owner-added
    $giver_added = ($recipient_id != $_SESSION['user_id']) ? 1 : 0;

    if ($recipient_id && $title) {
        $stmt = $pdo->prepare("
            INSERT INTO gifts (owner_id, title, description, link, price, allow_multiple, giver_added, expiry_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $recipient_id, $title, $description, $link, $price, $allow_multiple, $giver_added, $expiry_date
        ]);
        echo "<div class='alert alert-success'>Gift added successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Please fill in required fields.</div>";
    }
}
?>

<div class="container">
    <h2>Add a Gift</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="recipient_id" class="form-label">Recipient</label>
            <select class="form-select" name="recipient_id" required>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" 
                        <?= ($user['id'] == $_SESSION['user_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" class="form-control" name="title" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Link</label>
            <input type="url" class="form-control" name="link">
        </div>

        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" name="price">
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_multiple" id="allow_multiple">
            <label class="form-check-label" for="allow_multiple">Allow Multiple</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Expiry Date</label>
            <input type="date" class="form-control" name="expiry_date">
        </div>

        <button type="submit" class="btn btn-success">Add Gift</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
