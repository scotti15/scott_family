<?php
// Start session if needed for future authentication features
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">

</head>
<body>

<!-- Navigation Bar -->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    
    <!-- Left: Brand title -->
    <a class="navbar-brand" href="#">Inventory System</a>
    
    <!-- Center: Menu items -->
    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

    <!-- Toggler for mobile view -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Centered nav links -->
    <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'add_inventory.php') ? 'active' : '' ?>" href="add_inventory.php">Add Inventory</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'add_item.php') ? 'active' : '' ?>" href="add_item.php">Add Items</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'add_rooms.php') ? 'active' : '' ?>" href="add_room.php">Add Rooms</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'add_location.php') ? 'active' : '' ?>" href="add_location.php">Add Locations</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($current_page == 'add_category.php') ? 'active' : '' ?>" href="add_category.php">Add Category</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

    </div>

  </div>
</nav>
<div class="container">
