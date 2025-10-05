<?php
require_once '../../config/db.php';
//require_once '../../auth/login.php';
require_once '../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

// Get gift info
$gift_id = $_GET['gift_id'] ?? null;
if (!$gift_id) {
    echo "<div class='alert alert-danger'>No gift selected.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM gifts WHERE gift_id = ?");
$stmt->execute([$gift_id]);
$gift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gift) {
    echo "<div class='alert alert-danger'>Gift not found.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Handle claim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity'] ?? 1);

    // Check if gift is already claimed (for non-multiples)
    if (!$gift['allow_multiple']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE gift_id = ?");
        $stmt->execute([$gift_id]);
        $already_claimed = $stmt->fetchColumn();

        if ($already_claimed > 0) {
            echo "<div class='alert alert-danger'>This gift is already claimed.</div>";
            require_once '../../includes/footer.php';
            exit;
        }
    }

    // Insert claim
    $stmt = $pdo->prepare("
        INSERT INTO claims (gift_id, claimer_id, quantity)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$gift_id, $_SESSION['user_id'], $quantity]);

    echo "<div class='alert alert-success'>You claimed this gift successfully!</div>";
}
?>

<div class="container">
    <h2>Claim Gift</h2>
    <p><strong>Gift:</strong> <?= htmlspecialchars($gift['title']) ?></p>
    <p><strong>Description:</strong> <?= htmlspecialchars($gift['description']) ?></p>
    <p><strong>Price:</strong> <?= htmlspecialchars($gift['price']) ?></p>

    <form method="POST">
        <?php if ($gift['allow_multiple']): ?>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" value="1" min="1" class="form-control">
            </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Confirm Claim</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
