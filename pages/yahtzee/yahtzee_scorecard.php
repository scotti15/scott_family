<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Yahtzee 6-game Scorebook (Final Functional Prototype)</title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; padding: 12px; }
  table { border-collapse: collapse; width: 100%; max-width: 1200px; }
  th, td { border: 1px solid #bbb; padding: 6px; text-align: center; vertical-align: middle; }
  th { background: #f3f3f3; cursor: pointer; user-select:none; }
  .category-col { text-align: left; padding-left: 8px; background: #fafafa; width: 220px; }
  .scorecell { cursor: pointer; min-width: 58px; height: 32px; }
  .active { outline: 2px solid #f2c94c; }
  .locked { background: #eee; pointer-events: none; opacity: 0.85; }
  .scratch { color: #c0392b; font-weight: bold; }
  select.inline { width: 68px; padding: 3px; }
  tfoot td { font-weight: bold; background: #fafafa; }
  .note { font-size: 0.9em; color: #333; margin-bottom: 8px; }
</style>
</head>
<body>
<h2>Yahtzee Scorebook — 6 Games (Final Functional Prototype)</h2>

<p class="note">
Click to cycle (left-click up, right-click down). For dropdown cells click the cell to open choices. Right-click toggles <strong>X</strong> for cells that allow scratch. Clicking a column header unlocks that column for editing.
</p>

<table id="scorecard" aria-label="Yahtzee scorebook">
  <thead>
    <tr>
      <th class="category-col">Category</th>
      <th class="column-header" data-game="1">Game 1</th>
      <th class="column-header" data-game="2">Game 2</th>
      <th class="column-header" data-game="3">Game 3</th>
      <th class="column-header" data-game="4">Game 4</th>
      <th class="column-header" data-game="5">Game 5</th>
      <th class="column-header" data-game="6">Game 6</th>
    </tr>
  </thead>
  <tbody id="body-rows"></tbody>
  <tfoot id="totals-rows"></tfoot>
</table>

<script>
/* ============================
   Configuration / Values
   ============================ */
const upperCats = ['ones','twos','threes','fours','fives','sixes'];
const lowerCats = ['three_kind','four_kind','full_house','small_straight','large_straight','yahtzee','chance'];

const cycleValues = {
  ones:    [1,2,3,4,5],
  twos:    [2,4,6,8,10],
  threes:  [3,6,9,12,15],
  fours:   [4,8,12,16,20],
  fives:   [5,10,15,20,25],
  sixes:   [6,12,18,24,30],
  full_house: [25],
  small_straight: [30],
  large_straight: [40],
  // Yahtzee cycles: first 50, then +100 for each extra (we don't include blank or X here)
  yahtzee: [50,150,250,350,450,550,650,750,850,950,1050,1150]
};

// manual dropdown categories:
//  - three_kind, four_kind: blank, X, 5..30
//  - chance: blank, 5..30 (no X)
const manualCategories = ['three_kind','four_kind','chance'];

// state: which element is active per game column (to manage locking).
const activeByGame = {1:null,2:null,3:null,4:null,5:null,6:null};

/* ============================
   Build Rows: upper, bonus, lower
   ============================ */
function buildBody() {
  const tbody = document.getElementById('body-rows');
  tbody.innerHTML = '';

  // Upper rows
  for (const cat of upperCats) {
    const tr = document.createElement('tr');
    const label = document.createElement('td');
    label.className = 'category-col';
    label.textContent = cat.replace('_',' ').toUpperCase();
    tr.appendChild(label);

    for (let g=1; g<=6; g++) {
      const td = document.createElement('td');
      td.className = 'scorecell';
      td.dataset.category = cat;
      td.dataset.game = String(g);
      td.tabIndex = 0;
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  }

  // Upper Total row (directly below Sixes)
  const trUpperTotal = document.createElement('tr');
  const labelUT = document.createElement('td');
  labelUT.className = 'category-col';
  labelUT.textContent = 'UPPER TOTAL';
  trUpperTotal.appendChild(labelUT);
  for (let g=1; g<=6; g++) {
    const td = document.createElement('td');
    td.className = 'upper-total';
    td.dataset.game = String(g);
    td.textContent = '0';
    trUpperTotal.appendChild(td);
  }
  tbody.appendChild(trUpperTotal);

  // Bonus row
  const trBonus = document.createElement('tr');
  const labelB = document.createElement('td');
  labelB.className = 'category-col';
  labelB.textContent = 'BONUS (≥63 → 35)';
  trBonus.appendChild(labelB);
  for (let g=1; g<=6; g++) {
    const td = document.createElement('td');
    td.className = 'upper-bonus';
    td.dataset.game = String(g);
    td.textContent = '0';
    trBonus.appendChild(td);
  }
  tbody.appendChild(trBonus);

  // Lower rows
  for (const cat of lowerCats) {
    const tr = document.createElement('tr');
    const label = document.createElement('td');
    label.className = 'category-col';
    label.textContent = cat.replace('_',' ').toUpperCase();
    tr.appendChild(label);

    for (let g=1; g<=6; g++) {
      const td = document.createElement('td');
      td.dataset.category = cat;
      td.dataset.game = String(g);

      if (manualCategories.includes(cat)) {
        // create inline select (always present) — blank option included
        const sel = document.createElement('select');
        sel.className = 'inline';
        sel.dataset.category = cat;
        sel.dataset.game = String(g);

        // add blank option
        const optBlank = document.createElement('option');
        optBlank.value = '';
        optBlank.textContent = '';
        sel.appendChild(optBlank);

        // If category allows scratch X (3-kind,4-kind)
        if (cat !== 'chance') {
          const optX = document.createElement('option');
          optX.value = 'X';
          optX.textContent = 'X';
          sel.appendChild(optX);
        }

        // add numeric options 5..30
        for (let n=5; n<=30; n++) {
          const opt = document.createElement('option');
          opt.value = String(n);
          opt.textContent = String(n);
          sel.appendChild(opt);
        }

        td.appendChild(sel);
      } else {
        td.className = td.className ? td.className + ' scorecell' : 'scorecell';
        td.tabIndex = 0;
      }
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  }
}

/* ============================
   Build totals foot (lower total, grand, cumulative)
   ============================ */
function buildFoot() {
  const tfoot = document.getElementById('totals-rows');
  tfoot.innerHTML = '';

  // Lower total
  const trLower = document.createElement('tr');
  const lblLower = document.createElement('td');
  lblLower.className = 'category-col';
  lblLower.textContent = 'LOWER TOTAL';
  trLower.appendChild(lblLower);
  for (let g=1; g<=6; g++) {
    const td = document.createElement('td');
    td.className = 'lower-total';
    td.dataset.game = String(g);
    td.textContent = '0';
    trLower.appendChild(td);
  }
  tfoot.appendChild(trLower);

  // Grand total
  const trGrand = document.createElement('tr');
  const lblGrand = document.createElement('td');
  lblGrand.className = 'category-col';
  lblGrand.textContent = 'GRAND TOTAL';
  trGrand.appendChild(lblGrand);
  for (let g=1; g<=6; g++) {
    const td = document.createElement('td');
    td.className = 'grand-total';
    td.dataset.game = String(g);
    td.textContent = '0';
    trGrand.appendChild(td);
  }
  tfoot.appendChild(trGrand);

  // Cumulative total
  const trCum = document.createElement('tr');
  const lblCum = document.createElement('td');
  lblCum.className = 'category-col';
  lblCum.textContent = 'CUMULATIVE TOTAL';
  trCum.appendChild(lblCum);
  for (let g=1; g<=6; g++) {
    const td = document.createElement('td');
    td.className = 'cumulative-total';
    td.dataset.game = String(g);
    td.textContent = '0';
    trCum.appendChild(td);
  }
  tfoot.appendChild(trCum);
}

/* ============================
   Utilities: cycle next/prev; get cell value
   ============================ */
function cycleNext(cat, currentText) {
  // returns next string value ('' for blank, 'X', or numeric string)
  const vals = cycleValues[cat];
  if (!vals) return '';
  currentText = String(currentText || '').trim();
  // blank -> first value
  if (currentText === '') return String(vals[0]);
  if (currentText === 'X') return '';
  // find numeric index
  const idx = vals.indexOf(Number(currentText));
  if (idx === -1) return String(vals[0]);
  return (idx === vals.length - 1) ? 'X' : String(vals[idx + 1]);
}

function cyclePrev(cat, currentText) {
  const vals = cycleValues[cat];
  if (!vals) return '';
  currentText = String(currentText || '').trim();
  if (currentText === '') return 'X';
  if (currentText === 'X') return String(vals[vals.length - 1]);
  const idx = vals.indexOf(Number(currentText));
  if (idx <= 0) return '';
  return String(vals[idx - 1]);
}

function getCellValue(category, game) {
  // returns numeric value (0 if blank or X, or number), plus raw display string
  if (manualCategories.includes(category)) {
    const sel = document.querySelector(`select.inline[data-category="${category}"][data-game="${game}"]`);
    if (!sel) return { num:0, raw:'' };
    const raw = sel.value;
    if (!raw || raw === '') return { num:0, raw:'' };
    if (raw === 'X') return { num:0, raw:'X' };
    return { num: Number(raw), raw: raw };
  } else {
    const td = document.querySelector(`.scorecell[data-category="${category}"][data-game="${game}"]`);
    if (!td) return { num:0, raw:'' };
    const raw = td.textContent.trim();
    if (!raw) return { num:0, raw:'' };
    if (raw === 'X') return { num:0, raw:'X' };
    return { num: Number(raw), raw: raw };
  }
}

/* ============================
   Totals calculation & bonus logic
   ============================ */
function updateTotals() {
  let cumulative = 0;
  for (let g=1; g<=6; g++) {
    // upper
    let upperSum = 0;
    let upperFilledCount = 0;
    for (const uc of upperCats) {
      const v = getCellValue(uc, g);
      if (v.raw === '') { /* not filled */ }
      else { upperFilledCount++; upperSum += v.num; }
    }

    // bonus logic
    let bonusDisplay = '0';
    let bonusNumeric = 0;
    if (upperSum >= 63) { bonusDisplay = '35'; bonusNumeric = 35; }
    else if (upperFilledCount === 6) { bonusDisplay = 'X'; bonusNumeric = 0; }
    else { bonusDisplay = '0'; bonusNumeric = 0; }

    // lower: fixed cycle cells
    let lowerSum = 0;
    for (const lc of ['full_house','small_straight','large_straight','yahtzee']) {
      const v = getCellValue(lc, g);
      lowerSum += v.num;
    }
    // manual dropdown cells
    for (const mc of ['three_kind','four_kind','chance']) {
      const v = getCellValue(mc, g);
      lowerSum += v.num;
    }

    const grand = upperSum + bonusNumeric + lowerSum;

    // update DOM
    const utTd = document.querySelector(`.upper-total[data-game="${g}"]`);
    if (utTd) utTd.textContent = String(upperSum);
    const ubTd = document.querySelector(`.upper-bonus[data-game="${g}"]`);
    if (ubTd) ubTd.textContent = bonusDisplay;
    const ltTd = document.querySelector(`.lower-total[data-game="${g}"]`);
    if (ltTd) ltTd.textContent = String(lowerSum);
    const gtTd = document.querySelector(`.grand-total[data-game="${g}"]`);
    if (gtTd) gtTd.textContent = String(grand);

    cumulative += grand;
    const ctTd = document.querySelector(`.cumulative-total[data-game="${g}"]`);
    if (ctTd) ctTd.textContent = String(cumulative);
  }
}

/* ============================
   Activation & locking logic
   - Left-click on cycle cell or focus/click on select activates that cell
   - Activation locks all other non-blank cells in same column except the activated one
   - Right-click scratch toggles X but DOES NOT lock or activate
   ============================ */
function lockOtherNonBlankInColumn(game, exceptEl=null) {
  // For the column, lock any non-blank elements except exceptEl
  // Cycle scorecells:
  document.querySelectorAll(`.scorecell[data-game="${game}"]`).forEach(td => {
    if (td === exceptEl) return;
    const cat = td.dataset.category;
    const raw = td.textContent.trim();
    if (raw !== '') {
      td.classList.add('locked');
    } else {
      td.classList.remove('locked');
    }
  });
  // Manual selects:
  document.querySelectorAll(`select.inline[data-game="${game}"]`).forEach(sel => {
    if (sel === exceptEl) return;
    const raw = sel.value;
    if (raw !== '') {
      sel.disabled = true;
      sel.classList.add('locked');
    } else {
      sel.disabled = false;
      sel.classList.remove('locked');
    }
  });
}

function unlockColumn(game) {
  document.querySelectorAll(`[data-game="${game}"]`).forEach(el => {
    if (el.classList) el.classList.remove('locked');
    if (el.tagName === 'SELECT') {
      el.disabled = false;
      el.classList.remove('locked');
    } else {
      // ensure pointer events available
      // locked class controls pointer-events in CSS
    }
  });
  activeByGame[game] = null;
}

/* ============================
   Attach handlers
   ============================ */
function attachHandlers() {
  // Cycle scorecells (left click cycles up and activates; right-click cycles down but does not activate)
  document.querySelectorAll('.scorecell').forEach(td => {
    const cat = td.dataset.category;
    const game = td.dataset.game;

    // Left click => activate and cycle up
    td.addEventListener('click', (e) => {
      // If this element or column is locked and this td is locked -> do nothing
      if (td.classList.contains('locked')) {
        // But allow clicking active? locked should prevent edits
        return;
      }
      // Activate this cell: set as active and lock other non-blank in column (except this)
      // Remove active class from previous active element in this column
      const prevActive = activeByGame[game];
      if (prevActive && prevActive !== td) {
        // prevActive remains visible but we will lock others (including prevActive if non-blank)
        // remove active styling on prevActive
        if (prevActive.classList) prevActive.classList.remove('active');
      }
      // set new active
      activeByGame[game] = td;
      td.classList.add('active');
      // lock other non-blank cells in this column (except td)
      lockOtherNonBlankInColumn(game, td);

      // cycle up
      const cur = td.textContent.trim();
      const next = cycleNext(cat, cur);
      td.textContent = next;
      td.classList.toggle('scratch', next === 'X');

      // update totals immediately
      updateTotals();
    });

    // Right-click => cycle down WITHOUT activating or locking
    td.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      // If locked, right-click should still allow toggling scratch? Spec: right-click does not lock, but if control is locked, maybe don't allow edit.
      // We'll allow right-click scratch even if locked? Spec earlier: right-click does not lock; but locking means prevent accidental edits — better to prevent right-click if locked.
      if (td.classList.contains('locked')) return;

      const cur = td.textContent.trim();
      const prev = cyclePrev(cat, cur);
      td.textContent = prev;
      td.classList.toggle('scratch', prev === 'X');
      updateTotals();
    });
  });

  // Manual dropdown select handlers
  document.querySelectorAll('select.inline').forEach(sel => {
    const game = sel.dataset.game;
    const cat = sel.dataset.category;

    // Clicking/focusing select will activate the column cell (and lock other non-blank cells except this select)
    sel.addEventListener('focus', (e) => {
      // If select is disabled (locked), do nothing on focus
      if (sel.disabled) return;
      const prevActive = activeByGame[game];
      if (prevActive && prevActive !== sel && prevActive.classList) prevActive.classList.remove('active');
      activeByGame[game] = sel;
      // lock other non-blank cells except this select
      lockOtherNonBlankInColumn(game, sel);
      // we don't add .active class to select — visual only for td elements
    });

    // Change selection (this does not lock anything)
    sel.addEventListener('change', (e) => {
      // Ensure clamp for numeric values: 5..30
      const v = sel.value;
      if (v === '') { /* blank */ }
      else if (v === 'X') { /* scratch */ }
      else {
        let n = Number(v);
        if (isNaN(n)) { sel.value = ''; }
        else {
          if (n < 5) n = 5;
          if (n > 30) n = 30;
          sel.value = String(n);
        }
      }
      updateTotals();
    });

    // Right-click on select toggles scratch if allowed (and does NOT lock or activate)
    sel.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      // If select is disabled, ignore right-click
      if (sel.disabled) return;
      if (cat === 'chance') {
        // chance does not allow scratch -> ignore
        return;
      }
      if (sel.value === 'X') sel.value = '';
      else sel.value = 'X';
      updateTotals();
    });
  });

  // Column header click unlocks column (temporary)
  document.querySelectorAll('.column-header').forEach(h => {
    h.addEventListener('click', (e) => {
      const g = h.dataset.game;
      unlockColumn(g);
      updateTotals();
    });
  });
}

/* ============================
   Init
   ============================ */
buildBody();
buildFoot();
attachHandlers();
updateTotals();

</script>
</body>
</html>
