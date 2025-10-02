<?php
require_once __DIR__ . '/../../config/db.php';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Menu Builder</title>

<link rel="stylesheet" href="../../style.css">
</head>
<body>
<div class="container">

<div class="controls">
  <label>
    <span>👤 User</span>
    <select id="userSelect"></select>
  </label>

  <label>
    <span>📅 Week Beginning</span>
    <input type="date" id="weekStart" value="<?= date('Y-m-d') ?>">
  </label>

  <button id="printBtn">🖨️ Print</button>
</div>


  <div class="builder">
    <!-- Left Panel: Scrollable Foods -->
    <div class="food-column">
      <h3>Foods</h3>
      <div>
        <input 
          type="text" 
          id="foodSearch" 
          placeholder="Search foods..." 
          style="width: 100%; padding: 5px; margin-bottom: 8px; border: 1px solid #888; border-radius: 4px;">
      </div>
      <div class="food-list" id="foodList"></div>
    </div>

    <!-- Right Panel: Two-week grids -->
    <div class="week-column">
      <div class="week-grid" id="weekGrid"></div>
    </div>
  </div>

</div>


<script src="js/menu_builder.js"></script>
</body>
</html>
