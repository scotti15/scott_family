<?php
require_once "../../config/db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../../includes/header.php";
include "../../includes/navbar.php";

// User access guard
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'user';
if (!$user_id || $role !== 'admin') {
    echo "<p>Access denied.</p>";
    include "../../includes/footer.php";
    exit();
}

// Fetch active locations for Parent dropdown
$stmt = $pdo->prepare("SELECT location_id, name FROM locations WHERE active=1 ORDER BY display_order, name");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../cleaning_schedule/cleaning_schedule.css">


<h1>Add Location</h1>

<form method="post" action="save_location.php" class="compact-form">

    <!-- General Info -->
    <div class="form-section">
        <div class="form-row">
            <label for="locationName">Name:</label>
            <input type="text" name="locationName" id="locationName" required>
        </div>

        <div class="form-row">
            <label for="parentLocation">Parent:</label>
            <select name="parentLocation" id="parentLocation">
                <option value="">-- None --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= htmlspecialchars($loc['location_id']) ?>"><?= htmlspecialchars($loc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="locationType">Type:</label>
            <select name="locationType" id="locationType">
                <option value="room">Room</option>
                <option value="object">Object</option>
                <option value="area">Area</option>
            </select>
        </div>
    </div>

    <!-- Cleaning Schedule -->
    <div class="form-section">
        <div class="form-row checkbox-row">
            <input type="checkbox" id="addToSchedule" name="addToSchedule">
            <label for="addToSchedule">Add to Cleaning Schedule</label>
        </div>

        <div class="form-row">
            <label for="frequency">Frequency:</label>
            <select id="frequency" name="frequency" disabled>
                <option value="">-- Select --</option>
                <option value="1">Daily</option>
                <option value="2">Weekly</option>
                <option value="3">Monthly</option>
                <option value="4">Quarterly</option>
            </select>
        </div>
    </div>

    <!-- Other Options -->
    <div class="form-section options-row">
        <div class="form-row small">
            <label for="displayOrder">Order:</label>
            <input type="number" name="displayOrder" id="displayOrder" value="0">
        </div>

        <div class="form-row checkbox-row small">
            <input type="checkbox" id="active" name="active" checked>
            <label for="active">Active</label>
        </div>
    </div>

    <div class="form-footer">
        <button type="submit">Save</button>
    </div>
</form>

<script>
const addToScheduleCheckbox = document.getElementById('addToSchedule');
const frequencyDropdown = document.getElementById('frequency');

addToScheduleCheckbox.addEventListener('change', () => {
    frequencyDropdown.disabled = !addToScheduleCheckbox.checked;
    if (!addToScheduleCheckbox.checked) frequencyDropdown.value = '';
});
</script>

<?php include "../../includes/footer.php"; ?>