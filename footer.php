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
                                    <p class="newsletter-text"><?php esc_html_e( 'Newsletter anmelden um die neuen Location zu abonnieren.', 'seminargo' ); ?></p>
                                    <form class="newsletter-form" action="#" method="post">
                                        <input type="email" class="newsletter-email" placeholder="<?php esc_attr_e( 'E-Mail-Adresse', 'seminargo' ); ?>" required>
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
                            <a href="<?php echo esc_url( home_url( '/agb' ) ); ?>"><?php esc_html_e( 'AGBs', 'seminargo' ); ?></a>
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

<!-- Back to top button -->
<button id="back-to-top" class="back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'seminargo' ); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</button>

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