/* yahtzee.js
   Final functional logic for the Yahtzee 6-game scorebook.
*/

/* =========================
   Configuration / Values
   ========================= */
const upperCats = ["ones", "twos", "threes", "fours", "fives", "sixes"];
const lowerCats = [
  "three_kind",
  "four_kind",
  "full_house",
  "small_straight",
  "large_straight",
  "yahtzee",
  "chance",
];

// per-game flag: when true, yahtzee cycles use bonus values (100,200,...) instead of normal (50,150,...)
const yahtzeeBonusModeByGame = {
  1: false,
  2: false,
  3: false,
  4: false,
  5: false,
  6: false,
};

const yahtzeeBonusMode = {}; // keyed by game number

// helper to read/write cleanly
function isYahtzeeBonusMode(game) {
  return !!yahtzeeBonusModeByGame[Number(game)];
}
function setYahtzeeBonusMode(game, on) {
  yahtzeeBonusModeByGame[Number(game)] = !!on;
}

const cycleValues = {
  ones: [1, 2, 3, 4, 5],
  twos: [2, 4, 6, 8, 10],
  threes: [3, 6, 9, 12, 15],
  fours: [4, 8, 12, 16, 20],
  fives: [5, 10, 15, 20, 25],
  sixes: [6, 12, 18, 24, 30],
  full_house: [25],
  small_straight: [30],
  large_straight: [40],
  yahtzee: [50, 150, 250, 350, 450, 550, 650, 750, 850, 950, 1050, 1150],
  bonus_yahtzee: [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000],
};

const manualCategories = ["three_kind", "four_kind", "chance"];

// track active element per game column
const activeByGame = { 1: null, 2: null, 3: null, 4: null, 5: null, 6: null };

//Track the active cell
let activeCell = null;

/* =========================
      Build table
      ========================= */
function buildBody() {
  const tbody = document.getElementById("body-rows");
  tbody.innerHTML = ""; // clear previous rows

  // Upper categories with icons
  const upperCats = [
    { key: "ones", label: "🎲 Ones" },
    { key: "twos", label: "🎲 Twos" },
    { key: "threes", label: "🎲 Threes" },
    { key: "fours", label: "🎲 Fours" },
    { key: "fives", label: "🎲 Fives" },
    { key: "sixes", label: "🎲 Sixes" },
  ];

  // Lower categories with icons
  const lowerCats = [
    { key: "three_kind", label: "🎲 Three of a Kind" },
    { key: "four_kind", label: "🎲 Four of a Kind" },
    { key: "full_house", label: "🎲 Full House" },
    { key: "small_straight", label: "🎲 Small Straight" },
    { key: "large_straight", label: "🎲 Large Straight" },
    { key: "yahtzee", label: "⭐ Yahtzee" },
    { key: "chance", label: "🎲 Chance" },
  ];

  // Build upper rows
  upperCats.forEach((cat) => {
    const tr = document.createElement("tr");
    const tdLabel = document.createElement("td");
    tdLabel.className = "category-col";
    tdLabel.textContent = cat.label;
    tr.appendChild(tdLabel);

    for (let g = 1; g <= 6; g++) {
      const td = document.createElement("td");
      td.className = "scorecell";
      td.dataset.category = cat.key;
      td.dataset.game = g;
      td.tabIndex = 0;
      tr.appendChild(td);
    }

    tbody.appendChild(tr);
  });

  // Upper Total row
  const trUT = document.createElement("tr");
  const tdUTlabel = document.createElement("td");
  tdUTlabel.className = "category-col";
  tdUTlabel.textContent = "UPPER TOTAL";
  trUT.appendChild(tdUTlabel);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement("td");
    td.className = "upper-total";
    td.dataset.game = g;
    td.textContent = "0";
    trUT.appendChild(td);
  }
  // Attach keypad click handlers for lower manual-entry cells
  document.querySelectorAll(".keypad").forEach((td) => {
    td.onclick = () => {
      const allowX = td.dataset.allowX === "true";
      openKeypad(td, allowX);
    };
  });

  tbody.appendChild(trUT);

  // Bonus row
  const trB = document.createElement("tr");
  const tdBlabel = document.createElement("td");
  tdBlabel.className = "category-col";
  tdBlabel.textContent = "BONUS (≥63 → 35)";
  trB.appendChild(tdBlabel);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement("td");
    td.className = "upper-bonus";
    td.dataset.game = g;
    td.textContent = "0";
    trB.appendChild(td);
  }
  tbody.appendChild(trB);
  // Build lower rows
  lowerCats.forEach((cat) => {
    const tr = document.createElement("tr");
    const tdLabel = document.createElement("td");
    tdLabel.className = "category-col";
    tdLabel.textContent = cat.label;
    tdLabel.dataset.category = cat.key;
    tr.appendChild(tdLabel);

    for (let g = 1; g <= 6; g++) {
      const td = document.createElement("td");
      td.dataset.category = cat.key;
      td.dataset.game = g;
      td.tabIndex = 0;

      // Manual-entry categories with keypad
      const manualCats = ["three_kind", "four_kind", "chance"];
      if (manualCats.includes(cat.key)) {
        td.className = "scorecell keypad";
        td.textContent = ""; // empty; will trigger keypad on click
        // Optionally store allowed entries for later
        td.dataset.allowX = cat.key !== "chance";
      } else {
        td.className = "scorecell";
      }

      tr.appendChild(td);
    }

    tbody.appendChild(tr);
  });
}

