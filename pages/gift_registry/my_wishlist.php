<?php
// my_wishlist.php
require_once '../../config/db.php';
//require_once '../../auth/check_login.php'; // ensures session + user logged in
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// determine logged-in user id (support both session names)
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    header('Location: ../../auth/login.php');
    exit;
}

// fetch this user's gifts (only active ones)
$stmt = $pdo->prepare("
    SELECT gift_id, owner_id, title, description, link, price, allow_multiple, giver_added, expiry_date, created_at
    FROM gifts
    WHERE owner_id = ?
      AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE)
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h1>My Wishlist</h1>

  <a href="add_gift.php" class="btn btn-success mb-3">Add New Gift</a>

  <?php if (empty($gifts)): ?>
    <div class="alert alert-info">You haven't added any gifts yet.</div>
  <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Title</th>
          <th>Description</th>
          <th>Link</th>
          <th>Price</th>
          <th>Multiple Allowed?</th>
          <th>Expiry</th>
          <th>Added</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($gifts as $gift): 
            $title = htmlspecialchars($gift['title'] ?? '');
            $desc  = nl2br(htmlspecialchars($gift['description'] ?? ''));
            $link  = $gift['link'] ?? '';
            $price = isset($gift['price']) ? number_format($gift['price'], 2) : '';
            $allowMultiple = !empty($gift['allow_multiple']);
            $expiry = $gift['expiry_date'] ?: '-';
            $added  = isset($gift['created_at']) ? date('Y-m-d', strtotime($gift['created_at'])) : '-';
            $gid = $gift['gift_id'];
        ?>
          <tr>
            <td><?= $title ?></td>
            <td><?= $desc ?></td>
            <td>
              <?php if ($link): ?>
                <a href="<?= htmlspecialchars($link) ?>" target="_blank">View</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td><?= $price ?: '-' ?></td>
            <td><?= $allowMultiple ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($expiry) ?></td>
            <td><?= $added ?></td>
            <td>
              <a href="edit_gift.php?gift_id=<?= $gid ?>" class="btn btn-sm btn-warning">Edit</a>
              <a href="delete_gift.php?gift_id=<?= $gid ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Delete this gift?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
