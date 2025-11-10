document.addEventListener('DOMContentLoaded', () => {
  const card = document.getElementById('playing-card');
  const cardFront = document.querySelector('.card-front');
  const cardInput = document.getElementById('card-input');
  const button = document.getElementById('flip-button');
  const answerDisplay = document.getElementById('answer-display');
  const cycleScoreEl = document.getElementById('cycle-score');
  const overallScoreEl = document.getElementById('overall-score');

  // ---------------- State ----------------
  let allWords = [];
  let currentCycleWords = [];
  let currentWordObj = null;
  let animating = false;
  let rotation = 0;
  let doubleFlipPhase = 0;
  let nextBatchIndex = 0;
  let passQueue = [];

  // ensure card starts showing front
  card.style.transform = 'rotateY(0deg)';

  // -------------- Load words --------------
  fetch('get_words.php')
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        console.error('No data returned');
        return;
      }

      // each word has a value and flags
      allWords = data.map(item => ({
        question: item.question || item,
        answer: item.answer || item,
        value: 3,
        inCycle: false,
        mastered: false
      }));

      startNewCycle(5);
      prepareNextPass();
      showNextWord();
    })
    .catch(err => console.error('Failed to fetch words:', err));

  // -------------- Utility functions --------------
  function updateScoreDisplays() {
    const cycleSum = allWords
      .filter(w => w.inCycle && w.value > 0)
      .reduce((s, w) => s + w.value, 0);
    const overallSum = allWords.reduce((s, w) => s + w.value, 0);
    cycleScoreEl.textContent = `Cycle: ${cycleSum}`;
    overallScoreEl.textContent = `Overall: ${overallSum}`;
  }

  function setFontSizeByLength(element) {
    const len = (element.textContent || '').length;
    let fontSize;
    if (len <= 3) fontSize = 80;
    else if (len === 4) fontSize = 60;
    else if (len === 5) fontSize = 45;
    else if (len <= 7) fontSize = 35;
    else if (len <= 9) fontSize = 30;
    else fontSize = 20;
    element.style.fontSize = fontSize + 'px';
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
    // mark previous cycle words mastered if value == 0
    currentCycleWords.forEach(w => {
      if (w.value <= 0) {
        w.value = 1; // mastered words come back with 1
        w.mastered = true;
      }
      w.inCycle = false;
    });

    // gather mastered words (value > 0)
    const carryOver = allWords.filter(w => w.mastered && w.value > 0);
    // select new words from pool not yet inCycle or mastered
    const remainingPool = allWords.filter(w => !w.inCycle && !w.mastered && w.value === 3);
    const newBatch = remainingPool.slice(0, batchSize);

    newBatch.forEach(w => (w.inCycle = true));
    carryOver.forEach(w => (w.inCycle = true));

    currentCycleWords = [...carryOver, ...newBatch];
    nextBatchIndex += newBatch.length;

    updateScoreDisplays();
  }

  function prepareNextPass() {
    // get words still active (value > 0)
    const active = currentCycleWords.filter(w => w.value > 0);
    if (active.length === 0) {
      // cycle complete, start a new one
      startNewCycle(5);
      return prepareNextPass();
    }

    // sort by descending value, shuffle equal-value groups
    const grouped = {};
    active.forEach(w => {
      if (!grouped[w.value]) grouped[w.value] = [];
      grouped[w.value].push(w);
    });

    let sortedValues = Object.keys(grouped)
      .map(Number)
      .sort((a, b) => b - a);

    passQueue = [];
    sortedValues.forEach(val => {
      passQueue = passQueue.concat(shuffleArray(grouped[val]));
    });
  }

  function showNextWord() {
    if (passQueue.length === 0) {
      prepareNextPass();
    }
    currentWordObj = passQueue.shift();
    displayWord(currentWordObj.question);
  }

  function displayWord(text) {
    cardFront.textContent = text;
    setFontSizeByLength(cardFront);
    cardFront.style.backgroundColor = 'white';
    cardFront.style.color = '#111';
    cardInput.value = '';
    cardInput.focus();
  }

  // -------------- Input handling --------------
  cardInput.addEventListener('input', () => {
    if (!currentWordObj) return;
    const val = cardInput.value.trim().toUpperCase();
    const correct = (currentWordObj.answer || '').toUpperCase();

    if (!val) {
      cardFront.style.backgroundColor = 'white';
      return;
    }
    if (val === correct) {
      cardFront.style.backgroundColor = '#0a0';
      answerDisplay.textContent = '';
    } else {
      cardFront.style.backgroundColor = '#f88';
    }
  });

  function applyScoring() {
    if (!currentWordObj) return;
    const val = cardInput.value.trim().toUpperCase();
    const correct = (currentWordObj.answer || '').toUpperCase();

    if (val === correct) {
      currentWordObj.value = Math.max(0, currentWordObj.value - 1);
      answerDisplay.textContent = '';
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

  card.addEventListener('transitionend', e => {
    if (e.propertyName !== 'transform') return;

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
      answerDisplay.textContent = '';
      cardInput.value = '';
      cardInput.focus();
    }
  });

  // -------------- Controls --------------
  button.addEventListener('click', () => {
    if (animating) return;
    applyScoring();
    startFlip();
  });

  cardInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (animating) return;
      applyScoring();
      startFlip();
    }
  });
});
