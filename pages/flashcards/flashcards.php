<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user's flashcards
$stmt = $pdo->prepare("SELECT question, answer FROM flashcards WHERE user_id = ?");
$stmt->execute([$userId]);
$flashcards = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container">
    <h2>My Flashcards</h2>

    <?php if ($flashcards): ?>

        <div id="flashcard-container">
    <div id="flashcard" class="flashcard">
        <div class="front"></div>
    </div>

    <input type="text" id="user-answer" placeholder="Type your answer" style="margin-top: 12px;">
    <button id="submit-answer" style="margin-top: 8px;">Submit</button>
    <button id="reset-btn" style="margin-top: 8px;">Reset / Shuffle</button>

    <div id="correct-answer" style="display:none; margin-top: 10px; font-weight:bold;"></div>
</div>

<div id="progress" style="margin:10px 0; font-weight:bold;"></div>
        <!-- progress bars -->
        <div style="flex:1;display:flex;flex-direction:row;gap:24px;align-items:flex-end;">
            <div class="progress-bar" id="batch-bar">
                <div class="progress-fill" id="batch-fill"></div>
                <div class="progress-percent" id="batch-percent"></div>
                <div class="progress-label">Batch</div>
                <div class="progress-value" id="batch-value"></div>
            </div>
            <div class="progress-bar" id="overall-bar">
                <div class="progress-fill" id="overall-fill"></div>
                <div class="progress-percent" id="overall-percent"></div>
                <div class="progress-label">Overall</div>
                <div class="progress-value" id="overall-value"></div>
            </div>
        </div>

        <div id="progress" style="margin-top:12px;font-weight:bold;"></div>


    <?php else: ?>
        <p>No flashcards found. Upload a CSV first.</p>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="flashcards.css">

<!-- Pass flashcards to JS -->
<script>
const allFlashcards = <?php echo json_encode($flashcards); ?>;
</script>
<script src="flashcards.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
