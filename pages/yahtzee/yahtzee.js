/* yahtzee.js
   Final functional logic for the Yahtzee 6-game scorebook.
*/

/* =========================
   Configuration / Values
   ========================= */
   const upperCats = ['ones','twos','threes','fours','fives','sixes'];
   const lowerCats = ['three_kind','four_kind','full_house','small_straight','large_straight','yahtzee','chance'];

   // per-game flag: when true, yahtzee cycles use bonus values (100,200,...) instead of normal (50,150,...)
    const yahtzeeBonusModeByGame = { 1: false, 2: false, 3: false, 4: false, 5: false, 6: false };

    const yahtzeeBonusMode = {}; // keyed by game number

    // helper to read/write cleanly
    function isYahtzeeBonusMode(game) {
      return !!yahtzeeBonusModeByGame[Number(game)];
    }
    function setYahtzeeBonusMode(game, on) {
      yahtzeeBonusModeByGame[Number(game)] = !!on;
    }

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
      yahtzee: [50,150,250,350,450,550,650,750,850,950,1050,1150],
      bonus_yahtzee: [100,200,300,400,500,600,700,800,900,1000]
    };
    
   const manualCategories = ['three_kind','four_kind','chance'];
   
   // track active element per game column
   const activeByGame = {1:null,2:null,3:null,4:null,5:null,6:null};

