<?php
/**
 * The template for displaying the footer
 *
 * @package Seminargo
 */
?>

    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="footer-main">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-grid">
                        <!-- Company Info Column -->
                        <div class="footer-column footer-company">
                            <h3 class="footer-title"><?php esc_html_e( 'seminargo GmbH', 'seminargo' ); ?></h3>
                            <div class="footer-company-info">
                                <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                                    <?php dynamic_sidebar( 'footer-1' ); ?>
                                <?php else : ?>
                                    <p><?php esc_html_e( 'Ihr Partner für unvergessliche Events und Seminare.', 'seminargo' ); ?></p>
                                <?php endif; ?>

                                <!-- Social Media Links -->
                                <div class="footer-social-links">
                                    <a href="https://linkedin.com/company/seminargo-gmbh" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="footer-social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                        </svg>
                                    </a>
                                    <a href="https://www.instagram.com/seminargo_/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="footer-social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                    </a>
                                    <a href="https://www.facebook.com/seminargo.gmbh/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="footer-social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
                                        </svg>
                                    </a>
                                    <a href="https://www.youtube.com/@seminargo-nextlevelbooking" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="footer-social-link">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links Column -->
                        <div class="footer-column footer-links">
                            <h3 class="footer-title"><?php esc_html_e( 'Für Firmen', 'seminargo' ); ?></h3>
                            <?php if ( has_nav_menu( 'footer' ) ) : ?>
                                <?php
                                wp_nav_menu( array(
                                    'theme_location' => 'footer',
                                    'menu_class'     => 'footer-menu',
                                    'container'      => false,
                                    'depth'          => 1,
                                    'fallback_cb'    => false,
                                ) );
                                ?>
                            <?php else : ?>
                                <ul class="footer-menu">
                                    <li><a href="#"><?php esc_html_e( 'Partner werden', 'seminargo' ); ?></a></li>
                                    <li><a href="#"><?php esc_html_e( 'Werbung', 'seminargo' ); ?></a></li>
                                    <li><a href="#"><?php esc_html_e( 'Hilfe/FAQ', 'seminargo' ); ?></a></li>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Bookings Column -->
                        <div class="footer-column footer-booking">
                            <h3 class="footer-title"><?php esc_html_e( 'Für Buchende', 'seminargo' ); ?></h3>
                            <?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
                                <?php dynamic_sidebar( 'footer-2' ); ?>
                            <?php else : ?>
                                <ul class="footer-menu">
                                    <li><a href="<?php echo esc_url( home_url( '/seminarhotels' ) ); ?>"><?php esc_html_e( 'Unsere Seminarhotels', 'seminargo' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( home_url( '/faq' ) ); ?>"><?php esc_html_e( 'FAQ', 'seminargo' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( home_url( '/partner' ) ); ?>"><?php esc_html_e( 'Partner', 'seminargo' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( home_url( '/kontakt' ) ); ?>"><?php esc_html_e( 'Kontakt', 'seminargo' ); ?></a></li>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Newsletter Column -->
                        <div class="footer-column footer-newsletter">
                            <?php if ( is_active_sidebar( 'footer-3' ) ) : ?>
                                <?php dynamic_sidebar( 'footer-3' ); ?>
                            <?php else : ?>
                                <div class="newsletter-signup">
                                    <p class="newsletter-text"><?php esc_html_e( 'Bleiben Sie informiert über Locations und Angebote.', 'seminargo' ); ?></p>
                                    <form class="newsletter-form" id="footer-newsletter-form" action="#" method="post">
                                        <input type="email" class="newsletter-email" id="footer-newsletter-email" placeholder="<?php esc_attr_e( 'E-Mail-Adresse', 'seminargo' ); ?>" required>
                                        <button type="submit" class="newsletter-submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-inner">
                    <div class="footer-copyright">
                        <p class="footer-addresses">
                            DE-81673 München, Hermann-Weinhauser-Straße 73 | Tel: +49 89 700 741 69<br>
                            AT-1160 Wien, Liebhartsgasse 16 | Tel: +43/1/90 858
                        </p>
                        <p class="footer-copy">
                            &copy; 2025 seminargo.com | Alle Rechte vorbehalten (Version 1.1.0)
                        </p>
                        <p class="footer-legal">
                            <a href="<?php echo esc_url( home_url( '/agb-kunden/' ) ); ?>"><?php esc_html_e( 'AGBs', 'seminargo' ); ?></a>
                            <span class="separator">|</span>
                            <a href="<?php echo esc_url( home_url( '/datenschutz' ) ); ?>"><?php esc_html_e( 'Datenschutz', 'seminargo' ); ?></a>
                            <span class="separator">|</span>
                            <a href="<?php echo esc_url( home_url( '/impressum' ) ); ?>"><?php esc_html_e( 'Impressum', 'seminargo' ); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div><!-- .footer-bottom -->
    </footer><!-- #colophon -->

</div><!-- #page -->

<!-- Newsletter Modal -->
<div id="newsletter-modal" class="newsletter-modal" style="display: none;">
    <div class="newsletter-modal-overlay"></div>
    <div class="newsletter-modal-content">
        <button class="newsletter-modal-close" id="newsletter-modal-close" aria-label="<?php esc_attr_e( 'Close', 'seminargo' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="newsletter-modal-header">
            <h2><?php esc_html_e( 'Newsletter abonnieren', 'seminargo' ); ?></h2>
            <p><?php esc_html_e( 'Erhalten Sie Updates zu neuen Locations und exklusiven Angeboten.', 'seminargo' ); ?></p>
        </div>

        <form id="newsletter-modal-form" class="newsletter-modal-form">
            <div class="form-group">
                <label for="newsletter-anrede"><?php esc_html_e( 'Anrede', 'seminargo' ); ?> *</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="anrede" value="Herr" required>
                        <span><?php esc_html_e( 'Herr', 'seminargo' ); ?></span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="anrede" value="Frau" required>
                        <span><?php esc_html_e( 'Frau', 'seminargo' ); ?></span>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="newsletter-vorname"><?php esc_html_e( 'Vorname', 'seminargo' ); ?> *</label>
                    <input type="text" id="newsletter-vorname" name="vorname" required>
                </div>
                <div class="form-group">
                    <label for="newsletter-nachname"><?php esc_html_e( 'Nachname', 'seminargo' ); ?> *</label>
                    <input type="text" id="newsletter-nachname" name="nachname" required>
                </div>
            </div>

            <div class="form-group">
                <label for="newsletter-email-modal"><?php esc_html_e( 'E-Mail', 'seminargo' ); ?> *</label>
                <input type="email" id="newsletter-email-modal" name="email" required readonly>
            </div>

            <div class="form-group">
                <label for="newsletter-country"><?php esc_html_e( 'Land', 'seminargo' ); ?> *</label>
                <select id="newsletter-country" name="country" required>
                    <option value=""><?php esc_html_e( 'Bitte wählen...', 'seminargo' ); ?></option>
                    <option value="DE"><?php esc_html_e( 'Deutschland', 'seminargo' ); ?></option>
                    <option value="AT"><?php esc_html_e( 'Österreich', 'seminargo' ); ?></option>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="privacy" required>
                    <span><?php
                        printf(
                            esc_html__( 'Ich akzeptiere die %sDatenschutzerklärung%s', 'seminargo' ),
                            '<a href="' . esc_url( home_url( '/datenschutz' ) ) . '" target="_blank">',
                            '</a>'
                        );
                    ?> *</span>
                </label>
            </div>

            <div class="newsletter-modal-actions">
                <button type="button" class="button-secondary" id="newsletter-cancel"><?php esc_html_e( 'Abbrechen', 'seminargo' ); ?></button>
                <button type="submit" class="button-primary" id="newsletter-submit-btn"><?php esc_html_e( 'Anmelden', 'seminargo' ); ?></button>
            </div>

            <div id="newsletter-message" class="newsletter-message" style="display: none;"></div>
        </form>
    </div>
</div>

<!-- Back to top button -->
<button id="back-to-top" class="back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'seminargo' ); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</button>

<!-- Newsletter Modal Styles -->
<style>
.newsletter-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.newsletter-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.newsletter-modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 32px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.newsletter-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.newsletter-modal-close:hover {
    background: #f3f4f6;
    color: #111827;
}

.newsletter-modal-header {
    margin-bottom: 24px;
}

.newsletter-modal-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: #111827;
}

.newsletter-modal-header p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.newsletter-modal-form .form-group {
    margin-bottom: 20px;
}

.newsletter-modal-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
    font-size: 14px;
}

