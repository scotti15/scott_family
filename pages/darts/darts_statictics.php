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
    echo "<p>Please log in to use the darts tracker.</p>";
    include "../../includes/footer.php";
    exit();
}
?>

<!-- ---------------------------------
     Stats Dashboard UI
---------------------------------- -->
<link rel="stylesheet" href="darts_statistics.css">

<div class="container" style="padding:20px;">
    <div class="filters">

        <label>
            Session:
            <select id="session-filter">
                <option value="all">All Sessions</option>
                <option value="last1">Last Session</option>
                <option value="last3">Last 3 Sessions</option>
                <option value="last5">Last 5 Sessions</option>
            </select>
        </label>

    </div>
    <div class="tabs-row">

<!-- Tabs (LEFT) -->
<div class="tabs">
    <button class="tab-btn active" data-tab="overview">Overview</button>
    <button class="tab-btn" data-tab="scoring">Scoring</button>
    <button class="tab-btn" data-tab="finishing">Finishing</button>
    <button class="tab-btn" data-tab="insights">Insights</button>
    <button class="tab-btn" data-tab="heatmaps">Heatmaps</button>
</div>

<!-- Key (RIGHT) -->
<div class="metric-key">
    <span>▼ lower is better</span>
    <span>▲ higher is better</span>
</div>

</div>
    <!-- Tab Contents -->

    <div id="overview" class="tab-content">

        <h2>Overview</h2>

        <!-- Summary Cards -->
        <div class="stats-grid">

            <div class="stat-card selectable" data-metric="3da">
                <h3>3-Dart Average</h3>
                <p id="stat-3da">--</p>
            </div>

            <div class="stat-card selectable" data-metric="dpl">
                <h3>Darts / Leg</h3>
                <p id="stat-dpl">--</p>
            </div>

            <div class="stat-card selectable" data-metric="doubleAttempts">
                <h3>
                    Double Attempts
                    <span class="metric-direction">▼</span>
                </h3>
                <p id="stat-doubleAttempts">--</p>
            </div>

            <div class="stat-card selectable" data-metric="games">
                <h3>Games Played</h3>
                <p id="stat-games">--</p>
            </div>

            <div class="stat-card selectable" data-metric="t20">
                <h3>T20 %</h3>
                <p id="stat-t20">--</p>
            </div>

            <div class="stat-card selectable" data-metric="scoring3da">
                <h3>Scoring 3DA</h3>
                <p id="scoring3da-value">--</p>
            </div>
        </div>

        <div class="chart-container" style="margin-top:30px;">
            <h3 id="chart-title">3-Dart Average Over Time</h3>
            <canvas id="chart-3da" height="100"></canvas>
        </div>
        <div style="margin: 15px 0;">
            <label>
                Target:
                <span id="target-value"></span>
            </label>
            <input type="range" id="target-slider" min="0" max="100" step="1" />
        </div>
        <div id="scoring" class="tab-content" style="display:none;">
            <h2>Scoring</h2>
            <p>This is the scoring stats page.</p>
        </div>

        <div id="finishing" class="tab-content" style="display:none;">
            <h2>Finishing</h2>
            <p>This is the finishing stats page.</p>
        </div>

        <div id="insights" class="tab-content" style="display:none;">
            <h2>Insights</h2>
            <p>This is the insights page.</p>
        </div>

        <div id="heatmaps" class="tab-content" style="display:none;">
            <h2>Heatmaps</h2>
            <p>This is the heatmaps page.</p>
        </div>
    </div>

</div>

<script>
document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {

        // Active button styling
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const tab = btn.dataset.tab;

        // Show/hide content
        document.querySelectorAll(".tab-content").forEach(el => {
            el.style.display = "none";
        });

        document.getElementById(tab).style.display = "block";
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="darts_statistics.js"></script>

<?php include "../../includes/footer.php"; ?>