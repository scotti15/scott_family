<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$uploadMessage = '';
$errorMessage = '';
$selected_list_id = null;

// Fetch user's lists
$listsStmt = $pdo->prepare("SELECT id, name FROM flashcard_lists WHERE user_id = ? ORDER BY created_at DESC, name");
$listsStmt->execute([$userId]);
$userLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine selected list (GET param or first available)
if (isset($_GET['list_id']) && is_numeric($_GET['list_id'])) {
    $selected_list_id = (int)$_GET['list_id'];
} elseif (!empty($userLists)) {
    $selected_list_id = (int)$userLists[0]['id'];
}

// Handle Delete list request
if (isset($_POST['delete_list']) && isset($_POST['list_id']) && is_numeric($_POST['list_id'])) {
    $toDelete = (int)$_POST['list_id'];
    // ensure list belongs to user
    $chk = $pdo->prepare("SELECT id FROM flashcard_lists WHERE id = ? AND user_id = ?");
    $chk->execute([$toDelete, $userId]);
    if ($chk->fetch()) {
        $del = $pdo->prepare("DELETE FROM flashcard_lists WHERE id = ? AND user_id = ?");
        $del->execute([$toDelete, $userId]);
        $uploadMessage = "List deleted.";
        // refresh lists and selected
        $listsStmt->execute([$userId]);
        $userLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
        $selected_list_id = !empty($userLists) ? (int)$userLists[0]['id'] : null;
    } else {
        $errorMessage = "List not found or not permitted.";
    }
}

