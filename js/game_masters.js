// Activity and Reports Viewing Functions
function viewActivity(gmId) {
    if (!gmId) return;
    
    $.ajax({
        url: 'actions/game_master_actions.php',
        type: 'POST',
        data: {
            action: 'getActivity',
            gmId: gmId
        },
        success: function(response) {
            if (response.status === 'success') {
                const activity = response.data;
                Swal.fire({
                    title: 'Game Master Activity',
                    html: `
                        <div class="activity-details">
                            <div class="activity-section">
                                <h4>Recent Matches</h4>
                                <div class="activity-list">
                                    ${generateMatchesList(activity.recent_matches)}
                                </div>
                            </div>
                            
                            <div class="activity-section">
                                <h4>Activity Timeline</h4>
                                <div class="timeline">
                                    ${generateTimeline(activity.timeline)}
                                </div>
                            </div>

                            <div class="activity-stats">
                                <div class="stat-box">
                                    <span class="stat-label">Active Hours</span>
                                    <span class="stat-value">${activity.active_hours}</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">Response Rate</span>
                                    <span class="stat-value">${activity.response_rate}%</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">Avg. Match Duration</span>
                                    <span class="stat-value">${activity.avg_match_duration}</span>
                                </div>
                            </div>
                        </div>
                    `,
                    width: '800px',
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire('Error', 'Failed to load activity data', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to fetch activity data', 'error');
        }
    });
}

function viewReports(gmId) {
    if (!gmId) return;
    
    $.ajax({
        url: 'actions/game_master_actions.php',
        type: 'POST',
        data: {
            action: 'getReports',
            gmId: gmId
        },
        success: function(response) {
            if (response.status === 'success') {
                const reports = response.data;
                Swal.fire({
                    title: 'Match Reports',
                    html: `
                        <div class="reports-container">
                            <div class="reports-list">
                                ${generateReportsList(reports)}
                            </div>
                        </div>
                    `,
                    width: '800px',
                    showConfirmButton: true,
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire('Error', 'Failed to load reports', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to fetch reports', 'error');
        }
    });
}

// Helper Functions
function generateMatchesList(matches) {
    if (!matches || matches.length === 0) {
        return '<p class="no-data">No recent matches found</p>';
    }

    return matches.map(match => `
        <div class="match-item">
            <div class="match-info">
                <span class="match-id">#${match.id}</span>
                <span class="match-teams">${match.team1} vs ${match.team2}</span>
                <span class="match-date">${match.match_date}</span>
            </div>
            <div class="match-status ${match.status}">${match.status}</div>
        </div>
    `).join('');
}

function generateTimeline(timeline) {
    if (!timeline || timeline.length === 0) {
        return '<p class="no-data">No activity found</p>';
    }

    return timeline.map(item => `
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <span class="timeline-date">${item.date}</span>
                <p class="timeline-text">${item.action}</p>
            </div>
        </div>
    `).join('');
}

function generateReportsList(reports) {
    if (!reports || reports.length === 0) {
        return '<p class="no-data">No reports found</p>';
    }

    return reports.map(report => `
        <div class="report-item" data-type="${report.report_type}">
            <div class="report-header">
                <span class="report-id">#${report.id}</span>
                <span class="report-type ${report.report_type}">${report.report_type}</span>
                <span class="report-date">${report.created_at}</span>
            </div>
            <div class="report-content">
                <p class="report-description">${report.description}</p>
            </div>
            <div class="report-footer">
                <span class="report-status ${report.status}">${report.status}</span>
                ${report.attachments ? `<span class="report-attachment"><i class="bi bi-paperclip"></i></span>` : ''}
            </div>
        </div>
    `).join('');
}

// Filter functionality
$('#activityFilter').on('change', function() {
    const status = $(this).val();
    if (status === 'all') {
        $('.gm-card').show();
    } else {
        $('.gm-card').hide();
        $(`.gm-card[data-activity="${status}"]`).show();
    }
});

$('#searchGM').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.gm-card').each(function() {
        const username = $(this).find('h3').text().toLowerCase();
        const email = $(this).find('.info-item span').first().text().toLowerCase();
        if (username.includes(searchTerm) || email.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Game Alert Helper
function showGameAlert(type, message) {
    Swal.fire({
        title: type === 'success' ? 'Success!' : 'Error!',
        text: message,
        icon: type,
        background: '#1a1a1a',
        customClass: {
            popup: 'swal2-game-style',
            title: 'swal2-game-title',
            confirmButton: type === 'success' ? 'swal2-button-success' : 'swal2-button-error'
        },
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
} 