function buildFoot() {
  const tfoot = document.getElementById("totals-rows");
  tfoot.innerHTML = "";

  // Lower total
  const trLower = document.createElement("tr");
  const tdLabelLower = document.createElement("td");
  tdLabelLower.className = "category-col";
  tdLabelLower.textContent = "LOWER TOTAL";
  trLower.appendChild(tdLabelLower);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement("td");
    td.className = "lower-total";
    td.dataset.game = String(g);
    td.textContent = "0";
    trLower.appendChild(td);
  }
  tfoot.appendChild(trLower);

  // Grand total
  const trGrand = document.createElement("tr");
  const tdLabelGrand = document.createElement("td");
  tdLabelGrand.className = "category-col";
  tdLabelGrand.textContent = "GRAND TOTAL";
  trGrand.appendChild(tdLabelGrand);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement("td");
    td.className = "grand-total";
    td.dataset.game = String(g);
    td.textContent = "0";
    trGrand.appendChild(td);
  }
  tfoot.appendChild(trGrand);

  // Cumulative total
  const trCum = document.createElement("tr");
  const tdLabelCum = document.createElement("td");
  tdLabelCum.className = "category-col";
  tdLabelCum.textContent = "CUMULATIVE TOTAL";
  trCum.appendChild(tdLabelCum);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement("td");
    td.className = "cumulative-total";
    td.dataset.game = String(g);
    td.textContent = "0";
    trCum.appendChild(td);
  }
  tfoot.appendChild(trCum);
}

/* =========================
      Utilities
      ========================= */
function cycleNext(cat, cur, customVals) {
  const vals = customVals || cycleValues[cat];
  if (!vals) return "";
  cur = String(cur || "").trim();

  if (cur === "") return String(vals[0]);
  if (cur === "X") return "";

  const idx = vals.indexOf(Number(cur));
  if (idx === -1) return String(vals[0]);

  return idx === vals.length - 1 ? "X" : String(vals[idx + 1]);
}

function cyclePrev(cat, cur) {
  const vals = cycleValues[cat];
  if (!vals) return "";
  cur = String(cur || "").trim();
  if (cur === "") return "X";
  if (cur === "X") return String(vals[vals.length - 1]);
  const idx = vals.indexOf(Number(cur));
  if (idx <= 0) return "";
  return String(vals[idx - 1]);
}

function getCellValue(cat, game) {
  const td = document.querySelector(
    `.scorecell[data-category="${cat}"][data-game="${game}"]`
  );
  if (!td) return { num: 0, raw: "" };

  const raw = td.textContent.trim();
  if (!raw) return { num: 0, raw: "" };
  if (raw === "X") return { num: 0, raw: "X" };

  const num = Number(raw);
  return { num: isNaN(num) ? 0 : num, raw };
}

/* =========================
      Totals and Bonus
      ========================= */
function updateTotals() {
  console.log("updateTotals running...");

  let cumulative = 0;

  for (let g = 1; g <= 6; g++) {
    // Upper
    let upperSum = 0;
    let upperFilled = 0;
    upperCats.forEach((cat, idx) => {
      const v = getCellValue(cat, g);
      if (v.raw !== "") {
        upperFilled++;
        upperSum += v.num;
      }
      updateUpperCellColor(cat, g, v.raw);
    });

    // === Pace calculation ===
    let pace = 0;
    if (upperFilled < 6 && upperSum < 63) {
      // only show pace if upper not full and below 63
      let expectedSum = 0;
      upperCats.forEach((cat, idx) => {
        const v = getCellValue(cat, g);
        if (v.raw !== "") {
          expectedSum += 3 * (idx + 1); // 3×face value
        }
      });
      pace = upperSum - expectedSum;
    }

    // Bonus
    let bonusDisplay = "0";
    let bonusNumeric = 0;
    if (upperSum >= 63) {
      bonusDisplay = "35";
      bonusNumeric = 35;
    } else if (upperFilled === 6) {
      bonusDisplay = "X";
      bonusNumeric = 0;
    }

    // Lower
    let lowerSum = 0;
    ["full_house", "small_straight", "large_straight", "yahtzee"].forEach(
      (cat) => {
        const v = getCellValue(cat, g);
        lowerSum += v.num;
        updateLowerCellColor(cat, g, v.raw);
      }
    );
    ["three_kind", "four_kind", "chance"].forEach((cat) => {
      const v = getCellValue(cat, g);
      lowerSum += v.num;
      updateLowerCellColor(cat, g, v.raw);
    });

    const grand = upperSum + bonusNumeric + lowerSum;
    cumulative += grand;

    // Write totals
    const ut = document.querySelector(`.upper-total[data-game="${g}"]`);
    if (ut) {
      ut.innerHTML = String(upperSum);
      ut.classList.remove("upper-red", "upper-green", "upper-neutral");

      if (upperSum >= 63) {
        ut.classList.add("upper-green");
      } else if (upperFilled === 6) {
        ut.classList.add("upper-red");
      } else {
        ut.classList.add("upper-neutral");
      }

      if (pace !== 0) {
        const span = document.createElement("span");
        span.classList.add("pace");
        span.textContent = ` (${Math.abs(pace)})`;
        span.style.color = pace > 0 ? "#06800a" : "red";
        ut.appendChild(span);
      }
    }

    const ub = document.querySelector(`.upper-bonus[data-game="${g}"]`);
    if (ub) {
      ub.textContent = bonusDisplay;
      ub.classList.remove("upper-red", "upper-green", "upper-neutral");

      if (upperSum >= 63) {
        ub.classList.add("upper-green");
      } else if (upperFilled === 6) {
        ub.classList.add("upper-red");
      } else {
        ub.classList.add("upper-neutral");
      }
    }

    const lt = document.querySelector(`.lower-total[data-game="${g}"]`);
    if (lt) lt.textContent = String(lowerSum);

    const gt = document.querySelector(`.grand-total[data-game="${g}"]`);
    if (gt) gt.textContent = String(grand);

    const ct = document.querySelector(`.cumulative-total[data-game="${g}"]`);
    if (ct) ct.textContent = String(cumulative);
  }
}

