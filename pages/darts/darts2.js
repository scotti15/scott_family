// ================================
// darts2.js
// STEP 1: Dartboard → Live Table
// ================================

document.addEventListener("DOMContentLoaded", () => {
  const dartboard = document.getElementById("dartboard");

  const dartCells = [
    document.getElementById("d1"),
    document.getElementById("d2"),
    document.getElementById("d3"),
  ];

  const turnTotalCell = document.getElementById("turnTotal");

  let dartIndex = 0;
  let turnTotal = 0;
  let startingScore = 101; // or 501, whatever your game is
  let remainingScore = startingScore;

  let setTargetMode = false;
  let markers = [];
  let turnMarkers = [];
  const markersByTurn = {};


  const setTargetBtn = document.getElementById("btn-set-target");

  setTargetBtn.addEventListener("click", () => {
    setTargetMode = !setTargetMode;
    setTargetBtn.classList.toggle("active", setTargetMode);
  });

  let ricochetMode = false; // tracks whether next dart is a ricochet
  const ricochetBtn = document.getElementById("btn-ricochet");

  ricochetBtn.addEventListener("click", () => {
    ricochetMode = !ricochetMode;
    ricochetBtn.classList.toggle("active", ricochetMode); // visual feedback
  });


  // --- Per-turn toggle ---
document.addEventListener("change", (e) => {
    if (!e.target.classList.contains("turn-toggle")) return;
  
    const turnId = e.target.dataset.turnId;
    const show = e.target.checked;
  
    toggleTurnMarkers(turnId, show);
  });
  
  // --- Header toggle: toggle all turns ---
  const toggleAll = document.getElementById("toggle-all-turns");
  if (toggleAll) {
    toggleAll.addEventListener("change", (e) => {
      const checked = e.target.checked;
  
      document.querySelectorAll(".turn-toggle").forEach((cb) => {
        cb.checked = checked;
        toggleTurnMarkers(cb.dataset.turnId, checked);
      });
    });
  }
  
//  CONFIRM BUTTON //
const scoreboardBody = document.getElementById("scoreboard-body");
const confirmBtn = document.getElementById("confirmTurn");

confirmBtn.addEventListener("click", () => {
  const tbody = document.getElementById("scoreboard-body");
  const row = document.createElement("tr");

  const turnIndex = tbody.children.length; // zero-based index

  // 1️⃣ Checkbox for toggle markers
  const cbCell = document.createElement("td");
  cbCell.innerHTML = `<input type="checkbox" class="turn-toggle" data-turn-id="${turnIndex}" >`;
  row.appendChild(cbCell);

  // 2️⃣ Turn number
  const turnCell = document.createElement("td");
  turnCell.textContent = turnIndex + 1;
  row.appendChild(turnCell);

  // 3️⃣ Dart 1–3 cells
  dartCells.forEach((liveCell) => {
    const td = document.createElement("td");
    td.textContent = liveCell.textContent;

    // ✅ Copy styling from live table
    td.className = liveCell.className;

    row.appendChild(td);
  });

  // 4️⃣ Turn total
  const totalTd = document.createElement("td");
  totalTd.textContent = turnTotalCell.textContent;
  row.appendChild(totalTd);

  // 5️⃣ Score column (remaining score or cumulative)
  const scoreTd = document.createElement("td");
  scoreTd.textContent = remainingScore;
  row.appendChild(scoreTd);

  tbody.appendChild(row);

  // 6️⃣ Store this turn's markers for hide/show
  markersByTurn[turnIndex] = [...markers]; // copy current markers array

  // 7️⃣ Reset live table for next turn
  resetTurn();
});


//  CONFIRM BUTTON //

const undoBtn = document.getElementById("undo-btn");
undoBtn.addEventListener("click", () => {
  if (dartIndex <= 0) return; // nothing to undo

  // Step back one dart
  dartIndex--;

  // Step 1️⃣ Remove the last marker (circle or star)
  if (markers[dartIndex] && markers[dartIndex].length) {
    markers[dartIndex].forEach((m) => {
      if (m && m.remove) m.remove();
    });
    markers[dartIndex] = [];
  }

  // Step 2️⃣ Restore live table
  const lastCell = dartCells[dartIndex];
  const lastScoreText = lastCell.textContent;

  if (lastScoreText === "R") {
    // Ricochet → score = 0, remove class
    lastCell.textContent = "";
    lastCell.classList.remove("ricochet");
  } else {
    // Normal throw → subtract from totals
    const lastScore = Number(lastScoreText) || 0;
    turnTotal -= lastScore;
    remainingScore += lastScore;

    lastCell.textContent = "";
    lastCell.classList.remove("hit-target-s", "hit-target-d", "hit-target-t");
  }

  // Update UI for totals
  document.getElementById("remaining-score").textContent = remainingScore;
  turnTotalCell.textContent = turnTotal;

  // Step 3️⃣ Restore auto-target for next throw
  prepareNextTarget();
});





  let currentTarget = { value: 20, multiplier: 3 }; // T20
  dartboard.addEventListener("click", (e) => {
    const target = e.target;
  
    // Only react to scoring segments
    if (!target.classList.contains("scoring-segment")) return;
    if (dartIndex >= 3) return;
  
    // ---------------------------
    // 1️⃣ Ricochet handling (short-circuit)
    // ---------------------------
    if (ricochetMode) {
      // Use the v2 coordinate system
      placeRicochetMarker(e);             // draws X at click position
      dartCells[dartIndex].textContent = "R";  // show "R" in live table
      dartCells[dartIndex].classList.add("ricochet");
  
      // Score is 0 for database/stat purposes
      ricochetMode = false;               // turn off ricochet mode
      ricochetBtn.classList.remove("active");
  
      // Skip normal scoring/styling
      dartIndex++;
      return;
    }
  
    // ---------------------------
    // 2️⃣ Manual target override
    // ---------------------------
    if (setTargetMode) {
      const value = Number(target.dataset.value);
      const multiplier = Number(target.dataset.multiplier);
  
      currentTarget = { value, multiplier };
  
      highlightTarget(value, multiplier);
      updateTargetText(currentTarget);
  
      setTargetMode = false;
      setTargetBtn.classList.remove("active");
  
      return; // stop here — no throw is recorded
    }
  
    // ---------------------------
    // 3️⃣ Normal throw scoring
    // ---------------------------
    const value = Number(target.dataset.value);
    const multiplier = Number(target.dataset.multiplier);
    const score = value * multiplier;
  
    // Update live table
    dartCells[dartIndex].textContent = score;
    turnTotal += score;
    remainingScore -= score; // subtract this dart's score
    document.getElementById("remaining-score").textContent = remainingScore;
  
    turnTotalCell.textContent = turnTotal;
    console.log("Score hit:", score, "Remaining:", remainingScore);
  
    // ---------------------------
    // 4️⃣ Styling for exact target hits
    // ---------------------------
    dartCells[dartIndex].classList.remove(
      "hit-target-s",
      "hit-target-d",
      "hit-target-t"
    );
  
    if (
      value === currentTarget.value &&
      multiplier === currentTarget.multiplier
    ) {
      console.log("Exact target hit! Applying styling...");
      if (multiplier === 1) dartCells[dartIndex].classList.add("hit-target-s");
      else if (multiplier === 2) dartCells[dartIndex].classList.add("hit-target-d");
      else if (multiplier === 3) dartCells[dartIndex].classList.add("hit-target-t");
    } else {
      console.log("Target not hit. No styling applied.");
    }
  
    // ---------------------------
    // 5️⃣ Place normal marker
    // ---------------------------
    placeMarker(e);
  
    // ---------------------------
    // 6️⃣ Auto-calculate next target
    // ---------------------------
    prepareNextTarget();
    
  
    dartIndex++;
  });
  


// Normal dart marker
function placeMarker(e) {
    const svg = dartboard;
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
  
    const marker = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );
    marker.setAttribute("cx", svgP.x);
    marker.setAttribute("cy", svgP.y);
    marker.setAttribute("r", 2);
    marker.classList.add("dart-marker");
  
    svg.appendChild(marker);
  
    // Track for undo (current dart)
    if (!markers[dartIndex]) markers[dartIndex] = [];
    markers[dartIndex].push(marker);
  
    // Track per turn for show/hide toggle
    const turnNum = dartIndexToTurn();
    if (!markersByTurn[turnNum]) markersByTurn[turnNum] = [];
    markersByTurn[turnNum].push(marker);
  }
  
  
  
  

  function getTarget(remainingScore) {
    const preferredFinishes = [40, 32, 20, 16, 10, 8, 4, 2];

    // High score → T20
    if (remainingScore > 61) {
      return { value: 20, multiplier: 3 };
    }

    // Exact double-out
    if (remainingScore % 2 === 0 && remainingScore <= 40) {
      return { value: remainingScore / 2, multiplier: 2 };
    }

    // Double bull
    if (remainingScore === 50) {
      return { value: 25, multiplier: 2 };
    }

    // Setup shot
    for (let finish of preferredFinishes) {
      const needed = remainingScore - finish;
      if (needed >= 1 && needed <= 20) {
        return { value: needed, multiplier: 1 };
      }
    }

    // Safe fallback
    return { value: Math.min(20, remainingScore), multiplier: 1 };
  }

  function prepareNextTarget() {
    currentTarget = getTarget(remainingScore);
    highlightTarget(currentTarget.value, currentTarget.multiplier);
    updateTargetText(currentTarget);
  }

  function highlightTarget(value, multiplier = 1) {
    // Clear any existing target highlight
    dartboard.querySelectorAll(".target-suggest").forEach((el) => {
      el.classList.remove("target-suggest");
    });

    // Find matching wedge(s)
    const elements = dartboard.querySelectorAll(
      `[data-value='${value}'][data-multiplier='${multiplier}']`
    );

    elements.forEach((el) => el.classList.add("target-suggest"));
  }

  function updateTargetText(target) {
    const el = document.getElementById("target-text");
    if (!el) return;

    let prefix = "S";
    if (target.multiplier === 2) prefix = "D";
    if (target.multiplier === 3) prefix = "T";

    el.textContent = `${prefix}${target.value}`;
  }

  function placeRicochetMarker(e) {
    const svg = dartboard;
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
  
    const marker = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polygon"
    );
  
    // Define a small 6-point star centered at (svgP.x, svgP.y)
    const size = 4;
    const points = [
      [0, -size],
      [1.2 * size, -0.4 * size],
      [size, size],
      [0, 0.5 * size],
      [-size, size],
      [-1.2 * size, -0.4 * size]
    ]
      .map((p) => `${p[0] + svgP.x},${p[1] + svgP.y}`)
      .join(" ");
  
    marker.setAttribute("points", points);
    marker.setAttribute("fill", "#ff0000");        // red for ricochet
    marker.classList.add("dart-marker", "ricochet-marker");
  
    svg.appendChild(marker);
  
    // Track for undo
    if (!markers[dartIndex]) markers[dartIndex] = [];
    markers[dartIndex].push(marker);
  
    // Track for per-turn toggle
    const turnNum = Math.floor(dartIndex / 3) + 1;
    if (!markersByTurn[turnNum]) markersByTurn[turnNum] = [];
    markersByTurn[turnNum].push(marker);
  }
  
  
  
  
  function resetTurn() {
    // 1️⃣ Reset indices and totals
    dartIndex = 0;
    turnTotal = 0;
    // optional: boardLocked = false if you use it
    // optional: darts = []; if you track throws
  
    // 2️⃣ Clear live table
    dartCells.forEach((c) => (c.textContent = ""));
    dartCells.forEach((c) =>
      c.classList.remove("hit-target-s", "hit-target-d", "hit-target-t", "ricochet")
    );
    turnTotalCell.textContent = "0";
  
    // 3️⃣ Reset remaining score display
    document.getElementById("remaining-score").textContent = remainingScore;
  
    // 4️⃣ Hide all markers (circle or star) without removing
    markers.forEach((mArr) => {
      if (Array.isArray(mArr)) {
        mArr.forEach((m) => {
          if (m && m.style) m.style.display = "none";
        });
      } else if (mArr && mArr.style) {
        mArr.style.display = "none";
      }
    });
  
    // 5️⃣ Prepare next target based on remaining score
    prepareNextTarget();
  }
  
 
// Toggle markers for a specific turn
function toggleTurnMarkers(turnNum, show) {
    const turnMarkers = markersByTurn[turnNum];
    if (!turnMarkers) return;
  
    turnMarkers.forEach((m) => {
      if (m && m.style) m.style.display = show ? "block" : "none";
    });
  }

// Helper to get current turn number (1-based)
function dartIndexToTurn() {
    // Each turn has 3 darts
    return Math.floor(dartIndex / 3) + 1;
  }

  

  


// ADD NEW FUNCTIONS HERE





});
