<?php
// -----------------------------
// Standard setup
// -----------------------------
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../includes/header.php";
include "../../includes/navbar.php";

$user_id = $_SESSION["user_id"] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Habit History</title>

    <!-- Page CSS -->
    <link rel="stylesheet" href="mini_habits.css">
</head>

<body>

<div class="container">

    <h2>Habit History</h2>

    <!-- =========================
         Controls
    ========================== -->
    <div class="history-controls">
        <label for="monthPicker">Select Month:</label>
        <input type="month" id="monthPicker">
    </div>

    <!-- =========================
         Grid container (empty for now)
    ========================== -->
    <div id="historyGrid">
        <!-- JS will populate this -->
        <p>Loading history...</p>
    </div>

</div>

<!-- Page JS -->
<script src="history.js"></script>

</body>
</html>