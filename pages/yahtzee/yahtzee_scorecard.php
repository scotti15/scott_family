<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------------
// SESSION
// ------------------------
ini_set('session.cookie_path', '/'); // whole site
ini_set('session.cookie_domain', 'scotti.42web.io');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------
// INCLUDES
// ------------------------
require_once __DIR__ . '/../../config/db.php';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';

// ------------------------
// CURRENT USER
// ------------------------
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    header('Location: ../../auth/login.php');
    exit;
}

echo 'SESSION USERNAME: ' . ($_SESSION['username'] ?? 'not set');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Yahtzee 6-game Scorecard</title>
<link rel="stylesheet" href="yahtzee.css">
</head>
<body>
<?php    
require_once '../../config/db.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>
<h2>Yahtzee Scorecard — 6 Games</h2>

<p>
Click to cycle (left-click up, right-click down). For dropdown cells click the cell to open choices.  Clicking a column header unlocks that column to correct errors.
</p>

<div id="scorecard-container">
    <table id="scorecard" aria-label="Yahtzee scorecard">
    <thead>
        <tr>
            <th class="category-col">
                Category <span id="rolls-left" style="margin-left: 8px; font-weight: bold;"></span>
            </th>            
        <th class="column-header" data-game="1">Game 1 <span class="warning-icon" hidden>⚠️</span></th>
        <th class="column-header" data-game="2">Game 2 <span class="warning-icon" hidden>⚠️</span></th>
        <th class="column-header" data-game="3">Game 3 <span class="warning-icon" hidden>⚠️</span></th>
        <th class="column-header" data-game="4">Game 4 <span class="warning-icon" hidden>⚠️</span></th>
        <th class="column-header" data-game="5">Game 5 <span class="warning-icon" hidden>⚠️</span></th>
        <th class="column-header" data-game="6">Game 6 <span class="warning-icon" hidden>⚠️</span></th>
        </tr>
    </thead>
    <tbody id="body-rows"></tbody>
    <tfoot id="totals-rows"></tfoot>
    </table>
    
    <!-- Floating Keypad -->
        <div id="keypad" style="position:absolute; display:none; z-index:1000; background:#fff; border:1px solid #aaa; padding:5px; border-radius:5px;">
            <div class="key-row"><button class="key-btn">1</button><button class="key-btn">2</button><button class="key-btn">3</button></div>
            <div class="key-row"><button class="key-btn">4</button><button class="key-btn">5</button><button class="key-btn">6</button></div>
            <div class="key-row"><button class="key-btn">7</button><button class="key-btn">8</button><button class="key-btn">9</button></div>
            <div class="key-row"><button class="key-btn">0</button><button class="key-btn">X</button><button class="key-btn">←</button></div>
            <div class="key-row"><button class="enter-btn">Enter</button></div>
     </div>



    <div id="scorecard-buttons">
        <button id="new-session-btn">New Game</button>
        <button id="save-btn">Save Game</button>
        <button id="load-btn">Load Game</button>
    </div>

    <div id="scorecard-controls">
        <label for="load-session">Load Previous Session:</label>
        <select id="load-session">
            <option value="">-- Select Session --</option>
            <!-- Options will be populated dynamically -->
        </select>
    </div>
    
</div>
  
<script src="yahtzee.js"></script>
</body>
</html>
