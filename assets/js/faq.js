/**
 * FAQ Page JavaScript
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

                // Toggle current FAQ
                this.setAttribute('aria-expanded', !isExpanded);

                // Smooth height animation
                const answer = this.nextElementSibling;
                if (!isExpanded) {
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                } else {
                    answer.style.maxHeight = '0';
                }
            });
        });
    };

    // Category Toggle
    const initCategoryToggle = () => {
        const categoryBtns = document.querySelectorAll('.faq-category-btn');
        const faqWrappers = document.querySelectorAll('.faq-items-wrapper');

        if (!categoryBtns.length) return;

        categoryBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.getAttribute('data-category');

                // Update active button
                categoryBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Show/hide FAQ sections
                faqWrappers.forEach(wrapper => {
                    if (wrapper.getAttribute('data-category') === category) {
                        wrapper.style.display = 'block';
                    } else {
                        wrapper.style.display = 'none';
                    }
                });

                // Reset search
                const searchInput = document.getElementById('faq-search');
                if (searchInput) {
                    searchInput.value = '';
                    filterFAQs('');
                }
            });
        });
    };

    // Search Functionality
    const initSearch = () => {
        const searchInput = document.getElementById('faq-search');
        const clearBtn = document.getElementById('faq-search-clear');

        if (!searchInput) return;

        // Search on input
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            filterFAQs(query);

            // Show/hide clear button
            if (clearBtn) {
                clearBtn.style.display = query ? 'flex' : 'none';
            }
        });

        // Clear search
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterFAQs('');
                this.style.display = 'none';
                searchInput.focus();
            });
        }
    };

    // Filter FAQs
    const filterFAQs = (query) => {
        const activeCategoryBtn = document.querySelector('.faq-category-btn.active');
        const activeCategory = activeCategoryBtn ? activeCategoryBtn.getAttribute('data-category') : 'buchende';

        const faqItems = document.querySelectorAll(`.faq-item[data-category="${activeCategory}"]`);
        const noResults = document.querySelector('.faq-no-results');
        const resultsCount = document.querySelector('.faq-search-results-count');

        let visibleCount = 0;

        faqItems.forEach(item => {
            const questionText = item.querySelector('.faq-question-text').textContent.toLowerCase();
            const answerText = item.querySelector('.faq-answer-content').textContent.toLowerCase();

            if (query === '' || questionText.includes(query) || answerText.includes(query)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (noResults) {
            noResults.style.display = (visibleCount === 0 && query !== '') ? 'flex' : 'none';
        }

        // Update results count
        if (resultsCount) {
            if (query && visibleCount > 0) {
                resultsCount.textContent = `${visibleCount} Ergebnis${visibleCount !== 1 ? 'se' : ''} gefunden`;
                resultsCount.style.display = 'block';
            } else {
                resultsCount.style.display = 'none';
            }
        }
    };

    // Initialize all features
    const init = () => {
        initFAQAccordion();
        initCategoryToggle();
        initSearch();
    };

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
