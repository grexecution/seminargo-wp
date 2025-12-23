/**
 * Main JavaScript file for seminargo theme
 */
(function($) {
    'use strict';

    // Back to top button
    const initBackToTop = () => {
        const backToTop = document.getElementById('back-to-top');
        if (!backToTop) return;

        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        // Scroll to top on click
        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    };

    // Lazy loading for images
    const initLazyLoading = () => {
        const images = document.querySelectorAll('img[data-src]');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for browsers without IntersectionObserver
            images.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    };

    // AJAX Load More Posts
    const initLoadMore = () => {
        const loadMoreBtn = document.querySelector('.load-more-posts');
        if (!loadMoreBtn) return;

        let page = 2; // Start from page 2
        let loading = false;

        loadMoreBtn.addEventListener('click', function() {
            if (loading) return;

            loading = true;
            loadMoreBtn.classList.add('loading');
            loadMoreBtn.textContent = 'Loading...';

            // WordPress AJAX call
            $.ajax({
                url: seminargo_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_posts',
                    page: page,
                    nonce: seminargo_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.posts) {
                        const postsContainer = document.querySelector('.posts-grid');
                        postsContainer.insertAdjacentHTML('beforeend', response.data.posts);

                        page++;

                        if (!response.data.has_more) {
                            loadMoreBtn.style.display = 'none';
                        }
                    }
                },
                complete: function() {
                    loading = false;
                    loadMoreBtn.classList.remove('loading');
                    loadMoreBtn.textContent = 'Load More';
                }
            });
        });
    };

    // Responsive tables
    const initResponsiveTables = () => {
        const tables = document.querySelectorAll('.entry-content table');

        tables.forEach(table => {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    };

    // Enhance comment form
    const enhanceCommentForm = () => {
        const commentForm = document.getElementById('commentform');
        if (!commentForm) return;

        const textarea = commentForm.querySelector('textarea');
        if (!textarea) return;

        // Auto-resize textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Add character counter
        const maxLength = 1500;
        const counter = document.createElement('div');
        counter.className = 'comment-char-counter';
        counter.textContent = `0 / ${maxLength}`;
        textarea.parentNode.appendChild(counter);

        textarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length} / ${maxLength}`;

            if (length > maxLength) {
                counter.style.color = 'red';
            } else {
                counter.style.color = '';
            }
        });
    };

    // Animate on scroll
    const initScrollAnimations = () => {
        const animatedElements = document.querySelectorAll('.animate-on-scroll');

        if (!animatedElements.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        animatedElements.forEach(el => observer.observe(el));
    };

    // Copy code blocks to clipboard
    const initCodeCopy = () => {
        const codeBlocks = document.querySelectorAll('pre code');

        codeBlocks.forEach(block => {
            const button = document.createElement('button');
            button.className = 'copy-code-btn';
            button.textContent = 'Copy';
            button.setAttribute('aria-label', 'Copy code to clipboard');

            block.parentNode.style.position = 'relative';
            block.parentNode.appendChild(button);

            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(block.textContent);
                    button.textContent = 'Copied!';
                    setTimeout(() => {
                        button.textContent = 'Copy';
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
        });
    };

    // Reading progress bar
    const initReadingProgress = () => {
        if (!document.body.classList.contains('single-post')) return;

        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        document.body.appendChild(progressBar);

        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            progressBar.style.width = scrolled + '%';
        });
    };

    // Dark mode toggle
    const initDarkMode = () => {
        const toggle = document.querySelector('.dark-mode-toggle');
        if (!toggle) return;

        // Check for saved preference or default to light mode
        const currentMode = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentMode);

        toggle.addEventListener('click', () => {
            const currentMode = document.documentElement.getAttribute('data-theme');
            const newMode = currentMode === 'light' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', newMode);
            localStorage.setItem('theme', newMode);
        });
    };

    // Sticky search widget
    const initStickySearchWidget = () => {
        const searchWidget = document.querySelector('.search-widget-wrapper');
        const placeholder = document.querySelector('.search-widget-placeholder');

        if (!searchWidget || !placeholder) return;

        // Get the initial position and height
        let widgetInitialTop = 0;
        let widgetHeight = 0;
        let headerHeight = 80; // Default header height
        let isSticky = false;

        // Calculate positions
        const calculatePositions = () => {
            // Reset sticky state for accurate measurement
            if (isSticky) {
                searchWidget.classList.remove('is-sticky');
                placeholder.classList.remove('is-active');
            }

            const rect = searchWidget.getBoundingClientRect();
            widgetInitialTop = rect.top + window.pageYOffset;
            widgetHeight = rect.height;

            // Get actual header height from element
            const header = document.querySelector('.site-header');
            if (header) {
                headerHeight = header.offsetHeight;
            }

            // Account for WordPress admin bar
            const adminBar = document.getElementById('wpadminbar');
            if (adminBar) {
                headerHeight += adminBar.offsetHeight;
            }

            // Reset sticky state flag
            isSticky = false;
        };

        // Handle scroll
        const handleScroll = () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const triggerPoint = widgetInitialTop - headerHeight;

            if (scrollTop >= triggerPoint && !isSticky) {
                // Make sticky
                searchWidget.classList.add('is-sticky');
                placeholder.classList.add('is-active');
                placeholder.style.height = widgetHeight + 'px';
                isSticky = true;
            } else if (scrollTop < triggerPoint && isSticky) {
                // Remove sticky
                searchWidget.classList.remove('is-sticky');
                placeholder.classList.remove('is-active');
                placeholder.style.height = '0';
                isSticky = false;
            }
        };

        // Initialize
        calculatePositions();

        // Listen to scroll with throttle for performance
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });

        // Recalculate on resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (isSticky) {
                    searchWidget.classList.remove('is-sticky');
                    placeholder.classList.remove('is-active');
                    isSticky = false;
                }
                calculatePositions();
            }, 250);
        });
    };

    // Initialize all features when DOM is ready
    $(document).ready(function() {
        initBackToTop();
        initLazyLoading();
        initLoadMore();
        initResponsiveTables();
        enhanceCommentForm();
        initScrollAnimations();
        initCodeCopy();
        initReadingProgress();
        initDarkMode();
        initStickySearchWidget();
    });

    // Reinitialize features after AJAX content load
    $(document).on('seminargo_content_loaded', function() {
        initLazyLoading();
        initResponsiveTables();
        initScrollAnimations();
        initCodeCopy();
    });

})(jQuery);