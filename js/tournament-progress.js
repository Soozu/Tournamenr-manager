// Initialize progress circles
function initializeProgressCircles() {
    document.querySelectorAll('.progress-circle').forEach(circle => {
        const progress = circle.getAttribute('data-progress');
        const progressBar = circle.querySelector('.progress-bar');
        const circumference = 2 * Math.PI * 35;
        const offset = circumference - (progress / 100 * circumference);
        progressBar.style.strokeDasharray = `${circumference} ${circumference}`;
        progressBar.style.strokeDashoffset = offset;
    });
}

// Filter tournament progress by game
function filterTournamentProgress() {
    const gameId = document.getElementById('tournamentFilter').value;
    
    fetch(`api/tournament_progress.php?game_id=${gameId}`)
        .then(response => response.json())
        .then(data => {
            updateProgressCircles(data.stats);
            updateUpcomingTournaments(data.upcoming);
        })
        .catch(error => console.error('Error:', error));
}

// View tournament details
function viewTournamentDetails(tournamentId) {
    window.location.href = `tournament_details.php?id=${tournamentId}`;
}

// Update progress circles with new data
function updateProgressCircles(stats) {
    const total = stats.total_tournaments || 1;
    const ongoingPercent = (stats.ongoing / total) * 100;
    const completedPercent = (stats.completed / total) * 100;

    updateProgressCircle('ongoing', ongoingPercent, stats.ongoing);
    updateProgressCircle('completed', completedPercent, stats.completed);
}

// Update individual progress circle
function updateProgressCircle(type, percent, number) {
    const circle = document.querySelector(`.progress-circle .${type}`);
    const numberElement = circle.closest('.progress-circle').querySelector('.number');
    const circumference = 2 * Math.PI * 35;
    const offset = circumference - (percent / 100 * circumference);
    
    circle.style.strokeDashoffset = offset;
    numberElement.textContent = number;
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', initializeProgressCircles); 