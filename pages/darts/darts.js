document.addEventListener("DOMContentLoaded", () => {
  loadDartSessions();

  // ================================
  // CONFIG
  // ================================
  const svg = document.getElementById("dartboard");
  const MARKER_COLOR = "#66ccff";
  const markers = [];

  const DEBUG_STATS = true;

  let totalDarts = 0;
  const markersByTurn = {};

  let games = [];

  let turnNumber = 1;
  let startingScore = 501;

  let gameFinished = true;
  let currentTurns = []; // keeps all turns for the current game

  let remainingScore = startingScore;
  let ricochetMode = false;

  let turnStartRemaining = remainingScore;

  let currentTarget = getTarget(remainingScore);
  highlightTarget(currentTarget.score, currentTarget.multiplier);
  prepareNextTarget();

  const scoreboardBody = document.getElementById("scoreboard-body");
  const remainingSpan = document.getElementById("remaining-score");

  // ================================
  // DART SESSION STATE (B)
  // ================================
  let currentSessionId = null;
  let currentGameId = null;
  let sessionActive = false;

  let turnStartScore = null;

  document
    .getElementById("newSessionBtn")
    .addEventListener("click", startNewSession);

  document
    .getElementById("loadSessionBtn")
    .addEventListener("click", loadSelectedSession);

  document.getElementById("closeStatsBtn").addEventListener("click", () => {
    const modal = document.getElementById("gameStatsModal");
    modal.style.display = "none";
  });

  const gameStatsBtn = document.getElementById("btn-show-stats");

  if (gameStatsBtn) {
    gameStatsBtn.addEventListener("click", () => {
      if (!currentGameId) {
        console.warn("No active game for stats");
        return;
      }
      showGameStatsModal(currentGameId);
    });
  }

  const newGameBtn = document.getElementById("btn-new-game");

  newGameBtn.addEventListener("click", () => {
    createNewGame();
  });

  document.addEventListener("change", (e) => {
    if (!e.target.classList.contains("turn-toggle")) return;

    const turnId = e.target.dataset.turnId;
    const show = e.target.checked;

    toggleTurnMarkers(turnId, show);
  });

  document
    .getElementById("toggle-all-turns")
    .addEventListener("change", (e) => {
      const checked = e.target.checked;

      document.querySelectorAll(".turn-toggle").forEach((cb) => {
        cb.checked = checked;
        toggleTurnMarkers(cb.dataset.turnId, checked);
      });
    });

  let setTargetMode = false;
  const setTargetBtn = document.getElementById("btn-set-target");
  setTargetBtn.addEventListener("click", () => {
    setTargetMode = !setTargetMode;
    setTargetBtn.classList.toggle("active", setTargetMode); // optional visual feedback
  });

  const ricochetBtn = document.getElementById("btn-ricochet");

  ricochetBtn.addEventListener("click", () => {
    ricochetMode = !ricochetMode;
    ricochetBtn.classList.toggle("active", ricochetMode);
  });

  // ================================
  // STATE
  // ================================
  let dartIndex = 0;
  let turnTotal = 0;
  let boardLocked = false;
  let darts = [];

  const dartCells = [
    document.getElementById("d1"),
    document.getElementById("d2"),
    document.getElementById("d3"),
  ];

  const totalCell = document.getElementById("turnTotal");
  const confirmBtn = document.getElementById("confirmTurn");

  if (!svg || !confirmBtn) {
    console.error("Dartboard elements missing from DOM");
    return;
  }
  // ================================
  // BOARD CLICK (DEBUG VERSION)
  // ================================
  svg.addEventListener("click", (e) => {
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const cursor = pt.matrixTransform(svg.getScreenCTM().inverse());

    const el = e.target;

    // 1️⃣ Set Target Mode
    if (setTargetMode && el && el.classList.contains("scoring-segment")) {
      const score = Number(el.dataset.value);
      const multiplier = Number(el.dataset.multiplier) || 1;
      currentTarget = { score, multiplier };
      // console.log("Manual target set:", currentTarget);

      highlightTarget(score, multiplier);
      +updateTargetText(currentTarget); // ✅ ADD THIS
      setTargetMode = false;
      setTargetBtn.classList.remove("active");
      return;
    }

    // 2️⃣ Normal dart throw mode
    if (boardLocked || dartIndex >= 3) return;

    let score = 0;
    let segment = "MISS";

    if (
      el &&
      (el.tagName === "path" || el.tagName === "use" || el.tagName === "circle")
    ) {
      const value = Number(el.dataset.value);
      const multiplier = Number(el.dataset.multiplier);

      if (!isNaN(value) && !isNaN(multiplier)) {
        score = value * multiplier;
        segment = `${multiplier}x${value}`;
      }
    }

    // 3️⃣ Ricochet mode
    if (ricochetMode) {
      placeRicochetMarker(cursor.x, cursor.y);
      score = 0;
      segment = "R";
      ricochetMode = false;
      ricochetBtn.classList.remove("active");
      // 4️⃣ Record dart
      darts.push({
        dart: dartIndex + 1,
        x: cursor.x,
        y: cursor.y,
        score,
        segment,
      });
    } else {
      placeMarker(cursor.x, cursor.y);
    }

    dartCells[dartIndex].textContent = score;
    turnTotal += score;
    totalCell.textContent = turnTotal;

    // Determine actual dart multiplier/value
    let multiplier = 1;
    let value = 0;
    if (
      el &&
      (el.tagName === "path" || el.tagName === "use" || el.tagName === "circle")
    ) {
      value = Number(el.dataset.value);
      multiplier = Number(el.dataset.multiplier) || 1;
    }

    // --------------------
    // Step 1: Hit target check
    // --------------------
    // Step 1: Only check if there’s a current target and a valid score
    if (currentTarget && score > 0) {
      // Determine if this dart hit the target
      const hitTarget =
        value === currentTarget.score &&
        multiplier === currentTarget.multiplier;

      // Build an array of classes to apply (for live AND history tables)
      const dartClasses = [];
      if (hitTarget) {
        dartClasses.push("hit-target");
        if (currentTarget.multiplier === 3) dartClasses.push("triple");
        else if (currentTarget.multiplier === 2) dartClasses.push("double");
        else dartClasses.push("single");
      }

      // ----------------------
      // Store the dart for live table and history later
      // ----------------------
      darts.push({
        dart: dartIndex + 1,

        // HIT info
        value,
        multiplier,
        ring: multiplier === 3 ? "T" : multiplier === 2 ? "D" : "S",
        score,
        segment: `${multiplier}x${value}`,

        // AIMED info (safe)
        aimedRing: currentTarget
          ? currentTarget.multiplier === 3
            ? "T"
            : currentTarget.multiplier === 2
            ? "D"
            : "S"
          : null,

        aimedValue: currentTarget?.score ?? null,

        hitTarget,

        classes: dartClasses,
        x: cursor.x,
        y: cursor.y,
      });

      // ----------------------
      // Apply classes to live table
      // ----------------------
      dartCells[dartIndex].textContent = score;
      // Clear old classes
      dartCells[dartIndex].classList.remove(
        "hit-target",
        "single",
        "double",
        "triple"
      );
      // Apply stored classes
      dartClasses.forEach((cls) => dartCells[dartIndex].classList.add(cls));
    }
    // --------------------------------------
    // Bust validation + Update remaining score
    // --------------------------------------
    const nextRemaining = remainingScore - score;

    // Check for double-out win
    const isDoubleOut = nextRemaining === 0 && multiplier === 2;
    if (isDoubleOut) {
      // console.log("DOUBLE OUT! 🎉");
      boardLocked = true; // disable further throws
      // TODO: trigger visual celebration here
    }

    // Valid finish requires a double (bull is D25 automatically because multiplier=2)
    const isDoubleFinish = multiplier === 2;

    const isBust =
      nextRemaining < 0 ||
      nextRemaining === 1 ||
      (nextRemaining === 0 && !isDoubleFinish);

    if (isBust) {
      bustThisTurn = true;

      // console.log("BUST!", {
      //   remainingBefore: remainingScore,
      //   score,
      //   multiplier,
      //   nextRemaining,
      //   turnStartRemaining
      // });

      // Mark the dart that caused the bust as strike-out
      dartCells[dartIndex].classList.add("bust-dart");

      // Keep the real score visible
      dartCells[dartIndex].textContent = score;

      // Optional: mark all previous darts in the turn as strike-out
      dartCells.forEach((cell, idx) => {
        if (idx < dartIndex && cell.textContent !== "")
          cell.classList.add("bust-dart");
      });

      // Reset turn total
      turnTotal = 0;
      totalCell.textContent = "0";

      // Revert remaining score for the turn
      remainingScore = turnStartRemaining;
      remainingSpan.textContent = remainingScore;

      // Fill subsequent darts (not yet thrown) as 0
      for (let i = dartIndex + 1; i < 3; i++) {
        darts[i] = {
          dart: i + 1,
          x: null,
          y: null,
          score: 0,
          segment: "MISS",
          value: 0,
          multiplier: 1,
          hitTarget: false,
          classes: ["bust-dart"],
        };

        dartCells[i].textContent = 0;
        dartCells[i].classList.add("bust-dart");
      }

      // Lock the board
      boardLocked = true;

      // Stop further processing
      return;
    }

    // Normal scoring for non-bust darts
    remainingScore = nextRemaining;
    remainingSpan.textContent = remainingScore;

    // Not a bust → commit normally
    remainingScore = nextRemaining;
    remainingSpan.textContent = remainingScore;

    prepareNextTarget();

    dartIndex++;
    if (dartIndex === 3) boardLocked = true;

    // --------------------------
    // Recalculate  target
    // --------------------------
    if (!setTargetMode) {
      const target = getTarget(remainingScore);
      currentTarget = target;
      highlightTarget(target.score, target.multiplier);
      // console.log(" target:", target);
    }
  });

  // ================================
  // MARKER
  // ================================
  function placeMarker(x, y) {
    saveTurnToDb;
    if (ricochetMode) {
      placeRicochetMarker(x, y, turnNumber);
    } else {
      placeNormalMarker(x, y, turnNumber);
    }
  }
  // ================================
  // CONFIRM TURN
  // ================================
  confirmBtn.addEventListener("click", () => {
    console.group("CONFIRM TURN DEBUG");
    console.log("darts array:", darts);
    console.log("turnTotal:", turnTotal);
    console.log("remainingScore BEFORE:", remainingScore);

    if (darts.length > 0) {
      darts.forEach((d, i) => {
        // -------------------------------
        // Build aimed info (🔥 fix)
        // -------------------------------
        const aimedRing = currentTarget
          ? currentTarget.multiplier === 3
            ? "T"
            : currentTarget.multiplier === 2
            ? "D"
            : "S"
          : null;

        const aimedValue = currentTarget?.score ?? null;

        // Attach aimed info to dart for stats
        d.aimedRing = aimedRing;
        d.aimedValue = aimedValue;

        // Log everything for this dart
        console.log(`🎯 Dart ${i + 1}:`, {
          value: d.value,
          multiplier: d.multiplier,
          ring: d.multiplier === 3 ? "T" : d.multiplier === 2 ? "D" : "S",
          segment: `${d.multiplier}x${d.value}`,
          hitTarget: d.hitTarget ?? false,
          aimedRing,
          aimedValue,
          dartIndex: i + 1,
          classes: d.classes ?? [],
          x: d.x,
          y: d.y,
        });
      });
    }

    console.groupEnd();
    if (darts.length === 0) return; // skip if no darts thrown

    // 1️⃣ Commit the turn (remaining score already updated live)
    remainingSpan.textContent = remainingScore;

    // 2️⃣ Build classes array for each dart if not already done
    darts.forEach((dart, i) => {
      if (!dart.classes) {
        const hitTarget =
          currentTarget &&
          dart.value === currentTarget.score &&
          dart.multiplier === currentTarget.multiplier;

        const dartClasses = [];
        if (hitTarget) {
          dartClasses.push("hit-target");
          if (currentTarget.multiplier === 3) dartClasses.push("triple");
          else if (currentTarget.multiplier === 2) dartClasses.push("double");
          else dartClasses.push("single");
        }

        dart.classes = dartClasses; // store for history
        dart.hitTarget = hitTarget; // optional
        dart.dart = i + 1; // 1-based index for table cells
      }
    });

    const winningDart = darts.find(
      (d) => remainingScore === 0 && d.multiplier === 2
    );

    // 4️⃣ Add row to history table
    addLiveTurnRow(darts, turnTotal, remainingScore, turnNumber);

    // 5️⃣ Save turn to DB BEFORE clearing/resetting
    if (currentSessionId && currentGameId) {
      const payload = buildTurnPayload();
      console.log("Saving turn payload:", payload);
      saveTurnToDb(payload);
      // ✅ Add to in-memory turns so stats work immediately
      currentTurns.push(payload);
    } else {
      console.log("No active session/game — skipping DB save");
    }

    if (winningDart) {
      console.log("✅ winningDart detected:", winningDart);
      finishGame("double_out"); // updates DB
      return;
    }

    // 6️⃣ Clear live turn highlights
    dartCells.forEach((cell) => {
      cell.classList.remove("hit-target", "single", "double", "triple");
      cell.textContent = "";
    });

    // 7️⃣ Clear markers and reset live turn
    clearMarkers();
    resetTurn();

    // 8️⃣ Prepare next target (if automated)
    prepareNextTarget();

    // 9️⃣ Increment turn number
    turnNumber++;

    console.log("markersByTurn:", markersByTurn);

  });

  // REPLACED BY BELOW
  // function clearMarkers() {
  //   document.querySelectorAll(".dart-marker").forEach((m) => m.remove());
  // }

  function clearMarkers() {
    document.querySelectorAll(".dart-marker").forEach((m) => {
      m.style.display = "none";
    });
  }
  

  function resetTurn() {
    dartIndex = 0;
    turnTotal = 0;
    boardLocked = false;
    darts = [];

    dartCells.forEach((c) => (c.textContent = ""));
    totalCell.textContent = "0";
    turnStartRemaining = remainingScore;
  }

  document.getElementById("undo-btn").addEventListener("click", () => {
    if (darts.length === 0) return;

    // 1️⃣ Remove last dart data
    const lastDart = darts.pop();

    // 2️⃣ Remove marker
    const lastMarker = markers.pop();
    if (lastMarker) lastMarker.remove();

    // 3️⃣ Restore remaining score
    remainingScore += lastDart.score;
    document.getElementById("remaining-score").textContent = remainingScore;

    // Calculate new target
    const calculated = getTarget(remainingScore);
    highlightTarget(calculated.score, calculated.multiplier);
    prepareNextTarget();

    // 4️⃣ Update scoreboard
    dartIndex--;
    dartCells[dartIndex].textContent = "";

    turnTotal -= lastDart.score;
    totalCell.textContent = turnTotal;

    // 5️⃣ Unlock board if needed
    boardLocked = false;
  });

  function addLiveTurnRow(darts, turnTotal, remainingScore, turnNumber) {
    const tbody = document.getElementById("scoreboard-body");
    if (!tbody) return;

    const tr = document.createElement("tr");

    /* =========================
       Checkbox column
    ========================= */
    const tdCheck = document.createElement("td");
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.classList.add("turn-toggle");
    checkbox.dataset.turnId = turnNumber;

//    checkbox.dataset.turnNumber = turnNumber;  REPLACED BY ABOVE
    checkbox.checked = true; // optional default

    tdCheck.appendChild(checkbox);
    tr.appendChild(tdCheck);

    /* =========================
       Turn number
    ========================= */
    const tdTurn = document.createElement("td");
    tdTurn.textContent = turnNumber;
    tr.appendChild(tdTurn);

    /* =========================
       Dart 1–3
    ========================= */
    for (let i = 0; i < 3; i++) {
      const td = document.createElement("td");

      if (darts[i]) {
        const d = darts[i];
        const dartScore = d.value * d.multiplier;
        td.textContent = dartScore;
      } else {
        td.textContent = "-";
      }

      tr.appendChild(td);
    }

    /* =========================
       Turn total
    ========================= */
    const tdTotal = document.createElement("td");
    tdTotal.textContent = turnTotal;
    tr.appendChild(tdTotal);

    /* =========================
       Remaining score
    ========================= */
    const tdRemaining = document.createElement("td");
    tdRemaining.textContent = remainingScore;
    tr.appendChild(tdRemaining);

    tbody.appendChild(tr);
  }

  function highlightTarget(segmentNumber, multiplier = 1) {
    // Clear any existing target highlight
    document.querySelectorAll(".target-suggest").forEach((el) => {
      el.classList.remove("target-suggest");
    });

    // Find the element for this segment
    const elements = svg.querySelectorAll(
      `[data-value='${segmentNumber}'][data-multiplier='${multiplier}']`
    );
    elements.forEach((el) => el.classList.add("target-suggest"));
  }

  function clearTargetHighlight() {
    document.querySelectorAll(".target-suggest").forEach((el) => {
      el.classList.remove("target-suggest");
    });
  }
  function getTarget(remainingScore) {
    const preferredFinishes = [40, 32, 20, 16, 10, 8, 4, 2];

    // 1️⃣ High score → triple 20
    if (remainingScore > 60) {
      return { score: 20, multiplier: 3 };
    }

    // 2️⃣ Exact double-out (even numbers ≤ 40)
    if (remainingScore % 2 === 0 && remainingScore <= 40) {
      return { score: remainingScore / 2, multiplier: 2 };
    }

    // 3️⃣ Double bull finish
    if (remainingScore === 50) {
      return { score: 25, multiplier: 2 }; // D25
    }

    // 4️⃣ Setup shot → leave a preferred double
    for (let finish of preferredFinishes) {
      const needed = remainingScore - finish;
      if (needed >= 1 && needed <= 20) {
        return { score: needed, multiplier: 1 };
      }
    }

    // 5️⃣ Safe single fallback
    return { score: Math.min(20, remainingScore), multiplier: 1 };
  }

  function updateSuggestedTargetText({ score, multiplier }) {
    const el = document.getElementById("suggested-target");
    if (!el) return;

    let label = "";

    if (multiplier === 3) label = `T${score}`;
    else if (multiplier === 2) label = `D${score}`;
    else label = `S${score}`;

    el.textContent = label;
  }

  function placeNormalMarker(x, y, turnId) {
    const marker = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );

    marker.setAttribute("cx", x);
    marker.setAttribute("cy", y);
    marker.setAttribute("r", 2);
    marker.setAttribute("fill", MARKER_COLOR);
    marker.classList.add("dart-marker");

  // 🔒 CRITICAL: marker never blocks clicks
  marker.style.pointerEvents = "none";

    // start hidden by default (important)
    // marker.style.display = "none";

    svg.appendChild(marker);

    // register marker by turn
    if (!markersByTurn[turnId]) {
      markersByTurn[turnId] = [];
    }
    markersByTurn[turnId].push(marker);

    console.log(turnId, markersByTurn);

  }

  function placeRicochetMarker(x, y) {
    const size = 4;

    const line1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );
    const line2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "line"
    );

    line1.setAttribute("x1", x - size);
    line1.setAttribute("y1", y - size);
    line1.setAttribute("x2", x + size);
    line1.setAttribute("y2", y + size);

    line2.setAttribute("x1", x - size);
    line2.setAttribute("y1", y + size);
    line2.setAttribute("x2", x + size);
    line2.setAttribute("y2", y - size);

    [line1, line2].forEach((line) => {
      line.setAttribute("stroke", MARKER_COLOR);
      line.setAttribute("stroke-width", "1.5");
      line.classList.add("dart-marker", "ricochet-marker");
      svg.appendChild(line);
      markers.push(line);
    });
  }

  function updateTargetText(target) {
    const el = document.getElementById("target-text");
    if (!el || !target) return;

    let prefix = "S";
    if (target.multiplier === 2) prefix = "D";
    else if (target.multiplier === 3) prefix = "T";

    el.textContent = `Target: ${prefix}${target.score}`;
  }

  function prepareNextTarget() {
    currentTarget = getTarget(remainingScore); // your automated target logic
    highlightTarget(currentTarget.score, currentTarget.multiplier);
    updateTargetText(currentTarget); // "Target: T20" etc
  }

  function celebrateDoubleOut() {
    return new Promise((resolve) => {
      // First burst
      confetti({
        particleCount: 150,
        spread: 70,
        origin: { y: 0.6 },
        colors: ["#ff0", "#f00", "#0f0", "#0ff", "#f0f"],
      });

      // Second burst
      setTimeout(() => {
        confetti({
          particleCount: 100,
          spread: 100,
          origin: { y: 0.6 },
          colors: ["#ff0", "#f00", "#0f0", "#0ff", "#f0f"],
        });

        // 🎉 Confetti sequence done
        resolve();
      }, 5000);
    });
  }

  function startNewGame() {
    console.log("Starting new game!");
    resetGameUI();
    unlockGameUI();
    prepareNextTarget();
  }

  function buildTurnPayload() {
    let turnResult = "normal";

    if (remainingScore === 0 && darts.some((d) => d.multiplier === 2)) {
      turnResult = "double_out";
    } else if (remainingScore < 0 || remainingScore === 1) {
      turnResult = "bust";
    }

    return {
      game_id: currentGameId,
      turn_number: turnNumber,
      start_score: turnStartRemaining,
      end_score: remainingScore,
      turn_result: turnResult,

      darts: darts.map((d, i) => ({
        throw_number: i + 1,

        /* ======================
           HIT info (unchanged)
        ====================== */
        hit_score: d.value ?? 0,
        ring:
          d.multiplier === 3
            ? "T"
            : d.multiplier === 2
            ? "D"
            : d.multiplier === 1
            ? "S"
            : null,

        segment: d.segment ?? d.value ?? null,
        x: d.x ?? null,
        y: d.y ?? null,

        hit_target: !!d.hitTarget,

        /* ======================
           🎯 AIMED info (NEW)
           DO NOT infer — pass through
        ====================== */
        aimed_ring: d.aimedRing ?? null,
        aimed_value: d.aimedValue ?? null,

        /* ======================
           Other flags
        ====================== */
        is_implied: d.isImplied ? 1 : 0,
      })),
    };
  }

  function loadDartSessions() {
    fetch("list_dart_sessions.php")
      .then((res) => res.json())
      .then((data) => {
        const select = document.getElementById("sessionSelect");
        select.innerHTML = ""; // clear

        data.sessions.forEach((sess) => {
          const opt = document.createElement("option");
          opt.value = sess.session_id;

          // Display as "Session #ID <name>"
          opt.textContent = `Session #${sess.session_id} ${sess.name}`;

          select.appendChild(opt);
        });

        // Optionally, highlight active session
        if (sessionActive && currentSessionId) {
          for (let i = 0; i < select.options.length; i++) {
            select.options[i].selected =
              select.options[i].value == currentSessionId;
          }
        }
      })
      .catch((err) => console.error("Failed to load sessions", err));
  }

  function startNewSession() {
    fetch("new_dart_session.php", { method: "POST" })
      .then((res) => res.json()) // <-- convert to JSON here
      .then((data) => {
        currentSessionId = data.session_id;
        currentGameId = data.game_id;
        sessionActive = true;

        updateActiveSessionUI();
        loadDartSessions();
      })
      .catch((err) => console.error("Start session failed", err));
  }

  function loadSelectedSession() {
    const select = document.getElementById("sessionSelect");
    if (!select.value) return;

    fetch(`load_dart_session.php?session_id=${select.value}`)
      .then((res) => res.json())
      .then((data) => {
        if (!data || data.error) {
          console.error("Session load failed:", data?.error);
          return;
        }

        /* -------------------------
           1️⃣ Session + Game identity
        ------------------------- */
        currentSessionId = data.session.session_id;
        currentGameId = data.game ? data.game.game_id : null;
        sessionActive = true;

        games = data.games;

        /* -------------------------
           2️⃣ Populate game dropdown
        ------------------------- */
        populateGameDropdown(data.games, currentGameId);

        /* -------------------------
           3️⃣ Clear UI-only state
           (do NOT touch scoring)
        ------------------------- */
        clearMarkers();
        clearTargetHighlight();
        resetTurnUI(); // ⚠️ UI reset only
        boardLocked = false;

        /* -------------------------
           4️⃣ Populate history + restore score
        ------------------------- */
        if (data.turns && data.turns.length > 0) {
          populateHistoryTable(data.turns);
        } else {
          // Fresh game
          turnNumber = 1;
          remainingScore = startingScore;
          turnStartRemaining = startingScore;

          const remainingEl = document.getElementById("remaining-score");
          if (remainingEl) remainingEl.textContent = remainingScore;
        }

        /* -------------------------
           5️⃣ Prepare next turn
        ------------------------- */
        prepareNextTarget();
        updateActiveSessionUI();

        // Optional sanity check (remove later)
        console.log("Session resumed:", {
          currentGameId,
          turnNumber,
          remainingScore,
          turnStartRemaining,
        });
      })
      .catch((err) => console.error("Load session failed:", err));
  }

  function updateActiveSessionUI() {
    const label = document.getElementById("activeSessionLabel");

    if (!sessionActive) {
      label.textContent = "No active session";
    } else {
      label.textContent = `Active Session #${currentSessionId}`;
    }
  }

  function saveTurnToDb(payload) {
    fetch("save_dart_turn.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.status !== "ok") {
          // console.error("Save failed:", data);
        } else {
          // console.log("Turn saved:", data.turn_id);
        }
      })
      .catch((err) => console.error("Save error:", err));
  }

  function buildThrowsPayload(turnId) {
    return {
      turn_id: turnId,
      throws: currentTurnThrows.map((t, index) => ({
        throw_number: index + 1,
        hit_score: t.score,
        ring: t.ring, // 'S','D','T'
        segment: t.segment, // 1–20 or 25
      })),
    };
  }

  function resetGameUI() {
    resetTurn();
    turnNumber = 1;
    remainingScore = startingScore;
    remainingSpan.textContent = remainingScore;

    // clear history
    const rows = scoreboardBody.querySelectorAll("tr");
    rows.forEach((row) => row.remove());

    clearMarkers();
    clearTargetHighlight();
    boardLocked = false;
  }

  function populateGameDropdown(games, activeGameId) {
    const gameSelect = document.getElementById("gameSelect");
    gameSelect.innerHTML = "";

    games.forEach((game) => {
      const opt = document.createElement("option");
      opt.value = game.game_id;
      opt.textContent = `Game #${game.game_number}`;

      if (game.game_id === activeGameId) {
        opt.selected = true;
      }

      gameSelect.appendChild(opt);
    });

    gameSelect.disabled = false;
  }

  function populateHistoryTable(turns) {
    clearHistoryTable();

    if (!turns || turns.length === 0) return;

    turns.forEach((turn) => addHistoryTurnRow(turn));

    const lastTurn = turns[turns.length - 1];

    turnNumber = lastTurn.turn_number + 1;
    remainingScore = lastTurn.end_score;
    turnStartRemaining = lastTurn.end_score; // 🔒 authoritative baseline

    const remainingEl = document.getElementById("remaining-score");
    if (remainingEl) remainingEl.textContent = remainingScore;
  }

  function clearHistoryTable() {
    const tbody = document.getElementById("scoreboard-body");
    if (tbody) {
      tbody.innerHTML = "";
    }
  }

  function addHistoryRow(turn) {
    const tbody = document.getElementById("scoreboard-body");
    if (!tbody) return;

    const tr = document.createElement("tr");

    /* =========================
       NEW: Checkbox column
    ========================= */
    const tdToggle = document.createElement("td");
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.classList.add("turn-toggle");
    checkbox.dataset.turnId = turn.turn_id; // ← critical
    tdToggle.appendChild(checkbox);
    tr.appendChild(tdToggle);

    /* =========================
       Turn #
    ========================= */
    const tdTurn = document.createElement("td");
    tdTurn.textContent = turn.turn_number;
    tr.appendChild(tdTurn);

    /* =========================
       Dart 1–3
    ========================= */
    let turnTotal = 0;

    for (let i = 0; i < 3; i++) {
      const td = document.createElement("td");
      const dart = turn.darts?.[i];

      if (dart) {
        const dartValue = dart.score * (dart.segment || 1);
        td.textContent = dartValue;
        turnTotal += dartValue;

        if (dart.hit_target) {
          td.classList.add("hit-target");

          if (dart.ring === "T") td.classList.add("triple");
          else if (dart.ring === "D") td.classList.add("double");
          else td.classList.add("single");
        }
      } else {
        td.textContent = "-";
      }

      tr.appendChild(td);
    }

    /* =========================
       Turn total
    ========================= */
    const tdTotal = document.createElement("td");
    tdTotal.textContent = turnTotal;
    tr.appendChild(tdTotal);

    /* =========================
       Remaining score
    ========================= */
    const tdScore = document.createElement("td");
    tdScore.textContent = turn.end_score;
    tr.appendChild(tdScore);

    tbody.appendChild(tr);
  }

  function updateGameHeader(gameNumber) {
    const el = document.getElementById("currentGameLabel");
    if (el) el.textContent = `Game ${gameNumber}`;
  }

  const gameSelect = document.getElementById("gameSelect");

  gameSelect.addEventListener("change", () => {
    const selectedGameId = parseInt(gameSelect.value, 10);

    if (!selectedGameId) return;

    currentGameId = selectedGameId;

    // 🔁 reload game state + history
    loadGameById(currentGameId);
  });

  function loadGameById(gameId) {
    console.log("📥 Loading game:", gameId);

    fetch(
      `load_dart_session.php?session_id=${currentSessionId}&game_id=${gameId}`
    )
      .then((res) => res.json())
      .then((data) => {
        games = data.games;
        if (data.status !== "ok") {
          console.error("Load failed", data);
          return;
        }

        currentGameId = data.game.game_id;

        // Reset UI
        clearHistoryTable();
        resetGameUI();

        // Store turns globally
        currentTurns = data.turns || [];
        console.log(currentTurns[0].darts);
        console.log("Turns data from server:", data.turns);



        // Rebuild history
        currentTurns.forEach((turn) => addHistoryRow(turn));
        rebuildMarkersFromThrows(currentTurns); // <--- Step 1
        console.log("Markers after rebuild:", markersByTurn);

        // Restore remaining score
        remainingScore = currentTurns.length
          ? currentTurns[currentTurns.length - 1].end_score
          : 501;

        document.getElementById("remaining-score").textContent = remainingScore;

        // Restore turn number
        turnNumber = currentTurns.length
          ? currentTurns[currentTurns.length - 1].turn_number + 1
          : 1;
        prepareNextTarget();
      })
      .catch((err) => console.error(err));
  }

  function createNewGame() {
    fetch("create_new_game.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        session_id: currentSessionId,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status !== "ok") {
          console.error("Game creation failed", data);
          return;
        }

        // ✅ Normalize response shape
        const newGame = {
          game_id: data.game_id,
          game_number: data.game_number,
        };

        // ✅ Update globals
        currentGameId = newGame.game_id;
        turnNumber = 1;
        remainingScore = 501;

        // ✅ Update dropdown and auto-select
        games.push(newGame);
        populateGameDropdown(games, currentGameId);

        // ✅ Reset UI
        clearHistoryTable();
        resetGameUI();
        unlockGameUI();
        prepareNextTarget();
        updateGameHeader(newGame.game_number);

        console.log(`🎯 New Game #${newGame.game_number} started`);
      })
      .catch((err) => console.error(err));
  }

  async function finishGame(resultType = "finished") {
    // if (gameFinished) return;

    console.log("🏁 Finishing game");

    gameFinished = true;
    boardLocked = true;

    // 🎉 Celebration first
    await celebrateDoubleOut();

    // ✅ Build stats
    const turns = collectAllTurns(); // function to get all turns for currentGame
    const stats = calculateGameStats(turns);

    // 📊 Then stats
    showGameStatsModal(currentGameId);

    // 🔒 Lock UI
    lockGameUI();
    disableGameActions();

    // 💾 Persist in background (don’t block UX)
    persistFinishedGame(resultType);
  }

  function lockGameUI() {
    ["undo-btn", "btn-ricochet", "btn-set-target", "btn-loss"].forEach((id) => {
      const btn = document.getElementById(id);
      if (btn) btn.disabled = true;
    });

    document.body.classList.add("game-finished");
  }
  function unlockGameUI() {
    ["undo-btn", "btn-ricochet", "btn-set-target", "btn-loss"].forEach((id) => {
      const btn = document.getElementById(id);
      if (btn) btn.disabled = false;
    });
  }

  function disableGameActions() {
    // This is mostly semantic now, but powerful later
    // e.g. prevent keyboard shortcuts, timers, AI, etc.
  }

  function persistFinishedGame(result = "finished") {
    gameFinished = true;
    boardLocked = true;
    lockGameUI();

    fetch("finish_game.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        game_id: currentGameId,
        game_result: result,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status !== "ok") {
          console.error("Failed to finish game", data);
        }
      })
      .catch((err) => console.error(err));
  }

  async function showGameStatsModal(gameId) {
    if (!gameId) {
      console.error("showGameStatsModal called without gameId");
      return;
    }

    document.getElementById("gameStatsModal").style.display = "block";

    try {
      const res = await fetch(`load_game_stats.php?game_id=${gameId}`);
      const data = await res.json();

      if (data.status !== "ok") {
        console.error("Stats load failed", data);
        return;
      }

      /* =========================
       Flatten turns → darts
    ========================= */
      const darts = data.turns.flatMap((t) => t.darts || []);

      /* =========================
       Counters
    ========================= */
      let aimedT = 0,
        aimedD = 0,
        aimedS = 0;
      let hitT = 0,
        hitD = 0,
        hitS = 0;

      let t20Aimed = 0;
      let s20WhenT20 = 0;
      let totalDarts = 0;
      let gameFinished = false;

      /* =========================
       Iterate darts
    ========================= */
      darts.forEach((d) => {
        if (gameFinished) return;
        if (d.ring === "-") return;
        // COUNT THIS DART FIRST
        totalDarts++;
        const aimedRing = d.aimed_ring;
        const aimedValue = d.aimed_value;
        const hitRing = d.ring;
        const hitScore = d.hit_score;

        if (aimedRing === "D") {
          console.log("Double counted:", d);
        }

        /* ----- Aimed counts ----- */
        if (aimedRing === "T") aimedT++;
        else if (aimedRing === "D") aimedD++;
        else if (aimedRing === "S") aimedS++;

        /* ----- Hit counts (only if aimed target hit) ----- */
        if (d.hit_target === 1) {
          if (aimedRing === "T") hitT++;
          else if (aimedRing === "D") hitD++;
          else if (aimedRing === "S") hitS++;
        }

        /* ----- S20 when T20 aimed ----- */
        if (aimedRing === "T" && aimedValue === 20 && d.hit_target != 1) {
          t20Aimed++;
          if (d.hit_target !== 1 && hitRing === "S" && hitScore === 20) {
            s20WhenT20++;
          }
        }

        /* ----- Detect game end (double-out) ----- */
        if (d.hit_target === 1 && aimedRing === "D" && d.turn_end_score === 0) {
          gameFinished = true;
        }
      });

      /* =========================
       Helpers
    ========================= */
      const pct = (hit, aimed) =>
        aimed ? ((hit / aimed) * 100).toFixed(1) + "%" : "0%";

      /* =========================
       Populate modal
    ========================= */
      document.getElementById("aimedT").textContent = aimedT;
      document.getElementById("hitsT").textContent = hitT;
      document.getElementById("pctT").textContent = pct(hitT, aimedT);

      document.getElementById("aimedD").textContent = aimedD;
      document.getElementById("hitsD").textContent = hitD;
      document.getElementById("pctD").textContent = pct(hitD, aimedD);

      document.getElementById("aimedS").textContent = aimedS;
      document.getElementById("hitsS").textContent = hitS;
      document.getElementById("pctS").textContent = pct(hitS, aimedS);

      document.getElementById(
        "statS20vsT20"
      ).textContent = `${s20WhenT20} / ${t20Aimed}`;

      document.getElementById("statTotalDarts").textContent = totalDarts;

      document.getElementById("statThrowsToFinish").textContent = "—"; // placeholder for now
    } catch (err) {
      console.error("Error loading game stats", err);
    }
  }

  function calculateGameStats(turns) {
    const stats = {
      T: { aimed: 0, hit: 0 },
      D: { aimed: 0, hit: 0 },
      S: { aimed: 0, hit: 0 },
      S20_vs_T20: { aimed: 0, hit: 0 },
      throwsToFinishRange: 0,
    };

    let cumulativeThrows = 0;
    let finishRangeReached = false;

    for (let t of turns) {
      for (let d of t.darts) {
        cumulativeThrows++;

        // Count aimed vs hit by ring type
        if (d.ring === "T") {
          stats.T.aimed++;
          if (d.hit_target) stats.T.hit++;
        } else if (d.ring === "D") {
          stats.D.aimed++;
          if (d.hit_target) stats.D.hit++;
        } else if (d.ring === "S") {
          stats.S.aimed++;
          if (d.hit_target) stats.S.hit++;
        }

        // Count S20 when aiming at T20
        if (d.ring === "T" && d.segment === "20") {
          stats.S20_vs_T20.aimed++;
          if (d.hit_target === false && d.segment === "20" && d.ring === "S") {
            stats.S20_vs_T20.hit++;
          }
        }

        // Count throws to reach finish range (<161)
        if (!finishRangeReached && t.end_score < 161) {
          stats.throwsToFinishRange = cumulativeThrows;
          finishRangeReached = true;
        }
      }
    }

    // Calculate percentages
    for (let key of ["T", "D", "S"]) {
      stats[key].percent =
        stats[key].aimed > 0
          ? Math.round((stats[key].hit / stats[key].aimed) * 100)
          : 0;
    }

    if (stats.S20_vs_T20.aimed > 0) {
      stats.S20_vs_T20.percent = Math.round(
        (stats.S20_vs_T20.hit / stats.S20_vs_T20.aimed) * 100
      );
    } else {
      stats.S20_vs_T20.percent = 0;
    }

    return stats;
  }

  function collectAllTurns() {
    if (!Array.isArray(currentTurns)) return [];

    return currentTurns.map((turn) => ({
      ...turn,

      darts: (turn.darts || []).map((d) => {
        // -------------------------
        // Normalize HIT data
        // -------------------------
        const value =
          d.value ??
          d.hit_score ??
          (typeof d.segment === "string"
            ? parseInt(d.segment.split("x")[1], 10)
            : null);

        const ring =
          d.ring ??
          (typeof d.segment === "string"
            ? d.segment.startsWith("3")
              ? "T"
              : d.segment.startsWith("2")
              ? "D"
              : "S"
            : null);

        // -------------------------
        // Normalize AIM data
        // -------------------------
        const aimedRing =
          d.aimedRing ??
          (turn.target_multiplier === 3
            ? "T"
            : turn.target_multiplier === 2
            ? "D"
            : turn.target_multiplier === 1
            ? "S"
            : null);

        const aimedValue = d.aimedValue ?? turn.target_value ?? null;

        // -------------------------
        // Normalize hitTarget
        // -------------------------
        const hitTarget =
          typeof d.hitTarget === "boolean"
            ? d.hitTarget
            : typeof d.hit_target === "boolean"
            ? d.hit_target
            : aimedRing && aimedValue
            ? ring === aimedRing && value === aimedValue
            : false;

        return {
          dart: d.dart ?? d.throw_number ?? null,

          // HIT
          value,
          ring,
          multiplier: ring === "T" ? 3 : ring === "D" ? 2 : 1,

          // AIM
          aimedRing,
          aimedValue,

          hitTarget,

          // passthrough
          segment: d.segment,
          x: d.x ?? null,
          y: d.y ?? null,
        };
      }),
    }));
  }

  function resetTurnUI() {
    darts = [];
    dartIndex = 0;
    clearMarkers();
    clearTargetHighlight();
    boardLocked = false;
  }

  function resetTurnState(startScore) {
    turnStartRemaining = startScore;
  }

  function addHistoryTurnRow(turn) {
    addHistoryRow(turn);
  }

  function toggleTurnMarkers(turnId, show) {
    const markers = markersByTurn[turnId];
    if (!markers) return;

    markers.forEach((m) => {
      m.style.display = show ? "block" : "none";
    });
  }

  // NEW VERSION
  // function toggleTurnMarkers(turnId, show) {
  //   if (!markersByTurn[turnId]) return;
  
  //   markersByTurn[turnId].forEach((marker) => {
  //     marker.style.display = show ? "block" : "none";
  //   });
  // }
  
  function rebuildMarkersFromThrows(turns) {
    turns.forEach((turn) => {
      const turnId = turn.turn_id;
  
      turn.darts.forEach((dart) => {
        if (dart.x != null && dart.y != null) {
          placeNormalMarker(dart.x, dart.y, turnId); // <-- adds marker to markersByTurn
          // hide marker initially
          const lastMarker = markersByTurn[turnId][markersByTurn[turnId].length - 1];
          lastMarker.style.display = "none";
        }
      });
    });
  }
  
  
  
  
  
  
  //ADD NEW LISTENERS HERE

  // Initial calculated target on game start
  const initialTarget = getTarget(remainingScore);
  highlightTarget(initialTarget.score, initialTarget.multiplier);
});
