<?php
// ---------------------------------
// Standard includes
// ---------------------------------
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../includes/header.php";
include "../../includes/navbar.php";

// ---------------------------------
// User info
// ---------------------------------
$user_id = $_SESSION["user_id"] ?? 0;
$role = $_SESSION["role"] ?? "user";
$isAdmin = $role === "admin";

// Basic access guard
if (!$user_id) {
    echo "<p>Please log in to use the cleaning schedule.</p>";
    include "../../includes/footer.php";
    exit();
}

// Fetch all locations for parent dropdown
$stmt = $pdo->query("SELECT location_id, name FROM locations ORDER BY display_order, name");
$allLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<title>Location Management</title>
<link rel="stylesheet" href="cleaning_schedule.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<h2>Location Management</h2>

<!-- ===== Add / Edit Row ===== -->
<div class="container my-3">
<div class="row g-2 align-items-end">

  <!-- Name -->
  <div class="col-md-3">
    <label class="form-label">Name</label>
    <input type="text" id="name" class="form-control">
  </div>

  <!-- Parent -->
  <div class="col-md-2">
    <label class="form-label">Parent</label>
    <select id="parent_id" class="form-select"></select>
  </div>

  <!-- Order -->
  <div class="col-md-1">
    <label class="form-label">Order</label>
    <input type="number" id="display_order" class="form-control" value="0">
  </div>

  <!-- Active -->
  <div class="col-md-1">
    <div class="form-check mt-4">
      <input type="checkbox" id="active" class="form-check-input" checked>
      <label class="form-check-label">Active</label>
    </div>
  </div>

  <!-- Cleaning Task -->
  <div class="col-md-2">
    <div class="form-check mt-4">
      <input type="checkbox" id="cleanable" class="form-check-input">
      <label class="form-check-label">Cleaning</label>
    </div>
  </div>

  <!-- Frequency -->
  <div class="col-md-2">
    <label class="form-label">Frequency</label>
    <select id="frequency_id" class="form-select">
    <option value="">No frequency</option>
      <option value="1">Daily</option>
      <option value="2">Weekly</option>
      <option value="3">Monthly</option>
      <option value="4">Quarterly</option>
    </select>
  </div>
  <!-- Schedule -->
<div class="col-md-2">
  <label class="form-label">Schedule</label>
  <select id="schedule" class="form-select" disabled>
    <option value="">-- Select --</option>
  </select>
</div>
  <input type="hidden" id="location_id" value="">
  <!-- Buttons -->
<!-- Buttons -->
<div class="col-md-2 d-flex gap-2">
  <button type="button" id="saveBtn" class="btn btn-success flex-fill">Save</button>
  <button type="button" id="cancelEdit" class="btn btn-secondary flex-fill">Cancel</button>
</div>
</div>
</div>

<!-- ===== DataTable ===== -->
<div class="data-section">
  <table id="locationsTable" class="display">
    <thead>
      <tr>
        <th>Parent</th>
        <th>Name</th>
        <th>Frequency</th>
        <th>Schedule</th>
        <th>Order</th>
        <th>Active</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- ===== Scripts ===== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="locations_manage.js"></script>

<?php include "../../includes/footer.php"; ?>