<?php
/**
 * Template Name: Kontakt
 * Template for displaying the contact page
 *
 * @package Seminargo
 */

get_header();

// Get contact settings
$contact_settings = get_option( 'seminargo_contact_settings', [] );
$recipient_email = ! empty( $contact_settings['recipient_email'] ) ? $contact_settings['recipient_email'] : get_option( 'admin_email' );
$cc_email = $contact_settings['cc_email'] ?? '';
$from_email = ! empty( $contact_settings['from_email'] ) ? $contact_settings['from_email'] : 'noreply@' . parse_url( home_url(), PHP_URL_HOST );
$from_name = ! empty( $contact_settings['from_name'] ) ? $contact_settings['from_name'] : get_bloginfo( 'name' );
$subject_template = ! empty( $contact_settings['email_subject'] ) ? $contact_settings['email_subject'] : __( 'Neue Kontaktanfrage von {name}', 'seminargo' );

// Handle form submission
$form_submitted = false;
$form_error = false;
$form_message = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['seminargo_contact_nonce'] ) ) {
    if ( wp_verify_nonce( $_POST['seminargo_contact_nonce'], 'seminargo_contact_form' ) ) {
        $name = sanitize_text_field( $_POST['contact_name'] ?? '' );
        $email = sanitize_email( $_POST['contact_email'] ?? '' );
        $phone = sanitize_text_field( $_POST['contact_phone'] ?? '' );
        $subject = sanitize_text_field( $_POST['contact_subject'] ?? '' );
        $message = sanitize_textarea_field( $_POST['contact_message'] ?? '' );

        if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
            $form_error = true;
            $form_message = __( 'Bitte füllen Sie alle Pflichtfelder aus.', 'seminargo' );
        } elseif ( ! is_email( $email ) ) {
            $form_error = true;
            $form_message = __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'seminargo' );
        } else {
            // Build email subject
            $email_subject = ! empty( $subject ) ? $subject : str_replace( '{name}', $name, $subject_template );

            // Build email body
            $body = sprintf(
                "Name: %s\nE-Mail: %s\nTelefon: %s\nBetreff: %s\n\nNachricht:\n%s\n\n---\nGesendet über das Kontaktformular auf %s",
                $name,
                $email,
                $phone ?: '-',
                $subject ?: '-',
                $message,
                home_url()
            );

            // Build headers
            $headers = [
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>',
                'Reply-To: ' . $name . ' <' . $email . '>',
            ];

            if ( ! empty( $cc_email ) ) {
                $headers[] = 'Cc: ' . $cc_email;
            }

            // Send email
            $sent = wp_mail( $recipient_email, $email_subject, $body, $headers );

            if ( $sent ) {
                $form_submitted = true;
                $form_message = __( 'Vielen Dank für Ihre Nachricht! Wir werden uns schnellstmöglich bei Ihnen melden.', 'seminargo' );
            } else {
                $form_error = true;
                $form_message = __( 'Es gab einen Fehler beim Senden Ihrer Nachricht. Bitte versuchen Sie es später erneut.', 'seminargo' );
            }
        }
    }
}
?>

