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
?>

<link rel="stylesheet" href="cleaning_schedule.css">

<!-- ADD LOCATION BUTTON -->
<?php if ($isAdmin): ?>
    <div class="admin-actions" style="margin-bottom:15px;">
        <a href="locations_manage.php" class="btn btn-primary">Add Location</a>
    </div>
<?php endif; ?>
<div class="page-container">

    <h1>Cleaning Schedule</h1>

    <div class="cleaning-controls">
        <div class="control-group">
            <label for="frequency">Frequency</label>
            <select id="frequency">
                <option value="daily" selected>Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <!-- future: Monthly / Quarterly -->
            </select>
        </div>

        <div class="control-group">
            <label for="period">Month</label>
            <input type="month" id="period" value="<?= date('Y-m') ?>">
        </div>
        <div id="currentPeriodLabel" class="current-period-label"></div>
    </div>

    <div class="cleaning-grid-wrapper" style="display: flex; gap: 20px; align-items: flex-start;">
        <table class="cleaning-grid">
            <!-- JS will populate the grid -->
        </table>

        <div id="todayTasks" style="min-width: 200px; max-width: 250px;">
            <!-- JS will populate tasks here -->
        </div>
    </div>

</div>

<!-- Add Location Modal -->
<div id="addLocationModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add Location</h2>
      <span class="modal-close" id="closeAddLocationModal">&times;</span>
    </div>
    <div class="modal-body">
      <form id="addLocationForm">
        <div class="form-group">
          <label for="locationName">Name:</label>
          <input type="text" id="locationName" name="locationName" required>
        </div>

        <div class="form-group">
          <label for="parentLocation">Parent Location:</label>
          <select id="parentLocation" name="parentLocation">
            <option value="">-- None --</option>
            <?php
                // Fetch active locations from DB
                $stmt = $pdo->prepare("SELECT location_id, name FROM locations WHERE active = 1 ORDER BY display_order, name");
                $stmt->execute();
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($locations as $loc) {
                    echo '<option value="' . htmlspecialchars($loc['location_id']) . '">' . htmlspecialchars($loc['name']) . '</option>';
                }
                ?>
          </select>
        </div>

        <div class="form-group">
          <label for="locationType">Type:</label>
          <select id="locationType" name="locationType">
            <option value="room">Room</option>
            <option value="object">Object</option>
            <option value="area">Area</option>
          </select>
        </div>

        <div class="form-group">
          <input type="checkbox" id="addToSchedule" name="addToSchedule">
          <label for="addToSchedule">Add to Cleaning Schedule</label>
        </div>

        <div class="form-group">
          <label for="frequency">Frequency:</label>
          <select id="frequency" name="frequency" disabled>
            <option value="">-- Select --</option>
            <option value="1">Daily</option>
            <option value="2">Weekly</option>
            <option value="3">Monthly</option>
            <option value="4">Quarterly</option>
          </select>
        </div>

        <div class="form-group">
          <label for="displayOrder">Display Order:</label>
          <input type="number" id="displayOrder" name="displayOrder" value="0">
        </div>

        <div class="form-group">
          <input type="checkbox" id="active" name="active" checked>
          <label for="active">Active</label>
        </div>

      </form>
      <div id="todayTasks"></div>
    </div>
    <div class="modal-footer">
      <button type="button" id="saveLocationBtn">Save</button>
      <button type="button" id="cancelLocationBtn">Cancel</button>
    </div>
  </div>
</div>
<?php include "../../includes/footer.php"; ?>
<script src="cleaning_schedule.js"></script>