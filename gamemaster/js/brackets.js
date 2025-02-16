document.addEventListener('DOMContentLoaded', function() {
    const tournamentSelect = document.getElementById('tournamentSelect');
    const generateBtn = document.getElementById('generateBrackets');
    const viewBtn = document.getElementById('viewBrackets');
    const clearBtn = document.getElementById('clearBrackets');

    if (tournamentSelect) {
        tournamentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const tournamentId = this.value;
            const status = selectedOption.dataset.status;
            const bracketsGenerated = selectedOption.dataset.brackets === '1';
            const teamCount = parseInt(selectedOption.dataset.teams) || 0;

            // Update button states
            generateBtn.disabled = true;
            viewBtn.disabled = true;
            clearBtn.disabled = true;

            if (tournamentId) {
                // Enable generate button only for active tournaments with enough teams
                if (status === 'active' && teamCount >= 4) {
                    generateBtn.disabled = false;
                }

                // Enable view button for ongoing tournaments with generated brackets
                if (status === 'ongoing' && bracketsGenerated) {
                    viewBtn.disabled = false;
                    // Load brackets immediately
                    loadBrackets(tournamentId);
                }

                // Enable clear button only for ongoing tournaments with generated brackets
                if (status === 'ongoing' && bracketsGenerated) {
                    clearBtn.disabled = false;
                }
            }
        });
    }

    // Generate brackets button handler
    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const tournamentId = tournamentSelect.value;
            if (tournamentId && !this.disabled) {
                if (confirm('Are you sure you want to generate brackets? This will start the tournament.')) {
                    generateBrackets(tournamentId);
                }
            }
        });
    }
});

function generateBrackets(tournamentId) {
    // Show loading state
    document.querySelector('.rounds-wrapper').innerHTML = `
        <div class="loading-brackets">
            <i class="bi bi-arrow-clockwise"></i>
            <p>Generating brackets...</p>
        </div>
    `;

    fetch('ajax/generate_brackets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `tournament_id=${tournamentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Load brackets immediately after generation
            loadBrackets(tournamentId);
            
            // Update button states
            document.getElementById('generateBrackets').disabled = true;
            document.getElementById('viewBrackets').disabled = false;
            document.getElementById('clearBrackets').disabled = false;
            
            // Show success message
            alert('Brackets generated successfully!');
        } else {
            throw new Error(data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.querySelector('.rounds-wrapper').innerHTML = `
            <div class="no-brackets error">
                <i class="bi bi-exclamation-triangle"></i>
                <h3>Error Generating Brackets</h3>
                <p>${error.message || 'An error occurred while generating brackets. Please try again.'}</p>
            </div>
        `;
    });
}

function loadBrackets(tournamentId) {
    if (!tournamentId) return;

    // Show loading state
    const bracketsContainer = document.querySelector('.rounds-wrapper');
    if (bracketsContainer) {
        bracketsContainer.innerHTML = `
            <div class="loading-brackets">
                <i class="bi bi-arrow-clockwise"></i>
                <p>Loading brackets...</p>
            </div>
        `;
    }

    fetch(`ajax/get_brackets.php?tournament_id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.brackets && data.brackets.length > 0) {
                renderBrackets(data.brackets);
            } else {
                document.querySelector('.rounds-wrapper').innerHTML = `
                    <div class="no-brackets">
                        <i class="bi bi-diagram-3"></i>
                        <h3>No Brackets Available</h3>
                        <p>${data.message || 'Brackets have not been generated yet.'}</p>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('.rounds-wrapper').innerHTML = `
                <div class="no-brackets error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h3>Error Loading Brackets</h3>
                    <p>There was a problem loading the brackets. Please try again.</p>
                </div>`;
        });
}

function renderBrackets(brackets) {
    const roundsWrapper = document.querySelector('.rounds-wrapper');
    roundsWrapper.innerHTML = '';
    
    // Group matches by round
    const rounds = {};
    brackets.forEach(match => {
        if (!rounds[match.round_number]) {
            rounds[match.round_number] = [];
        }
        rounds[match.round_number].push(match);
    });

    // Sort rounds and matches
    Object.keys(rounds).sort((a, b) => a - b).forEach(roundNum => {
        rounds[roundNum].sort((a, b) => a.match_order - b.match_order);
        const roundColumn = createRoundColumn(roundNum, rounds[roundNum]);
        roundsWrapper.appendChild(roundColumn);
    });
}

function createRoundColumn(roundNum, matches) {
    const roundDiv = document.createElement('div');
    roundDiv.className = 'round';
    
    let roundTitle = 'Round ' + roundNum;
    if (matches.length === 2) roundTitle = 'Semi Finals';
    if (matches.length === 1) roundTitle = 'Finals';
    if (matches.length === 4) roundTitle = 'Quarter Finals';
    
    roundDiv.innerHTML = `<h3 class="round-header">${roundTitle}</h3>`;

    const matchesDiv = document.createElement('div');
    matchesDiv.className = 'matches';

    matches.forEach(match => {
        const matchCard = createMatchCard(match);
        matchesDiv.appendChild(matchCard);
    });

    roundDiv.appendChild(matchesDiv);
    return roundDiv;
}

