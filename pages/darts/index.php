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

// (Optional) Basic access guard
if (!$user_id) {
    echo "<p>Please log in to use the darts tracker.</p>";
    include "../../includes/footer.php";
    exit();
}
?>

<link rel="stylesheet" href="darts.css">

<div class="container darts-page">

    <h1>Darts Scoring with stats</h1>

    <p class="muted">
        Darts tracking and statistics (work in progress)
    </p>

    <div class="darts-layout">

        <div class="darts-main">
            <!-- Future dartboard / visuals go here -->

            <svg
            id="dartboard"
            viewBox="-200 -200 400 400"
            width="400"
            height="400"
            style="cursor: crosshair;"
            >
          <!-- Constants -->
          <!-- Outer radius: 170 units -->
          <!-- Double ring: 162–170 -->
          <!-- Triple ring: 99–107 -->
          <!-- Bull: outer 15.9, inner 6.35 -->

  <!-- Base wedge definition -->
  <defs>
    <path class="scoring-segment"  id="wedge" d="M0 0 L0 -170 A170 170 0 0 1 52.11 -161.97 Z" />
  </defs>

  <!-- SEGMENTS: rotated 9° counter-clockwise -->
  <g id="segments" transform="rotate(-9)">
    <!-- Segment order clockwise starting from top: 20,1,18,4,13,6,10,15,2,17,3,19,7,16,8,11,14,9,12,5 -->
    <!-- Alternating colors black/yellow -->
    <use href="#wedge" class="scoring-segment"  fill="black" data-value="20" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(18)" data-value="1" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(36)" data-value="18" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(54)" data-value="4" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(72)" data-value="13" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(90)" data-value="6" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(108)" data-value="10" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(126)" data-value="15" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(144)" data-value="2" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(162)" data-value="17" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(180)" data-value="3" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(198)" data-value="19" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(216)" data-value="7" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(234)" data-value="16" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(252)" data-value="8" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(270)" data-value="11" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(288)" data-value="14" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(306)" data-value="9" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="black" transform="rotate(324)" data-value="12" data-multiplier="1" />
<use href="#wedge" class="scoring-segment"  fill="#FFFF99" transform="rotate(342)" data-value="5" data-multiplier="1" />

  </g>

  <!-- Triple ring (middle of segment) -->
  <!-- <circle r="99" fill="none" stroke="green" stroke-width="16" /> -->
<!-- Full Triple Ring -->
<g id="triple-ring">
  <!-- Segment order clockwise starting from T20 at top -->
  <!-- Colors alternate red/green starting with red on T20 -->
  <!-- Inner radius 99, outer radius 107, trapezoid wedge shape -->
  <path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(-9)" data-value="20" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(9)" data-value="1" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(27)" data-value="18" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(45)" data-value="4" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(63)" data-value="13" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(81)" data-value="6" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(99)" data-value="10" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(117)" data-value="15" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(135)" data-value="2" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(153)" data-value="17" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(171)" data-value="3" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(189)" data-value="19" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(207)" data-value="7" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(225)" data-value="16" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(243)" data-value="8" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(261)" data-value="11" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(279)" data-value="14" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(297)" data-value="9" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="red" transform="rotate(315)" data-value="12" data-multiplier="3" />
<path class="scoring-segment"  d="M0 -99 A99 99 0 0 1 30 -92.34 L32.5 -100.89 A107 107 0 0 0 0 -107 Z" fill="green" transform="rotate(333)" data-value="5" data-multiplier="3" />

</g>



  <!-- Double ring (outer) -->
