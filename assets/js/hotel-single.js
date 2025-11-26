/**
 * Hotel Single Page JavaScript
 */
(function() {
    'use strict';

    // FAQ Accordion
    const initFAQAccordion = () => {
        const faqQuestions = document.querySelectorAll('.faq-question');

        if (!faqQuestions.length) return;

        faqQuestions.forEach(question => {
            question.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                // Close all other FAQs
                faqQuestions.forEach(q => {
                    q.setAttribute('aria-expanded', 'false');
                });

                // Toggle current FAQ
                this.setAttribute('aria-expanded', !isExpanded);
            });
        });
    };

    // Wishlist functionality (reuse from seminarhotels.js)
    const initWishlist = () => {
        const wishlistBtn = document.querySelector('.btn-wishlist');

        if (!wishlistBtn) return;

        // Load saved state
        const hotelId = wishlistBtn.getAttribute('data-hotel-id');
        const wishlist = JSON.parse(localStorage.getItem('seminargo_wishlist') || '[]');

        if (wishlist.includes(hotelId)) {
            wishlistBtn.classList.add('active');
        }

        // Toggle wishlist
        wishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            this.classList.toggle('active');
            const isActive = this.classList.contains('active');

            if (isActive) {
                addToWishlist(hotelId);
            } else {
                removeFromWishlist(hotelId);
            }
        });
    };

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

    // Booking form handling
    const initBookingForm = () => {
        const form = document.getElementById('hotel-booking-form');

        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Here you would typically send to server via AJAX
            console.log('Booking request:', data);

            // Show success message (temporary)
            alert('Vielen Dank für Ihre Anfrage! Wir melden uns innerhalb von 24 Stunden bei Ihnen.');

            // Reset form
            form.reset();
        });
    };

    // Gallery lightbox (simple implementation)
    const initGallery = () => {
        const galleryThumbs = document.querySelectorAll('.gallery-thumb');

        if (!galleryThumbs.length) return;

        galleryThumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const img = this.querySelector('img');
                if (img) {
                    // Simple implementation - open in new window
                    // In production, you'd use a proper lightbox library
                    window.open(img.src, '_blank');
                }
            });
        });
    };

    // Map initialization
    const initMap = () => {
        const mapElement = document.getElementById('hotel-map');

        if (!mapElement) return;

        const lat = parseFloat(mapElement.getAttribute('data-lat'));
        const lng = parseFloat(mapElement.getAttribute('data-lng'));
        const hotelName = mapElement.getAttribute('data-name');

        // Initialize map
        const map = L.map('hotel-map').setView([lat, lng], 13);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Custom marker icon (berry colored)
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: #AC2A6E; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><div style="width: 12px; height: 12px; background: white; border-radius: 50%; position: absolute; top: 6px; left: 6px;"></div></div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        // Add marker
        L.marker([lat, lng], { icon: customIcon }).addTo(map);
    };

    // Initialize all features
    const init = () => {
        initFAQAccordion();
        initWishlist();
        initBookingForm();
        initGallery();
        initMap();
    };

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