// Handle CSV upload (create or overwrite)
if (isset($_POST['upload_csv']) && isset($_FILES['csvFile'])) {
    $targetListId = null;
    $useNewName = !empty(trim($_POST['new_list_name'] ?? ''));
    $overwriteSelected = isset($_POST['overwrite_selected']) && $_POST['overwrite_selected'] === '1';
    $selectedDropdown = isset($_POST['existing_list']) && is_numeric($_POST['existing_list']) ? (int)$_POST['existing_list'] : null;

    // Decide target list
    if ($useNewName) {
        $newName = trim($_POST['new_list_name']);
        // create new list if not exists, otherwise use existing id
        $chk = $pdo->prepare("SELECT id FROM flashcard_lists WHERE user_id = ? AND name = ?");
        $chk->execute([$userId, $newName]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $targetListId = (int)$row['id'];
        } else {
            $ins = $pdo->prepare("INSERT INTO flashcard_lists (user_id, name) VALUES (?, ?)");
            $ins->execute([$userId, $newName]);
            $targetListId = (int)$pdo->lastInsertId();
            // refresh lists
            $listsStmt->execute([$userId]);
            $userLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($selectedDropdown) {
        // use selected
        $targetListId = $selectedDropdown;
    } else {
        $errorMessage = "Select an existing list or provide a new list name.";
    }

    // If target found and file uploaded, process CSV
    if ($targetListId && empty($errorMessage)) {
        if ($_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = "File upload error.";
        } else {
            $tmp = $_FILES['csvFile']['tmp_name'];
            $fh = fopen($tmp, 'r');
            if (!$fh) {
                $errorMessage = "Could not read uploaded file.";
            } else {
                try {
                    $pdo->beginTransaction();

                    if ($overwriteSelected && !$useNewName && $selectedDropdown === $targetListId) {
                        // delete existing flashcards for that list
                        $del = $pdo->prepare("DELETE FROM flashcards WHERE list_id = ? AND user_id = ?");
                        $del->execute([$targetListId, $userId]);
                    }

                    // if new (created above) or overwrite not selected we still insert (new rows will be appended)
                    $insert = $pdo->prepare("INSERT INTO flashcards (user_id, list_id, question, answer) VALUES (?, ?, ?, ?)");
                    $rowCount = 0;
                    while (($data = fgetcsv($fh, 2000, ',')) !== false) {
                        // allow CSV without header; ensure at least two columns
                        if (count($data) < 2) continue;
                        $q = trim($data[0]);
                        $a = trim($data[1]);
                        if ($q === '' || $a === '') continue;
                        $insert->execute([$userId, $targetListId, $q, $a]);
                        $rowCount++;
                    }
                    fclose($fh);
                    $pdo->commit();
                    $uploadMessage = "Uploaded $rowCount flashcards to list.";
                    // refresh lists and ensure selected list is current
                    $listsStmt->execute([$userId]);
                    $userLists = $listsStmt->fetchAll(PDO::FETCH_ASSOC);
                    $selected_list_id = $targetListId;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errorMessage = "Database error while importing: " . $e->getMessage();
                }
            }
        }
    }
}

// If list selection changed via POST select button, respect it
if (isset($_POST['select_list']) && isset($_POST['existing_list']) && is_numeric($_POST['existing_list'])) {
    $selected_list_id = (int)$_POST['existing_list'];
}

// Finally, fetch flashcards for selected_list_id (if any)
$flashcards = [];
if ($selected_list_id) {
    $stmt = $pdo->prepare("SELECT id, question, answer FROM flashcards WHERE user_id = ? AND list_id = ? ORDER BY id");
    $stmt->execute([$userId, $selected_list_id]);
    $flashcards = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// include header/navbar
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="container">
    <h2>My Flashcards</h2>

    <?php if ($uploadMessage): ?>
        <p style="color:green;"><?= htmlspecialchars($uploadMessage) ?></p>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <p style="color:red;"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <h3>Manage Lists & Upload (CSV)</h3>

    <form action="" method="post" enctype="multipart/form-data" style="border:1px solid #ddd;padding:12px;border-radius:6px;margin-bottom:16px;">
        <div style="margin-bottom:8px;">
            <label><strong>Existing lists:</strong></label>
            <select name="existing_list">
                <option value="">-- select a list --</option>
                <?php foreach ($userLists as $l): ?>
                    <option value="<?= (int)$l['id'] ?>" <?= $selected_list_id == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="select_list">Load</button>
            <?php if ($selected_list_id): ?>
                <button type="submit" name="delete_list" onclick="return confirm('Delete this list? This cannot be undone.');" value="1" formaction="">
                    Delete
                </button>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:8px;">
            <label><strong>Or new list name:</strong></label>
            <input type="text" name="new_list_name" placeholder="New list name (optional)">
        </div>

        <div style="margin-bottom:8px;">
            <label><strong>CSV file:</strong></label>
            <input type="file" name="csvFile" accept=".csv" required>
        </div>

        <div style="margin-bottom:8px;">
            <label><input type="checkbox" name="overwrite_selected" value="1"> Overwrite selected list (delete existing cards first)</label>
        </div>

        <div>
            <button type="submit" name="upload_csv">Upload CSV to selected/new list</button>
        </div>
    </form>

    <?php if ($selected_list_id && empty($flashcards)): ?>
        <p>No cards in this list yet. Upload CSV to add cards.</p>
    <?php endif; ?>

    <?php if (!empty($flashcards)): ?>
    <h3>Quiz: <?= htmlspecialchars(array_values(array_filter($userLists, function($x) use ($selected_list_id){return $x['id']==$selected_list_id;}))[0]['name'] ?? 'Selected List') ?></h3>

    <div id="flashcard-wrapper" style="display:flex;gap:40px;align-items:flex-start;">
        <div style="flex:2">
            <div id="flashcard" class="flashcard">
                <div class="front"></div>
            </div>

            <input type="text" id="user-answer" placeholder="Type your answer" style="padding:8px;width:80%;">
            <button id="submit-answer" style="padding:8px 12px;">Submit</button>
            <div id="correct-answer" style="display:none; margin-top: 10px; font-weight:bold;"></div>
            <p id="quiz-status"></p>
            <button id="reset-btn" style="margin-top:8px;padding:8px 12px;">Reset / Shuffle Session</button>
        </div>

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
    </div>

    <div id="progress" style="margin-top:12px;font-weight:bold;"></div>

    <?php endif; ?>
</div>

<style>

/* ---- Global styles ---- */
body {
    font-family: "Inter", "Segoe UI", sans-serif;
    background: #f6f7fb;
    color: #333;
}

.container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 20px 30px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* ---- Headings ---- */
h2, h3 {
    text-align: center;
    color: #333;
    font-weight: 600;
    margin-bottom: 16px;
}

/* ---- Upload form ---- */
form {
    background: linear-gradient(145deg, #f9fafc, #eef1f7);
    border: 1px solid #d9dee9;
    border-radius: 12px;
    padding: 20px;
    box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
}

form label {
    display: block;
    margin-bottom: 4px;
    font-weight: 500;
}

form input[type="file"],
form input[type="text"],
form select {
    width: 100%;
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    margin-bottom: 10px;
    transition: border-color 0.2s;
}

form input:focus,
form select:focus {
    outline: none;
    border-color: #4a90e2;
}

form button {
    background: linear-gradient(to right, #4a90e2, #357ab8);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    cursor: pointer;
    font-weight: 500;
    transition: transform 0.15s, background 0.3s;
}

form button:hover {
    background: linear-gradient(to right, #5aa2f0, #3b81c9);
    transform: scale(1.03);
}

form button:active {
    transform: scale(0.98);
}

/* ---- Flashcard ---- */
.flashcard {
    width: 100%;
    height: 160px;
    border: 2px solid #4a90e2;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25em;
    background: linear-gradient(145deg, #e7f0ff, #f4f8ff);
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    padding: 20px;
    transition: background 0.3s, transform 0.2s;
}

.flashcard:hover {
    transform: scale(1.01);
}

/* ---- Buttons ---- */
#submit-answer, #reset-btn {
    background: linear-gradient(to right, #4caf50, #2e7d32);
    border: none;
    color: #fff;
    font-weight: 500;
    border-radius: 8px;
    padding: 8px 16px;
    margin-top: 10px;
    cursor: pointer;
    transition: transform 0.2s, background 0.3s;
}

#submit-answer:hover, #reset-btn:hover {
    background: linear-gradient(to right, #66bb6a, #388e3c);
    transform: scale(1.05);
}

/* ---- Progress bars ---- */
.progress-bar {
    position: relative;
    width: 80px;
    height: 320px;
    border-radius: 12px;
    overflow: hidden;
    background-color: #f0f0f0;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    border: 2px solid #aaa;
}

.progress-fill {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: linear-gradient(to top, #f44336, #ff9800);
    height: 100%;
    border-radius: 0 0 10px 10px;
    transition: height 0.6s ease, background 0.6s ease;
}

.progress-label {
    position: absolute;
    bottom: 22px;
    width: 100%;
    text-align: center;
    font-weight: 600;
    color: #333;
}

.progress-value {
    position: absolute;
    bottom: 4px;
    width: 100%;
    text-align: center;
    font-size: 0.9em;
    color: #444;
    font-weight: 500;
}

.progress-percent {
    position: absolute;
    top: 8px;
    width: 100%;
    text-align: center;
    font-weight: 600;
    color: #333;
    font-size: 0.9em;
}

/* ---- Layout tweaks ---- */
#flashcard-wrapper {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 30px;
    align-items: flex-start;
    margin-top: 20px;
}

#flashcard-wrapper > div:first-child {
    flex: 2;
}

#flashcard-wrapper > div:last-child {
    flex: 1;
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 30px;
}

/* ---- Text & feedback ---- */
#quiz-status {
    margin-top: 10px;
    font-weight: 600;
    color: #333;
}

#correct-answer {
    margin-top: 8px;
    color: #f44336;
}

/* ---- Animations ---- */
@keyframes pulseGreen {
    0% { box-shadow: 0 0 0 0 rgba(76,175,80,0.4); }
    70% { box-shadow: 0 0 0 10px rgba(76,175,80,0); }
    100% { box-shadow: 0 0 0 0 rgba(76,175,80,0); }
}

@keyframes pulseRed {
    0% { box-shadow: 0 0 0 0 rgba(244,67,54,0.4); }
    70% { box-shadow: 0 0 0 10px rgba(244,67,54,0); }
    100% { box-shadow: 0 0 0 0 rgba(244,67,54,0); }
}

.flashcard.correct {
    animation: pulseGreen 1s ease;
}
.flashcard.incorrect {
    animation: pulseRed 1s ease;
}

/* ---- Responsive ---- */
@media (max-width: 700px) {
    #flashcard-wrapper {
        flex-direction: column;
        align-items: center;
    }
    .progress-bar {
        height: 220px;
        width: 60px;
    }
}


</style>

<?php if (!empty($flashcards)): ?>
<!-- confetti library (for celebration) -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
/* ============================
   Data from PHP (selected list)
   ============================ */
const allFlashcards = <?php echo json_encode($flashcards, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
console.log('allFlashcards length:', allFlashcards.length);
console.log('allFlashcards ids:', allFlashcards.map(f=>f.id).slice(0,50));

const batchSize = 5;

/* ===== state ===== */
let sessionFlashcards = [];
let currentBatch = [];
let roundQueue = [];
let currentIndex = 0;

/* ===== UI elements ===== */
const flashcardEl = document.getElementById('flashcard');
const frontEl = flashcardEl.querySelector('.front');
const answerInput = document.getElementById('user-answer');
const submitBtn = document.getElementById('submit-answer');
const correctAnswerDiv = document.getElementById('correct-answer');
const quizStatus = document.getElementById('quiz-status');

const batchFill = document.getElementById('batch-fill');
const totalFill = document.getElementById('overall-fill');
const batchValue = document.getElementById('batch-value');
const overallValue = document.getElementById('overall-value');
const batchPercentLabel = document.getElementById('batch-percent');
const overallPercentLabel = document.getElementById('overall-percent');
const progressDiv = document.getElementById('progress');

/* sound (ta-da) */
const tadaSound = new Audio("https://soundbible.com/mp3/Ta%20Da-SoundBible.com-1884170640.mp3");

/* helper: shuffle */
function shuffle(array) {
    return array.sort(() => Math.random() - 0.5);
}

/* ===== session control ===== */
function startSession() {
    // create sessionFlashcards as the full list for this list (counter = batchSize)
    sessionFlashcards = shuffle(allFlashcards.map(f => ({
        id: f.id,
        question: f.question,
        answer: f.answer,
        counter: batchSize
    })));
    currentBatch = [];
    roundQueue = [];
    loadNextBatch();
}

function loadNextBatch() {
    const start = currentBatch.length;
    const nextBatch = sessionFlashcards.slice(start, start + batchSize);

    // reduce previous batch counters >0 to 1
    currentBatch.forEach(q => { if (q.counter > 0) q.counter = 1; });

    // init counters for newly added items
    nextBatch.forEach(q => { q.counter = batchSize; });

    currentBatch = currentBatch.concat(nextBatch);

    // build roundQueue: indices of currentBatch with counter > 0
    roundQueue = currentBatch.map((_,i)=>i).filter(i => currentBatch[i].counter > 0);
    roundQueue = shuffle(roundQueue);

    currentIndex = roundQueue.shift();
    displayCurrentQuestion();
    updateCounters();
}

/* display */
function displayCurrentQuestion() {
    const q = currentBatch[currentIndex];
    if (!q) return;
    frontEl.textContent = 'Q: ' + q.question;
    flashcardEl.style.backgroundColor = '';
    flashcardEl.style.borderColor = '';
    correctAnswerDiv.style.display = 'none';
}

/* counters & progress */
function getTotals() {
    const batchRemaining = currentBatch.reduce((s,q)=>s + (q.counter>0? q.counter:0),0);
    const totalRemaining = sessionFlashcards.reduce((s,q)=>s + (q.counter>0? q.counter:0),0);
    return { batchRemaining, totalRemaining };
}

function updateCounters() {
    const currentQ = currentBatch[currentIndex] || { counter: 0 };
    const currentCounter = currentQ.counter;
    progressDiv.textContent = `Current Question Counter: ${currentCounter}`;
    updateProgressBars();
}

/* visual progress bars */
function updateProgressBars() {
    const { batchRemaining, totalRemaining } = getTotals();

    // Maxes are counts * batchSize (because counters are up to batchSize)
    const batchMax = currentBatch.length * batchSize;
    const totalMax = sessionFlashcards.length * batchSize;

    const batchPercent = (batchRemaining / batchMax) * 100;
    const totalPercent = (totalRemaining / totalMax) * 100;

    batchFill.style.height = batchPercent + '%';
    totalFill.style.height = totalPercent + '%';

    batchFill.style.backgroundColor = getProgressColor(batchPercent);
    totalFill.style.backgroundColor = getProgressColor(totalPercent);

    batchValue.textContent = batchRemaining;
    overallValue.textContent = totalRemaining;

    batchPercentLabel.textContent = Math.round(100 - batchPercent) + '%';
    overallPercentLabel.textContent = Math.round(100 - totalPercent) + '%';

    // Celebrate if everything done
    if (totalRemaining === 0) {
        celebrate();
    }
}

/* color helper: red -> yellow -> green */
function getProgressColor(percent) {
    // percent = remaining% (100 full, 0 empty)
    if (percent > 50) {
        // 50..100 -> red -> yellow
        const ratio = (percent - 50) / 50; // 0..1
        const r = 255;
        const g = Math.round(255 * (1 - ratio));
        return `rgb(${r},${g},0)`;
    } else {
        // 0..50 -> yellow -> green
        const ratio = percent / 50; // 0..1
        const r = Math.round(255 * ratio);
        const g = 255;
        return `rgb(${r},${g},0)`;
    }
}

/* sequencing logic: next question in current round */
function nextQuestion() {
    if (roundQueue.length === 0) {
        // assemble remaining indices in currentBatch with counter>0
        const remainingNonZero = currentBatch
            .map((_,i)=>i)
            .filter(i => currentBatch[i].counter > 0);

        if (remainingNonZero.length === 0) {
            // all counters in current batch zero -> load next batch (or celebrate if none left)
            const totalRemaining = sessionFlashcards.reduce((s,q)=>s + (q.counter>0? q.counter:0),0);
            if (totalRemaining === 0) {
                // all done
                updateCounters();
                return;
            } else {
                loadNextBatch();
                return;
            }
        }

        roundQueue = remainingNonZero;
        roundQueue = shuffle(roundQueue);
    }

    currentIndex = roundQueue.shift();
    displayCurrentQuestion();
    updateCounters();
}

/* check answer */
function checkAnswer() {
    const q = currentBatch[currentIndex];
    const userAnswer = answerInput.value.trim();
    if (!q) return;

    answerInput.disabled = true;
    submitBtn.disabled = true;

    // reset card appearance first
    flashcardEl.style.backgroundColor = '';
    flashcardEl.style.borderColor = '';

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
            flashcardEl.style.backgroundColor = '';
            flashcardEl.style.borderColor = '';
            quizStatus.textContent = '';
            nextQuestion();
            answerInput.focus();
        }, 5000);
    }

    updateCounters();
}

/* celebration: confetti + sound */
function celebrate() {
    // avoid repeating celebration multiple times
    if (window._celebrated) return;
    window._celebrated = true;

    // confetti
    if (typeof confetti === 'function') {
        const duration = 3000;
        const end = Date.now() + duration;
        (function frame() {
            confetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0 } });
            confetti({ particleCount: 3, angle: 120, spread: 55, origin: { x: 1 } });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    // play tada
    tadaSound.play().catch(()=>{ /* autoplay may be blocked until user interaction */ });

    // message
    quizStatus.textContent = "🎉 You finished all flashcards!";
}

/* event listeners */
submitBtn.addEventListener('click', checkAnswer);
answerInput.addEventListener('keypress', e => { if (e.key === 'Enter') checkAnswer(); });
document.getElementById('reset-btn').addEventListener('click', () => {
    // reset celebration flag so user can replay
    window._celebrated = false;
    startSession();
});

/* start */
startSession();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
