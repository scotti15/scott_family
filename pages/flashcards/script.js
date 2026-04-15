document.addEventListener("DOMContentLoaded", () => {
  const card = document.getElementById("playing-card");
  const cardFront = document.querySelector(".card-front");
  const cardInput = document.getElementById("card-input");
  const button = document.getElementById("flip-button");
  const answerDisplay = document.getElementById("answer-display");
  const cycleScoreEl = document.getElementById("cycle-score");
  const overallScoreEl = document.getElementById("overall-score");

  const celebration = document.getElementById("test-celebration");
  const quizStatus = document.getElementById("quiz-status");
  const listDropdown = document.getElementById("listSelect"); // not listDropdown

  const loadListBtn = document.getElementById("loadListBtn");
  const restartButton = document.getElementById("restart-btn");

  const timerDisplay = document.getElementById("timer-display");
  const pauseBtn = document.getElementById("pause-btn");

  let correctCount = 0; // counts correct answers
  let totalCards = 0; // total number of words in the current list
  let currentCycleCount = 0; // counts how many cycles have been completed
  let totalAnswers = 0; // increments on every answer

  let finalTimeSeconds = 0;
  let finalTimeDisplay = "00:00";

  let reviewMode = false;

  // Progress bar elements
  const cycleFill = document.getElementById("cycle-fill");
  const overallFill = document.getElementById("overall-fill");
  const cyclePercentLabel = document.getElementById("cycle-percent");
  const overallPercentLabel = document.getElementById("overall-percent");
  const cycleValue = document.getElementById("cycle-value");
  const overallValue = document.getElementById("overall-value");

  /* sound (ta-da) */
  const tadaSound = new Audio(
    "https://soundbible.com/mp3/Ta%20Da-SoundBible.com-1884170640.mp3"
  );

  // ---------------- State ----------------
  let allWords = [];
  let currentCycleWords = [];
  let currentWordObj = null;
  let animating = false;
  let rotation = 0;
  let doubleFlipPhase = 0;
  let nextBatchIndex = 0;
  let passQueue = [];
  let gameOver = false; // new global flag
  let unusedWords = []; // tracks which words haven’t been used yet
  // Progress bar starting totals (used by updateProgressBars)
  let totalStartingValue = 0; // sum of values for all words when session starts or when reloaded
  let cycleStartingValue = 0; // sum of values for the current cycle when the cycle begins

  let timerInterval = null;
  let totalSeconds = 0;
  let isPaused = false;
  let timerStarted = false;

  document.getElementById("reviewModeBtn").addEventListener("click", () => {
    if (!confirm("Start Review Mode? This will restart the game with all words requiring only 1 correct answer.")) {
      return;
    }
  
    // restart the game with base value = 1
    resetGame(1);
  
    // update button text so user knows Review Mode is active
    document.getElementById("reviewModeBtn").textContent = "Review Mode Active";
  });
  

  // ensure card starts showing front
  card.style.transform = "rotateY(0deg)";

  // -------------- Load words --------------
  fetch("get_words.php")
    .then((res) => res.json())
    .then((data) => {
      if (!Array.isArray(data) || data.length === 0) {
        console.error("No data returned");
        return;
      }

      // each word has a value and flags
      allWords = data.map((item) => ({
        question: item.question || item,
        answer: item.answer || item,
        value: 3,
        inCycle: false,
        mastered: false,
      }));

      startNewCycle(5);
      prepareNextPass();
      showNextWord();
    })
    .catch((err) => console.error("Failed to fetch words:", err));

  // -------------- Utility functions --------------
  function updateScoreDisplays() {
    if (gameOver) return; // stop updating once finished
    const cycleSum = allWords
      .filter((w) => w.inCycle && w.value > 0)
      .reduce((s, w) => s + Math.min(w.value, getRequiredValue()), 0);
    const overallSum = allWords.reduce((s, w) => s + w.value, 0);
    cycleScoreEl.textContent = `Cycle: ${cycleSum}`;
    overallScoreEl.textContent = `Overall: ${overallSum}`;
    // 🎉 Trigger celebration when all words are mastered
    if (overallSum === 0) {
      gameOver = true;
      celebrate();
    }
    updateProgressBars();
  }

  function setFontSizeByLength(element) {
    const len = (element.textContent || "").length;
    let fontSize;
    if (len <= 3) fontSize = 80;
    else if (len === 4) fontSize = 60;
    else if (len === 5) fontSize = 45;
    else if (len <= 7) fontSize = 35;
    else if (len <= 9) fontSize = 30;
    else fontSize = 20;
    element.style.fontSize = fontSize + "px";
  }

  function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  // -------------- Cycle logic --------------
  function startNewCycle(batchSize) {
    if (gameOver) return;

    // mark previous cycle words mastered if value == 0
    currentCycleWords.forEach((w) => {
      if (w.value <= 0) {
        w.value = 1; // mastered words come back with 1
        w.mastered = true;
      }
      w.inCycle = false;
    });

    // gather mastered words (value > 0)
    const carryOver = allWords.filter((w) => w.mastered && w.value > 0);
    // select new words from pool not yet inCycle or mastered
    const remainingPool = allWords.filter(
      (w) => !w.inCycle && !w.mastered && w.value === 3
    );
    // const newBatch = remainingPool.slice(0, batchSize);
    const newBatch = getRandomWords(batchSize);

    newBatch.forEach((w) => (w.inCycle = true));
    carryOver.forEach((w) => (w.inCycle = true));

    currentCycleWords = [...carryOver, ...newBatch];
    nextBatchIndex += newBatch.length;
    // end of startNewCycle()
    cycleStartingValue = currentCycleWords.reduce(
      (s, w) => s + (w.value || 0),
      0
    );
    updateScoreDisplays();
    updateProgressBars();
  }

  function prepareNextPass() {
    if (gameOver) return;

    // get words still active (value > 0)
    const active = currentCycleWords.filter((w) => w.value > 0);
    if (active.length === 0) {
      // cycle complete, start a new one
      startNewCycle(5);
      return prepareNextPass();
    }

    // sort by descending value, shuffle equal-value groups
    const grouped = {};
    active.forEach((w) => {
      if (!grouped[w.value]) grouped[w.value] = [];
      grouped[w.value].push(w);
    });

    let sortedValues = Object.keys(grouped)
      .map(Number)
      .sort((a, b) => b - a);

    passQueue = [];
    sortedValues.forEach((val) => {
      passQueue = passQueue.concat(shuffleArray(grouped[val]));
    });
  }

  function showNextWord() {
    if (gameOver) return; // <-- stop if finished
    if (passQueue.length === 0) {
      prepareNextPass();
    }
    currentWordObj = passQueue.shift();
    displayWord(currentWordObj.question);
  }

  function displayWord(text) {
    cardFront.textContent = text;
    setFontSizeByLength(cardFront);
    cardFront.style.backgroundColor = "white";
    cardFront.style.color = "#111";
    cardInput.value = "";
    cardInput.focus();
  }

  // -------------- Input handling --------------
  cardInput.addEventListener("input", () => {
    if (!currentWordObj) return;

    const val = cardInput.value.trim().toUpperCase();
    const correct = (currentWordObj.answer || "").toUpperCase();

    if (val.length === 0) {
      // no input → neutral
      cardFront.style.backgroundColor = "white";
      cardFront.style.color = "#111";
      return;
    }

    // check only the typed portion
    const expectedPortion = correct.slice(0, val.length);

    if (val === expectedPortion) {
      // typed letters so far are correct
      cardFront.style.backgroundColor = "#0a0"; // green
    } else {
      // mismatch
      cardFront.style.backgroundColor = "#f88"; // red
    }

    cardFront.style.color = "#111";
  });

  function applyScoring() {
    totalAnswers++;
    if (!currentWordObj) return;
    const val = cardInput.value.trim().toUpperCase();
    const correct = (currentWordObj.answer || "").toUpperCase();

    if (val === correct) {
      currentWordObj.value = Math.max(0, currentWordObj.value - 1);
      answerDisplay.textContent = "";
      correctCount++;
    } else {
      currentWordObj.value += 1;
      answerDisplay.textContent = `Answer: ${currentWordObj.answer}`;
    }
    updateScoreDisplays();
  }

  // -------------- Flip logic --------------
  function startFlip() {
    if (animating) return;
    animating = true;
    rotation += 180;
    card.style.transform = `rotateY(${rotation}deg)`;
    doubleFlipPhase = 1;
  }

  card.addEventListener("transitionend", (e) => {
    if (e.propertyName !== "transform") return;

    if (doubleFlipPhase === 1) {
      // halfway point — swap content now
      showNextWord();
      rotation += 180;
      requestAnimationFrame(() => {
        card.style.transform = `rotateY(${rotation}deg)`;
      });
      doubleFlipPhase = 2;
      return;
    }

    if (doubleFlipPhase === 2) {
      animating = false;
      doubleFlipPhase = 0;
      answerDisplay.textContent = "";
      cardInput.value = "";
      cardInput.focus();
    }
  });

  // -------------- Controls --------------
  button.addEventListener("click", () => {
    if (animating) return;
    applyScoring();
    startFlip();
  });

  celebration.addEventListener("click", () => {
    celebrate();
  });

  cardInput.addEventListener("input", () => {
    startTimer();
  });

  cardInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      if (animating) return;
      applyScoring();
      startFlip();
    }
  });

  pauseBtn.addEventListener("click", togglePause);
  /* celebration: confetti + sound */
  function celebrate() {
    // Stop the timer
    clearInterval(timerInterval);
    timerStarted = false;

    // avoid repeating celebration multiple times
    if (window._celebrated) return;
    window._celebrated = true;

    // final time
    const finalTimeSeconds = totalSeconds;
    const finalTimeDisplay = timerDisplay.textContent.replace("Time: ", "");

    // confetti
    if (typeof confetti === "function") {
      const duration = 3000;
      const end = Date.now() + duration;
      (function frame() {
        confetti({ particleCount: 3, angle: 60, spread: 55, origin: { x: 0 } });
        confetti({
          particleCount: 3,
          angle: 120,
          spread: 55,
          origin: { x: 1 },
        });
        if (Date.now() < end) requestAnimationFrame(frame);
      })();
    }

    // play tada
    tadaSound.play().catch(() => {
      /* autoplay may be blocked until user interaction */
    });

    // UI updates
    quizStatus.textContent = "🎉 You finished all flashcards!";
    cardInput.disabled = true;
    button.disabled = true;

    // Show the back of the card
    cardFront.textContent = "";
    card.style.transform = "rotateY(0deg)"; // ensure card is showing the back
    cardFront.style.backgroundColor = "#eee"; // optional back color
    cardFront.style.color = "#111";

    // --- Save history ---
    const data = {
      user_id: CURRENT_USER_ID,
      list_id: CURRENT_LIST_ID,
      completed_at: new Date().toISOString().slice(0, 19).replace("T", " "),
      final_time_seconds: finalTimeSeconds,
      final_time_display: finalTimeDisplay,
      correct_total: correctCount,
      total_questions: totalAnswers, // this matches your table
    };

    fetch("save_history.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    })
      .then((resp) => resp.json())
      .then((res) => {
        if (res.success) {
          console.log("History saved successfully");
          // Show table of completed lists
          setTimeout(() => {
            showHistoryModal(CURRENT_LIST_ID, data.completed_at);
          }, 4000);
        } else {
          console.error("Failed to save history:", res.message);
        }
      })
      .catch((err) => console.error("Error saving history:", err));

    // --- Show results in the interface ---
  }

  loadListBtn.addEventListener("click", () => {
    const selectedId = listDropdown.value;
    if (!selectedId) {
      alert("Please select a list first.");
      return;
    }

    loadFlashcards(selectedId);

    // TODO: Fetch the flashcards for this list from server
    // and reset your flashcard cycle/overall counters
  });
  async function loadFlashcards(listId) {
    CURRENT_LIST_ID = listId;
    window._celebrated = false;
    try {
      const res = await fetch(`get_flashcards.php?list_id=${listId}`);
      const data = await res.json();

      if (!data.success) {
        alert("Error loading flashcards: " + data.message);
        return;
      }

      if (!Array.isArray(data.flashcards) || data.flashcards.length === 0) {
        alert("No flashcards found for this list.");
        return;
      }

      // Reset all state
      allWords = data.flashcards.map((item) => ({
        question: item.question,
        answer: item.answer,
        value: 3,
        inCycle: false,
        mastered: false,
      }));

      totalCards = allWords.length;

      nextBatchIndex = 0;
      currentCycleWords = [];
      passQueue = [];

      // Initialize the random pool
      unusedWords = [...allWords];

      // Pick 5 random unique words
      currentCycleWords = getRandomWords(5);

      prepareNextPass();
      showNextWord();
      updateScoreDisplays();

      console.log(
        `Loaded ${allWords.length} flashcards from list ID ${listId}`
      );
    } catch (err) {
      console.error("Failed to load flashcards:", err);
    }
  }

  // --- constants ---

  // --- event listener ---
  restartButton.addEventListener("click", () => {
    if (!confirm("Are you sure you want to restart? All progress will be lost."))
      return;
  
    resetGame(3); // normal mode
  });
  

  function getRandomWords(count) {
    if (unusedWords.length < count) {
      // refill if all words used
      unusedWords = [...allWords];
    }

    const selected = [];
    for (let i = 0; i < count && unusedWords.length > 0; i++) {
      const index = Math.floor(Math.random() * unusedWords.length);
      selected.push(unusedWords.splice(index, 1)[0]);
    }

    // mark selected as "in cycle"
    selected.forEach((w) => (w.inCycle = true));

    return selected;
  }

  function startTimer() {
    if (timerStarted) return;
    timerStarted = true;

    timerInterval = setInterval(() => {
      if (!isPaused) {
        totalSeconds++;
        updateTimerDisplay();
      }
    }, 1000);
  }

  function updateTimerDisplay() {
    const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, "0");
    const seconds = String(totalSeconds % 60).padStart(2, "0");
    timerDisplay.textContent = `Time: ${minutes}:${seconds}`;
  }

  const pauseOverlay = document.getElementById("pause-overlay");

  function togglePause() {
    isPaused = !isPaused;

    if (isPaused) {
      pauseBtn.textContent = "Resume";
      cardInput.disabled = true;
      pauseOverlay.classList.add("visible");
    } else {
      pauseBtn.textContent = "Pause";
      cardInput.disabled = false;
      pauseOverlay.classList.remove("visible");
      cardInput.focus();
    }
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      e.preventDefault(); // optional but recommended
      togglePause();
    }
  });

  function updateProgressBars() {
    // get the DOM elements (guarding in case DOM not ready)
    const cycleFill = document.getElementById("cycle-fill");
    const overallFill = document.getElementById("overall-fill");
    const cyclePercentLabel = document.getElementById("cycle-percent");
    const overallPercentLabel = document.getElementById("overall-percent");
    const cycleValueEl = document.getElementById("cycle-value");
    const overallValueEl = document.getElementById("overall-value");

    if (
      !cycleFill ||
      !overallFill ||
      !cyclePercentLabel ||
      !overallPercentLabel ||
      !cycleValueEl ||
      !overallValueEl
    ) {
      // missing elements — nothing to update
      return;
    }

    // derive current numeric totals from your arrays (guarding for missing data)
    const cycleSum = Array.isArray(allWords)
      ? allWords
          .filter((w) => w.inCycle && w.value > 0)
          .reduce((s, w) => s + (w.value || 0), 0)
      : 0;
    const overallSum = Array.isArray(allWords)
      ? allWords.reduce((s, w) => s + (w.value || 0), 0)
      : 0;

    // Fallback for starting totals: if not defined, set them to current sums (prevents divide-by-zero)
    if (!totalStartingValue || totalStartingValue <= 0)
      totalStartingValue = overallSum || 1;
    if (!cycleStartingValue || cycleStartingValue <= 0)
      cycleStartingValue = cycleSum || 1;

    // percent remaining (0..100) — clamp values
    const cyclePercentRemaining = Math.max(
      0,
      Math.min(100, (cycleSum / cycleStartingValue) * 100)
    );
    const overallPercentRemaining = Math.max(
      0,
      Math.min(100, (overallSum / totalStartingValue) * 100)
    );

    // visual fill height is inverse (100% remaining -> fill 100% height)
    cycleFill.style.height = cyclePercentRemaining + "%";
    overallFill.style.height = overallPercentRemaining + "%";

    cycleFill.style.backgroundColor = getProgressColor(cyclePercentRemaining);
    overallFill.style.backgroundColor = getProgressColor(
      overallPercentRemaining
    );

    cyclePercentLabel.textContent = `${Math.round(
      100 - cyclePercentRemaining
    )}%`;
    overallPercentLabel.textContent = `${Math.round(
      100 - overallPercentRemaining
    )}%`;

    cycleValueEl.textContent = cycleSum;
    overallValueEl.textContent = overallSum;
  }

  function getProgressColor(percent) {
    let r,
      g,
      b = 0;

    if (percent >= 70) {
      const ratio = (100 - percent) / 30; // red → yellow
      r = 255;
      g = Math.round(255 * ratio);
    } else if (percent >= 20) {
      const ratio = (70 - percent) / 50; // yellow → deep green
      r = Math.round(255 * (1 - ratio)); // fade red down
      g = Math.round(255 - 127 * ratio); // fade green toward 128
      b = Math.round(10 * ratio); // add a touch of blue
    } else {
      // final deep green
      r = 6;
      g = 128;
      b = 10;
    }

    return `rgb(${r},${g},${b})`;
  }

  // ---------- History modal helper ----------
  (function () {
    let lightInterval = null;
    let lightsEls = [];
    let lastHighlightedKey = null;

    function positionLights(cardEl, lightsContainer, num) {
      // place `num` lights around the perimeter evenly
      const rect = cardEl.getBoundingClientRect();
      const w = rect.width;
      const h = rect.height;
      const pad = 8; // offset from edge
      lightsContainer.innerHTML = "";

      for (let i = 0; i < num; i++) {
        const span = document.createElement("span");
        span.className = "light";
        span.style.left = "0px";
        span.style.top = "0px";
        lightsContainer.appendChild(span);
      }

      const lights = lightsContainer.querySelectorAll(".light");
      lightsEls = Array.from(lights);

      for (let i = 0; i < lightsEls.length; i++) {
        const ratio = i / lightsEls.length;
        let x = 0,
          y = 0;
        if (ratio < 0.25) {
          // top edge, left -> right
          const t = ratio / 0.25;
          x = pad + t * (w - pad * 2);
          y = pad;
        } else if (ratio < 0.5) {
          // right edge, top->bottom
          const t = (ratio - 0.25) / 0.25;
          x = w - pad;
          y = pad + t * (h - pad * 2);
        } else if (ratio < 0.75) {
          // bottom edge, right->left
          const t = (ratio - 0.5) / 0.25;
          x = w - pad - t * (w - pad * 2);
          y = h - pad;
        } else {
          // left edge, bottom->top
          const t = (ratio - 0.75) / 0.25;
          x = pad;
          y = h - pad - t * (h - pad * 2);
        }
        lightsEls[i].style.left = `${x}px`;
        lightsEls[i].style.top = `${y}px`;

        // set a color cycling (neon palette)
        const palette = [
          "#FF4D4D",
          "#FFD24D",
          "#4DF0FF",
          "#6DFF8A",
          "#B86DFF",
          "#FF6DCB",
        ];
        lightsEls[i].style.color = palette[i % palette.length];
        lightsEls[i].style.background = "rgba(255,255,255,0.06)";
      }
    }

    function startLightRunner() {
      if (!lightsEls || lightsEls.length === 0) return;
      let idx = 0;
      if (lightInterval) clearInterval(lightInterval);

      lightInterval = setInterval(() => {
        lightsEls.forEach((el, i) => {
          if (i === idx) {
            // dim traveling light
            el.classList.add("dim");
          } else {
            // lit “sun-like” bulbs
            el.classList.remove("dim");
          }
        });
        idx = (idx + 1) % lightsEls.length;
      }, 120);
    }

    function stopLightRunner() {
      if (lightInterval) {
        clearInterval(lightInterval);
        lightInterval = null;
      }
    }

    // Build table rows and insert into container
    function buildHistoryTable(historyRows, justFinishedListId) {
      const cols = [
        { key: "list_name", label: "List" },
        { key: "correct_total", label: "Correct" },
        { key: "total_questions", label: "Total" },
        { key: "final_time_display", label: "Time" },
        { key: "completed_at", label: "Completed" },
      ];

      let table = document.createElement("table");
      table.className = "history-table";
      let thead = document.createElement("thead");
      let thr = document.createElement("tr");
      cols.forEach((c) => {
        let th = document.createElement("th");
        th.textContent = c.label;
        thr.appendChild(th);
      });
      thead.appendChild(thr);
      table.appendChild(thead);

      let tbody = document.createElement("tbody");

      // highlight first row matching justFinishedListId (most recent match)
      let firstMatchFound = false;

      historyRows.forEach((row) => {
        let tr = document.createElement("tr");

        // highlight logic: first row where list_id == justFinishedListId
        if (
          !firstMatchFound &&
          String(row.list_id) === String(justFinishedListId)
        ) {
          tr.classList.add("highlighted");
          firstMatchFound = true;
        }

        cols.forEach((c) => {
          const td = document.createElement("td");
          td.textContent = row[c.key] ?? "";
          tr.appendChild(td);
        });

        tbody.appendChild(tr);
      });

      table.appendChild(tbody);
      return table;
    }

    // Show modal & fetch history
    window.showHistoryModal = function (justFinishedListId) {
      const modal = document.getElementById("historyModal");
      const lightsContainer = document.getElementById("historyLights");
      const card = modal.querySelector(".history-card");
      const container = document.getElementById("historyTableContainer");
      const summary = document.getElementById("historySummary");

      // show overlay
      modal.style.display = "flex";
      document.getElementById("historyBackdrop").style.display = "block";
      modal.setAttribute("aria-hidden", "false");

      // clear previous content
      container.innerHTML = "";
      summary.textContent = "Loading your completed sessions…";

      // fetch history for current user
      fetch("get_history.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: CURRENT_USER_ID }),
      })
        .then((r) => {
          // check content-type is json-ish first
          const ct = r.headers.get("content-type") || "";
          if (!ct.includes("application/json")) {
            return r.text().then((t) => {
              throw new Error("Invalid JSON response: " + t.slice(0, 200));
            });
          }
          return r.json();
        })
        .then((data) => {
          if (!data.success) {
            throw new Error(data.message || "Failed to load history");
          }

          const rows = data.history || [];
          if (!Array.isArray(rows) || rows.length === 0) {
            summary.textContent = "No completed sessions found yet.";
            return;
          }

          // show short summary
          summary.textContent = `Showing ${rows.length} completed sessions (most recent first).`;

          // build table
          const table = buildHistoryTable(rows, justFinishedListId);
          container.innerHTML = "";
          container.appendChild(table);

          // After DOM inserted, position lights around card
          // Use a small delay so the layout is stable
          setTimeout(() => {
            const lightsCount = 36;
            positionLights(card, lightsContainer, lightsCount);
            startLightRunner();
          }, 60);
        })
        .catch((err) => {
          summary.textContent = "Error loading history";
          console.error("History fetch error:", err);
          container.innerHTML = `<div style="color:#f88; padding:12px;">${String(
            err.message
          ).slice(0, 200)}</div>`;
        });
    };

    // close modal
    function closeModal() {
      const modal = document.getElementById("historyModal");
      modal.style.display = "none";
      modal.setAttribute("aria-hidden", "true");
      stopLightRunner();
    }

    // hook close buttons
    document.addEventListener("click", function (e) {
      if (
        e.target &&
        (e.target.id === "historyCloseBtn" || e.target.id === "historyOkBtn")
      ) {
        closeModal();
      }
      // clicking outside the card closes (backdrop)
      if (e.target && e.target.id === "historyBackdrop") closeModal();
    });

    // expose close function
    window.closeHistoryModal = closeModal;
  })();

  function resetAllWordValues() {
    const required = reviewMode ? 1 : 3;

    allWords.forEach((word) => {
      if (word.value > 0) {
        word.value = required;
      }
    });

    overall = allWords.reduce((sum, w) => sum + w.value, 0);
  }

  function getRequiredValue() {
    return reviewMode ? 1 : 3;
  }

  function resetGame(baseScore) {

    // stop celebration / game-over state
    window._celebrated = false;
    gameOver = false;
  
    allWords.forEach((w) => {
      w.value = baseScore;
      w.inCycle = false;
      w.mastered = false;
    });
  
    nextBatchIndex = 0;
    currentCycleWords = [];
    passQueue = [];
  
    startNewCycle(5);
    prepareNextPass();
    showNextWord();
    updateScoreDisplays();
  
    cardInput.disabled = false;
    button.disabled = false;
  
    clearInterval(timerInterval);
    totalSeconds = 0;
    updateTimerDisplay();
    timerStarted = false;
    isPaused = false;
    pauseBtn.textContent = "Pause";
  
    quizStatus.textContent = "Restarted! Let's go again 🚀";
    setTimeout(() => {
      quizStatus.textContent = "";
    }, 1800);
  }
  
  
  
  
  

  //ADD NEW FUNCTIONS HERE
});