/* =========================
      Locking helpers
      ========================= */
function lockOtherNonBlankInColumn(game, exceptEl = null) {
  document.querySelectorAll(`[data-game="${game}"]`).forEach((el) => {
    if (el === exceptEl) return;

    let value = "";
    if (el.tagName === "SELECT") {
      value = el.value.trim();
    } else {
      value = el.textContent.trim();
    }

    if (value !== "") {
      el.classList.add("locked");
      if (el.classList.contains("keypad")) el.dataset.locked = "true"; // track keypad state
      if (el.tagName === "SELECT") el.disabled = true;
    } else {
      el.classList.remove("locked");
      if (el.classList.contains("keypad")) el.dataset.locked = "false";
      if (el.tagName === "SELECT") el.disabled = false;
    }
  });
}

function lockOtherNonBlank(exceptEl = null) {
  document.querySelectorAll(".scorecell").forEach((td) => {
    if (td === exceptEl) return; // skip current active
    const raw = td.textContent.trim();
    if (raw !== "") td.classList.add("locked");
    else td.classList.remove("locked");
  });
}

function unlockColumn(game) {
  const header = document.querySelector(`.column-header[data-game="${game}"]`);
  if (header) header.classList.add("inverted");

  document.querySelectorAll(`[data-game="${game}"]`).forEach((el) => {
    el.classList.remove("locked");

    if (el.tagName === "SELECT") {
      el.disabled = false; // re-enable dropdowns
    }

    if (el.classList.contains("keypad")) {
      el.dataset.locked = "false"; // unlock keypad cell
    }
  });

  activeByGame[game] = null;
}

function lockFullColumn(game) {
  // lock all non-blank cells; if full, lock everything regardless
  document.querySelectorAll(`.scorecell[data-game="${game}"]`).forEach((td) => {
    const raw = td.textContent.trim();
    if (raw !== "") td.classList.add("locked");
  });
  document
    .querySelectorAll(`select.inline[data-game="${game}"]`)
    .forEach((sel) => {
      if (sel.value !== "") {
        sel.disabled = true;
        sel.classList.add("locked");
      } else {
        sel.disabled = false;
        sel.classList.remove("locked");
      }
    });
}

/* Check whether column is fully filled (13 scoring cells) and lock if so */
function checkAndLockCompletedColumns() {
  for (let g = 1; g <= 6; g++) {
    let filledCount = 0;
    // count score cells and manual selects in that column (13 total)
    document
      .querySelectorAll(
        `.scorecell[data-game="${g}"], select.inline[data-game="${g}"]`
      )
      .forEach((el) => {
        const val = el.tagName === "SELECT" ? el.value : el.textContent.trim();
        if (val !== "") filledCount++;
      });
    //    if (filledCount >= 13) {
    //      // lock whole column (but still allow header to unlock)
    //      lockFullColumn(g);
    //    }
  }
}

/* =========================
      Event attachment
      ========================= */
