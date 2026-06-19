<?php
require_once "../../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../includes/header.php";
include "../../includes/navbar.php";

$user_id = $_SESSION["user_id"] ?? 0;
$role = $_SESSION["role"] ?? "user";

if (!$user_id) {
    echo "<p>Please log in to use the darts tracker.</p>";
    include "../../includes/footer.php";
    exit();
}
?>

<link rel="stylesheet" href="darts_statistics.css">

<div class="container" style="padding:20px;">

    <!-- FILTERS -->
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

    <!-- TABS -->
    <div class="tabs-row">

        <div class="tabs">
            <button class="tab-btn active" data-tab="overview">Overview</button>
            <button class="tab-btn" data-tab="scoring">Scoring</button>
            <button class="tab-btn" data-tab="finishing">Finishing</button>
            <button class="tab-btn" data-tab="insights">Insights</button>
            <button class="tab-btn" data-tab="heatmaps">Heatmaps</button>
        </div>

        <div class="metric-key">
            <span>▼ lower is better</span>
            <span>▲ higher is better</span>
        </div>

    </div>

    <!-- ========================= -->
    <!-- OVERVIEW TAB -->
    <!-- ========================= -->
    <div id="overview" class="tab-content">

        <h2>Overview</h2>

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
                <h3>Double Attempts ▼</h3>
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

    </div>

    <!-- ========================= -->
    <!-- SCORING TAB -->
    <!-- ========================= -->
    <div id="scoring" class="tab-content" style="display:none;">

        <h2>Scoring</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>S20 (when aiming T20) %</h3>
                <p id="stat-s20-t20">--</p>
            </div>
        </div>

        <div class="target-wheel-grid">

            <div class="stat-card">
                <h3 id="t20-wheel-title">T20 Wedge Distribution</h3>
                <div id="t20-distribution-wheel"></div>
            </div>

            <div class="legend-section">
                <div class="legend-item">
                    <span class="box dark-green"></span> Elite
                </div>

                <div class="legend-item">
                    <span class="box light-green"></span> Strong
                </div>

                <div class="legend-item">
                    <span class="box yellow"></span> Average
                </div>

                <div class="legend-item">
                    <span class="box light-red"></span> Needs Work
                </div>

                <div class="legend-item">
                    <span class="box red"></span> Major Leak
                </div>
            </div>

        </div>

    </div>

    <!-- ========================= -->
    <!-- FINISHING TAB -->
    <!-- ========================= -->
    <div id="finishing" class="tab-content" style="display:none;">

        <h2>Finishing</h2>

        <div class="stats-grid">

            <div class="stat-card">
                <h3>Pure Double %</h3>
                <p id="stat-pure-double">--</p>
            </div>

            <div class="stat-card">
                <h3>Gameplay Double %</h3>
                <p id="stat-gameplay-double">--</p>
            </div>

            <div class="stat-card">
                <h3>Setup S %</h3>
                <p id="stat-setup-s">--</p>
            </div>

            <div class="stat-card">
                <h3>Darts per Checkout A</h3>
                <p id="stat-dpc-a">--</p>
            </div>

            <div class="stat-card">
                <h3>Darts per Checkout B</h3>
                <p id="stat-dpc-b">--</p>
            </div>

        </div>

        <div class="target-wheel-grid">

            <div class="stat-card">
                <h3>Double Accuracy by Target</h3>
                <div id="double-target-wheel"></div>
            </div>

            <div class="legend-section">
                <div class="legend-item"><span class="box green"></span> ≥ 10%</div>
                <div class="legend-item"><span class="box yellow"></span> 5–9.99%</div>
                <div class="legend-item"><span class="box red"></span>
                    < 5%</div>
                </div>

            </div>

            <div class="target-wheel-grid">

                <div class="stat-card">
                    <h3>Setup Accuracy by Target</h3>
                    <div id="setup-target-wheel"></div>
                </div>

                <div class="legend-section">
                    <div class="legend-item"><span class="box green"></span> ≥ 35%</div>
                    <div class="legend-item"><span class="box yellow"></span> 20–34.99%</div>
                    <div class="legend-item"><span class="box red"></span>
                        < 20%</div>
                    </div>

                </div>

            </div>

            <!-- ========================= -->
            <!-- INSIGHTS TAB -->
            <!-- ========================= -->
            <div id="insights" class="tab-content" style="display:none;">
                <h2>Insights</h2>
                <p>This is the insights page.</p>
            </div>

            <!-- ========================= -->
            <!-- HEATMAPS TAB -->
            <!-- ========================= -->
            <div id="heatmaps" class="tab-content" style="display:none;">
                <h2>Heatmaps</h2>
                <p>This is the heatmaps page.</p>
            </div>

        </div>

        <!-- TOOLTIP (GLOBAL - IMPORTANT) -->
        <div id="svg-tooltip"></div>

        <!-- SCRIPTS -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="darts_statistics.js"></script>

        <?php include "../../includes/footer.php"; ?>