<div id="primary" class="content-area contact-page">
    <main id="main" class="site-main">

        <!-- Hero Section -->
        <section class="contact-hero">
            <div class="container">
                <div class="contact-hero-content">
                    <h1 class="contact-hero-title"><?php esc_html_e( 'Kontakt', 'seminargo' ); ?></h1>
                    <p class="contact-hero-subtitle"><?php esc_html_e( 'Wie können wir Ihnen weiterhelfen? Wir freuen uns auf Ihre Kontaktaufnahme.', 'seminargo' ); ?></p>
                    <p class="contact-hours">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php esc_html_e( 'Montag – Donnerstag: 08:00 – 18:00 Uhr | Freitag: 08:00 – 14:00 Uhr', 'seminargo' ); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Cards Section -->
        <section class="contact-cards-section">
            <div class="container">
                <div class="contact-cards-grid">
                    <!-- Email Card -->
                    <a href="mailto:office@seminargo.com" class="contact-card">
                        <div class="contact-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div class="contact-card-content">
                            <h3><?php esc_html_e( 'E-Mail', 'seminargo' ); ?></h3>
                            <p>office@seminargo.com</p>
                        </div>
                    </a>

                    <!-- Phone Card -->
                    <a href="tel:+43190858" class="contact-card">
                        <div class="contact-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <div class="contact-card-content">
                            <h3><?php esc_html_e( 'Telefon', 'seminargo' ); ?></h3>
                            <p>+43/1/90 858</p>
                        </div>
                    </a>

                    <!-- Chat Card -->
                    <div class="contact-card" onclick="if(typeof smartsupp !== 'undefined') smartsupp('chat:open');" style="cursor: pointer;">
                        <div class="contact-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="contact-card-content">
                            <h3><?php esc_html_e( 'Live Chat', 'seminargo' ); ?></h3>
                            <p><?php esc_html_e( 'Sofort verfügbar', 'seminargo' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Contact Section -->
        <section class="contact-main-section">
            <div class="container">
                <div class="contact-grid">

                    <!-- Contact Form -->
                    <div class="contact-form-wrapper">
                        <h2><?php esc_html_e( 'Schreiben Sie uns', 'seminargo' ); ?></h2>
                        <p class="form-intro"><?php esc_html_e( 'Füllen Sie das Formular aus und wir melden uns innerhalb von 24 Stunden bei Ihnen.', 'seminargo' ); ?></p>

                        <?php if ( $form_submitted ) : ?>
                            <div class="form-message form-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <span><?php echo esc_html( $form_message ); ?></span>
                            </div>
                        <?php elseif ( $form_error ) : ?>
                            <div class="form-message form-error">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                <span><?php echo esc_html( $form_message ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! $form_submitted ) : ?>
                        <form class="contact-form" method="post" action="">
                            <?php wp_nonce_field( 'seminargo_contact_form', 'seminargo_contact_nonce' ); ?>

                            <div class="form-row form-row-half">
                                <div class="form-group">
                                    <label for="contact_name"><?php esc_html_e( 'Name', 'seminargo' ); ?> <span class="required">*</span></label>
                                    <input type="text" id="contact_name" name="contact_name" required placeholder="<?php esc_attr_e( 'Ihr Name', 'seminargo' ); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="contact_email"><?php esc_html_e( 'E-Mail', 'seminargo' ); ?> <span class="required">*</span></label>
                                    <input type="email" id="contact_email" name="contact_email" required placeholder="<?php esc_attr_e( 'ihre@email.com', 'seminargo' ); ?>">
                                </div>
                            </div>

                            <div class="form-row form-row-half">
                                <div class="form-group">
                                    <label for="contact_phone"><?php esc_html_e( 'Telefon', 'seminargo' ); ?></label>
                                    <input type="tel" id="contact_phone" name="contact_phone" placeholder="<?php esc_attr_e( '+43 1 234 567', 'seminargo' ); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="contact_subject"><?php esc_html_e( 'Betreff', 'seminargo' ); ?></label>
                                    <input type="text" id="contact_subject" name="contact_subject" placeholder="<?php esc_attr_e( 'Wie können wir helfen?', 'seminargo' ); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="contact_message"><?php esc_html_e( 'Nachricht', 'seminargo' ); ?> <span class="required">*</span></label>
                                <textarea id="contact_message" name="contact_message" rows="5" required placeholder="<?php esc_attr_e( 'Ihre Nachricht an uns...', 'seminargo' ); ?>"></textarea>
                            </div>

                            <div class="form-group form-submit">
                                <button type="submit" class="btn-submit">
                                    <?php esc_html_e( 'Nachricht senden', 'seminargo' ); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Contact Info & Map -->
                    <div class="contact-info-wrapper">

                        <!-- Office Locations -->
                        <div class="office-locations">
                            <h2><?php esc_html_e( 'Unsere Standorte', 'seminargo' ); ?></h2>

                            <!-- Vienna Office -->
                            <div class="office-card">
                                <div class="office-flag">
                                    <span class="flag-at">AT</span>
                                </div>
                                <div class="office-details">
                                    <h3>seminargo GmbH</h3>
                                    <address>
                                        Liebhartsgasse 16<br>
                                        1160 Wien, Österreich
                                    </address>
                                    <p class="office-contact">
                                        <a href="tel:+43190858">+43/1/90 858</a>
                                    </p>
                                </div>
                            </div>

                            <!-- Munich Office -->
                            <div class="office-card">
                                <div class="office-flag">
                                    <span class="flag-de">DE</span>
                                </div>
                                <div class="office-details">
                                    <h3>seminargo Deutschland GmbH</h3>
                                    <address>
                                        Hermann-Weinhauser-Straße 73<br>
                                        81673 München, Deutschland
                                    </address>
                                    <p class="office-contact">
                                        <a href="tel:+498970074169">+49 89 700 741 69</a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="contact-map-wrapper">
                            <div id="contact-map" class="contact-map"></div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="location-finder-cta">
            <div class="container">
                <div class="cta-content">
                    <div class="cta-text">
                        <h2><?php esc_html_e( 'Lieber persönlich sprechen?', 'seminargo' ); ?></h2>
                        <p><?php esc_html_e( 'Unser Team berät Sie gerne telefonisch oder per Video-Call zu all Ihren Fragen rund um Seminarhotels.', 'seminargo' ); ?></p>
                    </div>
                    <div class="cta-actions">
                        <a href="tel:+43190858" class="button button-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <?php esc_html_e( 'Jetzt anrufen', 'seminargo' ); ?>
                        </a>
                        <a href="mailto:office@seminargo.com?subject=Termin-Anfrage%20für%20Online-Meeting" class="button button-outline-white">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <?php esc_html_e( 'Termin vereinbaren', 'seminargo' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map centered between Vienna and Munich
    var map = L.map('contact-map').setView([48.5, 12.5], 6);

    // Use CartoDB Positron - clean, modern style matching hotel pages
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // Custom marker icon
    var markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div class="marker-pin"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#AC2A6E" stroke="#fff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3" fill="#fff"></circle></svg></div>',
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -40]
    });

    // Vienna marker
    L.marker([48.2082, 16.3738], {icon: markerIcon})
        .addTo(map)
        .bindPopup('<strong>seminargo GmbH</strong><br>Liebhartsgasse 16<br>1160 Wien');

    // Munich marker
    L.marker([48.1351, 11.5820], {icon: markerIcon})
        .addTo(map)
        .bindPopup('<strong>seminargo Deutschland GmbH</strong><br>Hermann-Weinhauser-Straße 73<br>81673 München');
});
</script>

<?php get_footer(); ?>
