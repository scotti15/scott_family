<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

include '../../includes/header.php';
include '../../includes/navbar.php';


$user_id = (int)$_SESSION['user_id'];
?>
<link rel="stylesheet" href="mini_habits.css">

<h2>Mini Habit Log</h2>

<label>Month:</label>
<input type="month" id="monthPicker" value="<?= date('Y-m') ?>">

<!-- HEAT MAP -->
<div id="heatmap" class="heatmap mt-4"></div>


<table class="table table-sm mt-3">
    <thead>
        <tr>
            <th>Date</th>
            <th>Habit</th>
            <th>Completed</th>
            <th>Target</th>
            <th>%</th>
        </tr>
    </thead>
    <tbody id="logTableBody"></tbody>
</table>

<!-- Expose logged-in user ID to JS -->
<script>
    window.LOGGED_IN_USER_ID = <?= $user_id ?>;
</script>

<script src="mini_habits_log.js"></script>
