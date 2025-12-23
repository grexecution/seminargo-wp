<?php
/**
 * Template Name: Preise
 * Template for Pricing/Preise Page
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <!-- Hero Section -->
        <section class="solutions-hero">
            <div class="container">
                <div class="solutions-hero-content">
                    <h1 class="solutions-hero-title">Angebot & Preise</h1>
                    <p class="solutions-hero-subtitle">Sie wollen den Buchungsprozess optimieren, suchen einen direkten Preisvergleich oder möchten einheitliche Rechnungen von einem Lieferanten? Wir haben die optimale Lösung für Ihre Tagungsorganisation.</p>
                    <p class="solutions-hero-text">Von einfacher und kostenloser Tagungshotelvermittlung bis hin zur Abwicklung des gesamten Beschaffungsprozesses inkl. Abrechnung und Teilnehmermanagement für Power-User ist für Sie alles dabei.</p>
                </div>
            </div>
        </section>

        <!-- Pricing Table Section -->
        <section class="solutions-pricing-section">
            <div class="container">
                <div class="pricing-table-wrapper">
                    <table class="pricing-table">
                        <thead>
                            <tr>
                                <th class="pricing-header-feature">Leistungsumfang</th>
                                <th class="pricing-header-plan">
                                    <div class="plan-name">Vermittlung Basic</div>
                                </th>
                                <th class="pricing-header-plan">
                                    <div class="plan-name">Premium Light</div>
                                </th>
                                <th class="pricing-header-plan highlighted">
                                    <div class="plan-name">Premium Power-User</div>
                                    <div class="plan-badge">Beliebt</div>
                                </th>
                                <th class="pricing-header-plan">
                                    <div class="plan-name">Individual</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Service Scope -->
                            <tr>
                                <td class="feature-label">Leistungsumfang</td>
                                <td>kostenlos auf seminargo.com suchen und buchen</td>
                                <td>gesamter Buchungsprozess inkl. Abrechnung über seminargo.com zu seminargo-Vertragskonditionen</td>
                                <td class="highlighted">gesamter Buchungsprozess inkl. Abrechnung über seminargo.com zu seminargo-Vertragskonditionen</td>
                                <td>gesamter Buchungsprozess inkl. Abrechnung über seminargo.com zu seminargo-Vertragskonditionen</td>
                            </tr>

                            <!-- Contract Between -->
                            <tr>
                                <td class="feature-label">Vertrag zwischen</td>
                                <td>Ihnen & Hotel</td>
                                <td>Ihnen & seminargo</td>
                                <td class="highlighted">Ihnen & seminargo</td>
                                <td>Ihnen & seminargo</td>
                            </tr>

                            <!-- Access -->
                            <tr>
                                <td class="feature-label">Zugang</td>
                                <td>
                                    <div class="access-item">Registrierung auf seminargo.com</div>
                                    <div class="access-item">Firmen-Account mit Rechteverwaltung</div>
                                </td>
                                <td>
                                    <div class="access-item">Registrierung auf seminargo.com</div>
                                    <div class="access-item">Firmen-Account mit Rechteverwaltung</div>
                                </td>
                                <td class="highlighted">
                                    <div class="access-item">Registrierung auf seminargo.com</div>
                                    <div class="access-item">Firmen-Account mit Rechteverwaltung</div>
                                </td>
                                <td>
                                    <div class="access-item">Registrierung auf seminargo.com</div>
                                    <div class="access-item">Firmen-Account mit Rechteverwaltung</div>
                                </td>
                            </tr>

                            <!-- Prices per Click -->
                            <tr>
                                <td class="feature-label">Preise (pro Klick)</td>
                                <td>BAR <sup>1</sup></td>
                                <td>seminargo-Rate <sup>2</sup></td>
                                <td class="highlighted">seminargo-Rate <sup>2</sup></td>
                                <td>Firmentarif</td>
                            </tr>

                            <!-- Cancellation -->
                            <tr>
                                <td class="feature-label">Stornierung</td>
                                <td>(hotel-) individuelle Bedingungen</td>
                                <td>DE &gt; 29 Tage: 0%<br>AT: &gt; 15 Tage: 0% <sup>3</sup></td>
                                <td class="highlighted">DE &gt; 29 Tage: 0%<br>AT: &gt; 15 Tage: 0% <sup>3</sup></td>
                                <td>Unternehmens-bedingungen</td>
                            </tr>

                            <!-- Invoicing -->
                            <tr>
                                <td class="feature-label">Rechnungsstellung</td>
                                <td>—</td>
                                <td>alle Funktionen</td>
                                <td class="highlighted">alle Funktionen</td>
                                <td>alle Funktionen</td>
                            </tr>

                            <!-- Setup Fee Unique -->
                            <tr>
                                <td class="feature-label">Setup-Gebühr (Einmalig)</td>
                                <td>—</td>
                                <td>—</td>
                                <td class="highlighted">€ 2.900,- zzgl. USt.</td>
                                <td>€ 2.900,- zzgl. USt.</td>
                            </tr>

                            <!-- Setup Fee Monthly -->
                            <tr>
                                <td class="feature-label">Servicepauschale (Monatlich)</td>
                                <td>—</td>
                                <td>—</td>
                                <td class="highlighted">€ 250,- zzgl. USt.</td>
                                <td>€ 250,- zzgl. USt.</td>
                            </tr>

                            <!-- Processing Fee -->
                            <tr>
                                <td class="feature-label">Bearbeitungsgebühr</td>
                                <td>—</td>
                                <td>6% <sup>4</sup></td>
                                <td class="highlighted">—</td>
                                <td>6% <sup>4</sup></td>
                            </tr>

                            <!-- Support -->
                            <tr>
                                <td class="feature-label">Support</td>
                                <td>Meeting Planner</td>
                                <td>Persönliche/r Assistent/in</td>
                                <td class="highlighted">Persönliche/r Assistent/in(nen)</td>
                                <td>Persönliche/r Assistent/in(nen)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Single CTA -->
                <div class="pricing-single-cta">
                    <a href="<?php echo esc_url(home_url('/kontakt')); ?>" class="btn-pricing-main">
                        Jetzt Beratungsgespräch vereinbaren
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>

                <!-- Footnotes -->
                <div class="pricing-footnotes">
                    <div class="footnote-item">
                        <sup>1</sup> BAR = Best Available Rate / Regulärer Verkaufstarif
                    </div>
                    <div class="footnote-item">
                        <sup>2</sup> seminargo-Rate = Ausgehandelte, günstigere Tarife in Vertragshotels
                    </div>
                    <div class="footnote-item">
                        <sup>3</sup> DE: Stornierung bis 29 Kalendertage vor Veranstaltungsbeginn = keine Stornogebühr | AT: Stornierung bis 15 Kalendertage vor Veranstaltungsbeginn = keine Stornogebühr
                    </div>
                    <div class="footnote-item">
                        <sup>4</sup> Auf den Bruttorechnungsbetrag
                    </div>
                </div>
            </div>
        </section>

        <!-- Consultant Section -->
        <section class="solutions-consultant-section">
            <div class="container">
                <?php
                // Get Andreas Kernreiter from team custom post type
                $andreas_query = new WP_Query(array(
                    'post_type' => 'team',
                    'posts_per_page' => 1,
                    'title' => 'Andreas Kernreiter'
                ));

                $andreas_image = '';
                $andreas_position = 'CEO';

                if ($andreas_query->have_posts()) {
                    while ($andreas_query->have_posts()) {
                        $andreas_query->the_post();
                        $andreas_image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        $position_field = get_post_meta(get_the_ID(), 'position', true);
                        if ($position_field) {
                            $andreas_position = $position_field;
                        }
                    }
                    wp_reset_postdata();
                }
                ?>
                <div class="consultant-card">
                    <div class="consultant-image-wrapper">
                        <?php if ($andreas_image) : ?>
                            <img src="<?php echo esc_url($andreas_image); ?>"
                                 alt="Andreas Kernreiter - <?php echo esc_attr($andreas_position); ?>"
                                 class="consultant-image">
                        <?php else : ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/andreas-kernreiter.jpg"
                                 alt="Andreas Kernreiter - CEO"
                                 class="consultant-image"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Andreas+Kernreiter&size=400&background=AC2A6E&color=fff&font-size=0.4'">
                        <?php endif; ?>
                    </div>
                    <div class="consultant-content">
                        <div class="consultant-role"><?php echo esc_html($andreas_position); ?></div>
                        <h3 class="consultant-name">Andreas Kernreiter</h3>
                        <a href="mailto:office@seminargo.com" class="consultant-email">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            office@seminargo.com
                        </a>
                        <p class="consultant-text">Sie haben Fragen zu unseren Lösungen? Wir nehmen uns gerne Zeit für Sie und beraten Sie!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="solutions-final-cta">
            <div class="container">
                <div class="final-cta-content">
                    <h2 class="final-cta-title">Bereit für optimierte Tagungsorganisation?</h2>
                    <p class="final-cta-text">Starten Sie noch heute mit seminargo und erleben Sie die Vorteile unserer professionellen Buchungsplattform.</p>
                    <div class="final-cta-actions">
                        <a href="<?php echo esc_url(home_url('/registrierung')); ?>" class="btn-final-cta btn-final-cta-primary">
                            Kostenlos registrieren
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url(home_url('/kontakt')); ?>" class="btn-final-cta btn-final-cta-secondary">
                            Beratung vereinbaren
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<?php get_footer(); ?>