<!-- Double ring -->
<g id="double-ring">
  <!-- Segment order: 20, 1, 18, 4, 13, 6, 10, 15, 2, 17, 3, 19, 7, 16, 8, 11, 14, 9, 12, 5 -->
  <!-- Colors alternate starting with red for 20 -->
  <path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(-9)" data-value="20" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(9)" data-value="1" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(27)" data-value="18" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(45)" data-value="4" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(63)" data-value="13" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(81)" data-value="6" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(99)" data-value="10" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(117)" data-value="15" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(135)" data-value="2" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(153)" data-value="17" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(171)" data-value="3" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(189)" data-value="19" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(207)" data-value="7" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(225)" data-value="16" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(243)" data-value="8" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(261)" data-value="11" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(279)" data-value="14" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(297)" data-value="9" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="red" transform="rotate(315)" data-value="12" data-multiplier="2" />
<path class="scoring-segment"  d="M0 -162 A162 162 0 0 1 49.71 -153.24 L52.11 -161.97 A170 170 0 0 0 0 -170 Z" fill="green" transform="rotate(333)" data-value="5" data-multiplier="2" />

</g>

<g id="numbers" text-anchor="middle" font-family="Arial" font-size="16" fill="white" stroke="black" stroke-width="2">
  <!-- Segment order clockwise starting from T20 at top -->
  <text x="0" y="-180">20</text>
  <text x="55" y="-171" transform="rotate(18 55 -171)">1</text>
  <text x="105" y="-144" transform="rotate(36 105 -144)">18</text>
  <text x="144" y="-105" transform="rotate(54 144 -105)">4</text>
  <text x="171" y="-55" transform="rotate(72 171 -55)">13</text>
  <text x="180" y="0" transform="rotate(90 180 0)">6</text>
  <text x="171" y="55" transform="rotate(108 171 55)">10</text>
  <text x="144" y="105" transform="rotate(126 144 105)">15</text>
  <text x="105" y="144" transform="rotate(144 105 144)">2</text>
  <text x="55" y="171" transform="rotate(162 55 171)">17</text>
  <text x="0" y="180" transform="rotate(180 0 180)">3</text>
  <text x="-55" y="171" transform="rotate(198 -55 171)">19</text>
  <text x="-105" y="144" transform="rotate(216 -105 144)">7</text>
  <text x="-144" y="105" transform="rotate(234 -144 105)">16</text>
  <text x="-171" y="55" transform="rotate(252 -171 55)">8</text>
  <text x="-180" y="0" transform="rotate(270 -180 0)">11</text>
  <text x="-171" y="-55" transform="rotate(288 -171 -55)">14</text>
  <text x="-144" y="-105" transform="rotate(306 -144 -105)">9</text>
  <text x="-105" y="-144" transform="rotate(324 -105 -144)">12</text>
  <text x="-55" y="-171" transform="rotate(342 -55 -171)">5</text>
</g>


<!-- Outer Bull (25) -->
<circle
  r="15.9"
  fill="green"
  data-value="25"
  data-multiplier="1"
/>

<!-- Inner Bull (50) -->
<circle 
  r="6.35"
  fill="red"
  data-value="25"
  data-multiplier="2"
/>
</svg>
        </div>

        <div class="darts-sidebar">
            <!-- Game info / controls -->
            <div class="panel">
                <h2>Game</h2>
                <div>
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
                    <div id="scoreboard-container">
                        <table id="scoreboard">
                            <thead>
                            <tr>
                                <th>Turn</th>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>Total</th>
                                <th>Score</th>
                            </tr>
                            </thead>
                            <tbody id="scoreboard-body">
                            <!-- Rows will be added here -->
                            </tbody>
                        </table>

                        <div id="remaining-container">
                            Score: <span id="remaining-score">501</span>
                        </div>
                        </div>


            <button id="confirmTurn">Confirm</button>

            <div class="target">
              Target:
              <strong id="target-text">T20</strong>
            </div>


                <div class="dartboardcontrols">
                  <button id="btn-new-game">New Game</button>
                  <button id="undo-btn">Undo Dart</button>
                  <button id="btn-ricochet">Ricochet</button>
                  <button id="btn-loss">End Game (Loss)</button>
                  <button id="btn-set-target">Set Target</button>

              </div>

            </div>
        </div>

    </div>
</div>

<script src="darts.js"></script>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<?php include "../../includes/footer.php"; ?>