function attachHandlers() {
  // document.querySelectorAll('.scorecell[data-category="yahtzee"]').forEach(td => {
  //   td.addEventListener('dblclick', (e) => {
  //     const game = td.dataset.game;
  //     const val = td.textContent.trim();

  //     // Only allow toggling if Yahtzee was scratched
  //     if (val === 'X') {
  //       yahtzeeBonusMode[game] = !yahtzeeBonusMode[game]; // flip mode
  //       td.classList.toggle('bonus-mode', yahtzeeBonusMode[game]);

  //       // Optionally update the cell to first value in bonus list
  //       if (yahtzeeBonusMode[game]) {
  //         td.textContent = cycleValues['bonus_yahtzee'][0];
  //         td.classList.remove('scratch');
  //       } else {
  //         td.textContent = 'X';
  //       }

  //       updateTotals();
  //     }
  //   });
  // });

  // Add toggle for Yahtzee bonus mode
  document
    .querySelectorAll('.scorecell[data-category="yahtzee"]')
    .forEach((td) => {
      td.addEventListener("dblclick", (e) => {
        // Only allow toggle if this cell is scratched
        if (td.textContent.trim() !== "X") return;

        // Toggle bonus mode
        if (!td.dataset.bonusMode || td.dataset.bonusMode === "off") {
          td.dataset.bonusMode = "on";
          cycleValues.yahtzee = [
            100, 200, 300, 400, 500, 600, 700, 800, 900, 1000,
          ];
        } else {
          td.dataset.bonusMode = "off";
          cycleValues.yahtzee = [
            50, 150, 250, 350, 450, 550, 650, 750, 850, 950, 1050, 1150,
          ];
        }

        // Reset current cell to first value in new array
        td.textContent = String(cycleValues.yahtzee[0]);
        td.classList.remove("scratch");

        updateTotals();
      });
    });

  // cycle cells

  const yahtzeeHeader = document.querySelector(
    '.category-col[data-category="yahtzee"]'
  );
  if (yahtzeeHeader) {
    yahtzeeHeader.addEventListener("dblclick", (e) => {
      const game = currentActiveGame(); // however you determine the active game column
      if (!game) return;

      const td = document.querySelector(
        `.scorecell[data-category="yahtzee"][data-game="${game}"]`
      );
      if (!td) return;

      if (td.textContent.trim() !== "X") return; // only toggle if scratched

      // toggle bonus mode
      if (!td.dataset.bonusMode || td.dataset.bonusMode === "off") {
        td.dataset.bonusMode = "on";
        cycleValues.yahtzee = [
          100, 200, 300, 400, 500, 600, 700, 800, 900, 1000,
        ];
      } else {
        td.dataset.bonusMode = "off";
        cycleValues.yahtzee = [
          50, 150, 250, 350, 450, 550, 650, 750, 850, 950, 1050, 1150,
        ];
      }

      td.textContent = String(cycleValues.yahtzee[0]);
      td.classList.remove("scratch");

      updateTotals();
      updateRollsLeft(game);
    });
  }

  document.querySelectorAll(".scorecell").forEach((td) => {
    const cat = td.dataset.category;
    const game = td.dataset.game;

    td.addEventListener("click", (e) => {
      const game = td.dataset.game;

      // --- revert header color for this game ---
      const header = document.querySelector(
        `.column-header[data-game="${game}"]`
      );
      if (header) {
        header.classList.remove("inverted"); // revert to default color
        console.log("Header revert triggered for game", game);
      } else {
        console.warn("Header not found for game", game);
      }

      // ignore if locked
      if (td.classList.contains("locked")) return;

      // deactivate previous active in this column
      const prev = activeByGame[game];
      if (prev && prev !== td) prev.classList.remove("active");

      // set this as active and lock other non-blank cells except this
      activeByGame[game] = td;
      td.classList.add("active");
      lockOtherNonBlank(td);

      // cycle the value (handle Yahtzee bonus mode if needed)
      let values = cycleValues[td.dataset.category];
      if (td.dataset.category === "yahtzee" && isYahtzeeBonusMode(game)) {
        values = cycleValues["bonus_yahtzee"];
      }
      const next = cycleNext(
        td.dataset.category,
        td.textContent.trim(),
        values
      );
      td.textContent = next;
      td.classList.toggle("scratch", next === "X");

      updateTotals();
      updateRollsLeft(game);
      updateGameHeaderStatus(game);

      // check completed columns
      checkAndLockCompletedColumns();
    });

    td.addEventListener("contextmenu", (e) => {
      e.preventDefault();
      if (td.classList.contains("locked")) return;
      // cycle down
      const prevVal = cyclePrev(cat, td.textContent.trim());
      td.textContent = prevVal;
      td.classList.toggle("scratch", prevVal === "X");
      updateTotals();
      updateRollsLeft(game);
      updateGameHeaderStatus(game);

      // No locking triggered by right-click
      checkAndLockCompletedColumns();
    });
  });

  // selects (manual dropdowns)
  document.querySelectorAll("select.inline").forEach((sel) => {
    const cat = sel.dataset.category;
    const game = sel.dataset.game;

    sel.addEventListener("focus", (e) => {
      if (sel.disabled) return;
      // deactivate previous active in this column
      const prev = activeByGame[game];
      if (prev && prev !== sel && prev.classList)
        prev.classList.remove("active");
      // set this as active
      activeByGame[game] = sel;
      // lock other non-blank in column except sel
      lockOtherNonBlank(sel);
    });

    sel.addEventListener("change", (e) => {
      // clamp numeric values if entered (shouldn't be necessary for option selects)
      const v = sel.value;
      if (v === "") {
        /* blank */
      } else if (v === "X") {
        /* scratch */
      } else {
        let n = Number(v);
        if (isNaN(n)) sel.value = "";
        else {
          if (n < 5) n = 5;
          if (n > 30) n = 30;
          sel.value = String(n);
        }
      }
      updateTotals();
      updateRollsLeft(game);

      updateGameHeaderStatus(game);

      // After change, check for completed columns
      checkAndLockCompletedColumns();
    });

    sel.addEventListener("change", () => {
      updateTotals();
      updateGameHeaderStatus(game);
      checkAndLockCompletedColumns();

      if (cat.key === "chance") {
        updateChanceWarning(g);
      }
    });

    sel.addEventListener("contextmenu", (e) => {
      e.preventDefault();
      if (sel.disabled) return;
      if (cat === "chance") return; // chance disallows scratch
      sel.value = sel.value === "X" ? "" : "X";
      if (cat.key === "chance") {
        updateChanceWarning(g);
      }
      updateTotals();
      // Right-click scratch does not lock
      checkAndLockCompletedColumns();
    });
  });

  // column header unlock
  document.querySelectorAll(".column-header").forEach((h) => {
    h.addEventListener("click", (e) => {
      const g = h.dataset.game;
      if (!g) return;

      // remove highlight from all headers first
      document
        .querySelectorAll(".column-header")
        .forEach((hh) => hh.classList.remove("inverted"));

      // highlight only this one
      h.classList.add("inverted");

      unlockColumn(g);
      updateTotals();
    });
  });
}

