function filterTournaments(gameId) {
    // Show loading state
    document.querySelector('.tournament-stats-container').style.opacity = '0.5';
    document.querySelector('.tournament-list').style.opacity = '0.5';

    // Fetch filtered data
    fetch(`api/get_tournament_stats.php?game_id=${gameId}`)
        .then(response => response.json())
        .then(data => {
            // Update statistics
            updateStatistics(data.stats);
            // Update upcoming tournaments
            updateUpcomingTournaments(data.upcoming);
            
            // Remove loading state
            document.querySelector('.tournament-stats-container').style.opacity = '1';
            document.querySelector('.tournament-list').style.opacity = '1';
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            showErrorMessage('Failed to load tournament data');
        });
}

function updateStatistics(stats) {
    document.querySelector('.stat-circle:nth-child(1) .circle-value').textContent = stats.active;
    document.querySelector('.stat-circle:nth-child(2) .circle-value').textContent = stats.completed;
}

function updateUpcomingTournaments(tournaments) {
    const tournamentList = document.querySelector('.tournament-list');
    
    if (tournaments.length === 0) {
        tournamentList.innerHTML = `
            <div class="no-tournaments">
                <p>No upcoming tournaments scheduled</p>
            </div>
        `;
        return;
    }

    tournamentList.innerHTML = tournaments.map(tournament => `
        <div class="tournament-item">
            <div class="game-icon">
                <img src="../images/games/${tournament.game_icon}" 
                     alt="${tournament.game_name}">
            </div>
            <div class="tournament-info">
                <h6>${tournament.name}</h6>
                <div class="tournament-meta">
                    <span class="date">
                        <i class="bi bi-calendar3"></i>
                        ${tournament.start_date}
                    </span>
                    <span class="teams">
                        <i class="bi bi-people-fill"></i>
                        ${tournament.team_count} teams registered
                    </span>
                </div>
            </div>
            <a href="tournament_details.php?id=${tournament.id}" 
               class="view-details">View Details</a>
        </div>
    `).join('');
}

function showErrorMessage(message) {
    const errorAlert = document.createElement('div');
    errorAlert.className = 'alert alert-danger alert-dismissible fade show';
    errorAlert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.tournament-progress-card').prepend(errorAlert);
} 