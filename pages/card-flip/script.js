document.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('playing-card');
    const cardFront = document.querySelector('.card-front');
    const cardInput = document.getElementById('card-input');
    const button = document.getElementById('flip-button');
    const answerDisplay = document.getElementById('answer-display');
  
    let words = []; // array of objects {question, answer}
    let currentIndex = 0;
    let currentWord = '';
    let currentAnswer = '';
    let rotation = 180;
    let animating = false;
    let doubleFlipPhase = 0;
  
    // --- Fetch words from DB ---
    fetch('get_words.php')
      .then(res => res.json())
      .then(data => {
        console.log('Fetched words:', data);
        if (!Array.isArray(data) || data.length === 0) return;
        words = data;
  
        // pick random initial card
        currentIndex = Math.floor(Math.random() * words.length);
        currentWord = words[currentIndex].question;
        currentAnswer = words[currentIndex].answer;
  
        cardFront.textContent = currentWord;
        setFontSizeByLength(cardFront);
        card.style.transform = `rotateY(${rotation}deg)`;
        cardInput.focus();
      })
      .catch(err => console.error('Failed to fetch words:', err));
  
    // --- Font sizing by word length ---
    function setFontSizeByLength(element) {
      const len = element.textContent.length;
      let fontSize;
      if (len <= 3) fontSize = 80;
      else if (len === 4) fontSize = 60;
      else if (len === 5) fontSize = 45;
      else if (len === 6 || len === 7) fontSize = 35;
      else if (len === 8 || len === 9) fontSize = 30;
      else fontSize = 20;
      element.style.fontSize = fontSize + 'px';
    }
  
    cardInput.focus();
  
    // --- Input check against answer ---
    cardInput.addEventListener('input', () => {
      const userText = cardInput.value.trim().toUpperCase();
      const answerUpper = currentAnswer.toUpperCase();
  
      if (userText === answerUpper) {
        cardFront.style.backgroundColor = '#06800a'; // green
        cardFront.style.color = '#111';              // keep text black
      } else if (userText.length > 0) {
        cardFront.style.backgroundColor = '#f88';    // red
        cardFront.style.color = '#111';
      } else {
        cardFront.style.backgroundColor = 'white';
        cardFront.style.color = '#111';
      }
    });
  
    // --- Enter triggers flip ---
    cardInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        button.click();
      }
    });
  
    // --- Card flip logic ---
    card.addEventListener('transitionend', (e) => {
      if (e.propertyName !== 'transform') return;
  
      if (doubleFlipPhase === 1 && words.length > 0) {
        let newIndex;
        do {
          newIndex = Math.floor(Math.random() * words.length);
        } while (newIndex === currentIndex && words.length > 1);
  
        currentIndex = newIndex;
        currentWord = words[currentIndex].question;
        currentAnswer = words[currentIndex].answer;
  
        cardFront.textContent = currentWord;
        setFontSizeByLength(cardFront);
  
        // reset input and card color
        cardInput.value = '';
        cardFront.style.backgroundColor = 'white';
        cardInput.focus();
  
        // clear previous answer display
        answerDisplay.textContent = '';
        answerDisplay.style.background = '#0b6623';
  
        // continue rotation
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
      }
    });
  
    button.addEventListener('click', () => {
      if (animating || words.length === 0) return;
  
      // show the answer if user is wrong
      const userText = cardInput.value.trim().toUpperCase();
      const answerUpper = currentAnswer.toUpperCase();
      if (userText !== answerUpper) {
        answerDisplay.textContent = `Answer: ${currentAnswer}`;
        answerDisplay.classList.add('visible');
        answerDisplay.style.background = '#f88';
        answerDisplay.style.opacity = 1;
        answerDisplay.style.visibility = 'visible';
        answerDisplay.style.height = 'auto';
        answerDisplay.style.padding = '8px 12px';
        
      
      } else {
        answerDisplay.textContent = '';
        answerDisplay.classList.remove('visible');
        answerDisplay.style.opacity = 0;
        answerDisplay.style.visibility = 'hidden';
        answerDisplay.style.height = '0';
        answerDisplay.style.padding = '0';
        answerDisplay.style.background = 'none';
        
      }
  
      animating = true;
      rotation += 180;
      card.style.transform = `rotateY(${rotation}deg)`;
      doubleFlipPhase = 1;
  
      cardInput.value = '';
      cardInput.focus();
    });
  });
  