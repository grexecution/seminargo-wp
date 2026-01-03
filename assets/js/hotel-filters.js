/**
 * Hotel Archive Filter Functionality
 */
(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const filterTabs = document.querySelectorAll('.filter-tab');
        const filterOptionGroups = document.querySelectorAll('.filter-option-group');
        const filterButtons = document.querySelectorAll('.filter-button');
        const hotelCards = document.querySelectorAll('.hotel-card');
        const loadMoreButton = document.getElementById('loadMoreHotels');

        let currentFilterMode = 'top';
        let currentTheme = 'all';
        let currentLocation = 'all';

        // Filter Tab Switching
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const filterType = this.dataset.filter;

                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update active filter group
                filterOptionGroups.forEach(group => {
                    group.classList.remove('active');
                });
                // Only show filter options for theme and location, hide for top
                if (filterType !== 'top') {
                    const activeGroup = document.querySelector(`[data-filter-group="${filterType}"]`);
                    if (activeGroup) {
                        activeGroup.classList.add('active');
                    }
                }

                // Update current filter mode
                currentFilterMode = filterType;

                // Reset filters
                currentTheme = 'all';
                currentLocation = 'all';

                // Reset filter buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                const defaultButtons = document.querySelectorAll('.filter-button[data-theme="all"], .filter-button[data-location="all"]');
                defaultButtons.forEach(btn => btn.classList.add('active'));

                // Apply filter
                applyFilters();
            });
        });

        // Filter Button Clicking
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Get filter type (theme or location)
                const theme = this.dataset.theme;
                const location = this.dataset.location;

                // Update active button within the same group
                if (theme) {
                    // Theme buttons
                    const themeButtons = document.querySelectorAll('.filter-button[data-theme]');
                    themeButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentTheme = theme;
                } else if (location) {
                    // Location buttons
                    const locationButtons = document.querySelectorAll('.filter-button[data-location]');
                    locationButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    currentLocation = location;
                }

                // Apply filter
                applyFilters();
            });
        });

        // Apply Filters Function
        function applyFilters() {
            hotelCards.forEach(card => {
                let shouldShow = false;

                // Check if card belongs to current filter tab
                const cardTabs = card.dataset.filterTabs ? card.dataset.filterTabs.split(' ') : [];
                const belongsToCurrentTab = cardTabs.includes(currentFilterMode);

                if (!belongsToCurrentTab) {
                    shouldShow = false;
                } else if (currentFilterMode === 'top') {
                    // Show hotels for top filter
                    shouldShow = true;
                } else if (currentFilterMode === 'theme') {
                    // Filter by theme (for now, show all - can be enhanced)
                    if (currentTheme === 'all') {
                        shouldShow = true;
                    } else {
                        // You can add theme filtering logic here
                        // For now, showing all cards that belong to theme tab
                        shouldShow = true;
                    }
                } else if (currentFilterMode === 'location') {
                    // Filter by location
                    if (currentLocation === 'all') {
                        shouldShow = true;
                    } else {
                        const cardLocation = card.dataset.location ? card.dataset.location.toLowerCase() : '';
                        shouldShow = cardLocation.includes(currentLocation.toLowerCase());
                    }
                }

                // Show or hide card
                if (shouldShow) {
                    card.classList.remove('hidden');
                    // Trigger animation
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.classList.add('hidden');
                }
            });

            // Check if any cards are visible
            const visibleCards = document.querySelectorAll('.hotel-card:not(.hidden)');
            if (visibleCards.length === 0) {
                // Show "no results" message if needed
                console.log('No hotels match the current filter');
            }
        }

        // Load More Button
        if (loadMoreButton) {
            loadMoreButton.addEventListener('click', function() {
                // For now, just show a message
                // In production, this would load more hotels via AJAX
                alert('Load more hotels functionality will be implemented with AJAX');
            });
        }

        // Initial filter application
        applyFilters();
    });
})();
