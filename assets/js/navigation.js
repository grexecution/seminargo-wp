/**
 * Navigation functionality with slide-out menu
 */
(function() {
    'use strict';

    // Slide-out menu functionality
    const initSlideMenu = () => {
        const menuToggle = document.querySelector('.menu-toggle');
        const slideMenu = document.getElementById('slide-menu');
        const slideMenuClose = document.querySelector('.slide-menu-close');
        const slideMenuOverlay = document.querySelector('.slide-menu-overlay');
        const body = document.body;

        if (!menuToggle || !slideMenu) return;

        // Open slide menu
        const openSlideMenu = () => {
            slideMenu.classList.add('active');
            body.classList.add('slide-menu-open');
            menuToggle.setAttribute('aria-expanded', 'true');
            menuToggle.classList.add('active');

            // Trap focus in menu
            setTimeout(() => {
                const firstFocusable = slideMenu.querySelector('a, button');
                if (firstFocusable) firstFocusable.focus();
            }, 300);
        };

        // Close slide menu
        const closeSlideMenu = () => {
            slideMenu.classList.remove('active');
            body.classList.remove('slide-menu-open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.classList.remove('active');
            menuToggle.focus();
        };

        // Toggle menu on button click
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (slideMenu.classList.contains('active')) {
                closeSlideMenu();
            } else {
                openSlideMenu();
            }
        });

        // Close on close button click
        if (slideMenuClose) {
            slideMenuClose.addEventListener('click', closeSlideMenu);
        }

        // Close on overlay click
        if (slideMenuOverlay) {
            slideMenuOverlay.addEventListener('click', closeSlideMenu);
        }

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && slideMenu.classList.contains('active')) {
                closeSlideMenu();
            }
        });

        // Prevent body scroll when menu is open
        slideMenu.addEventListener('transitionend', function() {
            if (slideMenu.classList.contains('active')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        });

        // Handle submenu toggles in mobile menu
        const subMenuToggles = slideMenu.querySelectorAll('.menu-item-has-children > a');
        subMenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    const subMenu = this.nextElementSibling;
                    if (subMenu && subMenu.classList.contains('sub-menu')) {
                        e.preventDefault();
                        this.parentElement.classList.toggle('submenu-open');

                        // Update aria-expanded
                        const isExpanded = this.parentElement.classList.contains('submenu-open');
                        this.setAttribute('aria-expanded', isExpanded);
                    }
                }
            });
        });
    };


    // Smooth scroll for anchor links
    const initSmoothScroll = () => {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#' || targetId === '#0') return;

                const targetElement = document.querySelector(targetId);
                if (!targetElement) return;

                e.preventDefault();

                const headerHeight = document.querySelector('.site-header').offsetHeight || 0;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Update URL without jumping
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                }
            });
        });
    };

    // Keyboard navigation enhancements
    const initKeyboardNav = () => {
        const menuItems = document.querySelectorAll('.main-navigation a, .slide-menu a');

        menuItems.forEach((item) => {
            item.addEventListener('keydown', function(e) {
                const parent = this.parentElement;

                // Handle submenu navigation
                if (e.key === 'ArrowDown' && parent.classList.contains('menu-item-has-children')) {
                    e.preventDefault();
                    const firstChild = parent.querySelector('.sub-menu a');
                    if (firstChild) firstChild.focus();
                }

                if (e.key === 'ArrowUp' && this.closest('.sub-menu')) {
                    e.preventDefault();
                    const parentLink = this.closest('.menu-item-has-children').querySelector('> a');
                    if (parentLink) parentLink.focus();
                }
            });
        });
    };

    // Mobile viewport height fix
    const initViewportFix = () => {
        // Fix for mobile viewport height
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
    };

    // Initialize all navigation features
    const init = () => {
        initSlideMenu();
        initSmoothScroll();
        initKeyboardNav();
        initViewportFix();
    };

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();