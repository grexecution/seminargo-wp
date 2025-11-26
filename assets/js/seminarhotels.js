/**
 * Seminarhotels Archive Page JavaScript
 */
(function() {
    'use strict';

    // Toggle advanced filters
    const initFilterToggle = () => {
        const toggleBtn = document.getElementById('toggle-filters');
        const advancedFilters = document.getElementById('advanced-filters');

        if (!toggleBtn || !advancedFilters) return;

        toggleBtn.addEventListener('click', function() {
            this.classList.toggle('active');
            advancedFilters.classList.toggle('active');
        });
    };

    // Clear filters
    const initClearFilters = () => {
        const clearBtn = document.getElementById('clear-filters');
        const form = document.getElementById('hotel-search-form');

        if (!clearBtn || !form) return;

        clearBtn.addEventListener('click', function() {
            // Reset all form inputs
            form.reset();

            // Uncheck all checkboxes
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Clear all text inputs
            form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]').forEach(input => {
                input.value = '';
            });

            // Reset selects to first option
            form.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });

            // Clear active filters display
            const activeFilters = document.getElementById('active-filters');
            if (activeFilters) {
                activeFilters.innerHTML = '';
            }
        });
    };

    // View toggle (grid/list)
    const initViewToggle = () => {
        const viewBtns = document.querySelectorAll('.view-btn');
        const hotelsGrid = document.getElementById('hotels-grid');

        if (!viewBtns.length || !hotelsGrid) return;

        viewBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.getAttribute('data-view');

                // Update active state
                viewBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update grid class
                hotelsGrid.className = 'hotels-grid view-' + view;
            });
        });
    };

    // Wishlist functionality
    const initWishlist = () => {
        const wishlistBtns = document.querySelectorAll('.btn-wishlist');

        wishlistBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                this.classList.toggle('active');

                // Here you would typically save to localStorage or send to server
                const hotelId = this.getAttribute('data-hotel-id');
                const isActive = this.classList.contains('active');

                if (isActive) {
                    addToWishlist(hotelId);
                } else {
                    removeFromWishlist(hotelId);
                }
            });
        });
    };

    // Wishlist management
    const addToWishlist = (hotelId) => {
        let wishlist = JSON.parse(localStorage.getItem('seminargo_wishlist') || '[]');
        if (!wishlist.includes(hotelId)) {
            wishlist.push(hotelId);
            localStorage.setItem('seminargo_wishlist', JSON.stringify(wishlist));
        }
    };

    const removeFromWishlist = (hotelId) => {
        let wishlist = JSON.parse(localStorage.getItem('seminargo_wishlist') || '[]');
        wishlist = wishlist.filter(id => id !== hotelId);
        localStorage.setItem('seminargo_wishlist', JSON.stringify(wishlist));
    };

    // Load wishlist state on page load
    const loadWishlistState = () => {
        const wishlist = JSON.parse(localStorage.getItem('seminargo_wishlist') || '[]');
        wishlist.forEach(hotelId => {
            const btn = document.querySelector(`.btn-wishlist[data-hotel-id="${hotelId}"]`);
            if (btn) {
                btn.classList.add('active');
            }
        });
    };

    // Display active filters
    const displayActiveFilters = () => {
        const form = document.getElementById('hotel-search-form');
        const activeFiltersContainer = document.getElementById('active-filters');

        if (!form || !activeFiltersContainer) return;

        const urlParams = new URLSearchParams(window.location.search);
        activeFiltersContainer.innerHTML = '';

        // Check for active filters
        let hasFilters = false;

        urlParams.forEach((value, key) => {
            if (value && key !== 'sort') {
                hasFilters = true;
                const tag = document.createElement('div');
                tag.className = 'filter-tag';
                tag.innerHTML = `
                    <span>${key}: ${value}</span>
                    <button type="button" data-filter="${key}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `;
                activeFiltersContainer.appendChild(tag);

                // Add click handler to remove filter
                tag.querySelector('button').addEventListener('click', function() {
                    const filterKey = this.getAttribute('data-filter');
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete(filterKey);
                    window.location.href = newUrl.toString();
                });
            }
        });
    };

    // Load more functionality
    const initLoadMore = () => {
        const loadMoreBtn = document.querySelector('.btn-load-more');

        if (!loadMoreBtn) return;

        loadMoreBtn.addEventListener('click', function() {
            this.classList.add('loading');
            this.innerHTML = '<span>Lädt...</span>';

            // Simulate loading - in production this would be an AJAX call
            setTimeout(() => {
                this.classList.remove('loading');
                this.innerHTML = `
                    Mehr Hotels laden
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                `;
            }, 1000);
        });
    };

    // Initialize all features
    const init = () => {
        initFilterToggle();
        initClearFilters();
        initViewToggle();
        initWishlist();
        loadWishlistState();
        displayActiveFilters();
        initLoadMore();
    };

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