function updateUpperCellColor(cat, game, value) {
  // find the TD for this upper category
  const td = document.querySelector(
    `.scorecell[data-category="${cat}"][data-game="${game}"]`
  );
  if (!td) return;

  // clear any previous color classes (keep names consistent with your CSS)
  td.classList.remove("red", "yellow", "green", "scratch");

  // blank or null -> no color
  if (value === "" || value === null) return;

  // scratch -> red
  if (value === "X") {
    td.classList.add("red");
    td.classList.add("scratch"); // optional: if you want a specific scratch style
    return;
  }

  // numeric value -> color by multiples of face value
  const num = Number(value);
  if (isNaN(num)) return;

  const base = {
    ones: 1,
    twos: 2,
    threes: 3,
    fours: 4,
    fives: 5,
    sixes: 6,
  }[cat];

  if (!base) return;

  if (num >= 4 * base) {
    td.classList.add("green");
  } else if (num === 3 * base) {
    td.classList.add("yellow");
  } else if (num === 1 * base || num === 2 * base) {
    td.classList.add("red");
  } else {
    // fallback: if it's some other valid number, treat >3*base as green, else red
    td.classList.add(num > 3 * base ? "green" : "red");
  }
}

// Update Colour
function updateLowerCellColor(cat, game, value) {
  // Always target the TD now (since dropdowns are gone)
  const target = document.querySelector(
    `.scorecell[data-category="${cat}"][data-game="${game}"]`
  );
  if (!target) return;

  // Remove previous color classes
  target.classList.remove("red", "yellow", "green", "neutral");

  if (value === "" || value === null) return; // blank → no color
  if (value === "X") {
    target.classList.add("red"); // scratch
    return;
  }

  const numVal = Number(value);

  // Apply your thresholds for lower categories
  if (["three_kind", "four_kind", "chance"].includes(cat)) {
    if (numVal >= 20) {
      target.classList.add("green");
    } else if (numVal >= 14) {
      target.classList.add("yellow");
    } else {
      target.classList.add("red");
    }
  } else {
    // Other lower categories (straights, etc.)
    target.classList.add("green");
  }
}

async function saveGame() {
  try {
    const scores = {};

    for (let g = 1; g <= 6; g++) {
      scores[g] = {};

      // Upper categories
      upperCats.forEach((cat) => {
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        scores[g][cat] = td ? td.textContent.trim() : "";
      });

      // Lower categories
      lowerCats.forEach((cat) => {
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        if (!td) {
          scores[g][cat] = "";
          return;
        }
        const input = td.querySelector("input"); // for keypad inputs
        if (input) {
          scores[g][cat] = input.value.trim();
        } else {
          scores[g][cat] = td.textContent.trim();
        }
      });
    }

    console.log("Saving payload:", scores);

    const response = await fetch("save_yahtzee.php", {
      method: "POST",
      credentials: "include",               // <- IMPORTANT: send session cookie
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify({ scores })
    });

    const result = await response.json();
    console.log("Save response:", result);

    if (!response.ok) {
      // Server returned non-2xx
      alert("Save failed: " + (result.error || response.statusText));
      return;
    }

    if (result.status === "ok") {
      alert(`Saved to session ${result.session_id}`);
    } else {
      alert("Save failed: " + (result.error || "unknown"));
    }
  } catch (err) {
    console.error("Error saving game:", err);
    alert("Error saving game — check console/network");
  }
}


function loadGame() {
  fetch("load_yahtzee.php")
    .then((res) => res.json())
    .then((data) => {
      if (!data.scores) return;
      for (let g = 1; g <= 6; g++) {
        upperCats.forEach((cat) => {
          const td = document.querySelector(
            `.scorecell[data-category="${cat}"][data-game="${g}"]`
          );
          if (td) td.textContent = data.scores[g]?.[cat] || "";
        });
        lowerCats.forEach((cat) => {
          const sel = document.querySelector(
            `select.inline[data-category="${cat}"][data-game="${g}"]`
          );
          const td = document.querySelector(
            `.scorecell[data-category="${cat}"][data-game="${g}"]`
          );
          if (sel) sel.value = data.scores[g]?.[cat] || "";
          else if (td) td.textContent = data.scores[g]?.[cat] || "";
        });
      }
      updateTotals();  
    });
}

document
  .getElementById("new-session-btn")
  .addEventListener("click", async () => {
    if (!confirm("Start a new session? This will clear the current board."))
      return;

    try {
      const res = await fetch("new_yahtzee_session.php", { method: "POST" });
      const data = await res.json();

      if (data.status === "ok") {
        alert(`New session started (Session ${data.session_id}).`);
        clearScorecard(); // we'll define this below
      } else {
        alert("Failed to start new session.");
      }
    } catch (err) {
      console.error("Error creating new session:", err);
      alert("Error creating new session.");
    }
  });

// helper to blank all cells
function clearScorecard() {
  document.querySelectorAll(".scorecell, select.inline").forEach((el) => {
    if (el.tagName === "SELECT") el.value = "";
    else el.textContent = "";
    el.classList.remove("red", "green", "yellow", "scratch");
  });
}


document.getElementById("save-btn").addEventListener("click", async () => {
  try {
    const scores = {};

    for (let g = 1; g <= 6; g++) {
      scores[g] = {};

      // --- Upper section ---
      upperCats.forEach((cat) => {
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        scores[g][cat] = td ? td.textContent.trim() : "";
      });

      // --- Lower section ---
      lowerCats.forEach((cat) => {
        // Try to find a <select> (dropdown) first
        const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${g}"]`);
        if (sel) {
          scores[g][cat] = sel.value.trim();
          return; // continue to next category
        }

        // Otherwise, use <td> (for keypad or text input versions)
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        if (td) {
          scores[g][cat] = td.textContent.trim();
        }
      });
    }

    console.log("✅ Saving payload:", scores);

    const response = await fetch("save_yahtzee.php", {
      method: "POST",
      credentials: "include", // send cookies/session
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ scores }),
    });

    const result = await response.json();
    console.log("✅ Save response:", result);

    if (response.ok && result.status === "ok") {
      alert(`Game saved successfully! (Session ${result.session_id})`);
    } else {
      alert("❌ Error saving game: " + (result.error || "Unknown error"));
    }
  } catch (err) {
    console.error("❌ Error saving game:", err);
    alert("Error saving game — see console");
  }
});


