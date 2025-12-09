<?php
// Standard includes
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../../includes/header.php';
include '../../includes/navbar.php';

// User info
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'user';
$isAdmin = ($role === 'admin');


require_once 'rollover_daily_stats.php';

?>

<div class="container mt-4">
    <h2>Mini Habits</h2>

<?php
    // Fetch list of all users
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine which user's list is being viewed
    $view_user_id = $_GET['user_id'] ?? $user_id; // default: logged-in user
?>


<!-- User Selection -->
<div class="card p-3 mb-4">
    <label><strong>Viewing Habits For:</strong></label>
    <select id="userSelector" class="form-select" style="max-width: 300px;">
        <?php foreach ($users as $u): ?>
            <option value="<?= $u['id'] ?>" 
                <?= ($u['id'] == $view_user_id ? 'selected' : '') ?>>
                <?= htmlspecialchars($u['username']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<button id="showDeactivatedBtn" class="btn btn-outline-secondary mb-3">
    Show Deactivated Habits
</button>



    <!-- Add Habit Box -->
    <div class="card p-3 mb-4">
        <h4>Add a New Mini Habit</h4>

        <form id="newHabitForm" class="d-flex gap-2">
            <input 
                type="text" 
                id="newHabitName" 
                name="newHabitName"
                class="form-control" 
                placeholder="e.g. 10 minutes on a treadmill"
                required
            >
            <label>Daily Target:</label>
            <input type="number" name="daily_target" min="1" value="1" required>
            <button type="submit" class="btn btn-primary" id="addHabitBtn">Add</button>
        </form>

        <div id="habitMessage" class="mt-2 text-success"></div>
    </div>

    <div class="row">
        <!-- Left: To Do Today -->
        <div class="col-md-6">
            <h4>To Do Today</h4>
            <ul id="todoList" class="list-group"></ul>
        </div>

        <!-- Right: Completed Today -->
        <div class="col-md-6">
            <h4>Completed Today</h4>
            <ul id="completedList" class="list-group"></ul>
        </div>
    </div>
</div>



<!-- MODALS -->
<div class="modal fade" id="showDeactivatedModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Deactivated Habits</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <ul id="showDeactivatedList" class="list-group">
          <!-- Filled dynamically by JS -->
        </ul>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- Edit Habit Modal -->
<div class="modal fade" id="editHabitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Habit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="editHabitForm">
          <div class="mb-3">
            <label for="editHabitName" class="form-label">Habit Name</label>
            <input type="text" id="editHabitName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="editHabitTarget" class="form-label">Daily Target</label>
            <input type="number" id="editHabitTarget" class="form-control" min="1" required>
          </div>
          <input type="hidden" id="editHabitId">
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="saveEditHabitBtn" class="btn btn-primary">Save</button>
      </div>

    </div>
  </div>
</div>


<!-- JS file (we will fill it later) -->
<script src="mini_habits.js"></script>

<?php include '../../includes/footer.php'; ?>
