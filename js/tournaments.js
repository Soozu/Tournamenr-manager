document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const tournamentCards = document.querySelectorAll('.tournament-card');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');

            const gameFilter = tab.dataset.game;

            tournamentCards.forEach(card => {
                if (gameFilter === 'all' || card.dataset.game === gameFilter) {
                    card.style.display = 'block';
                    card.classList.add('fade-in');
                } else {
                    card.style.display = 'none';
                    card.classList.remove('fade-in');
                }
            });
        });
    });

    // Mobile filter button
    const filterBtn = document.querySelector('.btn-filter');
    const filterTabsContainer = document.querySelector('.filter-tabs');

    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            filterTabsContainer.classList.toggle('show');
        });

        // Close filter tabs when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tournament-filters')) {
                filterTabsContainer.classList.remove('show');
            }
        });
    }
}); 