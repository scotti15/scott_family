document.addEventListener('DOMContentLoaded', () => {
    let currentIndex = 0;
    let correctCount = 0;
    let total = allFlashcards.length;
    const batchSize = 5;

/* ===== state ===== */
let sessionFlashcards = [];
let currentBatch = [];
let roundQueue = [];
  
    const flashcardEl = document.getElementById('flashcard');
    const userAnswerEl = document.getElementById('user-answer');
    const submitBtn = document.getElementById('submit-answer');
    const resetBtn = document.getElementById('reset-btn');
    const correctAnswerEl = document.getElementById('correct-answer');
    const progressEl = document.getElementById('progress');
    const progressDiv = document.getElementById('progress');
  
      
    function showCard(index) {
        if (index < allFlashcards.length) {
          const question = allFlashcards[index].question;
          const front = flashcardEl.querySelector('.front');
          front.textContent = question;
          userAnswerEl.value = '';
          correctAnswerEl.style.display = 'none';
          flashcardEl.style.backgroundColor = '#fdfd96'; // yellowish neutral
          progressEl.textContent = `Card ${index + 1} of ${total}`;
          userAnswerEl.focus();
      
          // ✅ Debug + resize
          console.log("Displaying question:", question);
          setFontSizeByLength(question, flashcardEl);
         } else {
          flashcardEl.querySelector('.front').textContent = "✅ You've finished all cards!";
          progressEl.textContent = `Score: ${correctCount}/${total}`;
          userAnswerEl.style.display = 'none';
          submitBtn.style.display = 'none';
          correctAnswerEl.style.display = 'none';
        }
      }
      
      function checkAnswer() {
        const userAnswer = userAnswerEl.value.trim().toLowerCase();
        const correctAnswer = allFlashcards[currentIndex].answer.trim().toLowerCase();
        const front = flashcardEl.querySelector('.front');
    
        if (userAnswer === correctAnswer) {
            // ✅ Correct answer
            flashcardEl.style.backgroundColor = '#a8e6a1'; // green
            currentIndex++;
            userAnswerEl.value = '';
            userAnswerEl.focus();
            setTimeout(() => showCard(currentIndex), 800);
        } else {
            // ❌ Incorrect answer
            flashcardEl.style.backgroundColor = '#f8a1a1'; // red
    
            const question = allFlashcards[currentIndex].question;
            front.innerHTML = `
                <div>${question}</div>
                <div style="margin-top:10px; font-size:1.5rem; color:#222;">
                    ✅ ${allFlashcards[currentIndex].answer}
                </div>
            `;
    
            currentIndex++;
            userAnswerEl.value = '';
            userAnswerEl.focus();
            setTimeout(() => showCard(currentIndex), 3000); // longer so user can see correct answer
        }
    }
    
  
    function resetDeck() {
      currentIndex = 0;
      correctCount = 0;
      shuffle(allFlashcards);
      userAnswerEl.style.display = 'inline';
      submitBtn.style.display = 'inline';
      showCard(currentIndex);
    }
  
    function shuffle(array) {
      for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
      }
    }
  
    submitBtn.addEventListener('click', checkAnswer);
    userAnswerEl.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') checkAnswer();
    });
    resetBtn.addEventListener('click', resetDeck);
  
    showCard(currentIndex);
  });

  
//   function resizeFlashcardText() {
//     const flashcard = document.getElementById('flashcard');
//     const front = flashcard.querySelector('.front');
//     if (!front) return;

//     const minFontSize = 12; // minimum px
//     const maxFontSize = parseFloat(getComputedStyle(front).fontSize); // clamp() value

//     let fontSize = maxFontSize;
//     front.style.fontSize = fontSize + 'px';

//     // Shrink until both width and height fit inside card
//     while ((front.scrollWidth > flashcard.clientWidth - 10 || front.scrollHeight > flashcard.clientHeight - 10)
//            && fontSize > minFontSize) {
//         fontSize -= 0.5;
//         front.style.fontSize = fontSize + 'px';
//     }
// }

// function displayCurrentQuestion() {
//     console.log("Displaying question:", q.question);

//     const q = currentBatch[currentIndex];
//     if (!q) return;

//     const frontEl = document.querySelector('#flashcard .front');
//     frontEl.textContent = 'Q: ' + q.question;

//     flashcardEl.style.backgroundColor = '#fdfd96';
//     flashcardEl.style.borderColor = '#333';
//     correctAnswerDiv.style.display = 'none';

//     setFontSizeByLength(q.question); // set size based on letters
// }


