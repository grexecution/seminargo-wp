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
                    'fallback_cb'     => false,
                ) );
                ?>

                <!-- Mobile Icon Navigation -->
                <div class="mobile-icon-nav">
                    <ul class="mobile-icon-list">
                        <li class="mobile-icon-item">
                            <a href="#" class="mobile-icon-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span><?php esc_html_e( 'Standorte', 'seminargo' ); ?></span>
                            </a>
                        </li>
                        <li class="mobile-icon-item">
                            <a href="#" class="mobile-icon-link">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span><?php esc_html_e( 'Veranstaltungen', 'seminargo' ); ?></span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Mobile CTA Button -->
                <div class="mobile-cta">
                    <button class="btn-cta-primary btn-block">
                        <?php esc_html_e( 'Angebot anfragen', 'seminargo' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div><!-- .slide-menu -->

    <div id="content" class="site-content"