.newsletter-modal-form input[type="text"],
.newsletter-modal-form input[type="email"],
.newsletter-modal-form select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.2s;
}

.newsletter-modal-form input[type="text"]:focus,
.newsletter-modal-form input[type="email"]:focus,
.newsletter-modal-form select:focus {
    outline: none;
    border-color: #AC2A6E;
    box-shadow: 0 0 0 3px rgba(172, 42, 110, 0.1);
}

.newsletter-modal-form input[readonly] {
    background: #f9fafb;
    color: #6b7280;
}

.newsletter-modal-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.newsletter-modal-form .radio-group {
    display: flex;
    gap: 16px;
}

.newsletter-modal-form .radio-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.newsletter-modal-form .radio-label input[type="radio"] {
    margin-right: 8px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.newsletter-modal-form .radio-label span {
    font-size: 14px;
    color: #374151;
}

.newsletter-modal-form .checkbox-label {
    display: flex;
    align-items: start;
    cursor: pointer;
}

.newsletter-modal-form .checkbox-label input[type="checkbox"] {
    margin-right: 8px;
    margin-top: 2px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.newsletter-modal-form .checkbox-label span {
    font-size: 13px;
    color: #374151;
    line-height: 1.5;
}

.newsletter-modal-form .checkbox-label a {
    color: #AC2A6E;
    text-decoration: underline;
}

.newsletter-modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.newsletter-modal-actions .button-primary,
.newsletter-modal-actions .button-secondary {
    flex: 1;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.newsletter-modal-actions .button-primary {
    background: #AC2A6E;
    color: white;
}

.newsletter-modal-actions .button-primary:hover {
    background: #8A1F56;
}

.newsletter-modal-actions .button-primary:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.newsletter-modal-actions .button-secondary {
    background: #f3f4f6;
    color: #374151;
}

.newsletter-modal-actions .button-secondary:hover {
    background: #e5e7eb;
}

.newsletter-message {
    margin-top: 16px;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
}

.newsletter-message.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.newsletter-message.error {
    background: #fef2f2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}

@media (max-width: 640px) {
    .newsletter-modal-content {
        padding: 24px;
    }

    .newsletter-modal-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Newsletter Modal Script -->
<script>
jQuery(document).ready(function($) {
    // Intercept footer newsletter form
    $('#footer-newsletter-form').on('submit', function(e) {
        e.preventDefault();

        var email = $('#footer-newsletter-email').val().trim();

        if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            alert('<?php echo esc_js( __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'seminargo' ) ); ?>');
            return;
        }

        // Pre-fill email in modal and show modal
        $('#newsletter-email-modal').val(email);
        $('#newsletter-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');

        // Focus first field
        setTimeout(function() {
            $('input[name="anrede"][value="Herr"]').focus();
        }, 350);
    });

    // Close modal
    function closeModal() {
        $('#newsletter-modal').fadeOut(300);
        $('body').css('overflow', '');
        $('#newsletter-modal-form')[0].reset();
        $('#newsletter-message').hide().removeClass('success error');
    }

    $('#newsletter-modal-close, #newsletter-cancel, .newsletter-modal-overlay').on('click', function() {
        closeModal();
    });

    // ESC key to close
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#newsletter-modal').is(':visible')) {
            closeModal();
        }
    });

    // Handle modal form submission
    $('#newsletter-modal-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $('#newsletter-submit-btn');
        var $message = $('#newsletter-message');

        // Get form data
        var formData = {
            action: 'seminargo_newsletter_signup',
            email: $('#newsletter-email-modal').val(),
            anrede: $('input[name="anrede"]:checked').val(),
            vorname: $('#newsletter-vorname').val(),
            nachname: $('#newsletter-nachname').val(),
            country: $('#newsletter-country').val()
        };

        // Disable submit button
        $submitBtn.prop('disabled', true).text('<?php echo esc_js( __( 'Wird gesendet...', 'seminargo' ) ); ?>');
        $message.hide();

        // Submit to WordPress AJAX
        $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', formData, function(response) {
            $submitBtn.prop('disabled', false).text('<?php echo esc_js( __( 'Anmelden', 'seminargo' ) ); ?>');

            if (response.success) {
                // Success
                $message
                    .removeClass('error')
                    .addClass('success')
                    .html('✅ ' + response.data.message)
                    .show();

                // Close modal after 3 seconds
                setTimeout(function() {
                    closeModal();
                    // Clear footer email field
                    $('#footer-newsletter-email').val('');
                }, 3000);
            } else {
                // Error
                $message
                    .removeClass('success')
                    .addClass('error')
                    .html('❌ ' + response.data.message)
                    .show();
            }
        }).fail(function(xhr, status, error) {
            $submitBtn.prop('disabled', false).text('<?php echo esc_js( __( 'Anmelden', 'seminargo' ) ); ?>');

            $message
                .removeClass('success')
                .addClass('error')
                .html('❌ <?php echo esc_js( __( 'Serverfehler. Bitte versuchen Sie es später erneut.', 'seminargo' ) ); ?>')
                .show();
        });
    });
});
</script>

<!-- Smartsupp Live Chat script -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = '58db59304f00daaee300b76e9cba03b75d8d88d3';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<noscript> Powered by <a href="https://www.smartsupp.com" target="_blank">Smartsupp</a></noscript>

<?php wp_footer(); ?>

</body>
</html>