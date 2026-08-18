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
$user_id = $_SESSION["user_id"] ?? 0;//get user id
//GET USER NAME -----------------------------------
$stmt = $pdo->prepare("
    SELECT username
    FROM users
    WHERE ID = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$player1 = $stmt->fetchColumn();
//--------------------------------------------------
$role = $_SESSION["role"] ?? "user";
$isAdmin = $role === "admin";

// Basic access guard
if (!$user_id) {
    echo "<p>Please log in to use the darts tracker.</p>";
    include "../../includes/footer.php";
    exit();
}
?>

<link rel="stylesheet" href="darts.css">
<style>
#scoreTable td.hit-target-s {
    background-color: pink;
}

#scoreTable td.hit-target-d {
    background-color: red;
    color: yellow;
    font-weight: bold;
}

#scoreTable td.hit-target-t {
    background-color: darkpink;
}
</style>

<div class="darts-two-player">
    <input type="hidden" id="current-user-id" value="<?= htmlspecialchars($user_id) ?>">
    <div class="container darts-page">

        <header class="page-header">
            <div class="header-left">
                <h1>Darts Scoring with Stats</h1>
                <p class="muted">Darts tracking and statistics (work in progress)</p>
            </div>
            <input type="hidden" id="game-mode" value="2">
            <button id="infoBtn" class="info-button" title="About this site">
                ⓘ
            </button>
        </header>

        <!-- ===================== -->
        <!-- 3-COLUMN LAYOUT -->
        <!-- ===================== -->
        <div class="darts-layout">

            <!-- ===================== -->
            <!-- PLAYER COLUMN -->
            <!-- ===================== -->
            <div class="player-panel">

                <div class="panel">
                    <div class="view-toggle">
                        <span class="toggle-label left">Raw Scores</span>

                        <label class="switch">
                            <input type="checkbox" id="scoreToggle">
                            <span class="slider"></span>
                        </label>

                        <span class="toggle-label right">Wedge Values</span>
                    </div>
                    <h2>Game <span id="currentGameLabel">#1</span></h2>
                    <table id="player-scoreboard" class="player1-active">
                        <thead>
                            <tr>
                                <th></th>
                                <th id="player1-name">
                                    <?= htmlspecialchars($player1) ?>
                                </th>
                                <th id="player2-name">Player 2</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <th>Remaining</th>
                                <td id="player1-remaining">501</td>
                                <td id="player2-remaining">501</td>
                            </tr>

                            <tr>
                                <th>Checkout</th>
                                <td id="player1-checkout">...</td>
                                <td id="player2-checkout">...</td>
                            </tr>
                        </tbody>
                    </table>
                    <table id="scoreTable">
                        <thead>
                            <tr>
                                <th>Dart 1</th>
                                <th>Dart 2</th>
                                <th>Dart 3</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="d1"></td>
                                <td id="d2"></td>
                                <td id="d3"></td>
                                <td id="turnTotal">0</td>
                            </tr>
                        </tbody>
                    </table>

                    <button id="confirmTurn">Confirm</button>
                    <button id="btn-test-player">Switch Player</button>
                </div>

                <div id="remaining-container">
                    <span id="remaining-score">501</span>
                    <span id="visit-number"></span>
                </div>
                <div class="stat-card" id="target-card">
                    <div id="target-panel">

                        <div class="stat-row">
                            <span class="stat-label">Target</span>
                            <span class="stat-value" id="target-text">T20</span>
                        </div>
                    </div>

                    <div id="checkout-panel" style="display:none;">
                        <div class="stat-row">
                            <span class="stat-label">Checkout</span>
                            <span class="stat-value" id="checkout-route">—</span>
                        </div>

                        <div class="stat-row">
                            <span class="stat-label secondary">• on single</span>
                            <span class="stat-value" id="checkout-single-route">—</span>
                        </div>
                    </div>
                </div>


                <div class="dartboardcontrols">
                    <button id="btn-new-game">New Game</button>
                    <button id="undo-btn">Undo Dart</button>
                    <button id="btn-ricochet">Ricochet</button>
                    <button id="btn-loss">End Game (Loss)</button>
                    <button id="btn-set-target">Set Target</button>
                    <button id="btn-show-stats">📊 Game Stats</button>
                </div>

            </div>

            <!-- ===================== -->
            <!-- CENTER COLUMN -->
            <!-- ===================== -->
            <div class="board-panel">
                <svg id="dartboard" viewBox="-200 -200 400 400" width="800" height="800" style="cursor: crosshair;">
                    <!-- Constants -->
                    <!-- Outer radius: 170 units -->
                    <!-- Double ring: 162–170 -->
                    <!-- Triple ring: 99–107 -->
                    <!-- Bull: outer 15.9, inner 6.35 -->

                    <!-- Base wedge definition -->
                    <defs>
                        <path class="scoring-segment" id="wedge" d="M0 0 L0 -170 A170 170 0 0 1 52.11 -161.97 Z" />
                    </defs>

                    <!-- SEGMENTS: rotated 9° counter-clockwise -->
                    <g id="segments" transform="rotate(-9)">
                        <!-- Segment order clockwise starting from top: 20,1,18,4,13,6,10,15,2,17,3,19,7,16,8,11,14,9,12,5 -->

                        <use href="#wedge" class="scoring-segment" fill="#111111" data-value="20" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(18)" data-value="1"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(36)" data-value="18"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(54)" data-value="4"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(72)" data-value="13"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(90)" data-value="6"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(108)"
                            data-value="10" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(126)"
                            data-value="15" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(144)" data-value="2"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(162)"
                            data-value="17" data-multiplier="1" />

                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(180)" data-value="3"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(198)"
                            data-value="19" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(216)" data-value="7"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(234)"
                            data-value="16" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(252)" data-value="8"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(270)"
                            data-value="11" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(288)"
                            data-value="14" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(306)" data-value="9"
                            data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#111111" transform="rotate(324)"
                            data-value="12" data-multiplier="1" />
                        <use href="#wedge" class="scoring-segment" fill="#F2E6B3" transform="rotate(342)" data-value="5"
                            data-multiplier="1" />

                    </g>
                    <g id="triple-ring">
                        <!-- Triple wedges, symmetric, T20 centered on 0° -->
                        <!-- Inner radius: 99, outer radius: 110 -->
                        <!-- Each wedge spans 18° (half-width ±9°) -->
                        <!-- Fill alternates red/green, starting with red on T20 -->

                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(0)" data-value="20" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(18)" data-value="1" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(36)" data-value="18" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(54)" data-value="4" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(72)" data-value="13" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(90)" data-value="6" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(108)" data-value="10" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(126)" data-value="15" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(144)" data-value="2" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(162)" data-value="17" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(180)" data-value="3" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(198)" data-value="19" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(216)" data-value="7" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(234)" data-value="16" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(252)" data-value="8" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(270)" data-value="11" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(288)" data-value="14" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(306)" data-value="9" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#cc0000" transform="rotate(324)" data-value="12" data-multiplier="3" />
                        <path class="scoring-segment"
                            d="M-15.45 -93.86 A99 99 0 0 1 15.45 -93.86 L17.21 -104.71 A110 110 0 0 0 -17.21 -104.71 Z"
                            fill="#008000" transform="rotate(342)" data-value="5" data-multiplier="3" />
                    </g>



                    <g id="double-ring">

                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(0)" data-value="20" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(18)" data-value="1" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(36)" data-value="18" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(54)" data-value="4" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(72)" data-value="13" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(90)" data-value="6" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(108)" data-value="10" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(126)" data-value="15" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(144)" data-value="2" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(162)" data-value="17" data-multiplier="2" />

                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(180)" data-value="3" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(198)" data-value="19" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(216)" data-value="7" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(234)" data-value="16" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(252)" data-value="8" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(270)" data-value="11" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(288)" data-value="14" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(306)" data-value="9" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#cc0000" transform="rotate(324)" data-value="12" data-multiplier="2" />
                        <path class="scoring-segment"
                            d="M-25.48 -157.99 A162 162 0 0 1 25.48 -157.99 L26.75 -168.87 A170 170 0 0 0 -26.75 -168.87 Z"
                            fill="#008000" transform="rotate(342)" data-value="5" data-multiplier="2" />

                    </g>

                    <g id="numbers" text-anchor="middle" font-family="Arial, sans-serif" font-size="16"
                        font-weight="bold" fill="black" stroke="white" stroke-width="1.8" paint-order="stroke fill"
                        transform="translate(0, 4) scale(1.03)">
                        <!-- Segment order clockwise starting from T20 at top -->
                        <text x="0" y="-180">20</text>
                        <text x="55" y="-171">1</text>
                        <text x="105" y="-144">18</text>
                        <text x="144" y="-105">4</text>
                        <text x="171" y="-55">13</text>
                        <text x="180" y="0">6</text>
                        <text x="171" y="55">10</text>
                        <text x="144" y="105">15</text>
                        <text x="105" y="144">2</text>
                        <text x="55" y="171">17</text>
                        <text x="0" y="180">3</text>
                        <text x="-55" y="171">19</text>
                        <text x="-105" y="144">7</text>
                        <text x="-144" y="105">16</text>
                        <text x="-171" y="55">8</text>
                        <text x="-180" y="0">11</text>
                        <text x="-171" y="-55">14</text>
                        <text x="-144" y="-105">9</text>
                        <text x="-105" y="-144">12</text>
                        <text x="-55" y="-171">5</text>
                    </g>


                    <!-- Outer Bull (25) -->
                    <circle class="scoring-segment" stroke="#99AABB" stroke-width="1" r="15.9" fill="#008000"
                        data-value="25" data-multiplier="1" />

                    <!-- Inner Bull (50) -->
                    <circle class="scoring-segment" stroke="#99AABB" stroke-width="1" r="6.35" fill="#cc0000"
                        data-value="25" data-multiplier="2" />
                </svg>
            </div>

            <!-- ===================== -->
            <!-- RIGHT COLUMN -->
            <!-- ===================== -->
            <div class="session-panel">
                <div id="sessionPanel" class="session-panel">
                    <h3>Dart Sessions</h3>

                    <div class="session-controls">
                        <button id="newSessionBtn">New Session</button>

                        <select id="sessionSelect">
                            <option value="" disabled selected>Select a session</option>
                        </select>

                        <button id="loadSessionBtn">Load Selected</button>

                        <?php
                            $stmt = $pdo->prepare("
                                SELECT ID, username
                                FROM users
                                ORDER BY username
                            ");
                            $stmt->execute(['user_id' => $_SESSION['user_id']]);
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                        <script>
                        const allUsers = <?= json_encode($users) ?>;
                        console.log("All users:", allUsers);
                        </script>
                        <div class="player2-selection">
                            <select id="player2-user">
                                <option value="">Select Player 2</option>

                                <?php foreach ($users as $user): ?>
                                <?php if ($user['ID'] == $_SESSION['user_id']) continue; ?>

                                <option value="<?= htmlspecialchars($user['ID']) ?>">
                                    <?= htmlspecialchars($user['username']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="game-select-wrapper">
                            <label for="gameSelect"><strong>Games in Session</strong></label>

                            <select id="gameSelect" disabled>
                                <option value="">No session loaded</option>
                            </select>
                        </div>

                        <input type="text" id="sessionDescription" placeholder="Add a description for this session..."
                            maxlength="255" disabled>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <!-- existing page content -->
    <div id="board"></div>
    <div id="scoreboard"></div>
    <!-- 🎯 GAME STATS MODAL -->
    <div id="gameStatsModal" class="game-summary-overlay" style="display:none;">
        <div class="game-summary-modal">

            <h1 class="summary-title">🏁 Game Complete</h1>
            <p id="gameSummarySubtitle" class="summary-subtitle">Game #4 · 501 Double Out</p>

            <div class="summary-section">
                <h2>🎯 Target Accuracy</h2>
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Target</th>
                            <th>Hit</th>
                            <th>Aimed</th>
                            <th>%</th>
                            <th>Accuracy</th> <!-- New column -->
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Triple</td>
                            <td id="hitsT">0</td>
                            <td id="aimedT">0</td>
                            <td id="pctT">0%</td>
                            <td id="accT">0.0 cm</td> <!-- New field -->
                        </tr>
                        <tr>
                            <td>Double</td>
                            <td id="hitsD">0</td>
                            <td id="aimedD">0</td>
                            <td id="pctD">0%</td>
                            <td id="accD">0.0 cm</td> <!-- New field -->
                        </tr>
                        <tr>
                            <td>Single</td>
                            <td id="hitsS">0</td>
                            <td id="aimedS">0</td>
                            <td id="pctS">0%</td>
                            <td id="accS">0.0 cm</td> <!-- New field -->
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="summary-section">
                <h2>📊 Key Stats</h2>
                <ul class="summary-stats">
                    <li>
                        <strong>20s hit while targeting T20:</strong>
                        <span id="statS20vsT20">0 / 0</span>
                    </li>

                    <li>
                        <strong>Darts to finish range (&lt;161):</strong>
                        <span id="statThrowsToFinish">0</span>
                    </li>

                    <li>
                        <strong>Total darts thrown:</strong>
                        <span id="statTotalDarts">0</span>
                    </li>

                    <li>
                        <strong>3-Dart Average (overall):</strong>
                        <span id="stat3DAOverall">0.00</span>
                    </li>

                    <li>
                        <strong>3-Dart Average (before finish range):</strong>
                        <span id="stat3DAPreFinish">0.00</span>
                    </li>
                    <li>
                        <strong>Grouping Radius:</strong>
                        <span id="statGroupingRadius">0.0 cm</span>
                    </li>
                </ul>
            </div>
            <div class="summary-actions">
                <button class="btn-secondary" onclick="replayGame()">Replay Game</button>
                <button id="closeStatsBtn" class="btn-primary">Close</button>
            </div>

        </div>
    </div>

    <!-- Info / Help Modal -->
    <div id="infoModal" class="game-summary-overlay" style="display:none;">
        <div class="game-summary-modal">

            <div class="modal-header">
                <h2>ℹ️ How to Use This Board</h2>
                <button id="closeInfoBtn" class="modal-close">✕</button>
            </div>

            <div class="modal-body">
                <section>
                    <h3>🎯 Targets</h3>
                    <ul>
                        <li>Highlighted segment shows the current target</li>
                        <li>Manual target overrides automatic calculation</li>
                        <li>Targets are recorded per dart for stats</li>
                    </ul>
                </section>

                <section>
                    <h3>📍 Markers</h3>
                    <ul>
                        <li><span class="legend normal"></span> Normal throw</li>
                        <li><span class="legend ricochet"></span> Ricochet (R)</li>
                        <li><span class="legend bust"></span> Bust throw</li>
                        <li>Use turn checkboxes to replay markers</li>
                    </ul>
                </section>

                <section>
                    <h3>📊 Stats</h3>
                    <ul>
                        <li>Target accuracy compares aimed vs hit</li>
                        <li>Heat maps show actual impact points</li>
                        <li>Key stats are calculated per game</li>
                    </ul>
                </section>

                <section>
                    <h3>🏁 Scoring</h3>
                    <ul>
                        <li>Bust resets the turn score</li>
                        <li>Double required to finish</li>
                        <li>All darts count toward total darts thrown</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>
</div>

<script src="darts.js"></script>
<script src="darts2player.js"></script>


<?php include "../../includes/footer.php"; ?>