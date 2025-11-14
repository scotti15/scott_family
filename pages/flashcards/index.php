<?php
// ------------------------
// SESSION
// ------------------------
ini_set('session.cookie_path', '/'); 
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
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// ------------------------
// FETCH USER LISTS
// ------------------------
$listsStmt = $pdo->prepare("SELECT id, name, description FROM flashcard_lists WHERE user_id = ? ORDER BY created_at DESC, name");
$listsStmt->execute([$userId]);
$userLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine selected list (GET param or first available)
if (isset($_GET['list_id']) && is_numeric($_GET['list_id'])) {
    $selected_list_id = (int)$_GET['list_id'];
} elseif (!empty($userLists)) {
    $selected_list_id = (int)$userLists[0]['id'];
} else {
    $selected_list_id = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Word Flip Card</title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    /* ---------- PAGE LAYOUT FIX ---------- */
    body {
      margin: 0;
      background: #0b6623;
      font-family: Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    main.game-area {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      flex: 1;
    }

    .greeting {
      text-align: center;
      margin: 10px 0;
      font-size: 18px;
      color: white;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <div class="greeting">Hello, <?= $username ?>!</div>

  <!-- Control bar (NOT centered) -->
  <div class="control-bar">
    <label for="listSelect">Select List:</label>
    <select id="listSelect">
        <option value="">-- Choose a list --</option>
        <?php foreach ($userLists as $list): ?>
            <option value="<?= $list['id'] ?>" <?= ($list['id'] == $selected_list_id ? 'selected' : '') ?>>
                <?= htmlspecialchars($list['name']) ?><?= $list['description'] ? ' — ' . htmlspecialchars($list['description']) : '' ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button id="loadListBtn">Load List</button>
</div>
<main class="game-area">

  <div id="session-controls">
    <span id="timer-display">Time: 00:00</span>
    <button id="pause-btn">Pause</button>
  </div>

  <div id="score-panel">
    <div id="cycle-score">Cycle: 0 / 0</div>
    <div id="overall-score">Overall: 0 / 0</div>
  </div>

  <div id="answer-display" class="answer-display" style="margin-bottom: 10px; font-size: 24px; color: #333; min-height: 30px;"></div>

  <!-- NEW FLEX ROW: bars + card -->
  <div class="game-flex">
    <div class="progress-bar" id="cycle-bar">
      <div class="progress-fill" id="cycle-fill"></div>
      <div class="progress-percent" id="cycle-percent"></div>
      <div class="progress-label">Cycle</div>
      <div class="progress-value" id="cycle-value"></div>
    </div>

    <div class="card-container">
      <div class="card" id="playing-card">
        <div class="card-face card-back"></div>
        <div class="card-face card-front" id="card-front"></div>
      </div>
    </div>

    <div class="progress-bar" id="overall-bar">
      <div class="progress-fill" id="overall-fill"></div>
      <div class="progress-percent" id="overall-percent"></div>
      <div class="progress-label">Overall</div>
      <div class="progress-value" id="overall-value"></div>
    </div>
  </div>

  <input type="text" id="card-input" placeholder="Type here..." />
  <button id="flip-button" hidden>Next Card</button>
  <button id="restart-btn" class="btn btn-warning">Restart</button>

  <p id="quiz-status"></p>
  <button id="test-celebration" hidden>Test Celebration</button>
</main>


  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <script src="script.js"></script>
</body>

</html>
