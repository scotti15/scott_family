document.addEventListener("DOMContentLoaded", () => {
  // ================================
  // CONFIG
  // ================================
  const svg = document.getElementById("dartboard");
  const MARKER_COLOR = "#66ccff";
  const markers = [];

  let turnNumber = 1;
  let startingScore = 501;

  let remainingScore = startingScore;
  let ricochetMode = false;

  let turnStartRemaining = remainingScore;

  let currentTarget = getTarget(remainingScore);
  highlightTarget(currentTarget.score, currentTarget.multiplier);
  prepareNextTarget();

  const scoreboardBody = document.getElementById("scoreboard-body");
  const remainingSpan = document.getElementById("remaining-score");

  const newGameBtn = document.getElementById("btn-new-game");

newGameBtn.addEventListener("click", () => {
  startNewGame();
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
    console.log("Manual target set:", currentTarget);

    highlightTarget(score, multiplier);
    + updateTargetText(currentTarget); // ✅ ADD THIS
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
  darts.push({ dart: dartIndex + 1, x: cursor.x, y: cursor.y, score, segment });
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
  const hitTarget = value === currentTarget.score && multiplier === currentTarget.multiplier;

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
    dart: dartIndex + 1,  // 1-based
    score,
    value,
    multiplier,
    segment: `${multiplier}x${value}`,
    hitTarget,
    classes: dartClasses,
    x: cursor.x,           // optional, for markers
    y: cursor.y
  });

  // ----------------------
  // Apply classes to live table
  // ----------------------
  dartCells[dartIndex].textContent = score;
  // Clear old classes
  dartCells[dartIndex].classList.remove("hit-target", "single", "double", "triple");
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
  console.log("DOUBLE OUT! 🎉");
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

  console.log("BUST!", {
    remainingBefore: remainingScore,
    score,
    multiplier,
    nextRemaining,
    turnStartRemaining
  });

  // Mark the dart that caused the bust as strike-out
  dartCells[dartIndex].classList.add("bust-dart");

  // Keep the real score visible
  dartCells[dartIndex].textContent = score;

  // Optional: mark all previous darts in the turn as strike-out
  dartCells.forEach((cell, idx) => {
    if (idx < dartIndex && cell.textContent !== "") cell.classList.add("bust-dart");
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
      classes: ["bust-dart"]
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
    console.log(" target:", target);
  }
});


  // ================================
  // MARKER
  // ================================
  function placeMarker(x, y) {
    if (ricochetMode) {
      placeRicochetMarker(x, y);
    } else {
      placeNormalMarker(x, y);
    }
  }
// ================================
// CONFIRM TURN
// ================================
confirmBtn.addEventListener("click", () => {
  console.log("Confirm clicked", { dartIndex, boardLocked, darts });

  if (darts.length === 0) return;  // skip if no darts thrown

  // REMOVE AFTER DEBUG BEGIN
  console.log("=== BEFORE confirmTurn ===");
  darts.forEach((dart, i) => {
    console.log(`Dart ${i}:`, dart);
  });
  // REMOVE AFTER DEBUG END

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
      dart.dart = i + 1;          // 1-based index for table cells
    }
  });

  // 3️⃣ Determine if this turn contains a winning double
  // Find the dart that reduced the score to zero
  const winningDart = darts.find(d => remainingScore === 0 && d.multiplier === 2);

  if (winningDart) {
    console.log("DOUBLE OUT! 🎉", winningDart);
    boardLocked = true; // disable board after win
    celebrateDoubleOut(winningDart);
  }

  // 4️⃣ Add row to history table
  addTurnRow(darts, turnTotal);

  // 5️⃣ Clear live turn highlights
  dartCells.forEach((cell) => {
    cell.classList.remove("hit-target", "single", "double", "triple");
    cell.textContent = "";
  });

  // 6️⃣ Clear markers and reset live turn
  clearMarkers();
  resetTurn();

  // 7️⃣ Prepare next target (if automated)
  prepareNextTarget();

  // 8️⃣ Increment turn number
  turnNumber++;
});


  function clearMarkers() {
    document.querySelectorAll(".dart-marker").forEach((m) => m.remove());
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

  
  function addTurnRow(darts, turnTotal) {

    // BEGIN DEBUG
    console.log("=== addTurnRow called ===");
    darts.forEach((dart, i) => {
      console.log(`History Dart ${i}:`, dart);
    });

    // END DEBUG

    
    if (!darts || darts.length === 0) return;
  
    const row = document.createElement("tr");
    const remainingAfter = remainingScore;
  
    // Build the row with dart scores
    row.innerHTML = `
      <td>${turnNumber}</td>
      <td>${darts[0]?.score ?? 0}</td>
      <td>${darts[1]?.score ?? 0}</td>
      <td>${darts[2]?.score ?? 0}</td>
      <td>${turnTotal}</td>
      <td>${remainingAfter}</td>
    `;
  
    // Apply stored classes from each dart
    darts.forEach((dart, i) => {
      const cell = row.children[i + 1]; // dart score cell
      if (dart.classes && dart.classes.length > 0) {
        dart.classes.forEach((cls) => {
          console.log(`Applying class '${cls}' to dart ${i} in history`);
          cell.classList.add(cls);
        });
        
      }
    });
  
    // Append the row to the scoreboard
    scoreboardBody.appendChild(row);
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

  function placeNormalMarker(x, y) {
    const marker = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "circle"
    );

    marker.setAttribute("cx", x);
    marker.setAttribute("cy", y);
    marker.setAttribute("r", 2);
    marker.setAttribute("fill", MARKER_COLOR);
    marker.classList.add("dart-marker");

    svg.appendChild(marker);
    markers.push(marker);
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
    console.log("🎉 DOUBLE OUT CELEBRATION! 🎉");
  
    // Launch full-screen confetti
    confetti({
      particleCount: 150,
      spread: 70,
      origin: { y: 0.6 },
      colors: ["#ff0", "#f00", "#0f0", "#0ff", "#f0f"]
    });
  
    // Optional: repeat for a bigger effect
    setTimeout(() => {
      confetti({
        particleCount: 100,
        spread: 100,
        origin: { y: 0.6 },
        colors: ["#ff0", "#f00", "#0f0", "#0ff", "#f0f"]
      });
    }, 500);
  }
  
  function startNewGame() {
    console.log("Starting new game!");
  
    // ✅ Reset live turn
    resetTurn(); // clears dartCells, turnTotal, dartIndex, darts array
    turnNumber = 1;
    remainingScore = startingScore; // e.g., 501
    remainingSpan.textContent = remainingScore;
  
    // ✅ Clear history table
    const rows = scoreboardBody.querySelectorAll("tr");
    rows.forEach((row) => row.remove());
  
    // ✅ Clear any SVG markers
    clearMarkers();
  
    // ✅ Unlock board
    boardLocked = false;
  
    // ✅ Clear any target highlights
    clearTargetHighlight();
  
    // ✅ Prepare first target automatically
    prepareNextTarget();
  }
  
  

  //ADD NEW LISTENERS HERE

  // Initial calculated target on game start
  const initialTarget = getTarget(remainingScore);
  highlightTarget(initialTarget.score, initialTarget.multiplier);
});
