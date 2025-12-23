<?php
/**
 * Template Name: FAQ Page
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main faq-page">

        <!-- FAQ Header Section -->
        <section class="faq-header">
            <div class="container">
                <h1 class="faq-page-title">Häufig gestellte Fragen</h1>
                <p class="faq-page-subtitle">Finden Sie schnell Antworten auf Ihre Fragen</p>
            </div>
        </section>

        <!-- FAQ Content Section -->
        <section class="faq-content-section">
            <div class="container">

                <!-- Category Toggle -->
                <div class="faq-category-toggle">
                    <button class="faq-category-btn active" data-category="buchende">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Für Buchende
                    </button>
                    <button class="faq-category-btn" data-category="hotels">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Für Hotels
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="faq-search-wrapper">
                    <div class="faq-search-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="faq-search" placeholder="Suchen Sie nach Stichworten...">
                        <button class="faq-search-clear" id="faq-search-clear" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="faq-search-results-count"></div>
                </div>

                <!-- FAQ Items - Für Buchende -->
                <div class="faq-items-wrapper" data-category="buchende">
                    <?php
                    // Debug: Check what categories exist
                    if (current_user_can('administrator')) {
                        $all_cats = get_terms(array('taxonomy' => 'faq_category', 'hide_empty' => false));
                        echo '<!-- FAQ Categories: ';
                        if (!empty($all_cats) && !is_wp_error($all_cats)) {
                            foreach ($all_cats as $cat) {
                                echo sprintf('%s (slug: %s, id: %d, count: %d) | ', $cat->name, $cat->slug, $cat->term_id, $cat->count);
                            }
                        } else {
                            echo 'No categories found!';
                        }
                        echo ' -->';
                    }

                    // Query FAQs for Buchende category - try multiple possible slugs
                    $buchende_slugs = array('buchende', 'fur-buchende', 'fuer-buchende', 'für-buchende');

                    $faqs_buchende_query = new WP_Query(array(
                        'post_type' => 'faq',
                        'posts_per_page' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'faq_category',
                                'field' => 'slug',
                                'terms' => $buchende_slugs,
                                'operator' => 'IN',
                            ),
                        ),
                    ));

                    if ($faqs_buchende_query->have_posts()) :
                        while ($faqs_buchende_query->have_posts()) : $faqs_buchende_query->the_post(); ?>
                            <div class="faq-item" data-category="buchende">
                                <button class="faq-question" aria-expanded="false">
                                    <span class="faq-question-text"><?php the_title(); ?></span>
                                    <svg class="faq-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <div class="faq-answer">
                                    <div class="faq-answer-content">
                                        <?php the_content(); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                        wp_reset_postdata();
                    else : ?>
                        <p class="no-faqs-message" style="text-align: center; padding: 2rem; color: #6b7280;">
                            <?php _e('Noch keine FAQs für Buchende hinzugefügt.', 'seminargo'); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- FAQ Items - Für Hotels -->
                <div class="faq-items-wrapper" data-category="hotels" style="display: none;">
                    <?php
                    // Query FAQs for Hotels category - try multiple possible slugs
                    $hotels_slugs = array('hotels', 'fur-hotels', 'fuer-hotels', 'für-hotels');

                    $faqs_hotels_query = new WP_Query(array(
                        'post_type' => 'faq',
                        'posts_per_page' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'faq_category',
                                'field' => 'slug',
                                'terms' => $hotels_slugs,
                                'operator' => 'IN',
                            ),
                        ),
                    ));

                    if ($faqs_hotels_query->have_posts()) :
                        while ($faqs_hotels_query->have_posts()) : $faqs_hotels_query->the_post(); ?>
                            <div class="faq-item" data-category="hotels">
                                <button class="faq-question" aria-expanded="false">
                                    <span class="faq-question-text"><?php the_title(); ?></span>
                                    <svg class="faq-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <div class="faq-answer">
                                    <div class="faq-answer-content">
                                        <?php the_content(); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                        wp_reset_postdata();
                    else : ?>
                        <p class="no-faqs-message" style="text-align: center; padding: 2rem; color: #6b7280;">
                            <?php _e('Noch keine FAQs für Hotels hinzugefügt.', 'seminargo'); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- No Results Message -->
                <div class="faq-no-results" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <h3>Keine Ergebnisse gefunden</h3>
                    <p>Versuchen Sie es mit anderen Suchbegriffen oder wählen Sie eine andere Kategorie.</p>
                </div>

            </div>
        </section>

        <!-- FAQ CTA Section -->
        <section class="faq-cta-section">
            <div class="container">
                <div class="faq-cta-content">
                    <h2>Ihre Frage wurde nicht beantwortet?</h2>
                    <p>Unser Team steht Ihnen gerne zur Verfügung</p>
                    <div class="faq-cta-buttons">
                        <a href="mailto:info@seminargo.com" class="btn-faq-contact">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            E-Mail senden
                        </a>
                        <a href="tel:+43190858" class="btn-faq-contact btn-faq-contact-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Jetzt anrufen
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<?php get_footer(); ?>