//Track the active cell
   let activeCell = null;

   /* =========================
      Build table
      ========================= */
      function buildBody() {
  const tbody = document.getElementById('body-rows');
  tbody.innerHTML = ''; // clear previous rows

  // Upper categories with icons
  const upperCats = [
    { key: 'ones', label: '🎲 Ones' },
    { key: 'twos', label: '🎲 Twos' },
    { key: 'threes', label: '🎲 Threes' },
    { key: 'fours', label: '🎲 Fours' },
    { key: 'fives', label: '🎲 Fives' },
    { key: 'sixes', label: '🎲 Sixes' }
  ];

  // Lower categories with icons
  const lowerCats = [
    { key: 'three_kind', label: '🎲 Three of a Kind' },
    { key: 'four_kind', label: '🎲 Four of a Kind' },
    { key: 'full_house', label: '🎲 Full House' },
    { key: 'small_straight', label: '🎲 Small Straight' },
    { key: 'large_straight', label: '🎲 Large Straight' },
    { key: 'yahtzee', label: '⭐ Yahtzee' },
    { key: 'chance', label: '🎲 Chance' }
  ];

  // Build upper rows
  upperCats.forEach(cat => {
    const tr = document.createElement('tr');
    const tdLabel = document.createElement('td');
    tdLabel.className = 'category-col';
    tdLabel.textContent = cat.label;
    tr.appendChild(tdLabel);

    for (let g = 1; g <= 6; g++) {
      const td = document.createElement('td');
      td.className = 'scorecell';
      td.dataset.category = cat.key;
      td.dataset.game = g;
      td.tabIndex = 0;
      tr.appendChild(td);
    }

    tbody.appendChild(tr);
  });

  // Upper Total row
  const trUT = document.createElement('tr');
  const tdUTlabel = document.createElement('td');
  tdUTlabel.className = 'category-col';
  tdUTlabel.textContent = 'UPPER TOTAL';
  trUT.appendChild(tdUTlabel);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement('td');
    td.className = 'upper-total';
    td.dataset.game = g;
    td.textContent = '0';
    trUT.appendChild(td);
  }
  tbody.appendChild(trUT);

  // Bonus row
  const trB = document.createElement('tr');
  const tdBlabel = document.createElement('td');
  tdBlabel.className = 'category-col';
  tdBlabel.textContent = 'BONUS (≥63 → 35)';
  trB.appendChild(tdBlabel);
  for (let g = 1; g <= 6; g++) {
    const td = document.createElement('td');
    td.className = 'upper-bonus';
    td.dataset.game = g;
    td.textContent = '0';
    trB.appendChild(td);
  }
  tbody.appendChild(trB);

  // Build lower rows
  lowerCats.forEach(cat => {
    const tr = document.createElement('tr');
    const tdLabel = document.createElement('td');
    tdLabel.className = 'category-col';
    tdLabel.textContent = cat.label;
    tdLabel.dataset.category = cat.key;
    tr.appendChild(tdLabel);

    for (let g = 1; g <= 6; g++) {
      const td = document.createElement('td');

      // Manual-entry categories with dropdown
      const manualCats = ['three_kind', 'four_kind', 'chance'];
      if (manualCats.includes(cat.key)) {
        const sel = document.createElement('select');
        sel.className = 'inline';
        sel.dataset.category = cat.key;
        sel.dataset.game = g;

        // Blank
        const optBlank = document.createElement('option');
        optBlank.value = '';
        optBlank.textContent = '';
        sel.appendChild(optBlank);

        // Scratch (except for Chance)
        if (cat.key !== 'chance') {
          const optX = document.createElement('option');
          optX.value = 'X';
          optX.textContent = 'X';
          sel.appendChild(optX);
        }

        // Numbers 5-30
        for (let n = 5; n <= 30; n++) {
          const opt = document.createElement('option');
          opt.value = n;
          opt.textContent = n;
          sel.appendChild(opt);
        }

        td.appendChild(sel);
      } else {
        td.className = 'scorecell';
        td.tabIndex = 0;
        td.dataset.category = cat.key;
        td.dataset.game = g;
      }

      tr.appendChild(td);
    }

    tbody.appendChild(tr);
  });
}

      
   function buildFoot() {
     const tfoot = document.getElementById('totals-rows');
     tfoot.innerHTML = '';
   
     // Lower total
     const trLower = document.createElement('tr');
     const tdLabelLower = document.createElement('td');
     tdLabelLower.className = 'category-col';
     tdLabelLower.textContent = 'LOWER TOTAL';
     trLower.appendChild(tdLabelLower);
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
     const tdLabelGrand = document.createElement('td');
     tdLabelGrand.className = 'category-col';
     tdLabelGrand.textContent = 'GRAND TOTAL';
     trGrand.appendChild(tdLabelGrand);
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
     const tdLabelCum = document.createElement('td');
     tdLabelCum.className = 'category-col';
     tdLabelCum.textContent = 'CUMULATIVE TOTAL';
     trCum.appendChild(tdLabelCum);
     for (let g=1; g<=6; g++) {
       const td = document.createElement('td');
       td.className = 'cumulative-total';
       td.dataset.game = String(g);
       td.textContent = '0';
       trCum.appendChild(td);
     }
     tfoot.appendChild(trCum);
   }
   
   /* =========================
      Utilities
      ========================= */
      function cycleNext(cat, cur, customVals) {
        const vals = customVals || cycleValues[cat];
        if (!vals) return '';
        cur = String(cur || '').trim();
      
        if (cur === '') return String(vals[0]);
        if (cur === 'X') return '';
        
        const idx = vals.indexOf(Number(cur));
        if (idx === -1) return String(vals[0]);
        
        return (idx === vals.length - 1) ? 'X' : String(vals[idx + 1]);
      }
      
   
   function cyclePrev(cat, cur) {
     const vals = cycleValues[cat];
     if (!vals) return '';
     cur = String(cur || '').trim();
     if (cur === '') return 'X';
     if (cur === 'X') return String(vals[vals.length - 1]);
     const idx = vals.indexOf(Number(cur));
     if (idx <= 0) return '';
     return String(vals[idx - 1]);
   }
   
   function getCellValue(cat, game) {
     if (manualCategories.includes(cat)) {
       const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${game}"]`);
       if (!sel) return { num:0, raw:'' };
       const raw = sel.value;
       if (!raw || raw === '') return { num:0, raw:'' };
       if (raw === 'X') return { num:0, raw:'X' };
       return { num: Number(raw), raw };
     } else {
       const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${game}"]`);
       if (!td) return { num:0, raw:'' };
       const raw = td.textContent.trim();
       if (!raw) return { num:0, raw:'' };
       if (raw === 'X') return { num:0, raw:'X' };
       return { num: Number(raw), raw };
     }
   }
   
   /* =========================
      Totals and Bonus
      ========================= */
      function updateTotals() {
        let cumulative = 0;
      
        for (let g = 1; g <= 6; g++) {
          // Upper
          let upperSum = 0;
          let upperFilled = 0;
          upperCats.forEach((cat, idx) => {
            const v = getCellValue(cat, g);
            if (v.raw !== '') { upperFilled++; upperSum += v.num; }
            updateUpperCellColor(cat, g, v.raw);
          });
      
          // === Pace calculation ===
          let pace = 0;
          if (upperFilled < 6 && upperSum < 63) {  // only show pace if upper not full and below 63
            let expectedSum = 0;
            upperCats.forEach((cat, idx) => {
              const v = getCellValue(cat, g);
              if (v.raw !== '') {
                expectedSum += (3 * (idx + 1)); // 3×face value
              }
            });
            pace = upperSum - expectedSum;
          }
      
          // Bonus
          let bonusDisplay = '0';
          let bonusNumeric = 0;
          if (upperSum >= 63) { bonusDisplay = '35'; bonusNumeric = 35; }
          else if (upperFilled === 6) { bonusDisplay = 'X'; bonusNumeric = 0; }
      
          // Lower
          let lowerSum = 0;
          ['full_house','small_straight','large_straight','yahtzee'].forEach(cat => {
            const v = getCellValue(cat, g); lowerSum += v.num;
            updateLowerCellColor(cat, g, v.raw);
          });
          ['three_kind','four_kind','chance'].forEach(cat => {
            const v = getCellValue(cat, g); lowerSum += v.num;
            updateLowerCellColor(cat, g, v.raw);
          });
      
          const grand = upperSum + bonusNumeric + lowerSum;
          cumulative += grand;
      
          // Write totals
          const ut = document.querySelector(`.upper-total[data-game="${g}"]`);
          if (ut) {
            ut.innerHTML = String(upperSum);
            if (pace !== 0) {
              const span = document.createElement('span');
              span.classList.add('pace');
              span.textContent = ` (${Math.abs(pace)})`;
              span.style.color = pace > 0 ? '#06800a' : 'red';
              ut.appendChild(span);
            }
          }
      
          const ub = document.querySelector(`.upper-bonus[data-game="${g}"]`);
          if (ub) ub.textContent = bonusDisplay;
      
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
   function lockOtherNonBlankInColumn(game, exceptEl=null) {
     // cycle cells
     document.querySelectorAll(`.scorecell[data-game="${game}"], select.inline[data-game="${game}"]`)
     .forEach(el => {
         if (el === exceptEl) return;
         const value = el.value ?? el.textContent.trim();
         if (value !== '') el.classList.add('locked');
         else el.classList.remove('locked');
     });
   
     // manual selects
     document.querySelectorAll(`select.inline[data-game="${game}"]`).forEach(sel => {
       if (sel === exceptEl) return;
       if (sel.value !== '') { sel.disabled = true; sel.classList.add('locked'); }
       else { sel.disabled = false; sel.classList.remove('locked'); }
     });
   }

   function lockOtherNonBlank(exceptEl = null) {
    document.querySelectorAll('.scorecell').forEach(td => {
      if (td === exceptEl) return; // skip current active
      const raw = td.textContent.trim();
      if (raw !== '') td.classList.add('locked');
      else td.classList.remove('locked');
    });
  }
  
   
   function unlockColumn(game) {
    const header = document.querySelector(`.column-header[data-game="${game}"]`);
    if (header) header.classList.add('inverted');

     document.querySelectorAll(`[data-game="${game}"]`).forEach(el => {
       if (el.classList) el.classList.remove('locked');
       if (el.tagName === 'SELECT') {
         el.disabled = false;
         el.classList.remove('locked');
       }
     });
     activeByGame[game] = null;
   }
   
   function lockFullColumn(game) {
     // lock all non-blank cells; if full, lock everything regardless
     document.querySelectorAll(`.scorecell[data-game="${game}"]`).forEach(td => {
       const raw = td.textContent.trim();
       if (raw !== '') td.classList.add('locked');
     });
     document.querySelectorAll(`select.inline[data-game="${game}"]`).forEach(sel => {
       if (sel.value !== '') { sel.disabled = true; sel.classList.add('locked'); }
       else { sel.disabled = false; sel.classList.remove('locked'); }
     });
   }
   
   /* Check whether column is fully filled (13 scoring cells) and lock if so */
   function checkAndLockCompletedColumns() {
     for (let g=1; g<=6; g++) {
       let filledCount = 0;
       // count score cells and manual selects in that column (13 total)
       document.querySelectorAll(`.scorecell[data-game="${g}"], select.inline[data-game="${g}"]`).forEach(el => {
         const val = (el.tagName === 'SELECT') ? el.value : el.textContent.trim();
         if (val !== '') filledCount++;
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
      document.querySelectorAll('.scorecell[data-category="yahtzee"]').forEach(td => {
        td.addEventListener('dblclick', (e) => {
          // Only allow toggle if this cell is scratched
          if (td.textContent.trim() !== 'X') return;

          // Toggle bonus mode
          if (!td.dataset.bonusMode || td.dataset.bonusMode === 'off') {
            td.dataset.bonusMode = 'on';
            cycleValues.yahtzee = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];
          } else {
            td.dataset.bonusMode = 'off';
            cycleValues.yahtzee = [50, 150, 250, 350, 450, 550, 650, 750, 850, 950, 1050, 1150];
          }

          // Reset current cell to first value in new array
          td.textContent = String(cycleValues.yahtzee[0]);
          td.classList.remove('scratch');

          updateTotals();

        });
      });

     // cycle cells
     
     const yahtzeeHeader = document.querySelector('.category-col[data-category="yahtzee"]');
     if (yahtzeeHeader) {
         yahtzeeHeader.addEventListener('dblclick', (e) => {
             const game = currentActiveGame(); // however you determine the active game column
             if (!game) return;
     
             const td = document.querySelector(`.scorecell[data-category="yahtzee"][data-game="${game}"]`);
             if (!td) return;
     
             if (td.textContent.trim() !== 'X') return; // only toggle if scratched
     
             // toggle bonus mode
             if (!td.dataset.bonusMode || td.dataset.bonusMode === 'off') {
                 td.dataset.bonusMode = 'on';
                 cycleValues.yahtzee = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];
             } else {
                 td.dataset.bonusMode = 'off';
                 cycleValues.yahtzee = [50, 150, 250, 350, 450, 550, 650, 750, 850, 950, 1050, 1150];
             }
     
             td.textContent = String(cycleValues.yahtzee[0]);
             td.classList.remove('scratch');
     
             updateTotals();
             updateRollsLeft(game);


         });
     }
     


     document.querySelectorAll('.scorecell').forEach(td => {
       const cat = td.dataset.category;
       const game = td.dataset.game;
   
       td.addEventListener('click', (e) => {
        
        // remove inversion from the header once a cell is clicked
        const header = document.querySelector(`.column-header[data-game="${td.dataset.game}"]`);
        if (header) header.classList.remove('inverted');
        
         // ignore if locked
         if (td.classList.contains('locked')) return;
   
         // deactivate previous active in this column
         const prev = activeByGame[game];
         if (prev && prev !== td) {
           if (prev.classList) prev.classList.remove('active');
         }
   
         // set this as active and lock other non-blank in column except this
         activeByGame[game] = td;
         td.classList.add('active');
         lockOtherNonBlank(td);
   
        // cycle up — support Yahtzee bonus mode
        let values = cycleValues[cat];

        // If this is a Yahtzee cell, check for bonus mode
        if (cat === 'yahtzee' && isYahtzeeBonusMode(game)) {
          values = cycleValues['bonus_yahtzee'];
        }

        const next = cycleNext(cat, td.textContent.trim(), values);
        td.textContent = next;

         td.classList.toggle('scratch', next === 'X');
         updateTotals();
         updateRollsLeft(game);
         updateGameHeaderStatus(game);

   
         // After change, check if column completed
         checkAndLockCompletedColumns();
       });
   
       td.addEventListener('contextmenu', (e) => {
         e.preventDefault();
         if (td.classList.contains('locked')) return;
         // cycle down
         const prevVal = cyclePrev(cat, td.textContent.trim());
         td.textContent = prevVal;
         td.classList.toggle('scratch', prevVal === 'X');
         updateTotals();
         updateRollsLeft(game);
         updateGameHeaderStatus(game);

         // No locking triggered by right-click
         checkAndLockCompletedColumns();
       });
     });
   
     // selects (manual dropdowns)
     document.querySelectorAll('select.inline').forEach(sel => {
       const cat = sel.dataset.category;
       const game = sel.dataset.game;
   
       sel.addEventListener('focus', (e) => {
         if (sel.disabled) return;
         // deactivate previous active in this column
         const prev = activeByGame[game];
         if (prev && prev !== sel && prev.classList) prev.classList.remove('active');
         // set this as active
         activeByGame[game] = sel;
         // lock other non-blank in column except sel
         lockOtherNonBlank(td);

       });
   
       sel.addEventListener('change', (e) => {
         // clamp numeric values if entered (shouldn't be necessary for option selects)
         const v = sel.value;
         if (v === '') { /* blank */ }
         else if (v === 'X') { /* scratch */ }
         else {
           let n = Number(v);
           if (isNaN(n)) sel.value = '';
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

       sel.addEventListener('change', () => {
        updateTotals();
        updateGameHeaderStatus(game);
        checkAndLockCompletedColumns();
    
        if (cat.key === 'chance') {
            updateChanceWarning(g);
        }
    });
    
   
       sel.addEventListener('contextmenu', (e) => {
         e.preventDefault();
         if (sel.disabled) return;
         if (cat === 'chance') return; // chance disallows scratch
         sel.value = (sel.value === 'X') ? '' : 'X';
         if (cat.key === 'chance') {
           updateChanceWarning(g);
         }
         updateTotals();
         // Right-click scratch does not lock
         checkAndLockCompletedColumns();
       });
     });
   
     // column header unlock
     document.querySelectorAll('.column-header').forEach(h => {
       h.addEventListener('click', (e) => {
         const g = h.dataset.game;
         unlockColumn(g);
         updateTotals();

       });
     });
   }
   

   function updateUpperCellColor(cat, game, value) {
    // find the TD for this upper category
    const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${game}"]`);
    if (!td) return;
  
    // clear any previous color classes (keep names consistent with your CSS)
    td.classList.remove('red', 'yellow', 'green', 'scratch');
  
    // blank or null -> no color
    if (value === '' || value === null) return;
  
    // scratch -> red
    if (value === 'X') {
      td.classList.add('red');
      td.classList.add('scratch'); // optional: if you want a specific scratch style
      return;
    }
  
    // numeric value -> color by multiples of face value
    const num = Number(value);
    if (isNaN(num)) return;
  
    const base = {
      ones: 1, twos: 2, threes: 3, fours: 4, fives: 5, sixes: 6
    }[cat];
  
    if (!base) return;
  
    if (num >= 4 * base) {
      td.classList.add('green');
    } else if (num === 3 * base) {
      td.classList.add('yellow');
    } else if (num === 1 * base || num === 2 * base) {
      td.classList.add('red');
    } else {
      // fallback: if it's some other valid number, treat >3*base as green, else red
      td.classList.add(num > 3 * base ? 'green' : 'red');
    }
  }
  
  
  function updateLowerCellColor(cat, game, value) {
    let td;
    if (manualCategories.includes(cat)) {
      td = document.querySelector(`select.inline[data-category="${cat}"][data-game="${game}"]`);
      if (!td) return;
    } else {
      td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${game}"]`);
      if (!td) return;
    }
  
    // Remove previous color classes
    td.classList.remove('red', 'green');
  
    if (value === '' || value === null) return; // blank: no color
    if (value === 'X') td.classList.add('red');
    else td.classList.add('green');
  }

  function saveGame() {
    const scores = {};
    for (let g=1; g<=6; g++) {
      scores[g] = {};
      // Upper categories
      upperCats.forEach(cat => {
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        scores[g][cat] = td ? td.textContent.trim() : '';
      });
      // Lower categories
      lowerCats.forEach(cat => {
        const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${g}"]`);
        const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
        if (sel) scores[g][cat] = sel.value;
        else if (td) scores[g][cat] = td.textContent.trim();
      });
    }
  
    fetch('save_yahtzee.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ scores })
    }).then(res => res.json())
      .then(data => { if(data.status === 'ok') alert('Game saved!'); })
      .catch(err => console.error(err));
  }

  function loadGame() {
    fetch('load_yahtzee.php')
      .then(res => res.json())
      .then(data => {
        if (!data.scores) return;
        for (let g=1; g<=6; g++) {
          upperCats.forEach(cat => {
            const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
            if(td) td.textContent = data.scores[g]?.[cat] || '';
          });
          lowerCats.forEach(cat => {
            const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${g}"]`);
            const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
            if(sel) sel.value = data.scores[g]?.[cat] || '';
            else if(td) td.textContent = data.scores[g]?.[cat] || '';
          });
        }
        updateTotals();

      });
  }
  
  document.getElementById('save-btn').addEventListener('click', async () => {
    try {
        // Optionally, prompt for session or use the latest/current
        // const sessionId = prompt("Enter session ID (or leave blank for new session):");

        const scores = {};

        for (let g = 1; g <= 6; g++) {
            scores[g] = {};

            // Upper categories
            upperCats.forEach(cat => {
                const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
                if (td) {
                    const val = td.textContent.trim();
                    scores[g][cat] = val || '';
                }
            });

            // Lower categories
            lowerCats.forEach(cat => {
                if (manualCategories.includes(cat)) {
                    const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${g}"]`);
                    if (sel) scores[g][cat] = sel.value || '';
                } else {
                    const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
                    if (td) scores[g][cat] = td.textContent.trim() || '';
                }
            });
        }

        const response = await fetch('/yahtzee/save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({scores})
        });

        const result = await response.json();
        if (result.status === 'ok') alert('Game saved successfully!');
        else alert('Error saving game: ' + (result.error || 'unknown'));
    } catch (err) {
        console.error(err);
        alert('Error saving game');
    }
});
async function populateSessions() {
    const select = document.getElementById('load-session');
    try {
        const res = await fetch('/yahtzee/list_sessions.php');
        const data = await res.json();
        data.sessions.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = `Session ${s.id} — ${s.created_at}`;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("Failed to load sessions:", err);
    }
}

document.getElementById('load-session').addEventListener('change', async (e) => {
    const sessionId = e.target.value;
    if (!sessionId) return;

    try {
        const res = await fetch(`/yahtzee/load_session.php?session_id=${sessionId}`);
        const data = await res.json();
        if (!data.scores) return;

        // Clear existing scorecard
        buildBody();
        buildFoot();

        // Populate scores
        for (let g = 1; g <= 6; g++) {
            const gameScores = data.scores[g] || {};
            Object.keys(gameScores).forEach(cat => {
                const val = gameScores[cat];
                if (manualCategories.includes(cat)) {
                    const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${g}"]`);
                    if (sel) sel.value = val || '';
                } else {
                    const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${g}"]`);
                    if (td) td.textContent = val || '';
                }
            });
        }

        updateTotals();
        attachHandlers(); // re-attach click listeners

    } catch (err) {
        console.error("Failed to load session:", err);
    }
});



document.getElementById('load-btn').addEventListener('click', async () => {
    try {
        // Optionally, you could prompt user for a session id
        // const sessionId = prompt("Enter session ID (or leave blank for latest):");
        const response = await fetch('/yahtzee/load.php'); // adjust path
        if (!response.ok) throw new Error("Failed to load session");

        const data = await response.json();
        if (!data.scores) return;

        // Clear all cells first
        document.querySelectorAll('.scorecell').forEach(td => {
            td.textContent = '';
            td.classList.remove('locked', 'active', 'scratch', 'red', 'green');
        });
        document.querySelectorAll('select.inline').forEach(sel => {
            sel.value = '';
            sel.disabled = false;
            sel.classList.remove('locked');
        });

        // Populate scores
        for (const [gameStr, categories] of Object.entries(data.scores)) {
            const game = Number(gameStr);
            for (const [cat, val] of Object.entries(categories)) {
                if (manualCategories.includes(cat)) {
                    const sel = document.querySelector(`select.inline[data-category="${cat}"][data-game="${game}"]`);
                    if (sel) {
                        sel.value = val;
                        if (val === 'X') sel.classList.add('red', 'locked');
                        else sel.classList.add('green', 'locked');
                        sel.disabled = true;
                    }
                } else {
                    const td = document.querySelector(`.scorecell[data-category="${cat}"][data-game="${game}"]`);
                    if (td) {
                        td.textContent = val;
                        if (val === 'X') td.classList.add('red', 'locked', 'scratch');
                        else td.classList.add('green', 'locked');
                    }
                }
            }
            // Lock entire column if all 13 cells filled
          //  lockFullColumn(game);
        }

        // Recalculate totals and colors
        updateTotals();


        alert('Session loaded successfully!');
    } catch (err) {
        console.error(err);
        alert('Error loading session');
    }
});

function updateChanceWarning(game) {
  const warningId = `chance-warning-${game}`;

  // Remove existing warning first
  const existing = document.getElementById(warningId);
  if (existing) existing.remove();

  // Find the chance cell for this game
  const sel = document.querySelector(`select.inline[data-category="chance"][data-game="${game}"]`);
  if (!sel) return;  // exit if not found

  // Only show warning if a value has been selected (and not scratched)
  if (sel.value !== '' && sel.value !== 'X') {
      const icon = document.createElement('span');
      icon.id = warningId;
      icon.textContent = '⚠️';            // warning icon
      icon.style.marginLeft = '6px';
      icon.title = 'Chance taken — stakes are higher';

      // Append to the column header for this game
      const header = document.querySelector(`#scorecard thead th[data-game="${game}"]`);
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
  const blankCells = Array.from(allCells).filter(td => td.textContent.trim() === '');
  const header = document.querySelector(`.column-header[data-game="${game}"]`);

  if (!header) return;

  if (blankCells.length === 0) {
      header.classList.add('completed');   // dark blue when done
  } else {
      header.classList.remove('completed'); // revert to light blue if not complete
  }
}


function updateRollsLeft(game) {
  const cycleCells = Array.from(document.querySelectorAll(`.scorecell[data-game="${game}"]`));
  const manualCells = Array.from(document.querySelectorAll(`select.inline[data-game="${game}"]`));
  const allCells = cycleCells.concat(manualCells);

  let blanks = 0;
  allCells.forEach(cell => {
      const val = cell.tagName === 'SELECT' ? cell.value.trim() : cell.textContent.trim();
      if (val === '') blanks++;
  });

  const counter = document.getElementById('rolls-left');
  if (counter) {
      counter.textContent = blanks;              // show remaining rolls
      counter.style.color = (blanks === 1) ? 'red' : ''; // red when only 1 left
  }
}



   /* =========================
      Initialization
      ========================= */
   buildBody();
   buildFoot();
   attachHandlers();
   updateTotals();
   // Call on page load
   populateSessions();
   
   