async function populateSessions() {
  const select = document.getElementById("load-session");
  try {
    const res = await fetch("list_sessions.php", {
      credentials: "include", // ✅ ensures PHP session cookie is sent
    });

    if (!res.ok) {
      throw new Error(`HTTP error ${res.status}`);
    }

    const data = await res.json();
    if (!data.sessions) {
      throw new Error("No sessions returned");
    }

    data.sessions.forEach((s) => {
      const opt = document.createElement("option");
      opt.value = s.session_id; // ✅ match your PHP output
      opt.textContent = `Session ${s.session_id} — ${s.created_at}`;
      select.appendChild(opt);
    });
  } catch (err) {
    console.error("Failed to load sessions:", err);
  }
}
document.getElementById("load-session").addEventListener("change", async (e) => {
  const sessionId = e.target.value;
  if (!sessionId) return;

  try {
    const res = await fetch(`load_session.php?session_id=${sessionId}`);
    const data = await res.json();
    if (!data.scores) return;

    // Clear and rebuild scorecard
    buildBody();
    buildFoot();

    // Populate scores
    for (let g = 1; g <= 6; g++) {
      const gameScores = data.scores[g] || {};
      for (const [cat, val] of Object.entries(gameScores)) {
        const td = document.querySelector(
          `.scorecell[data-category="${cat}"][data-game="${g}"]`
        );
        if (td) td.textContent = val || "";
      }
    }

    updateTotals();
    attachHandlers(); // reattach click listeners

  } catch (err) {
    console.error("Failed to load session:", err);
  }
});

  document.getElementById("load-btn").addEventListener("click", async () => {
    try {
      const response = await fetch("load_yahtzee.php"); // path updated
      if (!response.ok) throw new Error("Failed to load session");
  
      const data = await response.json();
      if (!data.scores || Object.keys(data.scores).length === 0) {
        alert("No saved session found.");
        return;
      }
  
      // --- Clear everything first ---
      document.querySelectorAll(".scorecell").forEach((td) => {
        td.textContent = "";
        td.classList.remove("locked", "active", "scratch", "red", "green");
      });
  
      document.querySelectorAll("select.inline").forEach((sel) => {
        sel.value = "";
        sel.disabled = false;
        sel.classList.remove("locked", "red", "green");
      });
  
      document.querySelectorAll("input.keypad-input").forEach((inp) => {
        inp.value = "";
        inp.disabled = false;
        inp.classList.remove("locked", "red", "green");
      });
  
      // --- Fill in loaded values ---
      for (const [gameStr, categories] of Object.entries(data.scores)) {
        const game = Number(gameStr);
  
        for (const [cat, val] of Object.entries(categories)) {
          const isScratch = val === "X";
  
          // 1️⃣ Try keypad input (new UI)
          const keypadInput = document.querySelector(
            `input.keypad-input[data-category="${cat}"][data-game="${game}"]`
          );
          if (keypadInput) {
            keypadInput.value = isScratch ? "" : val;
            keypadInput.classList.add("locked", isScratch ? "red" : "green");
            keypadInput.disabled = true;
            if (isScratch) {
              // Optional: visually mark scratched categories
              keypadInput.closest("td")?.classList.add("scratch");
            }
            continue; // skip to next category
          }
  
          // 2️⃣ Try dropdown (old UI)
          const sel = document.querySelector(
            `select.inline[data-category="${cat}"][data-game="${game}"]`
          );
          if (sel) {
            sel.value = isScratch ? "X" : val;
            sel.classList.add("locked", isScratch ? "red" : "green");
            sel.disabled = true;
            continue;
          }
  
          // 3️⃣ Try direct score cell (for upper section)
          const td = document.querySelector(
            `.scorecell[data-category="${cat}"][data-game="${game}"]`
          );
          if (td) {
            td.textContent = val;
            td.classList.add("locked", isScratch ? "red" : "green");
            if (isScratch) td.classList.add("scratch");
          }
        }
      }
  
      // --- Update totals and highlight ---
      updateTotals();
      alert("Session loaded successfully!");
    } catch (err) {
      console.error("Load error:", err);
      alert("Error loading session");
    }
  });
  

function updateChanceWarning(game) {
  const warningId = `chance-warning-${game}`;

  // Remove existing warning first
  const existing = document.getElementById(warningId);
  if (existing) existing.remove();

  // Find the chance cell for this game
  const sel = document.querySelector(
    `select.inline[data-category="chance"][data-game="${game}"]`
  );
  if (!sel) return; // exit if not found

  // Only show warning if a value has been selected (and not scratched)
  if (sel.value !== "" && sel.value !== "X") {
    const icon = document.createElement("span");
    icon.id = warningId;
    icon.textContent = "⚠️"; // warning icon
    icon.style.marginLeft = "6px";
    icon.title = "Chance taken — stakes are higher";

    // Append to the column header for this game
    const header = document.querySelector(
      `#scorecard thead th[data-game="${game}"]`
    );
    if (header) header.appendChild(icon);
  }
}

