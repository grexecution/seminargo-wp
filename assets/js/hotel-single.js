/**
 * Hotel Single Page JavaScript
 */
(function() {
    'use strict';

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

    // Static Gallery with Thumbnail Navigation
    const initGalleryStatic = () => {
        const gallery = document.getElementById('hotel-gallery');
        if (!gallery) return;

        const mainImage = document.getElementById('gallery-current-image');
        const mainImageContainer = document.getElementById('gallery-main-image');
        const counter = gallery.querySelector('.gallery-counter .current-slide');
        const thumbnails = gallery.querySelectorAll('.gallery-thumb-btn');
        const thumbnailsContainer = gallery.querySelector('.gallery-thumbnails');
        const viewAllBtn = document.getElementById('open-lightbox');
        const prevBtn = document.getElementById('gallery-nav-prev');
        const nextBtn = document.getElementById('gallery-nav-next');

        let currentIndex = 0;
        const totalImages = thumbnails.length;

        // Scroll thumbnail into view
        const scrollThumbnailIntoView = (thumb) => {
            if (!thumbnailsContainer || !thumb) return;

            const containerRect = thumbnailsContainer.getBoundingClientRect();
            const thumbRect = thumb.getBoundingClientRect();

            // Calculate if thumbnail is outside the visible area
            const isOutOfView =
                thumbRect.left < containerRect.left ||
                thumbRect.right > containerRect.right;

            if (isOutOfView) {
                // Scroll to center the thumbnail
                const scrollLeft = thumb.offsetLeft - (thumbnailsContainer.offsetWidth / 2) + (thumb.offsetWidth / 2);

                thumbnailsContainer.scrollTo({
                    left: scrollLeft,
                    behavior: 'smooth'
                });
            }
        };

        // Update main image
        const updateMainImage = (index) => {
            const thumb = thumbnails[index];
            if (!thumb) return;

            currentIndex = index;
            const newSrc = thumb.getAttribute('data-src');

            // Update main image
            mainImage.src = newSrc;

            // Update counter
            if (counter) {
                counter.textContent = index + 1;
            }

            // Update active thumbnail
            thumbnails.forEach((t, i) => {
                t.classList.toggle('active', i === index);
            });

            // Scroll active thumbnail into view
            scrollThumbnailIntoView(thumb);
        };

        // Thumbnail clicks change main image
        thumbnails.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const index = parseInt(thumb.getAttribute('data-index'));
                updateMainImage(index);
            });
        });

        // Previous button
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const newIndex = currentIndex === 0 ? totalImages - 1 : currentIndex - 1;
                updateMainImage(newIndex);
            });
        }

        // Next button
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const newIndex = currentIndex === totalImages - 1 ? 0 : currentIndex + 1;
                updateMainImage(newIndex);
            });
        }

        // Main image click opens lightbox
        if (mainImageContainer) {
            mainImageContainer.addEventListener('click', (e) => {
                // Don't open lightbox if clicking the view all button or nav buttons
                if (e.target.closest('.gallery-view-all') || e.target.closest('.gallery-nav')) return;
                openLightbox(currentIndex);
            });
        }

        // View all button opens lightbox
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                openLightbox(currentIndex);
            });
        }
    };

    // Lightbox functionality
    const initLightbox = () => {
        const lightbox = document.getElementById('gallery-lightbox');
        if (!lightbox) return;

        const closeBtn = lightbox.querySelector('.lightbox-close');
        const overlay = lightbox.querySelector('.lightbox-overlay');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');
        const image = document.getElementById('lightbox-image');
        const counter = lightbox.querySelector('.lightbox-counter .current-slide');
        const thumbnails = lightbox.querySelectorAll('.lightbox-thumb-btn');
        const thumbnailsContainer = lightbox.querySelector('.lightbox-thumbnails');

        // Get all image sources from data attribute or thumbnails
        const mainImage = document.getElementById('gallery-current-image');
        let imageSources = [];

        if (mainImage && mainImage.dataset.images) {
            try {
                imageSources = JSON.parse(mainImage.dataset.images);
            } catch (e) {
                console.error('Error parsing gallery images:', e);
            }
        }

        // Fallback: get from thumbnails
        if (!imageSources.length) {
            const thumbs = document.querySelectorAll('.gallery-thumb-btn');
            imageSources = Array.from(thumbs).map(t => t.getAttribute('data-src'));
        }

        // Scroll lightbox thumbnail into view
        const scrollLightboxThumbnailIntoView = (thumb) => {
            if (!thumbnailsContainer || !thumb) return;

            const containerRect = thumbnailsContainer.getBoundingClientRect();
            const thumbRect = thumb.getBoundingClientRect();

            // Calculate if thumbnail is outside the visible area
            const isOutOfView =
                thumbRect.left < containerRect.left ||
                thumbRect.right > containerRect.right;

            if (isOutOfView) {
                // Scroll to center the thumbnail
                const scrollLeft = thumb.offsetLeft - (thumbnailsContainer.offsetWidth / 2) + (thumb.offsetWidth / 2);

                thumbnailsContainer.scrollTo({
                    left: scrollLeft,
                    behavior: 'smooth'
                });
            }
        };

        // Close lightbox
        const closeLightbox = () => {
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeLightbox);
        }

        if (overlay) {
            overlay.addEventListener('click', closeLightbox);
        }

        // Update lightbox image
        window.updateLightbox = (index) => {
            window.lightboxCurrentIndex = index;
            image.src = imageSources[index];

            if (counter) {
                counter.textContent = index + 1;
            }

            thumbnails.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });

            // Scroll active thumbnail into view
            const activeThumb = thumbnails[index];
            if (activeThumb) {
                scrollLightboxThumbnailIntoView(activeThumb);
            }
        };

        // Navigation
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                const newIndex = window.lightboxCurrentIndex === 0
                    ? imageSources.length - 1
                    : window.lightboxCurrentIndex - 1;
                window.updateLightbox(newIndex);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const newIndex = window.lightboxCurrentIndex === imageSources.length - 1
                    ? 0
                    : window.lightboxCurrentIndex + 1;
                window.updateLightbox(newIndex);
            });
        }

        // Thumbnail clicks
        thumbnails.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const index = parseInt(thumb.getAttribute('data-index'));
                window.updateLightbox(index);
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (lightbox.getAttribute('aria-hidden') === 'false') {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    const newIndex = window.lightboxCurrentIndex === 0
                        ? imageSources.length - 1
                        : window.lightboxCurrentIndex - 1;
                    window.updateLightbox(newIndex);
                } else if (e.key === 'ArrowRight') {
                    const newIndex = window.lightboxCurrentIndex === imageSources.length - 1
                        ? 0
                        : window.lightboxCurrentIndex + 1;
                    window.updateLightbox(newIndex);
                }
            }
        });

        // Touch/swipe in lightbox
        let touchStartX = 0;

        lightbox.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        lightbox.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            const swipeThreshold = 50;

            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swipe left - next
                    const newIndex = window.lightboxCurrentIndex === imageSources.length - 1
                        ? 0
                        : window.lightboxCurrentIndex + 1;
                    window.updateLightbox(newIndex);
                } else {
                    // Swipe right - prev
                    const newIndex = window.lightboxCurrentIndex === 0
                        ? imageSources.length - 1
                        : window.lightboxCurrentIndex - 1;
                    window.updateLightbox(newIndex);
                }
            }
        }, { passive: true });
    };

    // Open lightbox function (called from carousel)
    const openLightbox = (index) => {
        const lightbox = document.getElementById('gallery-lightbox');
        if (!lightbox) return;

        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (window.updateLightbox) {
            window.updateLightbox(index);
        }
    };

    // Legacy gallery support (for old gallery-thumb elements)
    const initGallery = () => {
        const galleryThumbs = document.querySelectorAll('.gallery-thumb');

        if (!galleryThumbs.length) return;

        galleryThumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const img = this.querySelector('img');
                if (img) {
                    window.open(img.src, '_blank');
                }
            });
        });
    };

    // Meeting rooms expandable details (accordion - only one open at a time)
    const initMeetingRooms = () => {
        const buttons = document.querySelectorAll('.btn-room-details');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('aria-controls');
                const targetRow = document.getElementById(targetId);
                const isExpanded = btn.getAttribute('aria-expanded') === 'true';

                // Close all other expanded rows first (accordion behavior)
                buttons.forEach(otherBtn => {
                    if (otherBtn !== btn) {
                        const otherId = otherBtn.getAttribute('aria-controls');
                        const otherRow = document.getElementById(otherId);
                        otherBtn.setAttribute('aria-expanded', 'false');
                        if (otherRow) {
                            otherRow.setAttribute('aria-hidden', 'true');
                        }
                    }
                });

                // Toggle the clicked row
                if (isExpanded) {
                    btn.setAttribute('aria-expanded', 'false');
                    targetRow.setAttribute('aria-hidden', 'true');
                } else {
                    btn.setAttribute('aria-expanded', 'true');
                    targetRow.setAttribute('aria-hidden', 'false');
                }
            });
        });
    };

    // Show more/less rooms functionality
    const initShowMoreRooms = () => {
        const btn = document.getElementById('btn-show-more-rooms');
        const wrapper = document.querySelector('.meeting-rooms-table-wrapper');

        if (!btn || !wrapper) return;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = btn.getAttribute('data-expanded') === 'true';

            if (isExpanded) {
                // Collapse - hide extra rows
                wrapper.classList.remove('expanded');
                btn.setAttribute('data-expanded', 'false');

                // Close any open detail rows in hidden section
                const hiddenDetailBtns = wrapper.querySelectorAll('.room-row-hidden .btn-room-details[aria-expanded="true"]');
                hiddenDetailBtns.forEach(detailBtn => {
                    const targetId = detailBtn.getAttribute('aria-controls');
                    const targetRow = document.getElementById(targetId);
                    detailBtn.setAttribute('aria-expanded', 'false');
                    if (targetRow) {
                        targetRow.setAttribute('aria-hidden', 'true');
                    }
                });
            } else {
                // Expand - show all rows
                wrapper.classList.add('expanded');
                btn.setAttribute('data-expanded', 'true');
            }
        });
    };

    // Read More functionality for description
    const initReadMore = () => {
        const wrapper = document.querySelector('.description-content-wrapper');
        const content = document.getElementById('hotel-description-content');
        const btn = document.getElementById('btn-read-more-description');

        if (!wrapper || !content || !btn) return;

        // Check if content overflows
        const checkOverflow = () => {
            const maxHeight = 150; // Match CSS max-height
            const actualHeight = content.scrollHeight;

            if (actualHeight <= maxHeight) {
                wrapper.classList.add('no-overflow');
            } else {
                wrapper.classList.remove('no-overflow');
            }
        };

        // Initial check
        checkOverflow();

        // Recheck on window resize
        window.addEventListener('resize', checkOverflow);

        // Toggle expand/collapse
        btn.addEventListener('click', () => {
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                // Collapse
                content.classList.remove('expanded');
                wrapper.classList.remove('expanded');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                // Expand
                content.classList.add('expanded');
                wrapper.classList.add('expanded');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    };

    // Map initialization
    const initMap = () => {
        const mapElement = document.getElementById('hotel-map');

        if (!mapElement) return;

        const lat = parseFloat(mapElement.getAttribute('data-lat'));
        const lng = parseFloat(mapElement.getAttribute('data-lng'));
        const hotelName = mapElement.getAttribute('data-name');

        // Initialize map with custom styling
        const map = L.map('hotel-map', {
            zoomControl: true,
            attributionControl: true
        }).setView([lat, lng], 12);

        // Use CartoDB Positron tiles as base (light, minimal style)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Custom marker icon (berry colored)
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: #AC2A6E; width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 4px 12px rgba(172, 42, 110, 0.4);"><div style="width: 14px; height: 14px; background: white; border-radius: 50%; position: absolute; top: 7px; left: 7px;"></div></div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        // Add marker with popup
        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

        if (hotelName) {
            marker.bindPopup(`<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 4px 0;"><strong style="color: #AC2A6E; font-size: 14px;">${hotelName}</strong></div>`);
        }
    };

    // Prevent unwanted anchor tag wrapping in iframe
    const initIframeProtection = () => {
        // Only run if in iframe
        if (window.self === window.top) return;

        console.log('[Iframe Protection] Running in iframe, monitoring for unwanted DOM manipulation...');

        // Function to remove unwanted anchor wrappers
        const removeUnwantedAnchors = () => {
            // Find the hotel-features-categories container
            const featuresContainer = document.querySelector('.hotel-features-categories');
            if (!featuresContainer) return;

            // Check if it's wrapped in an anchor tag
            const parentAnchor = featuresContainer.closest('a[href*="#seminar"]');
            if (parentAnchor) {
                console.warn('[Iframe Protection] Detected unwanted anchor wrapper, removing...', parentAnchor);

                // Unwrap: replace anchor with its children
                const parent = parentAnchor.parentNode;
                while (parentAnchor.firstChild) {
                    parent.insertBefore(parentAnchor.firstChild, parentAnchor);
                }
                parent.removeChild(parentAnchor);
            }

            // Also check for feature-category sections individually
            const featureCategories = document.querySelectorAll('.feature-category');
            featureCategories.forEach(category => {
                const categoryAnchor = category.closest('a[href*="#seminar"]');
                if (categoryAnchor) {
                    console.warn('[Iframe Protection] Detected unwanted anchor on category, removing...', categoryAnchor);
                    const parent = categoryAnchor.parentNode;
                    while (categoryAnchor.firstChild) {
                        parent.insertBefore(categoryAnchor.firstChild, categoryAnchor);
                    }
                    parent.removeChild(categoryAnchor);
                }
            });
        };

        // Run immediately
        removeUnwantedAnchors();

        // Monitor for DOM changes with MutationObserver
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    removeUnwantedAnchors();
                }
            });
        });

        // Observe the entire document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['href']
        });

        // Also run after a short delay (in case parent app manipulates after load)
        setTimeout(removeUnwantedAnchors, 100);
        setTimeout(removeUnwantedAnchors, 500);
        setTimeout(removeUnwantedAnchors, 1000);
    };

    // Initialize all features
    const init = () => {
        initIframeProtection(); // Run FIRST to catch early manipulation
        initWishlist();
        initBookingForm();
        initGalleryStatic();
        initLightbox();
        initGallery(); // Legacy support
        initMeetingRooms();
        initShowMoreRooms();
        initReadMore();
        initMap();
    };

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
