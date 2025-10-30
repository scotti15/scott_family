<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$uploadMessage = '';

// Handle CSV upload
if (isset($_POST['upload_csv'])) {
    if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        $uploadMessage = "Error uploading file.";
    } else {
        $fileTmpPath = $_FILES['csvFile']['tmp_name'];
        $fileHandle = fopen($fileTmpPath, 'r');

        if ($fileHandle) {
            // Remove previous flashcards
            $pdo->prepare("DELETE FROM flashcards WHERE user_id = ?")->execute([$userId]);
            $rowCount = 0;
            while (($data = fgetcsv($fileHandle, 1000, ',')) !== false) {
                if (count($data) < 2) continue;
                $question = trim($data[0]);
                $answer = trim($data[1]);
                if ($question !== '' && $answer !== '') {
                    $pdo->prepare("INSERT INTO flashcards (user_id, question, answer) VALUES (?, ?, ?)")
                        ->execute([$userId, $question, $answer]);
                    $rowCount++;
                }
            }
            fclose($fileHandle);
            $uploadMessage = "Successfully uploaded $rowCount flashcards!";
        } else {
            $uploadMessage = "Could not open uploaded file.";
        }
    }
}

// Fetch user's flashcards
$stmt = $pdo->prepare("SELECT * FROM flashcards WHERE user_id = ?");
$stmt->execute([$userId]);
$flashcards = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container">
    <h2>My Flashcards</h2>

    <?php if ($uploadMessage): ?>
        <p><?= htmlspecialchars($uploadMessage) ?></p>
    <?php endif; ?>

    <h3>Upload New Flashcards (CSV)</h3>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="csvFile" accept=".csv" required>
        <button type="submit" name="upload_csv">Upload</button>
    </form>

    <?php if ($flashcards): ?>
    <h3>Quiz Yourself</h3>
    <div id="flashcard-container">
        <div id="flashcard" class="flashcard">
            <div class="front"></div>
        </div>
        <input type="text" id="user-answer" placeholder="Type your answer">
        <button id="submit-answer">Submit</button>
        <div id="correct-answer" style="display:none; margin-top: 10px; font-weight:bold;"></div>
    </div>
    <p id="quiz-status"></p>
    <div id="progress" style="margin:10px 0; font-weight:bold;"></div>
    <button id="reset-btn">Reset / Shuffle</button>
    <?php else: ?>
        <p>No flashcards yet. Upload a CSV to start quizzing!</p>
    <?php endif; ?>
</div>

<style>
#flashcard-container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 40px;
    margin: 20px auto;
    max-width: 900px;
}

.flashcard-area {
    flex: 2;
    text-align: center;
}

