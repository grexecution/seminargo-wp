<?php
/**
 * Template Name: Hotel Newsletter Landing Page
 *
 * Simple landing page for hotel newsletter signup
 *
 * @package Seminargo
 */

get_header();
?>

<div class="hotel-newsletter-landing">
    <div class="container" style="max-width: 800px; margin: 80px auto; padding: 0 24px;">

        <!-- Hero Section -->
        <div class="newsletter-hero" style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 48px; font-weight: 700; color: #111827; margin-bottom: 4px; line-height: 1.2;">
                <?php esc_html_e( 'seminargo Hotel Newsletter', 'seminargo' ); ?>
            </h1>
            <p style="font-size: 20px; color: #6b7280; line-height: 1.6; max-width: 700px; margin: 0 auto 32px;">
                <?php esc_html_e( 'Melde dich jetzt zu unserem Newsletter für Hotels & Tagungshotels an.', 'seminargo' ); ?>
            </p>

            <!-- Benefits (Compact) -->
            <div style="background: #f0f9ff; border-left: 4px solid #AC2A6E; padding: 16px 20px; max-width: 600px; margin: 0 auto; border-radius: 6px;">
                <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.7;">
                    <strong style="color: #111827;"><?php esc_html_e( 'Du erhältst:', 'seminargo' ); ?></strong>
                    <?php esc_html_e( 'MICE-Trends, Updates zu Buchungen & Nachfrage, Insights aus der Hotelzusammenarbeit und Neuigkeiten rund um seminargo.', 'seminargo' ); ?>
                </p>
            </div>
        </div>

        <!-- Newsletter Form Card -->
        <div class="newsletter-form-card" style="background: white; border-radius: 16px; padding: 48px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
            <h2 style="font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 8px; text-align: center;">
                <?php esc_html_e( 'Jetzt anmelden', 'seminargo' ); ?>
            </h2>
            <p style="text-align: center; color: #6b7280; margin-bottom: 32px;">
                <?php esc_html_e( 'Füllen Sie das Formular aus, um sich zum Newsletter anzumelden', 'seminargo' ); ?>
            </p>

            <form id="hotel-newsletter-form" class="hotel-newsletter-form">
                <!-- Vorname & Nachname (2 columns) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="hotel-vorname" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
                            <?php esc_html_e( 'Vorname', 'seminargo' ); ?> *
                        </label>
                        <input type="text" id="hotel-vorname" name="vorname" required
                            style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; transition: all 0.2s;"
                            onfocus="this.style.borderColor='#AC2A6E'; this.style.boxShadow='0 0 0 3px rgba(172, 42, 110, 0.1)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>
                    <div class="form-group">
                        <label for="hotel-nachname" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
                            <?php esc_html_e( 'Nachname', 'seminargo' ); ?> *
                        </label>
                        <input type="text" id="hotel-nachname" name="nachname" required
                            style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; transition: all 0.2s;"
                            onfocus="this.style.borderColor='#AC2A6E'; this.style.boxShadow='0 0 0 3px rgba(172, 42, 110, 0.1)'"
                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>
                </div>

                <!-- E-Mail -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="hotel-email" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
                        <?php esc_html_e( 'E-Mail-Adresse', 'seminargo' ); ?> *
                    </label>
                    <input type="email" id="hotel-email" name="email" required
                        style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#AC2A6E'; this.style.boxShadow='0 0 0 3px rgba(172, 42, 110, 0.1)'"
                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                </div>

                <!-- Hotelname -->
                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="hotel-name" style="display: block; margin-bottom: 8px; font-weight: 500; color: #374151; font-size: 14px;">
                        <?php esc_html_e( 'Hotelname', 'seminargo' ); ?> *
                    </label>
                    <input type="text" id="hotel-name" name="hotelname" required
                        style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#AC2A6E'; this.style.boxShadow='0 0 0 3px rgba(172, 42, 110, 0.1)'"
                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                </div>

                <!-- Privacy Checkbox -->
                <div class="form-group" style="margin-bottom: 32px;">
                    <label style="display: flex; align-items: start; cursor: pointer;">
                        <input type="checkbox" name="privacy" required style="margin-right: 10px; margin-top: 2px; width: 20px; height: 20px; cursor: pointer;">
                        <span style="font-size: 14px; color: #374151; line-height: 1.5;">
                            <?php
                            printf(
                                esc_html__( 'Ich akzeptiere die %sDatenschutzerklärung%s und möchte den Newsletter erhalten.', 'seminargo' ),
                                '<a href="' . esc_url( home_url( '/datenschutz' ) ) . '" target="_blank" style="color: #AC2A6E; text-decoration: underline;">',
                                '</a>'
                            );
                            ?> *
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="hotel-submit-btn"
                    style="width: 100%; padding: 16px 32px; background: linear-gradient(135deg, #AC2A6E 0%, #8A1F56 100%); color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(172, 42, 110, 0.3);"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(172, 42, 110, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(172, 42, 110, 0.3)'">
                    <?php esc_html_e( 'Zum Newsletter anmelden', 'seminargo' ); ?>
                </button>

                <!-- Message Area -->
                <div id="hotel-newsletter-message" style="margin-top: 24px; padding: 16px; border-radius: 8px; display: none;"></div>
            </form>
        </div>

        <!-- Additional Info -->
        <div style="text-align: center; margin-top: 48px; color: #6b7280; font-size: 14px;">
            <p><?php esc_html_e( 'Sie können sich jederzeit wieder abmelden. Ihre Daten werden vertraulich behandelt.', 'seminargo' ); ?></p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#hotel-newsletter-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $('#hotel-submit-btn');
        var $message = $('#hotel-newsletter-message');

        // Get form data
        var formData = {
            action: 'seminargo_hotel_newsletter_signup',
            vorname: $('#hotel-vorname').val(),
            nachname: $('#hotel-nachname').val(),
            email: $('#hotel-email').val(),
            hotelname: $('#hotel-name').val()
        };

        // Disable submit
        $submitBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Wird gesendet...', 'seminargo' ) ); ?>');
        $message.hide();

        // Submit via AJAX
        $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', formData, function(response) {
            $submitBtn.prop('disabled', false).text('<?php echo esc_js( __( 'Zum Newsletter anmelden', 'seminargo' ) ); ?>');

            if (response.success) {
                // Success
                $message
                    .css({
                        'background': '#d1fae5',
                        'color': '#065f46',
                        'border-left': '4px solid #10b981'
                    })
                    .html('✅ ' + response.data.message)
                    .slideDown();

                // Clear form
                $form[0].reset();

                // Scroll to message
                $('html, body').animate({
                    scrollTop: $message.offset().top - 100
                }, 500);
            } else {
                // Error
                $message
                    .css({
                        'background': '#fef2f2',
                        'color': '#991b1b',
                        'border-left': '4px solid #dc2626'
                    })
                    .html('❌ ' + response.data.message)
                    .slideDown();
            }
        }).fail(function() {
            $submitBtn.prop('disabled', false).text('<?php echo esc_js( __( 'Zum Newsletter anmelden', 'seminargo' ) ); ?>');

            $message
                .css({
                    'background': '#fef2f2',
                    'color': '#991b1b',
                    'border-left': '4px solid #dc2626'
                })
                .html('❌ <?php echo esc_js( __( 'Serverfehler. Bitte versuchen Sie es später erneut.', 'seminargo' ) ); ?>')
                .slideDown();
        });
    });
});
</script>

<style>
/* Responsive adjustments */
@media (max-width: 640px) {
    .hotel-newsletter-landing h1 {
        font-size: 32px !important;
    }

    .hotel-newsletter-landing .newsletter-hero p {
        font-size: 16px !important;
    }

    .newsletter-form-card {
        padding: 32px 24px !important;
    }

    .hotel-newsletter-form > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php
get_footer();
