<?php
/**
 * The header template
 *
 * @package Seminargo
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'seminargo' ); ?></a>

    <header id="masthead" class="site-header">
        <div class="header-main">
            <div class="container">
                <div class="header-inner">
                    <!-- Site Logo (Left) -->
                    <div class="site-branding">
                        <?php if ( has_custom_logo() ) : ?>
                            <?php
                            // Get the full resolution logo
                            $custom_logo_id = get_theme_mod( 'custom_logo' );
                            $logo = wp_get_attachment_image_src( $custom_logo_id , 'full' );
                            if ( $logo ) : ?>
                                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="custom-logo-link">
                                    <img src="<?php echo esc_url( $logo[0] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="custom-logo">
                                </a>
                            <?php endif; ?>
                        <?php else : ?>
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="site-title-link">
                                <span class="logo-text">seminargo<sup class="logo-swiss">swiss</sup></span>
                                <span class="logo-tagline">locations & more</span>
                            </a>
                        <?php endif; ?>
                    </div><!-- .site-branding -->

                    <!-- Right Side Navigation (Login + Menu) -->
                    <div class="header-right">
                        <!-- Login Button -->
                        <a href="<?php echo esc_url( wp_login_url() ); ?>" class="header-login-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span><?php esc_html_e( 'Einloggen', 'seminargo' ); ?></span>
                        </a>

                        <!-- Menu Toggle Button -->
                        <button class="menu-toggle" aria-controls="slide-menu" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                            <span><?php esc_html_e( 'Menü', 'seminargo' ); ?></span>
                        </button>
                    </div>
                </div><!-- .header-inner -->
            </div><!-- .container -->
        </div><!-- .header-main -->
    </header><!-- #masthead -->

    <!-- Slide-out Menu -->
    <div id="slide-menu" class="slide-menu">
        <div class="slide-menu-overlay"></div>
        <div class="slide-menu-panel">
            <div class="slide-menu-header">
                <div class="slide-menu-logo">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                            <span class="logo-text">seminargo<sup class="logo-swiss">swiss</sup></span>
                        </a>
                    <?php endif; ?>
                </div>
                <button class="slide-menu-close" aria-label="<?php esc_attr_e( 'Close menu', 'seminargo' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="slide-menu-content">
                <?php
                wp_nav_menu( array(
                    'theme_location'  => 'seminargo-side',
                    'menu_id'         => 'mobile-menu',
                    'container_class' => 'mobile-menu-container',
                    'menu_class'      => 'mobile-menu',
                    'walker'          => new Seminargo_Mobile_Menu_Walker(),
                    'fallback_cb'     => false,
                ) );
                ?>

                <!-- Help & Support Section -->
                <div class="mobile-support-section">
                    <div class="support-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <h3><?php esc_html_e( 'Hilfe & Support', 'seminargo' ); ?></h3>
                    </div>
                    <p class="support-text"><?php esc_html_e( 'Unser Team steht Ihnen gerne zur Verfügung', 'seminargo' ); ?></p>
                    <div class="support-actions">
                        <a href="tel:+43123456789" class="support-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span><?php esc_html_e( 'Anrufen', 'seminargo' ); ?></span>
                        </a>
                        <a href="mailto:info@seminargo.com" class="support-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <span><?php esc_html_e( 'E-Mail', 'seminargo' ); ?></span>
                        </a>
                    </div>
                </div>

                <!-- Trust Badges -->
                <div class="mobile-trust-badges">
                    <div class="trust-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <polyline points="9 12 11 14 15 10"></polyline>
                        </svg>
                        <span><?php esc_html_e( 'Geprüfte Hotels', 'seminargo' ); ?></span>
                    </div>
                    <div class="trust-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        <span><?php esc_html_e( 'Top-Bewertungen', 'seminargo' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- .slide-menu -->

    <div id="content" class="site-content"