document.addEventListener('DOMContentLoaded', () => {

  // ================================
  // CONFIG
  // ================================
  const svg = document.getElementById("dartboard");
  const MARKER_COLOR = "#66ccff";
  const markers = [];

  let turnNumber = 0;
let startingScore = 501;
let remainingScore = startingScore;

const scoreboardBody = document.getElementById("scoreboard-body");
const remainingSpan = document.getElementById('remaining-score');




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
    document.getElementById("d3")
  ];

  const totalCell = document.getElementById("turnTotal");
  const confirmBtn = document.getElementById("confirmTurn");

  if (!svg || !confirmBtn) {
    console.error("Dartboard elements missing from DOM");
    return;
  }
// ================================
// BOARD CLICK
// ================================
svg.addEventListener("click", (e) => {
    if (boardLocked || dartIndex >= 3) return;
  
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const cursor = pt.matrixTransform(svg.getScreenCTM().inverse());
  
    let score = 0;
    let segment = "MISS";
  
    const el = e.target;
  
    if (el && (el.tagName === "path" || el.tagName === "use" || el.tagName === "circle")) {
      const value = Number(el.dataset.value);
      const multiplier = Number(el.dataset.multiplier);
  
      if (!isNaN(value) && !isNaN(multiplier)) {
        score = value * multiplier;
        segment = `${multiplier}x${value}`;
      }
    }
  
    placeMarker(cursor.x, cursor.y);
  
    darts.push({
      dart: dartIndex + 1,
      x: cursor.x,
      y: cursor.y,
      score,
      segment
    });
  
    dartCells[dartIndex].textContent = score;
        // Highlight T20 if hit
    if (el.dataset.value == 20 && el.dataset.multiplier == 3) {
        dartCells[dartIndex].classList.add('t20-highlight');
    } else {
        dartCells[dartIndex].classList.remove('t20-highlight');
    }
    turnTotal += score;
    totalCell.textContent = turnTotal;
  
    // 🔹 ADD THIS BLOCK (RIGHT HERE)
    const nextRemaining = remainingScore - score;
  
    if (nextRemaining >= 0) {
      remainingScore = nextRemaining;
    }
    remainingSpan.textContent = remainingScore;

    // Calculate new target
    const suggested = getSuggestedTarget(remainingScore);
    highlightTarget(suggested.score, suggested.multiplier);

    updateSuggestedTargetText(suggested);

    // (If negative, ignore for now — bust logic later)
  
    // document.getElementById("remaining-score").textContent = remainingScore;
    // 🔹 END ADDITION
  
    dartIndex++;
  
    if (dartIndex === 3) {
      boardLocked = true;
      
    // Add completed turn to scoreboard
    // addTurnRow(darts, turnTotal);

    // Reduce remaining score
    // remainingScore -= turnTotal;
    document.getElementById("remaining-score").textContent = remainingScore;
    

    // Prepare for next turn
    turnNumber++;
    }
  });
  

  // ================================
  // MARKER
  // ================================
  function placeMarker(x, y) {
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

  // ================================
  // CONFIRM TURN
  // ================================
  confirmBtn.addEventListener("click", () => {
    if (dartIndex === 0) return; // skip if no darts thrown
  
    // 1️⃣ Commit the turn
    // remainingScore -= turnTotal;
    remainingSpan.textContent = remainingScore;
    
  
    

    // 2️⃣ Add row to history table
    addTurnRow(darts, turnTotal);
  
    // 3️⃣ Clear markers and reset live turn
    clearMarkers();
    resetTurn();
  });
  

  function clearMarkers() {
    document.querySelectorAll(".dart-marker").forEach(m => m.remove());
  }

  function resetTurn() {
    dartIndex = 0;
    turnTotal = 0;
    boardLocked = false;
    darts = [];

    dartCells.forEach(c => (c.textContent = ""));
    totalCell.textContent = "0";
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
    const suggested = getSuggestedTarget(remainingScore);
    highlightTarget(suggested.score, suggested.multiplier);
  
    // 4️⃣ Update scoreboard
    dartIndex--;
    dartCells[dartIndex].textContent = "";
  
    turnTotal -= lastDart.score;
    totalCell.textContent = turnTotal;
  
    // 5️⃣ Unlock board if needed
    boardLocked = false;
  });

  function addTurnRow(darts, turnTotal) {
    const row = document.createElement("tr");
  
    // const remainingAfter = remainingScore - turnTotal;  ERROR
    
    const remainingAfter = remainingScore;
  
    row.innerHTML = `
      <td>${turnNumber}</td>
      <td>${darts[0]?.score ?? 0}</td>
      <td>${darts[1]?.score ?? 0}</td>
      <td>${darts[2]?.score ?? 0}</td>
      <td>${turnTotal}</td>
      <td>${remainingAfter}</td>
    `;
      // Add T20 highlight for history table
      darts.forEach((dart, i) => {
        if (dart.score === 60) { // triple 20
            row.children[i + 1].classList.add('t20-highlight');
        }
    });

    scoreboardBody.appendChild(row);
  }

  function highlightTarget(segmentNumber, multiplier = 1) {
    // Clear any existing target highlight
    document.querySelectorAll('.target-suggest').forEach(el => {
        el.classList.remove('target-suggest');
    });

    // Find the element for this segment
    const elements = svg.querySelectorAll(`[data-value='${segmentNumber}'][data-multiplier='${multiplier}']`);
    elements.forEach(el => el.classList.add('target-suggest'));
}

function clearTargetHighlight() {
    document.querySelectorAll('.target-suggest').forEach(el => {
        el.classList.remove('target-suggest');
    });
}

function getSuggestedTarget(remainingScore) {
    const preferredFinishes = [40, 32, 20, 16, 10, 8, 4, 2];

    // 1️⃣ High score → keep scoring
    if (remainingScore > 60) {
        return { score: 20, multiplier: 3 };
    }

    // 2️⃣ Exact double finish
    if (preferredFinishes.includes(remainingScore)) {
        return {
            score: remainingScore / 2,
            multiplier: 2
        };
    }

    // 3️⃣ Setup shot → leave a preferred double
    for (let finish of preferredFinishes) {
        const needed = remainingScore - finish;
        if (needed >= 1 && needed <= 20) {
            return { score: needed, multiplier: 1 };
        }
    }

    // 4️⃣ Bulls
    if (remainingScore === 50) {
        return { score: 50, multiplier: 1 };
    }

    if (remainingScore === 25) {
        return { score: 25, multiplier: 1 };
    }

    // 5️⃣ Fallback (safe single)
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

  //ADD NEW LISTENERS HERE

  // Initial suggested target on game start
const initialTarget = getSuggestedTarget(remainingScore);
highlightTarget(initialTarget.score, initialTarget.multiplier);

});
