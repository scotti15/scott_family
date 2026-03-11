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

  let lastGroupingStats = null;
// FOR GROUPING BUTTON BEGIN
let groupingVisible = false;

document.getElementById("btn-toggle-grouping").addEventListener("click", () => {

  console.log("Grouping stats at button click:", lastGroupingStats);

  const board = document.getElementById("dartboard");
  const existing = board.querySelector(".group-center-marker");

  // Toggle off
  if (existing) {
    existing.remove();
    return;
  }

  const center = board.viewBox.baseVal.width / 2;

  cx = center + lastGroupingStats.centerX;
  cy = center + lastGroupingStats.centerY;
  
  if (
    lastGroupingStats &&
    lastGroupingStats.centerX !== null &&
    lastGroupingStats.centerY !== null
  ) {
    cx = 200 + lastGroupingStats.centerX;
    cy = 200 + lastGroupingStats.centerY;
  }

  const marker = document.createElementNS("http://www.w3.org/2000/svg", "circle");

  marker.setAttribute("class", "group-center-marker");
  marker.setAttribute("cx", cx);
  marker.setAttribute("cy", cy);
  marker.setAttribute("r", "5");
  marker.setAttribute("fill", "lime");

  board.appendChild(marker);

});





// FOR GROUPING BUTTON END

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

  // document.getElementById("closeStatsBtn").addEventListener("click", () => {
  //   const modal = document.getElementById("gameStatsModal");
  //   modal.style.display = "none";
  // });

  const closeInfoBtn = document.getElementById("closeInfoBtn");
  const infoModal = document.getElementById("infoModal");

  closeInfoBtn.addEventListener("click", function () {
    infoModal.style.display = "none";
  });


  document.getElementById("infoBtn").addEventListener("click", () => {
    console.log("Info icon clicked");
    const modal = document.getElementById("infoModal");
    modal.style.display = "flex";
  });

  document.getElementById("closeStatsBtn").addEventListener("click", () => {

    document.getElementById("gameStatsModal").style.display = "none";

  });
  

  const gameStatsBtn = document.getElementById("btn-show-stats");

  if (gameStatsBtn) {
    gameStatsBtn.addEventListener("click", () => {
      if (!currentGameId) {
        console.warn("No active game for stats");
        return;
      }
      showGameStatsUnified(currentGameId);
    });
  }

  const newGameBtn = document.getElementById("btn-new-game");

  newGameBtn.addEventListener("click", () => {
    createNewGame();
    clearLiveBoard();
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
  // BOARD CLICK (RICHOCHET SHOW SCORE)
  // ================================
  svg.addEventListener("click", (e) => {
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const cursor = pt.matrixTransform(svg.getScreenCTM().inverse());

    const el = e.target;

    // ============================
    // 1∩╕ÅΓâú Manual Target Mode
    // ============================
    if (setTargetMode && el && el.classList.contains("scoring-segment")) {
      const score = Number(el.dataset.value);
      const multiplier = Number(el.dataset.multiplier) || 1;

      currentTarget = { score, multiplier };
      highlightTarget(score, multiplier);
      updateTargetText(currentTarget);

      setTargetMode = false;
      setTargetBtn.classList.remove("active");
      return;
    }

    // ============================
    // 2∩╕ÅΓâú Normal Dart Throw Mode
    // ============================
    if (boardLocked || dartIndex >= 3) return;

    let value = 0;
    let multiplier = 1;
    let score = 0;
    let segment = "MISS";

    if (
      el &&
      (el.tagName === "path" || el.tagName === "use" || el.tagName === "circle")
    ) {
      value = Number(el.dataset.value);
      multiplier = Number(el.dataset.multiplier) || 1;
      if (!isNaN(value) && !isNaN(multiplier)) {
        score = value * multiplier;
        segment = `${multiplier}x${value}`;
      }
    }

    // ============================
    // 3∩╕ÅΓâú Ricochet Handling
    // ============================
    let isRicochet = false;
    placeMarker(cursor.x, cursor.y, turnNumber);

    if (ricochetMode) {
      isRicochet = true;
      ricochetMode = false;
      ricochetBtn.classList.remove("active");
    }

    // ============================
    // 4∩╕ÅΓâú Snapshot Target
    // ============================
    const aimedRing = currentTarget
      ? currentTarget.multiplier === 3
        ? "T"
        : currentTarget.multiplier === 2
        ? "D"
        : "S"
      : null;
    const aimedValue = currentTarget?.score ?? null;

    // ============================
    // 5∩╕ÅΓâú Build Dart Object
    // ============================
    const dartData = {
      dart: dartIndex + 1,
      value, // hit value
      multiplier, // hit multiplier
      ring: multiplier === 3 ? "T" : multiplier === 2 ? "D" : "S", // valid enum
      score: isRicochet ? 0 : score, // turn score = 0 for ricochet
      segment: `${multiplier}x${value}`,
      aimed_ring: aimedRing,
      aimed_value: aimedValue,
      throw_type: isRicochet ? "ricochet" : "normal",
      classes: isRicochet ? ["ricochet"] : [],
      x: cursor.x,
      y: cursor.y,
      hitTarget: false,
    };

    darts.push(dartData);

    // ============================
    // 6∩╕ÅΓâú Live Table Update
    // ============================
    dartCells[dartIndex].textContent = value * multiplier; // always show the actual hit score
    dartData.classes.forEach((cls) => dartCells[dartIndex].classList.add(cls));

    // ============================
    // 7∩╕ÅΓâú Hit Target Evaluation
    // ============================
    if (!isRicochet && aimedRing && score > 0) {
      const expectedMultiplier =
        aimedRing === "T" ? 3 : aimedRing === "D" ? 2 : 1;
      const hitTarget =
        value === aimedValue && multiplier === expectedMultiplier;

      if (hitTarget) {
        dartData.hitTarget = true;
        dartData.classes.push("hit-target");
        if (aimedRing === "T") dartData.classes.push("triple");
        else if (aimedRing === "D") dartData.classes.push("double");
        else dartData.classes.push("single");

        dartData.classes.forEach((cls) =>
          dartCells[dartIndex].classList.add(cls)
        );
      }
    }

    // ============================
    // 8∩╕ÅΓâú Commit score to live turn
    // ============================
    turnTotal += dartData.score; // 0 for ricochet
    totalCell.textContent = turnTotal;

    remainingScore -= dartData.score; // 0 for ricochet
    remainingSpan.textContent = remainingScore;

// ============================
// 9∩╕ÅΓâú Bust Detection
// ============================
const isDoubleFinish = multiplier === 2;
const isBust =
  remainingScore < 0 ||
  remainingScore === 1 ||
  (remainingScore === 0 && !isDoubleFinish);

if (isBust) {
  dartCells[dartIndex].classList.add("bust-dart");
  darts[dartIndex].busted = true;
  bustThisTurn = true;
  boardLocked = true; // stop further clicks until Confirm

  // ≡ƒö╣ AUTO-FILL REMAINING DARTS
  for (let i = dartIndex + 1; i < 3; i++) {
    darts[i] = {
      dart: i + 1,
      value: 0,
      multiplier: 0,
      ring: "S",
      score: 0,
      segment: "1",
      aimed_ring: null,
      aimed_value: null,
      hitTarget: false,
      classes: [],
      x: null,
      y: null,
      busted: false,
      isImplied: 1,
    };

    // Update live table
    dartCells[i].textContent = 0; 
    dartCells[i].className = ""; // remove any prior styling
  }
  
dartIndex = 2;
}


    // ============================
    // ≡ƒöƒ Move to next dart
    // ============================
    dartIndex++;
    if (dartIndex === 3) boardLocked = true;

    // ============================
    // 1∩╕ÅΓâú1∩╕ÅΓâú Recalculate next target
    // ============================
    if (!setTargetMode && !boardLocked) {
      currentTarget = getTarget(remainingScore);
      highlightTarget(currentTarget.score, currentTarget.multiplier);
      updateTargetText(currentTarget);
    }
  });

  // ================================
  // MARKER
  // ================================

  function placeMarker(x, y, turnId, target) {
    const key = String(turnId);
  
    let marker;
    if (ricochetMode) {
      marker = createRicochetMarker(x, y);
    } else {
      marker = createNormalMarker(x, y);
    }
  
    marker.dataset.target = target; // store target
    let markerFilter = "T20";  // hard-coded filter for now
    console.log("target = ", target);
    console.log("markerfilter = ", markerFilter);


    // Show only if it matches current filter, otherwise hide
    if (markerFilter && markerFilter !== target) {
      marker.style.display = "none";
    }
  
    svg.appendChild(marker);
    markers.push(marker);
  
    if (!markersByTurn[key]) markersByTurn[key] = [];
    markersByTurn[key].push(marker);
  }
  
  

// ================================
// CONFIRM TURN
// ================================
confirmBtn.addEventListener("click", () => {
  console.group("CONFIRM TURN DEBUG");
  console.log("darts array:", darts);
  console.log("turnTotal:", turnTotal);
  console.log("remainingScore BEFORE:", remainingScore);

  if (darts.length === 0) return;

  // Keep a snapshot of remainingScore BEFORE any UI reset
  const remainingScoreBeforeReset = remainingScore;

  // ≡ƒ¢æ Handle bust
  const lastDart = darts[darts.length - 1];
  const turnWasBust =
    remainingScore < 0 ||
    remainingScore === 1 ||
    (remainingScore === 0 && lastDart?.multiplier !== 2);

  if (turnWasBust) {
    remainingScore = turnStartRemaining; // reset UI
    turnTotal = 0;
  }

  remainingSpan.textContent = remainingScore;

  // Build classes array for each dart if not already done
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
      dart.dart = i + 1; // 1-based index for table
    }
  });

  const winningDart = darts.find(
    (d) => remainingScoreBeforeReset === 0 && d.multiplier === 2
  );

  // Add row to history table
  addLiveTurnRow(darts, turnTotal, remainingScore, turnNumber);

  // Save turn to DB using the actual remaining score
  if (currentSessionId && currentGameId) {
    const payload = buildTurnPayload(remainingScoreBeforeReset);
    console.log("Saving turn payload:", payload);
    console.log("Payload JSON:", JSON.stringify(payload, null, 2));
    saveTurnToDb(payload);
    currentTurns.push(payload);
  } else {
    console.log("No active session/game – skipping DB save");
  }

  if (winningDart) {
    console.log("Winning dart detected:", winningDart);
    finishGame("double_out"); // updates DB
    return;
  }

  // Clear live turn highlights
  dartCells.forEach((cell) => {
    cell.className = "dart-cell";
    cell.textContent = "";
  });

  // Clear markers and reset live turn
  clearMarkers();
  resetTurn();

  // Prepare next target if automated
  prepareNextTarget();

  // Increment turn number
  turnNumber++;

  console.log("markersByTurn:", markersByTurn);
  console.groupEnd();
});


  // confirmBtn.addEventListener("click", () => {
  //   console.log("CONFIRM button clicked")
  //   // Step 1: Determine the current turn number
  //   // const turnNumber = currentTurnNumber; // whatever your global variable is

  //   // Step 2: Transfer live turn ΓåÆ history table
  //   transferLiveTurnToHistory(turnNumber);

  //   // Step 3: Reset live turn for next throw
  //   resetTurn();

  //   // Step 4 (optional for now): increment turn counter
  //   turnNumber++;
  // });
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

    // 1∩╕ÅΓâú Pop the last dart
    const lastDart = darts.pop();

    // 2∩╕ÅΓâú Remove the corresponding marker
    const lastMarker = markers.pop();
    if (lastMarker) lastMarker.remove();

    // 3∩╕ÅΓâú Remove from markersByTurn array
    markersByTurn[turnNumber]?.pop();

    // 4∩╕ÅΓâú Restore remaining score
    remainingScore += lastDart.score;
    remainingSpan.textContent = remainingScore;

    // 5∩╕ÅΓâú Update turn total
    turnTotal -= lastDart.score;
    totalCell.textContent = turnTotal;

    // 6∩╕ÅΓâú Clear the dart cell content and styling
    dartIndex--;
    if (dartIndex >= 0) {
      const cell = dartCells[dartIndex];
      cell.textContent = "";
      cell.className = "";
    }

    // 7∩╕ÅΓâú Unlock board for next throw
    boardLocked = false;

    // 8∩╕ÅΓâú Reset bust flag if undoing a bust
    if (lastDart.classes.includes("bust-dart")) {
      bustThisTurn = false;
    }

    // 9∩╕ÅΓâú Recalculate next target if needed
    if (!setTargetMode && !boardLocked) {
      currentTarget = getTarget(remainingScore);
      highlightTarget(currentTarget.score, currentTarget.multiplier);
      updateTargetText(currentTarget);
    }
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
    tdCheck.appendChild(checkbox);
    tr.appendChild(tdCheck);

    /* =========================
       Turn number
    ========================= */
    const tdTurn = document.createElement("td");
    tdTurn.textContent = turnNumber;
    tr.appendChild(tdTurn);

    /* =========================
       Dart 1ΓÇô3
    ========================= */
    for (let i = 0; i < 3; i++) {
      const td = document.createElement("td");

      if (darts[i]) {
        const d = darts[i];

        // Γ£à Display value
        if (d.throw_type === "ricochet") {
          td.textContent = "R"; // show a striking R for ricochet
          td.classList.add("ricochet"); // add ricochet class for styling
        } else {
          const dartScore = d.value * d.multiplier;
          td.textContent = dartScore;
        }

        // Γ£à Transfer all other styling from live dart
        if (d.classes && Array.isArray(d.classes)) {
          d.classes.forEach((cls) => td.classList.add(cls));
        }

        // Γ£à Bust styling
        if (d.busted) {
          td.classList.add("bust-dart");
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

    // 1∩╕ÅΓâú High score ΓåÆ triple 20
    if (remainingScore > 61) {
      return { score: 20, multiplier: 3 };
    }

    // 2∩╕ÅΓâú Exact double-out (even numbers Γëñ 40)
    if (remainingScore % 2 === 0 && remainingScore <= 40) {
      return { score: remainingScore / 2, multiplier: 2 };
    }

    // 3∩╕ÅΓâú Double bull finish
    if (remainingScore === 50) {
      return { score: 25, multiplier: 2 }; // D25
    }

    // 4∩╕ÅΓâú Setup shot ΓåÆ leave a preferred double
    for (let finish of preferredFinishes) {
      const needed = remainingScore - finish;
      if (needed >= 1 && needed <= 20) {
        return { score: needed, multiplier: 1 };
      }
    }

    // 5∩╕ÅΓâú Safe single fallback
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

  // function placeNormalMarker(x, y, turnId) {
  //   const marker = document.createElementNS(
  //     "http://www.w3.org/2000/svg",
  //     "circle"
  //   );

  //   marker.setAttribute("cx", x);
  //   marker.setAttribute("cy", y);
  //   marker.setAttribute("r", 2);
  //   marker.setAttribute("fill", MARKER_COLOR);
  //   marker.classList.add("dart-marker");

  //   // ≡ƒöÆ CRITICAL: marker never blocks clicks
  //   marker.style.pointerEvents = "none";

  //   // start hidden by default (important)
  //   // marker.style.display = "none";

  //   svg.appendChild(marker);

  //   // register marker by turn
  //   if (!markersByTurn[turnId]) {
  //     markersByTurn[turnId] = [];
  //   }
  //   markersByTurn[turnId].push(marker);

  //   console.log(turnId, markersByTurn);
  // }

  // function placeRicochetMarker(x, y, turnNumber) {
  //   const size = 6; // radius of star

  //   // calculate points for 5-point star
  //   const points = [];
  //   const outerRadius = size;
  //   const innerRadius = size / 2.5;
  //   for (let i = 0; i < 5; i++) {
  //     const outerX = x + outerRadius * Math.cos((Math.PI / 2 + (i * 2 * Math.PI) / 5));
  //     const outerY = y - outerRadius * Math.sin((Math.PI / 2 + (i * 2 * Math.PI) / 5));
  //     points.push(`${outerX},${outerY}`);

  //     const innerX = x + innerRadius * Math.cos((Math.PI / 2 + ((i * 2 + 1) * Math.PI) / 5));
  //     const innerY = y - innerRadius * Math.sin((Math.PI / 2 + ((i * 2 + 1) * Math.PI) / 5));
  //     points.push(`${innerX},${innerY}`);
  //   }

  //   const star = document.createElementNS("http://www.w3.org/2000/svg", "polygon");
  //   star.setAttribute("points", points.join(" "));
  //   star.setAttribute("fill", MARKER_COLOR);
  //   star.classList.add("dart-marker", "ricochet-marker");
  //   star.style.pointerEvents = "none";

  //   svg.appendChild(star);

  //   // push to markersByTurn using the SAME turnNumber as normal markers
  //   if (!markersByTurn[turnNumber]) {
  //     markersByTurn[turnNumber] = [];
  //   }
  //   markersByTurn[turnNumber].push(star);
  // }

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

        // ≡ƒÄë Confetti sequence done
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
  function buildTurnPayload(actualRemainingScore) {
    let turnResult = "normal";
  
    if (actualRemainingScore === 0 && darts.some((d) => d.multiplier === 2)) {
      turnResult = "double_out";
    } else if (actualRemainingScore < 0 || actualRemainingScore === 1) {
      turnResult = "bust";
    }
      // Carry forward aim for implied darts
      let lastAimRing = null;
      let lastAimValue = null;

      darts.forEach(d => {
        if (d.aimed_ring) lastAimRing = d.aimed_ring;
        if (d.aimed_value) lastAimValue = d.aimed_value;

        if (d.value === 0 && d.x == null && d.y == null) {
          d.aimed_ring = lastAimRing;
          d.aimed_value = lastAimValue;
        }
      });

  
    return {
      game_id: currentGameId,
      turn_number: turnNumber,
      start_score: turnStartRemaining,
      end_score: actualRemainingScore,
      turn_result: turnResult,
      darts: darts.map((d, i) => ({
        throw_number: i + 1,
        hit_score: d.value ?? 0,
        ring:
        d.multiplier === 3
          ? "T"
          : d.multiplier === 2
          ? "D"
          : "S",
      
        segment: d.segment ?? d.value ?? null,
        x: d.x ?? null,
        y: d.y ?? null,
        hit_target: !!d.hitTarget,
        aimed_ring: d.aimed_ring ?? null,
        aimed_value: d.aimed_value ?? null,
        is_implied: d.isImplied ? 1 : 0,
        throw_type: d.throw_type ?? "normal",
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
           1∩╕ÅΓâú Session + Game identity
        ------------------------- */
        currentSessionId = data.session.session_id;
        currentGameId = data.game ? data.game.game_id : null;
        sessionActive = true;

        games = data.games;

        /* -------------------------
           2∩╕ÅΓâú Populate game dropdown
        ------------------------- */
        populateGameDropdown(data.games, currentGameId);

        /* -------------------------
           3∩╕ÅΓâú Clear UI-only state
           (do NOT touch scoring)
        ------------------------- */
        clearMarkers();
        clearTargetHighlight();
        resetTurnUI(); // ΓÜá∩╕Å UI reset only
        boardLocked = false;

        /* -------------------------
           4∩╕ÅΓâú Populate history + restore score
        ------------------------- */
        if (data.turns && data.turns.length > 0) {
          populateHistoryTable(data.turns);
          // ≡ƒöü Rebuild markers for replay
          Object.keys(markersByTurn).forEach((k) => delete markersByTurn[k]);
          rebuildMarkersFromThrows(data.turns);
        } else {
          // Fresh game
          turnNumber = 1;
          remainingScore = startingScore;
          turnStartRemaining = startingScore;

          const remainingEl = document.getElementById("remaining-score");
          if (remainingEl) remainingEl.textContent = remainingScore;
        }

        /* -------------------------
           5∩╕ÅΓâú Prepare next turn
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
        segment: t.segment, // 1ΓÇô20 or 25
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
    
  console.log("populateHistoryTable called with turns:", turns);
    clearHistoryTable();

    if (!turns || turns.length === 0) return;

    turns.forEach((turn) => addHistoryTurnRow(turn));

    const lastTurn = turns[turns.length - 1];

    turnNumber = lastTurn.turn_number + 1;
    remainingScore = lastTurn.end_score;
    turnStartRemaining = lastTurn.end_score; // ≡ƒöÆ authoritative baseline

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
    console.group("ADD HISTORY ROW");
    console.log("Full turn object:", turn);
    console.log("Turn darts array:", turn.darts);
    console.groupEnd();

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
    checkbox.dataset.turnId = turn.turn_id; // ΓåÉ critical
    tdToggle.appendChild(checkbox);
    tr.appendChild(tdToggle);

    /* =========================
     Turn #
  ========================= */
    const tdTurn = document.createElement("td");
    tdTurn.textContent = turn.turn_number;
    tr.appendChild(tdTurn);

    /* =========================
     Dart 1ΓÇô3
  ========================= */
    let turnTotal = 0;
    const isBust = turn.turn_result === "bust";

    for (let i = 0; i < 3; i++) {
      const td = document.createElement("td");
      const dart = turn.darts?.[i];
    
      if (dart) {
    
        if (dart.throw_type === "ricochet") {
          td.textContent = dart.score;
          td.classList.add("ricochet");
    
        } else {
    
          let dartValue = Number(dart.score) * Number(dart.segment || 1);

          td.textContent = dartValue;
          if (!isBust) {
            turnTotal += dartValue;
          }
          
    
          if (dart.hitTarget) {
            td.classList.add("hit-target");
    
            if (dart.ring === "T") td.classList.add("triple");
            else if (dart.ring === "D") td.classList.add("double");
            else td.classList.add("single");
          }
        }
    
        if (dart.busted) td.classList.add("bust-dart");
    
        if (dart.classes && Array.isArray(dart.classes)) {
          dart.classes.forEach(cls => td.classList.add(cls));
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
  tdTotal.textContent = isBust ? 0 : turnTotal;
  tr.appendChild(tdTotal);

    /* =========================
     Remaining score
  ========================= */
  const tdScore = document.createElement("td");
  tdScore.textContent = isBust ? turn.start_score : turn.end_score;
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

    // ≡ƒöü reload game state + history
    loadGameById(currentGameId);
  });

  function loadGameById(gameId) {
    console.log("≡ƒôÑ Loading game:", gameId);

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
        // console.log(currentTurns[0].darts);
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

        // Γ£à Normalize response shape
        const newGame = {
          game_id: data.game_id,
          game_number: data.game_number,
        };

        // Γ£à Update globals
        currentGameId = newGame.game_id;
        turnNumber = 1;
        remainingScore = 501;

        // Γ£à Update dropdown and auto-select
        games.push(newGame);
        populateGameDropdown(games, currentGameId);

        // Γ£à Reset UI
        clearHistoryTable();
        resetGameUI();
        unlockGameUI();
        prepareNextTarget();
        updateGameHeader(newGame.game_number);

        console.log(`≡ƒÄ» New Game #${newGame.game_number} started`);
      })
      .catch((err) => console.error(err));
  }

  async function finishGame(resultType = "finished") {
    // if (gameFinished) return;

    console.log("≡ƒÅü Finishing game");

    gameFinished = true;
    boardLocked = true;

    // ≡ƒÄë Celebration first
    await celebrateDoubleOut();

    // Γ£à Build stats
    const turns = collectAllTurns(); // function to get all turns for currentGame
    const stats = calculateGameStats(turns);

    // ≡ƒôè Then stats
    showGameStatsUnified(currentGameId);

    // ≡ƒöÆ Lock UI
    clearLiveBoard();
    lockGameUI();
    disableGameActions();

    // ≡ƒÆ╛ Persist in background (donΓÇÖt block UX)
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
       Flatten turns ΓåÆ darts
    ========================= */
      const darts = data.turns.flatMap((t) =>
        (t.darts || []).map((d) => ({
          ...d,
          turn_end_score: t.end_score,
        }))
      );

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

      let throwsToFinish = 0;
      let inFinishRange = false;

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

        /* ----- Enter finish range (<161) ----- */
        if (!inFinishRange && d.turn_end_score < 161) {
          inFinishRange = true;
          throwsToFinish = 1; // count THIS dart
        } else if (inFinishRange) {
          throwsToFinish++;
        }

        if (aimedRing === "D") {
          console.log("Double counted:", d);
        }

        /* ----- Aimed vs Hit (exact match only) ----- */
        if (aimedRing === "S") {
          aimedS++;
          if (d.hit_target === 1) hitS++;
        }

        if (aimedRing === "D") {
          aimedD++;
          if (d.hit_target === 1) hitD++;
        }

        if (aimedRing === "T") {
          aimedT++;
          if (d.hit_target === 1) hitT++;
        }
        if (d.hit_target === 1) {
          console.log(
            "HIT:",
            "aimed:",
            d.aimed_ring + d.aimed_value,
            "hit:",
            d.ring + d.segment
          );
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

      document.getElementById("statThrowsToFinish").textContent =
        throwsToFinish > 0 ? throwsToFinish : "ΓÇö";
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

    let runningScore = 501;
    let cumulativeThrows = 0;
    let finishRangeReached = false;

    for (let t of turns) {
      for (let d of t.darts) {
        cumulativeThrows++;

        runningScore -= d.score;

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
        if (!finishRangeReached && runningScore < 161) {
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
    const key = String(turnId);
    const markers = markersByTurn[key];
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
        console.log(dart);

        if (dart.x != null && dart.y != null) {
          placeNormalMarker(dart.x, dart.y, turnId, dart.aimed_ring, dart.aimed_value);
          // hide marker initially
          const lastMarker =
            markersByTurn[turnId][markersByTurn[turnId].length - 1];
          lastMarker.style.display = "none";
        }
      });
    });
    console.log(
      "markersByTurn keys after rebuild:",
      Object.keys(markersByTurn)
    );
  }

  /**
   * Transfer the current live turn to the history table
   * Copies all dart cells including formatting (classes) and values
   */
  function transferLiveTurnToHistory(turnNumber) {
    const liveRow = document.getElementById(`live-turn-${turnNumber}`);
    if (!liveRow) return;

    const historyTbody = document.getElementById("scoreboard-body");
    if (!historyTbody) return;

    const tr = document.createElement("tr");

    // 1∩╕ÅΓâú Checkbox column
    const tdCheck = document.createElement("td");
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.classList.add("turn-toggle");
    checkbox.dataset.turnId = turnNumber;
    checkbox.checked = false;
    tdCheck.appendChild(checkbox);
    tr.appendChild(tdCheck);

    // 2∩╕ÅΓâú Turn number
    const tdTurn = document.createElement("td");
    tdTurn.textContent = turnNumber;
    tr.appendChild(tdTurn);

    // 3∩╕ÅΓâú Dart cells: copy from live row
    const liveDarts = liveRow.querySelectorAll(".dart-cell");
    liveDarts.forEach((liveCell) => {
      const td = document.createElement("td");
      td.textContent = liveCell.textContent;

      // Copy all classes (hit-target, single/double/triple, bust, etc.)
      liveCell.classList.forEach((cls) => td.classList.add(cls));

      tr.appendChild(td);
    });

    // 4∩╕ÅΓâú Turn total
    const tdTotal = document.createElement("td");
    const totalCell = liveRow.querySelector(".turn-total");
    tdTotal.textContent = totalCell ? totalCell.textContent : "";
    tr.appendChild(tdTotal);

    // 5∩╕ÅΓâú Remaining score
    const tdRemaining = document.createElement("td");
    const remainingCell = liveRow.querySelector(".remaining-score");
    tdRemaining.textContent = remainingCell ? remainingCell.textContent : "";
    tr.appendChild(tdRemaining);

    // Γ£à Append to history table
    historyTbody.appendChild(tr);
  }

  function createNormalMarker(x, y) {
    const circle = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );
    circle.setAttribute("cx", x);
    circle.setAttribute("cy", y);
    circle.setAttribute("r", 8);
    circle.setAttribute("fill", "#8fce00"); // normal = blue
    circle.classList.add("dart-marker");
    return circle;
  }

  function createRicochetMarker(x, y) {
    const circle = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );
    circle.setAttribute("cx", x);
    circle.setAttribute("cy", y);
    circle.setAttribute("r", 8);
    circle.classList.add("dart-marker");
    circle.setAttribute("fill", "#f08080"); // ricochet = red
    return circle;
  }

  function placeNormalMarker(x, y, turnId, aimed_ring, aimed_value) {
    const target = aimed_ring + aimed_value; // e.g., "T20"
    placeMarker(x, y, turnId, target);
  }
  

  // ADD REGULAR FUNCTIONS HERE//

  //GAME STATS FUNCTIONS//

  // function compileGameStatsFromDarts(darts) {
  //   return {
  //     throws: darts.length,
  //     scoringThrows: darts.filter((d) => d.score > 0).length,
  //     ricochets: darts.filter((d) => d.throw_type === "ricochet").length,
  //     targetAttempts: darts.filter((d) => d.aimedRing).length,
  //     targetHits: darts.filter((d) => d.hitTarget).length,
  //   };
  // }

  async function showGameStatsUnified(gameId) {
    try {
      const darts = await getDartsForGame(gameId);
      console.log(darts);
  
      const accuracyStats = compileGameStatsFromDarts(darts);
      populateTargetAccuracy(accuracyStats);
  
      const keyStats = compileKeyStatsFromDarts(darts);
      populateKeyStats(keyStats);
  
      // ✅ Calculate ONCE
      lastGroupingStats = calculateGroupingStats(darts);
  
      // Update text in modal
      document.getElementById("statGroupingRadius").textContent =
        lastGroupingStats.groupingRadius.toFixed(1) + " cm";
  
      // ❌ REMOVE THIS LINE
      // drawGroupingCenter(lastGroupingStats.centerX, lastGroupingStats.centerY);
  
      document.getElementById("gameStatsModal").style.display = "block";
  
    } catch (err) {
      console.error("Stats error:", err);
    }
  }
  

  async function getDartsForGame(gameId) {
    const res = await fetch(`load_game_stats.php?game_id=${gameId}`);
    const data = await res.json();
  
    if (data.status !== "ok") {
      throw new Error("Failed to load game stats");
    }
  
    // Flatten turns → darts
    return data.turns.flatMap((t) =>
      (t.darts || []).map((d) => ({
        // HIT
        ring: d.ring,
        value: d.hit_score,
        hitTarget: d.hit_target === 1,
  
        // AIM
        aimedRing: d.aimed_ring,
        aimedValue: d.aimed_value,
  
        // META
        throwType: d.throw_type ?? "normal",
        x: d.x,
        y: d.y,
  
        // NEW: accuracy metrics
        missDistance: d.miss_distance,
        missAngle: d.miss_angle,
      }))
    );
  }


// ---------------------------
// 1️⃣ Compile Game Stats from Darts
// ---------------------------
function compileGameStatsFromDarts(darts) {
  // Conversion: pixels → cm
  const board = document.getElementById("dartboard");
const boardRadiusPx = board.getBoundingClientRect().width / 2;

const PIXEL_TO_CM = 17 / boardRadiusPx;


  const stats = {
    T: { aimed: 0, hit: 0, missDistances: [] },
    D: { aimed: 0, hit: 0, missDistances: [] },
    S: { aimed: 0, hit: 0, missDistances: [] },
  };

  darts.forEach((d) => {
    // Ignore darts with no aim (ricochet / unknown)
    if (!d.aimedRing) return;

    const ring = d.aimedRing;

    // Count aimed
    stats[ring].aimed++;

    // Compute miss distance in cm
    let missCm = 0;
    // if (d.hitTarget !== true && d.missDistance != null) {
      missCm = d.missDistance * PIXEL_TO_CM;
    // }

    // Count hit
    if (d.hitTarget === true) {
      stats[ring].hit++;
      stats[ring].missDistances.push(0); // hit → 0 cm
    } else {
      stats[ring].missDistances.push(missCm); // miss → cm
    }
  });

  return stats;
}

// ---------------------------
// 2️⃣ Populate Modal Accuracy
// ---------------------------
function populateTargetAccuracy(stats) {
  const pct = (h, a) => (a ? ((h / a) * 100).toFixed(1) + "%" : "0.0%");
  const avg = (distances) =>
    distances.length
      ? (distances.reduce((sum, d) => sum + d, 0) / distances.length).toFixed(1)
      : "0.0";

  // ---------- Triple ----------
  document.getElementById("aimedT").textContent = stats.T.aimed;
  document.getElementById("hitsT").textContent = stats.T.hit;
  document.getElementById("pctT").textContent = pct(stats.T.hit, stats.T.aimed);
  document.getElementById("accT").textContent = avg(stats.T.missDistances) + " cm";

  // ---------- Double ----------
  document.getElementById("aimedD").textContent = stats.D.aimed;
  document.getElementById("hitsD").textContent = stats.D.hit;
  document.getElementById("pctD").textContent = pct(stats.D.hit, stats.D.aimed);
  document.getElementById("accD").textContent = avg(stats.D.missDistances) + " cm";

  // ---------- Single ----------
  document.getElementById("aimedS").textContent = stats.S.aimed;
  document.getElementById("hitsS").textContent = stats.S.hit;
  document.getElementById("pctS").textContent = pct(stats.S.hit, stats.S.aimed);
  document.getElementById("accS").textContent = avg(stats.S.missDistances) + " cm";
}

  function compileKeyStatsFromDarts(darts, startingScore = 501) {
    let remaining = startingScore;

    let totalDarts = 0;
    let totalScore = 0;

    let preFinishDarts = 0;
    let preFinishScore = 0;
    let dartsToFinishRange = null;

    let t20Attempts = 0;
    let t20WedgeHits = 0;

    for (let i = 0; i < darts.length; i++) {
      const d = darts[i];

      // ---------- Compute actual score including multiplier ----------
      let dartScore = d.value || 0;
      if (d.ring === "T") dartScore *= 3;
      else if (d.ring === "D") dartScore *= 2;

      totalDarts++;
      totalScore += dartScore;

      // ---------- A: 20 while targeting T20 ----------
      if (d.aimedRing === "T" && d.aimedValue === 20) {
        t20Attempts++;
        // Count any hit in 20 wedge (S, D, T)
        if (d.value === 20) t20WedgeHits++;
      }

      const nextRemaining = remaining - dartScore;

      // ---------- B: Darts to reach finish range (<161) ----------
      if (dartsToFinishRange === null && nextRemaining <= 160) {
        dartsToFinishRange = totalDarts;
      }

      // ---------- Pre-finish scoring ----------
      if (remaining > 160) {
        preFinishDarts++;
        preFinishScore += dartScore;
      }

      // ---------- Finish detection ----------
      const isWinningDart = nextRemaining === 0 && d.ring === "D";
      if (isWinningDart) break; // stop immediately

      // ---------- Bust ----------
      const isBust =
        nextRemaining < 0 ||
        nextRemaining === 1 ||
        (nextRemaining === 0 && d.ring !== "D");

      // Only update remaining if no bust
      if (!isBust) {
        remaining = nextRemaining;
      }
      // else remaining stays the same; totalDarts still counts the dart
    }

    // ---------- 3-Dart Averages ----------
    const overall3DA =
      totalDarts > 0 ? ((totalScore / totalDarts) * 3).toFixed(2) : "0.00";

    const preFinish3DA =
      preFinishDarts > 0
        ? ((preFinishScore / preFinishDarts) * 3).toFixed(2)
        : "0.00";

    return {
      // Key stats
      t20WedgeHits,
      t20Attempts,
      dartsToFinishRange,
      totalDarts,

      // 3-Dart Averages
      overall3DA,
      preFinish3DA,
    };
  }

  function populateKeyStats(stats) {
    document.getElementById(
      "statS20vsT20"
    ).textContent = `${stats.t20WedgeHits} / ${stats.t20Attempts}`;

    document.getElementById("statThrowsToFinish").textContent =
      stats.dartsToFinishRange ?? "-";

    document.getElementById("statTotalDarts").textContent = stats.totalDarts;

    document.getElementById("stat3DAOverall").textContent = stats.overall3DA;

    document.getElementById("stat3DAPreFinish").textContent =
      stats.preFinish3DA;
  }

  function calculateGroupingStats(darts) {

    const board = document.getElementById("dartboard");
    const boardRadiusPx = board.getBoundingClientRect().width / 2;
    const PIXEL_TO_CM = 17 / boardRadiusPx;
  
    // Only darts with coordinates
    const valid = darts.filter(d => d.x != null && d.y != null);
  
    if (valid.length === 0) {
      return {
        groupingRadius: 0,
        centerX: null,
        centerY: null
      };
    }
  
    // ---------- Group center ----------
    const meanX = valid.reduce((s,d)=>s+d.x,0) / valid.length;
    const meanY = valid.reduce((s,d)=>s+d.y,0) / valid.length;
  
    // ---------- Distances from center ----------
    const distances = valid.map(d =>
      Math.sqrt((d.x - meanX)**2 + (d.y - meanY)**2)
    );
  
    const avgPx = distances.reduce((s,d)=>s+d,0) / distances.length;
  
    return {
      groupingRadius: avgPx * PIXEL_TO_CM,
      centerX: meanX,
      centerY: meanY
    };
  }
  
  function clearLiveBoard() {
    dartCells.forEach((cell) => {
      cell.textContent = 0;
      cell.className = ""; // remove any prior styling
    });
  }
  
  function drawGroupingCenter(x, y) {

    if (x == null || y == null) return;
  
    const board = document.getElementById("dartboard");
  
    const marker = document.createElement("div");
    marker.className = "group-center-marker";
  
    marker.style.position = "absolute";
    marker.style.left = `${x}px`;
    marker.style.top = `${y}px`;
    marker.style.width = "10px";
    marker.style.height = "10px";
    marker.style.background = "lime";
    marker.style.borderRadius = "50%";
    marker.style.transform = "translate(-50%, -50%)";
    marker.style.pointerEvents = "none";
    marker.style.zIndex = "2000";
  
    board.appendChild(marker);
  }
  
  
  
  
  //ADD NEW LISTENERS HERE

  // Initial calculated target on game start
  const initialTarget = getTarget(remainingScore);
  highlightTarget(initialTarget.score, initialTarget.multiplier);
});
