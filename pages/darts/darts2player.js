document.addEventListener("DOMContentLoaded", () => {

    let activePlayer = 1;

    function switchPlayer() {
        activePlayer = activePlayer === 1 ? 2 : 1;
    
        console.log("Active player:", activePlayer);
    
        const table = document.getElementById("player-scoreboard");
    
        table.classList.remove("player1-active", "player2-active");
        table.classList.add(`player${activePlayer}-active`);
    }
    document.getElementById("btn-test-player")
        .addEventListener("click", switchPlayer);

});