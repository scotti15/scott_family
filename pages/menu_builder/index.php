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