function createMatchCard(match) {
    const matchDiv = document.createElement('div');
    matchDiv.className = `match-card ${match.status}`;
    matchDiv.setAttribute('data-match-id', match.id);

    const team1Logo = match.team1_logo ? 
        `<img src="../images/team-logos/${match.team1_logo}" alt="${match.team1_name}" class="team-logo">` : 
        `<img src="../images/team-logos/default-team-logo.png" alt="Default" class="team-logo">`;
    
    const team2Logo = match.team2_logo ? 
        `<img src="../images/team-logos/${match.team2_logo}" alt="${match.team2_name}" class="team-logo">` : 
        `<img src="../images/team-logos/default-team-logo.png" alt="Default" class="team-logo">`;

    matchDiv.innerHTML = `
        <div class="match-teams">
            <div class="team team1 ${match.winner_id == match.team1_id ? 'winner' : ''}">
                ${team1Logo}
                <span class="team-name">${match.team1_name || 'TBD'}</span>
                <span class="team-score">${match.team1_score || '0'}</span>
            </div>
            <div class="vs">VS</div>
            <div class="team team2 ${match.winner_id == match.team2_id ? 'winner' : ''}">
                ${team2Logo}
                <span class="team-name">${match.team2_name || 'TBD'}</span>
                <span class="team-score">${match.team2_score || '0'}</span>
            </div>
        </div>
        <div class="match-info">
            <span class="match-status ${match.status}">${match.status}</span>
            ${match.status !== 'completed' ? `
                <button class="btn-update" onclick="openUpdateMatch(${match.id}, '${match.team1_name}', '${match.team2_name}')">
                    <i class="bi bi-pencil"></i> Update
                </button>
            ` : ''}
        </div>
    `;

    return matchDiv;
}

function clearBrackets(tournamentId) {
    if (!confirm('Are you sure you want to clear all brackets? This will reset the tournament to active status.')) {
        return;
    }

    // Show loading state
    document.querySelector('.rounds-wrapper').innerHTML = `
        <div class="loading-brackets">
            <i class="bi bi-arrow-clockwise"></i>
            <p>Clearing brackets...</p>
        </div>
    `;

    fetch('ajax/clear_brackets.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `tournament_id=${tournamentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear the brackets display
            document.querySelector('.rounds-wrapper').innerHTML = `
                <div class="no-brackets">
                    <i class="bi bi-diagram-3"></i>
                    <h3>Brackets Cleared</h3>
                    <p>The tournament brackets have been cleared and reset to active status.</p>
                </div>
            `;
            
            // Update button states
            document.getElementById('generateBrackets').disabled = false;
            document.getElementById('viewBrackets').disabled = true;
            document.getElementById('clearBrackets').disabled = true;

            // Update tournament select option
            const option = document.querySelector(`#tournamentSelect option[value="${tournamentId}"]`);
            if (option) {
                option.dataset.status = 'active';
                option.dataset.brackets = '0';
            }

            // Show success message
            alert('Brackets have been cleared successfully.');
            
            // Reload page to update tournament status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to clear brackets');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.querySelector('.rounds-wrapper').innerHTML = `
            <div class="no-brackets error">
                <i class="bi bi-exclamation-triangle"></i>
                <h3>Error Clearing Brackets</h3>
                <p>${error.message || 'An error occurred while clearing brackets. Please try again.'}</p>
            </div>
        `;
    });
}

function setScore(team, score) {
    const form = document.getElementById('updateMatchForm');
    const otherTeam = team === 'team1' ? 'team2' : 'team1';
    
    // Set the winning team's score
    form.querySelector(`[name="${team}_score"]`).value = score;
    
    // Set the losing team's score based on the winning score
    form.querySelector(`[name="${otherTeam}_score"]`).value = score === 3 ? 0 : 1;
    
    // Update button states
    const buttons = form.querySelectorAll('.btn-score');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    const activeButton = form.querySelector(`.score-buttons button[onclick="setScore('${team}', ${score})"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
}

function resetScores() {
    const form = document.getElementById('updateMatchForm');
    form.querySelector('[name="team1_score"]').value = '';
    form.querySelector('[name="team2_score"]').value = '';
    
    // Reset button states
    const buttons = form.querySelectorAll('.btn-score');
    buttons.forEach(btn => btn.classList.remove('active'));
}

function openUpdateMatch(matchId, team1Name, team2Name) {
    const modal = document.getElementById('updateMatchModal');
    if (modal) {
        const form = modal.querySelector('form');
        form.querySelector('[name="match_id"]').value = matchId;
        form.querySelector('.team1-name').textContent = team1Name;
        form.querySelector('.team2-name').textContent = team2Name;
        
        // Reset scores when opening modal
        resetScores();
        
        // Show the modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }
}

function saveMatchResult(form) {
    const formData = new FormData(form);
    
    fetch('ajax/update_match.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateMatchModal'));
            modal.hide();
            
            // Reload brackets
            loadBrackets(document.getElementById('tournamentSelect').value);
            
            // Show success message
            alert('Match result updated successfully!');
        } else {
            throw new Error(data.message || 'Failed to update match');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating match: ' + error.message);
    });

    return false; // Prevent form submission
} 