function currentActiveGame() {
  for (const g in activeByGame) {
    if (activeByGame[g]) return g;
  }
  return null;
}

function updateGameHeaderStatus(game) {
  const allCells = document.querySelectorAll(`.scorecell[data-game="${game}"]`);
  const blankCells = Array.from(allCells).filter(
    (td) => td.textContent.trim() === ""
  );
  const header = document.querySelector(`.column-header[data-game="${game}"]`);

  if (!header) return;

  if (blankCells.length === 0) {
    header.classList.add("completed"); // dark blue when done
  } else {
    header.classList.remove("completed"); // revert to light blue if not complete
  }
}

function updateRollsLeft(game) {
  const cycleCells = Array.from(
    document.querySelectorAll(`.scorecell[data-game="${game}"]`)
  );
  const manualCells = Array.from(
    document.querySelectorAll(`select.inline[data-game="${game}"]`)
  );
  const allCells = cycleCells.concat(manualCells);

  let blanks = 0;
  allCells.forEach((cell) => {
    const val =
      cell.tagName === "SELECT" ? cell.value.trim() : cell.textContent.trim();
    if (val === "") blanks++;
  });

  const counter = document.getElementById("rolls-left");
  if (counter) {
    counter.textContent = blanks; // show remaining rolls
    counter.style.color = blanks === 1 ? "red" : ""; // red when only 1 left
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const keypad = document.getElementById("floatingKeypad");
  let currentCell = null;

  const keys = [
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    "0",
    "X",
    "←",
    "Enter",
  ];

  function buildKeypad(forChance = false) {
    keypad.innerHTML = "";
    keys.forEach((k) => {
      if (forChance && k === "X") return; // No X for chance
      const btn = document.createElement("button");
      btn.textContent = k;
      btn.style.margin = "3px";
      btn.style.padding = "10px 14px";
      btn.style.fontSize = "1em";
      btn.style.borderRadius = "4px";
      btn.style.border = "1px solid #999";
      btn.style.background = "#f9f9f9";
      btn.style.cursor = "pointer";
      btn.addEventListener("click", () => handleKeyPress(k));
      keypad.appendChild(btn);
    });
  }

  function handleKeyPress(k) {
    if (!currentCell) return;
    if (k === "Enter") {
      if (currentCell.dataset.value) {
        currentCell.textContent = currentCell.dataset.value;
        currentCell.classList.add("filled");
        if (typeof updateTotals === "function") updateTotals();
      }
      updateTotals();
      hideKeypad();
    } else if (k === "←") {
      if (currentCell.dataset.value)
        currentCell.dataset.value = currentCell.dataset.value.slice(0, -1);
    } else {
      currentCell.dataset.value = (currentCell.dataset.value || "") + k;
    }
  }

  //SHOW, HIDE KEYPAD BEGIN

  function showKeypad(cell) {
    if (cell.dataset.locked === "true") return;
    lockOtherNonBlankInColumn(cell.dataset.game, cell);

    // Remove existing keypad if any
    const oldKeypad = document.getElementById("keypad");
    if (oldKeypad) oldKeypad.remove();

    // Create keypad container
    const keypad = document.createElement("div");
    keypad.id = "keypad";
    keypad.style.position = "absolute";
    keypad.style.zIndex = 1000;
    keypad.style.background = "#fff";
    keypad.style.border = "1px solid #ccc";
    keypad.style.padding = "10px";
    keypad.style.borderRadius = "8px";
    keypad.style.boxShadow = "0 2px 8px rgba(0,0,0,0.3)";
    keypad.style.display = "grid";
    keypad.style.gridTemplateColumns = "repeat(3, 50px)";
    keypad.style.gridGap = "5px";
    keypad.style.justifyContent = "center";
    keypad.style.touchAction = "manipulation";

    // Close button (small, floating)
    const closeBtn = document.createElement("div");
    closeBtn.textContent = "X";
    closeBtn.style.position = "absolute";
    closeBtn.style.top = "17px";
    closeBtn.style.right = "16px";
    closeBtn.style.fontSize = "18px";
    closeBtn.style.fontWeight = "bold";
    closeBtn.style.color = "#888";
    closeBtn.style.cursor = "pointer";
    closeBtn.style.userSelect = "none";
    closeBtn.style.lineHeight = "1";
    ["click", "touchend"].forEach((evt) => {
      closeBtn.addEventListener(evt, () => keypad.remove());
    });
    closeBtn.addEventListener(
      "mouseenter",
      () => (closeBtn.style.color = "#000")
    );
    closeBtn.addEventListener(
      "mouseleave",
      () => (closeBtn.style.color = "#888")
    );
    keypad.appendChild(closeBtn);

    // Display for typed value (optional)
    const display = document.createElement("div");
    display.style.gridColumn = "1 / 4";
    display.style.height = "30px";
    display.style.lineHeight = "30px";
    display.style.textAlign = "center";
    display.style.marginBottom = "5px";
    display.style.fontSize = "16px";
    display.style.fontWeight = "bold";
    display.style.border = "1px solid #ccc";
    display.style.borderRadius = "4px";
    display.style.background = "#f0f0f0";
    keypad.appendChild(display);

    let inputValue = cell.dataset.value || "";
    display.textContent = inputValue;

    // Button layout
    const buttonLayout = [
      "1",
      "2",
      "3",
      "4",
      "5",
      "6",
      "7",
      "8",
      "9",
      "0",
      "X",
      "␣",
    ];

    buttonLayout.forEach((val) => {
      const btn = document.createElement("button");
      btn.textContent = val;
      btn.style.height = "40px";
      btn.style.fontSize = "16px";
      btn.style.cursor = "pointer";
      ["click", "touchend"].forEach((evt) => {
        btn.addEventListener(evt, (e) => {
          e.preventDefault();
          if (val === "␣") inputValue = "";
          else if (val === "X" && cell.dataset.category !== "chance")
            inputValue = "X";
          else if (!isNaN(val)) inputValue += val;
          display.textContent = inputValue;
        });
      });
      keypad.appendChild(btn);
    });

    // Enter button (spans 2 columns)
    const enterBtn = document.createElement("button");
    enterBtn.textContent = "Enter";
    enterBtn.style.gridColumn = "1 / 3";
    enterBtn.style.height = "40px";
    enterBtn.style.fontSize = "16px";
    enterBtn.style.background = "#4CAF50";
    enterBtn.style.color = "#fff";
    enterBtn.style.cursor = "pointer";
    enterBtn.style.borderRadius = "4px";
    ["click", "touchend"].forEach((evt) => {
      enterBtn.addEventListener(evt, (e) => {
        e.preventDefault();
        if (
          inputValue === "" ||
          inputValue === "X" ||
          (Number(inputValue) >= 5 && Number(inputValue) <= 30)
        ) {
          processManualEntry(cell, inputValue);
          lockOtherNonBlankInColumn(cell.dataset.game, cell);
          keypad.remove();
        } else {
          alert("Please enter a number between 5 and 30, X, or leave blank.");
          inputValue = "";
          display.textContent = "";
        }
      });
    });
    keypad.appendChild(enterBtn);

    // Backspace button
    const backBtn = document.createElement("button");
    backBtn.textContent = "←";
    backBtn.style.height = "40px";
    backBtn.style.fontSize = "16px";
    ["click", "touchend"].forEach((evt) => {
      backBtn.addEventListener(evt, (e) => {
        e.preventDefault();
        inputValue = inputValue.slice(0, -1);
        display.textContent = inputValue;
      });
    });
    keypad.appendChild(backBtn);

    document.body.appendChild(keypad);

    // Close keypad when clicking outside
    document.addEventListener("click", function handleOutsideClick(e) {
      if (!keypad.contains(e.target) && e.target !== cell) {
        keypad.remove();
        document.removeEventListener("click", handleOutsideClick);
      }
    });

    // Position keypad
    const rect = cell.getBoundingClientRect();
    let top = rect.bottom + window.scrollY + 5;
    let left = rect.right + window.scrollX + 5;

    if (window.innerWidth < 600) {
      // mobile: center
      keypad.style.left = "50%";
      keypad.style.top = "50%";
      keypad.style.transform = "translate(-50%, -50%)";
    } else {
      // desktop: below/right of cell
      if (left + keypad.offsetWidth > window.innerWidth - 10)
        left = window.innerWidth - keypad.offsetWidth - 10;
      if (top + keypad.offsetHeight > window.innerHeight - 10)
        top = rect.top + window.scrollY - keypad.offsetHeight - 5;
      keypad.style.top = top + "px";
      keypad.style.left = left + "px";
    }
  }

  //SHOW, HIDE KEYPAD END
  
window.showKeypad = showKeypad;

  // Click on relevant cells
  document
    .querySelectorAll(
      'td[data-category="three_kind"], td[data-category="four_kind"], td[data-category="chance"]'
    )
    .forEach((td) => {
      td.addEventListener("click", () => {
        if (td.dataset.locked === "true") return; // skip locked cells
        showKeypad(td);
      });

      td.addEventListener("click", (e) => {
        const isChance = td.dataset.category === "chance";
        showKeypad(td, isChance);
      });
    });

  // Click outside keypad hides it
  function attachKeypadListeners() {
    document.querySelectorAll("td.keypad").forEach((td) => {
      td.addEventListener("click", (e) => {
        showKeypad(e.currentTarget); // Your existing keypad function
      });
    });
  }

  function processManualEntry(td, value) {
    console.log(
      "processManualEntry called for",
      td.dataset.category,
      "value:",
      value
    );

    // Update visible and stored value
    td.textContent = value;
    td.dataset.value = value;

    // Locked state: non-blank values are locked
    td.dataset.locked = value !== "" && value !== null;
    td.classList.toggle("locked", td.dataset.locked === "true");

    const category = td.dataset.category;
    const game = td.dataset.game;

    // Update colors (still uses red/yellow/green/neutral)
    if (
      window.updateLowerCellColor &&
      typeof window.updateLowerCellColor === "function"
    ) {
      window.updateLowerCellColor(category, game, value);
    }
    if (
      window.updateUpperCellColor &&
      typeof window.updateUpperCellColor === "function"
    ) {
      window.updateUpperCellColor(category, game, value);
    }

    // Update totals
    if (window.updateTotals && typeof window.updateTotals === "function") {
      window.updateTotals();
    }
  }
});
document.addEventListener('click', (e) => {
  const cell = e.target.closest('.scorecell');
  if (!cell) return; // Not a score cell
  if (cell.dataset.locked === 'true') return; // Skip locked cells

  // Allow keypad only for manual categories
  const manualCategories = ['three_kind', 'four_kind', 'chance'];
  if (!manualCategories.includes(cell.dataset.category)) return;

  console.log("Keypad trigger:", cell.dataset.category, cell.dataset.game);
  showKeypad(cell);
});



/* =========================
      Initialization
      ========================= */
buildBody();
buildFoot();
attachHandlers();
updateTotals();
// Call on page load
populateSessions();
