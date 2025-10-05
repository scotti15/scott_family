<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container mt-4">
  <h1>Gift Registry Dashboard</h1>
  <p>Welcome to the gift registry. Choose an option:</p>

  <a href="my_wishlist.php" class="btn btn-primary">My Wishlist</a>
  <a href="browse_wishlist.php" class="btn btn-secondary">Browse Wishlists</a>
  <a href="add_gift.php" class="btn btn-success">Add a Gift</a>
</div>

<?php require_once '../../includes/footer.php'; ?>