// function fitTextToCard() {
//     const flashcard = document.getElementById('flashcard');
//     const front = flashcard.querySelector('.front');
//     if (!front) return;

//     const cardWidth = flashcard.clientWidth - 20;
//     const cardHeight = flashcard.clientHeight - 20;

//     const text = front.textContent;

//     // Create a temporary hidden div for measurement
//     const temp = document.createElement('div');
//     temp.style.position = 'absolute';
//     temp.style.visibility = 'hidden';
//     temp.style.whiteSpace = 'pre-wrap';
//     temp.style.wordWrap = 'break-word';
//     temp.style.width = cardWidth + 'px';
//     temp.style.fontFamily = getComputedStyle(front).fontFamily;
//     temp.style.fontWeight = getComputedStyle(front).fontWeight;
//     temp.textContent = text;
//     document.body.appendChild(temp);

//     let fontSize = 200; // start big
//     temp.style.fontSize = fontSize + 'px';

//     while ((temp.scrollWidth > cardWidth || temp.scrollHeight > cardHeight) && fontSize > 8) {
//         fontSize -= 1;
//         temp.style.fontSize = fontSize + 'px';
//     }

//     front.style.fontSize = fontSize + 'px';
//     document.body.removeChild(temp);
// }


function setFontSizeByLength(text, cardEl) {
    console.log("Question text:", text);
    let size;
  
    if (text.length <= 7) size = '4rem';
    else if (text.length <= 14) size = '3rem';
    else if (text.length <= 21) size = '2.5rem';
    else if (text.length <= 30) size = '2rem';
    else size = '1.5rem';
  
    cardEl.style.fontSize = size;
    console.log(`Question: "${text}", length: ${text.length}, font size set to: ${size}`);
  }
  
  let batchSize = 5; // or whatever batch size you use
let currentBatch = []; // array of current batch cards with counters
let sessionFlashcards = []; // all cards with counters

// Grab the fill elements
const batchFill = document.getElementById('batch-fill');
const overallFill = document.getElementById('overall-fill');

function initProgressBars() {
    // Set initial counters for sessionFlashcards
    sessionFlashcards = allFlashcards.map(f => ({ ...f, counter: batchSize }));

    loadNextBatch();
    updateProgressBars();
}

function loadNextBatch() {
    currentBatch = sessionFlashcards.filter(c => c.counter > 0).slice(0, batchSize);
}

  /* counters & progress */
function getTotals() {
    const batchRemaining = currentBatch.reduce((s,q)=>s + (q.counter>0? q.counter:0),0);
    const totalRemaining = sessionFlashcards.reduce((s,q)=>s + (q.counter>0? q.counter:0),0);
    return { batchRemaining, totalRemaining };
}

function updateCounters() {
    const currentQ = currentBatch[currentIndex] || { counter: 0 };
    const currentCounter = currentQ.counter;
    progressDiv.textContent = `Current Question Counter: ${currentCounter}`;
    updateProgressBars();
}

/* visual progress bars */
function updateProgressBars() {
    const { batchRemaining, totalRemaining } = getTotals();

    // Maxes are counts * batchSize (because counters are up to batchSize)
    const batchMax = currentBatch.length * batchSize;
    const totalMax = sessionFlashcards.length * batchSize;

    const batchPercent = (batchRemaining / batchMax) * 100;
    const totalPercent = (totalRemaining / totalMax) * 100;

    batchFill.style.height = batchPercent + '%';
    totalFill.style.height = totalPercent + '%';

    batchFill.style.backgroundColor = getProgressColor(batchPercent);
    totalFill.style.backgroundColor = getProgressColor(totalPercent);

    batchValue.textContent = batchRemaining;
    overallValue.textContent = totalRemaining;

    batchPercentLabel.textContent = Math.round(100 - batchPercent) + '%';
    overallPercentLabel.textContent = Math.round(100 - totalPercent) + '%';

    // Celebrate if everything done
    if (totalRemaining === 0) {
        celebrate();
    }
}

/* color helper: red -> yellow -> green */
function getProgressColor(percent) {
    // percent = remaining% (100 full, 0 empty)
    if (percent > 50) {
        // 50..100 -> red -> yellow
        const ratio = (percent - 50) / 50; // 0..1
        const r = 255;
        const g = Math.round(255 * (1 - ratio));
        return `rgb(${r},${g},0)`;
    } else {
        // 0..50 -> yellow -> green
        const ratio = percent / 50; // 0..1
        const r = Math.round(255 * ratio);
        const g = 255;
        return `rgb(${r},${g},0)`;
    }
}
