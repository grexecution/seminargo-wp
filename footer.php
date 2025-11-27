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
                            <h3 class="footer-title"><?php esc_html_e( 'Seminargo GmbH', 'seminargo' ); ?></h3>
                            <div class="footer-company-info">
                                <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                                    <?php dynamic_sidebar( 'footer-1' ); ?>
                                <?php else : ?>
                                    <p><?php esc_html_e( 'Ihr Partner für unvergessliche Events und Seminare.', 'seminargo' ); ?></p>
                                <?php endif; ?>
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
                                    <li><a href="#"><?php esc_html_e( 'Wie geht Buchende?', 'seminargo' ); ?></a></li>
                                    <li><a href="#"><?php esc_html_e( 'Katalog', 'seminargo' ); ?></a></li>
                                    <li><a href="#"><?php esc_html_e( 'Hilfe', 'seminargo' ); ?></a></li>
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
                            AT-1160 Wien, Liebhartsgasse 16 | Tel: +43 1 90 858
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