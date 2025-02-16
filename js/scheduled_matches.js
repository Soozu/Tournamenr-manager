// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});

// Edit Match Function
function editMatch(matchId) {
    fetch(`actions/match_actions.php?action=getMatch&id=${matchId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const match = data.data;
                document.getElementById('matchId').value = match.id;
                document.getElementById('gameMaster').value = match.gamemaster_id || '';
                document.getElementById('matchDate').value = match.match_date;
                document.getElementById('matchTime').value = match.match_time;
                document.getElementById('matchStatus').value = match.status;
                
                // Show modal
                new bootstrap.Modal(document.getElementById('matchModal')).show();
            } else {
                showAlert('Error', data.message, 'error');
            }
        })
        .catch(error => showAlert('Error', 'Failed to load match details', 'error'));
}

// Save Match Function
function saveMatch() {
    const formData = new FormData(document.getElementById('matchForm'));
    formData.append('action', 'updateMatch');

    fetch('actions/match_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert('Success', 'Match updated successfully', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('Error', data.message, 'error');
        }
    })
    .catch(error => showAlert('Error', 'Failed to update match', 'error'));
}

// View Match Details Function
function viewDetails(matchId) {
    fetch(`actions/match_actions.php?action=getMatchDetails&id=${matchId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const match = data.data;
                Swal.fire({
                    title: 'Match Details',
                    html: `
                        <div class="match-details-modal">
                            <div class="tournament-info">
                                <h4>${match.tournament_name}</h4>
                                <span class="game-name">${match.game_name}</span>
                            </div>
                            <div class="teams-info">
                                <div class="team">${match.team1_name}</div>
                                <div class="vs">VS</div>
                                <div class="team">${match.team2_name}</div>
                            </div>
                            <div class="match-info">
                                <p><i class="bi bi-calendar-event"></i> ${match.match_date}</p>
                                <p><i class="bi bi-clock"></i> ${match.match_time}</p>
                                <p><i class="bi bi-person-badge"></i> GM: ${match.gamemaster_name}</p>
                                <p><i class="bi bi-info-circle"></i> Status: ${match.status}</p>
                            </div>
                        </div>
                    `,
                    width: '600px',
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            } else {
                showAlert('Error', data.message, 'error');
            }
        })
        .catch(error => showAlert('Error', 'Failed to load match details', 'error'));
}

// Filter Functions
document.getElementById('tournamentFilter').addEventListener('change', filterMatches);
document.getElementById('statusFilter').addEventListener('change', filterMatches);
document.getElementById('dateFilter').addEventListener('change', filterMatches);

function filterMatches() {
    const tournament = document.getElementById('tournamentFilter').value;
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    const matches = document.querySelectorAll('.match-card');
    
    matches.forEach(match => {
        let show = true;
        
        if (tournament !== 'all' && match.dataset.tournament !== tournament) {
            show = false;
        }
        
        if (status !== 'all' && match.dataset.status !== status) {
            show = false;
        }
        
        if (date && match.dataset.date !== date) {
            show = false;
        }
        
        match.style.display = show ? 'block' : 'none';
    });
}

// Alert Function using SweetAlert2
function showAlert(title, message, icon) {
    Swal.fire({
        title: title,
        text: message,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
}

// Export to Excel Function
function exportToExcel() {
    fetch('actions/match_actions.php?action=exportMatches')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'scheduled_matches.xlsx';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => showAlert('Error', 'Failed to export matches', 'error'));
}

// Print Function
function printMatches() {
    window.print();
}

// Search Function
document.getElementById('searchMatches').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const matches = document.querySelectorAll('.match-card');
    
    matches.forEach(match => {
        const text = match.textContent.toLowerCase();
        match.style.display = text.includes(searchTerm) ? 'block' : 'none';
    });
}); 