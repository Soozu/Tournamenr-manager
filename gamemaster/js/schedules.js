document.addEventListener('DOMContentLoaded', function() {
    // Match filter functionality
    const matchFilter = document.getElementById('matchFilter');
    if (matchFilter) {
        matchFilter.addEventListener('change', function() {
            filterMatches(this.value);
        });
    }
});

function filterMatches(status) {
    const scheduleCards = document.querySelectorAll('.schedule-card');
    let visibleCount = 0;

    scheduleCards.forEach(card => {
        if (status === 'all' || card.querySelector('.status-badge').classList.contains(status)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show/hide no matches message
    const noSchedules = document.querySelector('.no-schedules');
    if (noSchedules) {
        noSchedules.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    // Update date headers visibility
    document.querySelectorAll('.schedule-group').forEach(group => {
        const visibleCards = group.querySelectorAll('.schedule-card[style=""]').length;
        const dateHeader = group.previousElementSibling;
        
        if (dateHeader && dateHeader.classList.contains('date-header')) {
            dateHeader.style.display = visibleCards > 0 ? '' : 'none';
        }
        group.style.display = visibleCards > 0 ? '' : 'none';
    });
}

function openCalendarView() {
    const calendarModal = new bootstrap.Modal(document.getElementById('calendarModal'));
    calendarModal.show();
    
    // Get all matches
    const matches = [];
    document.querySelectorAll('.schedule-card').forEach(card => {
        const dateTime = card.querySelector('.match-time').textContent.trim().replace('Time TBD', '');
        const team1 = card.querySelector('.team1 .team-name').textContent;
        const team2 = card.querySelector('.team2 .team-name').textContent;
        const status = card.querySelector('.status-badge').textContent.trim();
        const gameIcon = card.querySelector('.game-icon').src;
        const gameName = card.querySelector('.game-name').textContent;
        
        if (dateTime) {
            matches.push({
                title: `${team1} vs ${team2}`,
                start: new Date(dateTime),
                status: status.toLowerCase(),
                extendedProps: {
                    gameIcon: gameIcon,
                    gameName: gameName,
                    team1: team1,
                    team2: team2
                }
            });
        }
    });

    if (matches.length === 0) {
        const calendarEl = document.getElementById('matchCalendar');
        calendarEl.innerHTML = `
            <div class="calendar-empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No Matches Found</h3>
                <p>There are no matches scheduled at the moment.</p>
            </div>
        `;
        return;
    }

    // Check if all matches are completed
    const allCompleted = matches.every(match => match.status === 'completed');
    if (allCompleted) {
        const calendarEl = document.getElementById('matchCalendar');
        calendarEl.innerHTML = `
            <div class="calendar-empty-state">
                <i class="bi bi-trophy"></i>
                <h3>All Matches Completed</h3>
                <p>All tournament matches have been completed.</p>
            </div>
        `;
        return;
    }

    initializeCalendar(matches);
}

function initializeCalendar(matches) {
    const calendarEl = document.getElementById('matchCalendar');
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: matches,
        eventDidMount: function(info) {
            // Create custom event content
            const eventEl = info.el;
            const event = info.event;
            
            // Create tooltip content
            const tooltipContent = `
                <div class="calendar-event-tooltip">
                    <img src="${event.extendedProps.gameIcon}" class="game-icon" alt="${event.extendedProps.gameName}">
                    <div class="event-details">
                        <div class="game-name">${event.extendedProps.gameName}</div>
                        <div class="teams">
                            ${event.extendedProps.team1} vs ${event.extendedProps.team2}
                        </div>
                        <div class="match-time">
                            ${event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                </div>
            `;
            
            // Add tooltip using Bootstrap
            new bootstrap.Tooltip(eventEl, {
                title: tooltipContent,
                html: true,
                placement: 'top',
                customClass: 'calendar-tooltip'
            });
            
            // Add status class to event element
            eventEl.classList.add(`event-${event.extendedProps.status}`);
        },
        dayCellDidMount: function(info) {
            // Check if there are events on this day
            const date = info.date;
            const eventsOnDay = matches.filter(match => {
                const matchDate = new Date(match.start);
                return matchDate.toDateString() === date.toDateString();
            });

            if (eventsOnDay.length === 0) {
                const cellEl = info.el;
                const noMatchesEl = document.createElement('div');
                noMatchesEl.className = 'no-matches-indicator';
                noMatchesEl.innerHTML = '<small>No matches</small>';
                cellEl.appendChild(noMatchesEl);
            }
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week'
        },
        dayMaxEvents: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: true
        }
    });

    calendar.render();
    
    // Adjust calendar on modal shown
    document.getElementById('calendarModal').addEventListener('shown.bs.modal', function () {
        calendar.updateSize();
    });
} 