.flashcard {
    width: 100%;
    height: 150px;
    border: 2px solid #333;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3em;
    background-color: #fdfd96;
    margin-bottom: 10px;
    padding: 20px;
    transition: background-color 0.3s, border-color 0.3s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

input[type="text"] {
    padding: 6px;
    font-size: 1em;
    width: 80%;
}

button {
    padding: 6px 12px;
    margin: 5px;
}

/* Progress bars */
.progress-section {
    flex: 1;
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-end;
    gap: 40px;
}

.progress-bar {
    position: relative;
    width: 80px;
    height: 300px;
    border: 2px solid #333;
    border-radius: 10px;
    overflow: hidden;
    background-color: #e0e0e0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

/* Percentage label at top */
.progress-percent {
    position: absolute;
    top: 25px;
    width: 100%;
    text-align: center;
    font-weight: bold;
    color: #333;
}

/* Fill anchored at bottom */
.progress-fill {
    position: absolute;
    bottom: 0;          /* anchor at bottom */
    left: 0;
    width: 100%;
    height: 100%;
    transition: height 0.6s ease, background-color 0.6s ease;
}

.progress-label {
    position: absolute;
    top: 10px;
    width: 100%;
    text-align: center;
    font-weight: bold;
    color: #333;
}

.progress-value {
    position: absolute;
    bottom: 10px;
    width: 100%;
    text-align: center;
    font-size: 1.1em;
    color: #333;
    font-weight: bold;
}
</style>



<?php if ($flashcards): ?>
    <script>
const allFlashcards = <?php echo json_encode($flashcards); ?>;
const batchSize = 5;

let sessionFlashcards = [];
let currentBatch = [];
let roundQueue = [];
let currentIndex = 0;

const flashcardEl = document.getElementById('flashcard');
const frontEl = flashcardEl.querySelector('.front');
const answerInput = document.getElementById('user-answer');
const submitBtn = document.getElementById('submit-answer');
const correctAnswerDiv = document.getElementById('correct-answer');
const quizStatus = document.getElementById('quiz-status');
const progressDiv = document.getElementById('progress');

// Add vertical progress bars
const progressSection = document.createElement('div');
progressSection.className = 'progress-section';
progressSection.innerHTML = `
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
`;

document.getElementById('flashcard-container').appendChild(progressSection);

// Shuffle helper
function shuffle(array) {
    return array.sort(() => Math.random() - 0.5);
}

// Initialize session
function startSession() {
    sessionFlashcards = shuffle(allFlashcards.map(f => ({
        question: f.question,
        answer: f.answer,
        counter: batchSize
    })));
    currentBatch = [];
    roundQueue = [];
    loadNextBatch();
}

// Load next batch of 5 questions
function loadNextBatch() {
    const start = currentBatch.length;
    const nextBatch = sessionFlashcards.slice(start, start + batchSize);

    currentBatch.forEach(q => { if (q.counter > 0) q.counter = 1; });

    currentBatch = currentBatch.concat(nextBatch);

    roundQueue = currentBatch.map((_, i) => i);
    roundQueue = shuffle(roundQueue);

    currentIndex = roundQueue.shift();
    displayCurrentQuestion();
    updateProgressBars();
}

// Display question
function displayCurrentQuestion() {
    const q = currentBatch[currentIndex];
    if (!q) return;
    frontEl.textContent = 'Q: ' + q.question;
    flashcardEl.style.backgroundColor = '#fdfd96';
    flashcardEl.style.borderColor = '#333';
    correctAnswerDiv.style.display = 'none';
}

// Calculate totals
function getTotals() {
    const batchRemaining = currentBatch.reduce((sum, q) => sum + q.counter, 0);
    const totalRemaining = sessionFlashcards.reduce((sum, q) => sum + q.counter, 0);
    return { batchRemaining, totalRemaining };
}

// Update visual progress bars
function updateProgressBars() {
    const { batchRemaining, totalRemaining } = getTotals();

    const batchMax = currentBatch.length * batchSize;
    const totalMax = sessionFlashcards.length * batchSize;

    const batchPercent = (batchRemaining / batchMax) * 100;
    const totalPercent = (totalRemaining / totalMax) * 100;

    // Update fill height (shrinks from top)
    const batchFill = document.getElementById('batch-fill');
    const totalFill = document.getElementById('overall-fill');

    batchFill.style.height = batchPercent + '%';
    totalFill.style.height = totalPercent + '%';

    // Set dynamic colors
    batchFill.style.backgroundColor = getProgressColor(batchPercent);
    totalFill.style.backgroundColor = getProgressColor(totalPercent);

    document.getElementById('batch-value').textContent = batchRemaining;
    document.getElementById('overall-value').textContent = totalRemaining;
    
    document.getElementById('batch-percent').textContent = Math.round(100 - batchPercent) + '%';
    document.getElementById('overall-percent').textContent = Math.round(100 - totalPercent) + '%';

    progressDiv.textContent =
        `Current: ${currentBatch[currentIndex]?.counter || 0} | Batch left: ${batchRemaining} | Total left: ${totalRemaining}`;

    if (totalRemaining === 0) {
        celebrate();
    }
}

// Color gradient function (red → yellow → green)
function getProgressColor(percent) {
    if (percent > 50) {
        // 50–100% = red to yellow
        const ratio = (percent - 50) / 50;
        const r = 255;
        const g = Math.round(255 * (1 - ratio));
        return `rgb(${r},${g},0)`;
    } else {
        // 0–50% = yellow to green
        const ratio = percent / 50;
        const r = Math.round(255 * ratio);
        const g = 255;
        return `rgb(${r},${g},0)`;
    }
}


// Confetti & sound
function celebrate() {
    if (typeof confetti === 'function') {
        const duration = 3 * 1000;
        const end = Date.now() + duration;

        (function frame() {
            confetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0 } });
            confetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1 } });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    const tadaSound = new Audio("https://cdn.pixabay.com/download/audio/2022/03/15/audio_2b5e82e7b7.mp3?filename=success-fanfare-trumpets-6185.mp3");
    tadaSound.play();
}

// Show next question
function nextQuestion() {
    if (roundQueue.length === 0) {
        const remainingNonZero = currentBatch.filter(q => q.counter > 0);
        if (remainingNonZero.length === 0) {
            loadNextBatch();
            return;
        }
        roundQueue = currentBatch.map((_, i) => i);
        roundQueue = shuffle(roundQueue);
    }
    currentIndex = roundQueue.shift();
    displayCurrentQuestion();
    updateProgressBars();
}

// Check answer
function checkAnswer() {
    const q = currentBatch[currentIndex];
    const userAnswer = answerInput.value.trim();
    if (!q) return;

    answerInput.disabled = true;
    submitBtn.disabled = true;

    if (userAnswer.toLowerCase() === q.answer.toLowerCase()) {
        q.counter = Math.max(0, q.counter - 1);
        flashcardEl.style.backgroundColor = '#8bc34a';
        flashcardEl.style.borderColor = '#4caf50';
        quizStatus.textContent = 'Correct!';
        setTimeout(() => {
            answerInput.disabled = false;
            submitBtn.disabled = false;
            answerInput.value = '';
            quizStatus.textContent = '';
            flashcardEl.style.backgroundColor = '#fdfd96';
            nextQuestion();
            answerInput.focus();
        }, 1000);
    } else {
        q.counter += 1;
        flashcardEl.style.backgroundColor = '#f44336';
        flashcardEl.style.borderColor = '#d32f2f';
        correctAnswerDiv.textContent = 'Correct answer: ' + q.answer;
        correctAnswerDiv.style.display = 'block';
        quizStatus.textContent = 'Incorrect!';
        setTimeout(() => {
            answerInput.disabled = false;
            submitBtn.disabled = false;
            answerInput.value = '';
            flashcardEl.style.backgroundColor = '#fdfd96';
            quizStatus.textContent = '';
            nextQuestion();
            answerInput.focus();
        }, 5000);
    }

    updateProgressBars();
}

// Event listeners
submitBtn.addEventListener('click', checkAnswer);
answerInput.addEventListener('keypress', e => { if (e.key === 'Enter') checkAnswer(); });
document.getElementById('reset-btn').addEventListener('click', startSession);

// Start
startSession();
</script>


<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js">
const tadaSound = new Audio("https://soundbible.com/mp3/Ta Da-SoundBible.com-1884170640.mp3");

</script>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
