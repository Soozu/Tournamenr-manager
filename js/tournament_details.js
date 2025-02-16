// Initialize tooltips and other Bootstrap components
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    addHoverEffects();
});

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Add hover effects for gamer aesthetic
function addHoverEffects() {
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
            this.style.boxShadow = '0 0 15px rgba(var(--accent-rgb), 0.3)';
        });
        button.addEventListener('mouseout', function() {
            this.style.transform = 'none';
            this.style.boxShadow = 'none';
        });
    });
}

// Edit Tournament Function
function editTournament(tournamentId) {
    showLoadingOverlay();
    fetch(`actions/tournament_actions.php?action=getTournament&id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            hideLoadingOverlay();
            if (data.status === 'success') {
                showEditModal(data.data);
            } else {
                showAlert('Error', data.message, 'error');
            }
        })
        .catch(error => {
            hideLoadingOverlay();
            showAlert('Error', 'Failed to load tournament details', 'error');
        });
}

// Delete Tournament with Animation
function deleteTournament(tournamentId) {
    Swal.fire({
        title: 'Delete Tournament?',
        html: '<div class="delete-animation"><i class="bi bi-exclamation-triangle-fill"></i></div>' +
              'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        background: 'var(--dark-secondary)',
        customClass: {
            confirmButton: 'btn btn-danger btn-lg',
            cancelButton: 'btn btn-secondary btn-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            performDelete(tournamentId);
        }
    });
}

// Assign Game Masters Function
function assignGameMasters(tournamentId) {
    if (!tournamentId) {
        showAlert('Error', 'Tournament ID is required', 'error');
        return;
    }

    fetch(`../admin/actions/tournament_actions.php?action=getAvailableGMs&tournament_id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: 'Assign Game Masters',
                html: `
                    <div class="gm-assignment-form">
                        <select multiple class="form-select" id="gmSelect">
                            ${data.gamemasters.map(gm => 
                                `<option value="${gm.id}">${gm.username}</option>`
                            ).join('')}
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Assign',
                cancelButtonText: 'Cancel',
                customClass: {
                    container: 'gm-modal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const selected = Array.from(document.getElementById('gmSelect').selectedOptions)
                        .map(option => option.value);
                    
                    if (selected.length === 0) {
                        showAlert('Warning', 'Please select at least one game master', 'warning');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'assignGameMasters');
                    formData.append('tournament_id', tournamentId);
                    formData.append('gamemaster_ids', JSON.stringify(selected));

                    fetch('actions/tournament_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showAlert('Success', 'Game masters assigned successfully', 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('Error', data.message, 'error');
                        }
                    })
                    .catch(error => showAlert('Error', 'Failed to assign game masters', 'error'));
                }
            });
        })
        .catch(error => showAlert('Error', 'Failed to load game masters', 'error'));
}

// Manage Brackets Function
function manageBrackets(tournamentId) {
    if (!tournamentId) {
        showAlert('Error', 'Tournament ID is required', 'error');
        return;
    }

    fetch(`../admin/actions/tournament_actions.php?action=getTournamentBrackets&tournament_id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: 'Tournament Brackets',
                html: `
                    <div class="brackets-container">
                        <div class="bracket-controls mb-3">
                            <button class="btn btn-primary" onclick="generateBrackets(${tournamentId})">
                                Generate Brackets
                            </button>
                            <button class="btn btn-warning" onclick="shuffleTeams(${tournamentId})">
                                Shuffle Teams
                            </button>
                        </div>
                        <div class="bracket-display">
                            ${data.brackets ? generateBracketHTML(data.brackets) : 
                              '<p>No brackets available. Click Generate Brackets to create the tournament structure.</p>'}
                        </div>
                    </div>
                `,
                width: '800px',
                showCloseButton: true,
                customClass: {
                    container: 'bracket-modal'
                }
            });
        })
        .catch(error => showAlert('Error', 'Failed to load brackets', 'error'));
}

// Generate bracket HTML structure
function generateBracketHTML(brackets) {
    if (!brackets || brackets.length === 0) {
        return '<p>No brackets generated yet. Click "Generate Brackets" to create the tournament structure.</p>';
    }
    
    // Implementation of bracket visualization
    let html = '<div class="tournament-bracket">';
    // Add bracket structure here
    html += '</div>';
    return html;
}

// View Reports Function
function viewReports(tournamentId) {
    if (!tournamentId) {
        showAlert('Error', 'Tournament ID is required', 'error');
        return;
    }

    fetch(`../admin/actions/tournament_actions.php?action=getTournamentReports&tournament_id=${tournamentId}`)
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: 'Tournament Reports',
                html: `
                    <div class="reports-container">
                        <select class="form-select mb-3" id="reportTypeFilter">
                            <option value="all">All Reports</option>
                            <option value="match">Match Reports</option>
                            <option value="incident">Incident Reports</option>
                        </select>
                        <div class="reports-list">
                            ${generateReportsHTML(data.reports)}
                        </div>
                    </div>
                `,
                width: '800px',
                showCloseButton: true,
                customClass: {
                    container: 'reports-modal'
                }
            });

            // Add filter event listener
            document.getElementById('reportTypeFilter')?.addEventListener('change', function() {
                filterReports(this.value);
            });
        })
        .catch(error => showAlert('Error', 'Failed to load reports', 'error'));
}

// Generate reports HTML
function generateReportsHTML(reports) {
    if (!reports || reports.length === 0) {
        return '<p>No reports available for this tournament.</p>';
    }

    let html = '<div class="reports-grid">';
    reports.forEach(report => {
        html += `
            <div class="report-card" data-type="${report.type}">
                <div class="report-header">
                    <span class="report-type">${report.type}</span>
                    <span class="report-date">${report.date}</span>
                </div>
                <div class="report-content">
                    <p>${report.description}</p>
                </div>
                <div class="report-footer">
                    <button class="btn btn-sm btn-primary" onclick="viewReportDetails(${report.id})">
                        View Details
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

// Filter reports based on type
function filterReports(type, reports) {
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Save Settings Function with Animation
function saveSettings(tournamentId) {
    const formData = new FormData(document.getElementById('settingsForm'));
    formData.append('action', 'updateSettings');
    formData.append('tournament_id', tournamentId);

    const saveButton = document.querySelector('.settings-actions .btn-primary');
    saveButton.classList.add('loading');

    fetch('actions/tournament_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        saveButton.classList.remove('loading');
        if (data.status === 'success') {
            showSuccessAnimation();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('Error', data.message, 'error');
        }
    })
    .catch(error => {
        saveButton.classList.remove('loading');
        showAlert('Error', 'Failed to save settings', 'error');
    });
}

// Loading Overlay
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>
    `;
    document.body.appendChild(overlay);
}

function hideLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Success Animation
function showSuccessAnimation() {
    Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Changes have been saved successfully',
        timer: 1500,
        showConfirmButton: false,
        background: 'var(--dark-secondary)',
        customClass: {
            popup: 'animated-popup'
        }
    });
}

// Alert Function
function showAlert(title, message, icon) {
    Swal.fire({
        title: title,
        text: message,
        icon: icon,
        background: 'var(--dark-secondary)',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'animated-toast'
        }
    });
}

function viewTeamDetails(teamId) {
    window.location.href = `team_details.php?id=${teamId}`;
}

// Add smooth scrolling for back to top
document.addEventListener('DOMContentLoaded', function() {
    window.onscroll = function() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.querySelector('.back-to-top').style.display = 'block';
        } else {
            document.querySelector('.back-to-top').style.display = 'none';
        }
    };
}); 