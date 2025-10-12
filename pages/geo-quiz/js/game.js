document.addEventListener('DOMContentLoaded', () => {

  // DOM elements
  const guessInput = document.getElementById('guess-input');
  const suggestions = document.getElementById('suggestions');

  if(!guessInput || !suggestions) {
    console.error('Autocomplete elements not found');
    return;
  }

  // --- game state ---
  let currentRegionKey = 'usa';
  let regionData = null;
  let svgRoot = null;
  let unsolvedIds = [];
  let currentTarget = null;
  let timerInterval = null;
  let startTime = null;
  let score = 1000;
  const WRONG_PENALTY = 15;
  const SKIP_PENALTY = 30;

  // autocomplete state
  let filteredStates = [];
  let currentActiveIndex = 0;

  // --- initialization ---
  function init() {
    // populate region select
    const regionSelect = document.getElementById('region-select');
    regionSelect.innerHTML = '';
    Object.keys(REGIONS).forEach(key=>{
      const opt = document.createElement('option');
      opt.value = key; opt.textContent = REGIONS[key].displayName;
      regionSelect.appendChild(opt);
    });
    regionSelect.value = currentRegionKey;

    
    // wire controls
    regionSelect.addEventListener('change', e => loadRegion(e.target.value));
    document.getElementById('submit-btn').addEventListener('click', onSubmit);
    document.getElementById('skip-btn').addEventListener('click', onSkip);
    document.getElementById('reset-btn').addEventListener('click', resetGame);

    // autocomplete
    guessInput.addEventListener('input', onType);
    guessInput.addEventListener('keydown', onKey);
    document.addEventListener('click', e => {
      if(!e.target.closest('.autocomplete-wrapper')) hideSuggestions();
    });

    loadRegion(currentRegionKey);
  }

  // --- load region ---
  async function loadRegion(key){
    currentRegionKey = key;
    regionData = REGIONS[key];
    svgRoot = await UI.loadSVG(regionData.mapFile);
    // assume svgRoot is your loaded SVG element
    svgPanZoom(svgRoot, {
      zoomEnabled: true,
      controlIconsEnabled: true, // shows zoom buttons
      minZoom: 0.5,
      maxZoom: 3,
      fit: true,
      center: true
    });

    if(!svgRoot) return;

    // build list of state ids
    unsolvedIds = Object.keys(regionData.names).slice();

    // hover for names
    Object.keys(regionData.names).forEach(id=>{
      const p = svgRoot.getElementById(id);
      if(p){
        p.addEventListener('mouseenter', ()=>showTempLabel(regionData.names[id]));
        p.addEventListener('mouseleave', hideTempLabel);
      }
    });

    resetGame();
  }

  // --- game logic ---
  function resetGame(){
    $all('path').forEach(p=>p.classList.remove('highlight','correct','wrong'));
    unsolvedIds = Object.keys(regionData.names).slice();
    score = 1000;
    startTime = Date.now();
    updateStats();
    pickNext();
    if(timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(updateStats,1000);
  }

  function pickNext(){
    if(unsolvedIds.length===0){ endGame(); return; }
    if(currentTarget){
      const prev = svgRoot.getElementById(currentTarget);
      if(prev) prev.classList.remove('highlight');
    }
    const idx = Math.floor(Math.random()*unsolvedIds.length);
    currentTarget = unsolvedIds[idx];
    const el = svgRoot.getElementById(currentTarget);
    if(el) el.classList.add('highlight');
    guessInput.value='';
    guessInput.focus();
  }

  function onSubmit(){ checkAnswer(guessInput.value.trim()); }

  function onSkip(){
  if(!currentTarget) return;
  score -= SKIP_PENALTY;
  const answer = quizByCapital
    ? regionData.capitals[currentTarget]
    : regionData.names[currentTarget];
  message(`Skipped. The answer was ${answer} (−${SKIP_PENALTY})`, 'muted');
  markCorrect(currentTarget);
  unsolvedIds.splice(unsolvedIds.indexOf(currentTarget),1);
  pickNext();
}


function checkAnswer(guess) {
  if (!guess || !currentTarget) return;

  const correctAnswer = quizByCapital
    ? regionData.capitals[currentTarget]
    : regionData.names[currentTarget];

  if (guess.toLowerCase() === correctAnswer.toLowerCase()) {
    markCorrect(currentTarget);
    message(`Correct — ${correctAnswer}`, 'success');
    unsolvedIds.splice(unsolvedIds.indexOf(currentTarget), 1);
    pickNext();
  } else {
    score -= WRONG_PENALTY;
    const el = svgRoot.getElementById(currentTarget);
    if (el) {
      el.classList.add('wrong');
      setTimeout(() => el.classList.remove('wrong'), 400);
    }
    message(`Incorrect — try again (−${WRONG_PENALTY})`, 'error');
    updateStats();
  }
}


  function markCorrect(id){
    const el = svgRoot.getElementById(id);
    if(el){
      el.classList.remove('highlight');
      el.classList.add('correct');
      const title = document.createElementNS('http://www.w3.org/2000/svg','title');
      title.textContent = regionData.names[id];
      el.appendChild(title);
    }
  }

  // --- stats ---
  function updateStats(){
    const elapsed = Math.floor((Date.now()-startTime)/1000);
    document.getElementById('time').textContent = elapsed;
    document.getElementById('score').textContent = score;
    document.getElementById('remaining').textContent = unsolvedIds.length;
  }

  function endGame(){
    if(timerInterval) clearInterval(timerInterval);
    const elapsed = Math.floor((Date.now()-startTime)/1000);
    const finalScore = Math.max(0, score - elapsed);
    message(`Finished! Time: ${elapsed}s — Final score: ${finalScore}`,'success');
    document.getElementById('score').textContent = finalScore;
  }

  function message(txt,type){
    const el = document.getElementById('message');
    el.textContent = txt;
    if(type==='success') el.style.color='var(--success)';
    else if(type==='error') el.style.color='var(--danger)';
    else el.style.color='var(--muted)';
  }

  function showTempLabel(text){ message(text,'muted'); }
  function hideTempLabel(){ message('',''); }

  // --- autocomplete functions ---
  // --- autocomplete state ---
// Called whenever user types
function onType() {
  const val = guessInput.value.trim();
  if (!val) { filteredStates = []; currentActiveIndex = 0; renderSuggestions(); return; }

  const items = quizByCapital
    ? Object.values(regionData.capitals)
    : Object.values(regionData.names);

  filteredStates = items
    .filter(name => name.toLowerCase().startsWith(val.toLowerCase()))
    .sort();

  currentActiveIndex = 0;
  renderSuggestions();
}


// Render the dropdown suggestions
function renderSuggestions() {
  suggestions.innerHTML = '';
  
  if (filteredStates.length === 0) {
    hideSuggestions();
    return;
  }

  filteredStates.forEach((state, index) => {
    const typed = guessInput.value;
    const regex = new RegExp(`^(${typed})`, 'i');
    const html = state.replace(regex, `<strong>$1</strong>`);

    const div = document.createElement('div');
    div.innerHTML = html;
    div.classList.toggle('active', index === currentActiveIndex);

    div.addEventListener('click', () => selectState(index));
    suggestions.appendChild(div);
  });

  suggestions.style.display = 'block';
}

// Called when user selects a suggestion
function selectState(index) {
  const selected = filteredStates[index];
  guessInput.value = selected;   // Only change input on selection
  hideSuggestions();
  checkAnswer(selected);
}

// Hide dropdown
function hideSuggestions() {
  suggestions.innerHTML = '';
  suggestions.style.display = 'none';
}

// Handle keyboard events
function onKey(e) {
  if (filteredStates.length === 0) return;

  if (e.key === 'Enter') {
    e.preventDefault();
    selectState(currentActiveIndex);
  } else if (e.key === 'ArrowDown') {
    e.preventDefault();
    currentActiveIndex = (currentActiveIndex + 1) % filteredStates.length;
    renderSuggestions();
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    currentActiveIndex = (currentActiveIndex - 1 + filteredStates.length) % filteredStates.length;
    renderSuggestions();
  }
}
let quizByCapital = false;

// Listen for radio button changes
document.querySelectorAll('input[name="mode"]').forEach(radio => {
  radio.addEventListener('change', (e) => {
    quizByCapital = e.target.value === 'capital';
    resetGame(); // optional: restart the quiz in the new mode
  });
});


  init(); // start the game
});
