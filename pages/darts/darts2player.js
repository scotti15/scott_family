document.addEventListener("DOMContentLoaded", () => {

    let activePlayer = 1;

    function switchPlayer() {
        activePlayer = activePlayer === 1 ? 2 : 1;
    
        remainingScore = playerScores[activePlayer];
        remainingSpan.textContent = remainingScore;
    
        document.getElementById(`player${activePlayer}-remaining`).textContent =
            remainingScore;
    
        console.log("Active player:", activePlayer);
        console.log("Remaining score:", remainingScore);
    
        const table = document.getElementById("player-scoreboard");
    
        table.classList.remove("player1-active", "player2-active");
        table.classList.add(`player${activePlayer}-active`);
    }
    
    document.getElementById("btn-test-player")
        .addEventListener("click", switchPlayer);

});