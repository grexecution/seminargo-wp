<?php
/**
 * Seminargo Theme Customizer
 *
 * @package Seminargo
 */

/**
 * Add postMessage support for site title and description
 */
function seminargo_customize_register( $wp_customize ) {
    // Site title and description
    $wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
    $wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

    // Remove unused sections
    $wp_customize->remove_section( 'colors' );
    $wp_customize->remove_section( 'header_image' );
    $wp_customize->remove_section( 'background_image' );

    /**
     * Theme Options Panel
     */
    $wp_customize->add_panel( 'seminargo_theme_options', array(
        'title'    => __( 'Theme Options', 'seminargo' ),
        'priority' => 30,
    ) );

    /**
     * Colors Section
     */
    $wp_customize->add_section( 'seminargo_colors', array(
        'title'    => __( 'Colors', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 10,
    ) );

    // Primary Color
    $wp_customize->add_setting( 'seminargo_primary_color', array(
        'default'           => '#2563eb',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'seminargo_primary_color', array(
        'label'    => __( 'Primary Color', 'seminargo' ),
        'section'  => 'seminargo_colors',
        'settings' => 'seminargo_primary_color',
    ) ) );

    // Secondary Color
    $wp_customize->add_setting( 'seminargo_secondary_color', array(
        'default'           => '#10b981',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'seminargo_secondary_color', array(
        'label'    => __( 'Secondary Color', 'seminargo' ),
        'section'  => 'seminargo_colors',
        'settings' => 'seminargo_secondary_color',
    ) ) );

    // Accent Color
    $wp_customize->add_setting( 'seminargo_accent_color', array(
        'default'           => '#f59e0b',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'seminargo_accent_color', array(
        'label'    => __( 'Accent Color', 'seminargo' ),
        'section'  => 'seminargo_colors',
        'settings' => 'seminargo_accent_color',
    ) ) );

    /**
     * Typography Section
     */
    $wp_customize->add_section( 'seminargo_typography', array(
        'title'    => __( 'Typography', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 20,
    ) );

    // Body Font
    $wp_customize->add_setting( 'seminargo_body_font', array(
        'default'           => 'system',
        'sanitize_callback' => 'seminargo_sanitize_select',
    ) );

    $wp_customize->add_control( 'seminargo_body_font', array(
        'label'    => __( 'Body Font', 'seminargo' ),
        'section'  => 'seminargo_typography',
        'type'     => 'select',
        'choices'  => array(
            'system'    => __( 'System Fonts', 'seminargo' ),
            'sans'      => __( 'Sans Serif', 'seminargo' ),
            'serif'     => __( 'Serif', 'seminargo' ),
            'monospace' => __( 'Monospace', 'seminargo' ),
        ),
    ) );

    // Font Size
    $wp_customize->add_setting( 'seminargo_font_size', array(
        'default'           => '16',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'seminargo_font_size', array(
        'label'       => __( 'Base Font Size (px)', 'seminargo' ),
        'section'     => 'seminargo_typography',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 12,
            'max'  => 24,
            'step' => 1,
        ),
    ) );

    /**
     * Layout Section
     */
    $wp_customize->add_section( 'seminargo_layout', array(
        'title'    => __( 'Layout', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 30,
    ) );

    // Container Width
    $wp_customize->add_setting( 'seminargo_container_width', array(
        'default'           => '1200',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'seminargo_container_width', array(
        'label'       => __( 'Container Width (px)', 'seminargo' ),
        'section'     => 'seminargo_layout',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 960,
            'max'  => 1920,
            'step' => 10,
        ),
    ) );

    // Sidebar Position
    $wp_customize->add_setting( 'seminargo_sidebar_position', array(
        'default'           => 'right',
        'sanitize_callback' => 'seminargo_sanitize_select',
    ) );

    $wp_customize->add_control( 'seminargo_sidebar_position', array(
        'label'    => __( 'Sidebar Position', 'seminargo' ),
        'section'  => 'seminargo_layout',
        'type'     => 'select',
        'choices'  => array(
            'right' => __( 'Right', 'seminargo' ),
            'left'  => __( 'Left', 'seminargo' ),
            'none'  => __( 'No Sidebar', 'seminargo' ),
        ),
    ) );

    /**
     * Header Section
     */
    $wp_customize->add_section( 'seminargo_header', array(
        'title'    => __( 'Header', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 40,
    ) );

    // Sticky Header
    $wp_customize->add_setting( 'seminargo_sticky_header', array(
        'default'           => true,
        'sanitize_callback' => 'seminargo_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'seminargo_sticky_header', array(
        'label'   => __( 'Enable Sticky Header', 'seminargo' ),
        'section' => 'seminargo_header',
        'type'    => 'checkbox',
    ) );

    // Header Search
    $wp_customize->add_setting( 'seminargo_header_search', array(
        'default'           => true,
        'sanitize_callback' => 'seminargo_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'seminargo_header_search', array(
        'label'   => __( 'Show Search in Header', 'seminargo' ),
        'section' => 'seminargo_header',
        'type'    => 'checkbox',
    ) );

    /**
     * Footer Section
     */
    $wp_customize->add_section( 'seminargo_footer', array(
        'title'    => __( 'Footer', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 50,
    ) );

    // Footer Copyright Text
    $wp_customize->add_setting( 'seminargo_footer_text', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'seminargo_footer_text', array(
        'label'       => __( 'Footer Copyright Text', 'seminargo' ),
        'section'     => 'seminargo_footer',
        'type'        => 'textarea',
        'description' => __( 'Use {year} for current year and {site} for site name.', 'seminargo' ),
    ) );

    // Footer Columns
    $wp_customize->add_setting( 'seminargo_footer_columns', array(
        'default'           => '4',
        'sanitize_callback' => 'absint',
    ) );

    $wp_customize->add_control( 'seminargo_footer_columns', array(
        'label'    => __( 'Footer Widget Columns', 'seminargo' ),
        'section'  => 'seminargo_footer',
        'type'     => 'select',
        'choices'  => array(
            '1' => __( '1 Column', 'seminargo' ),
            '2' => __( '2 Columns', 'seminargo' ),
            '3' => __( '3 Columns', 'seminargo' ),
            '4' => __( '4 Columns', 'seminargo' ),
        ),
    ) );

    /**
     * Blog Section
     */
    $wp_customize->add_section( 'seminargo_blog', array(
        'title'    => __( 'Blog', 'seminargo' ),
        'panel'    => 'seminargo_theme_options',
        'priority' => 60,
    ) );

    // Blog Layout
    $wp_customize->add_setting( 'seminargo_blog_layout', array(
        'default'           => 'grid',
        'sanitize_callback' => 'seminargo_sanitize_select',
    ) );

    $wp_customize->add_control( 'seminargo_blog_layout', array(
        'label'    => __( 'Blog Layout', 'seminargo' ),
        'section'  => 'seminargo_blog',
        'type'     => 'select',
        'choices'  => array(
            'grid' => __( 'Grid', 'seminargo' ),
            'list' => __( 'List', 'seminargo' ),
            'card' => __( 'Cards', 'seminargo' ),
        ),
    ) );

    // Excerpt Length
    $wp_customize->add_setting( 'seminargo_excerpt_length', array(
        'default'           => '30',
        'sanitize_callback' => 'absint',
    ) );

    $wp_customize->add_control( 'seminargo_excerpt_length', array(
        'label'       => __( 'Excerpt Length (words)', 'seminargo' ),
        'section'     => 'seminargo_blog',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 10,
            'max'  => 100,
            'step' => 5,
        ),
    ) );

    // Show Author
    $wp_customize->add_setting( 'seminargo_show_author', array(
        'default'           => true,
        'sanitize_callback' => 'seminargo_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'seminargo_show_author', array(
        'label'   => __( 'Show Author', 'seminargo' ),
        'section' => 'seminargo_blog',
        'type'    => 'checkbox',
    ) );

    // Show Date
    $wp_customize->add_setting( 'seminargo_show_date', array(
        'default'           => true,
        'sanitize_callback' => 'seminargo_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'seminargo_show_date', array(
        'label'   => __( 'Show Date', 'seminargo' ),
        'section' => 'seminargo_blog',
        'type'    => 'checkbox',
    ) );

    // Show Categories
    $wp_customize->add_setting( 'seminargo_show_categories', array(
        'default'           => true,
        'sanitize_callback' => 'seminargo_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'seminargo_show_categories', array(
        'label'   => __( 'Show Categories', 'seminargo' ),
        'section' => 'seminargo_blog',
        'type'    => 'checkbox',
    ) );
}
add_action( 'customize_register', 'seminargo_customize_register' );

/**
 * Sanitize select
 */
function seminargo_sanitize_select( $input, $setting ) {
    $input = sanitize_key( $input );
    $choices = $setting->manager->get_control( $setting->id )->choices;
    return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

/**
 * Sanitize checkbox
 */
function seminargo_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

/**
 * Enqueue customizer preview scripts
 */
function seminargo_customize_preview_js() {
    wp_enqueue_script(
        'seminargo-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array( 'customize-preview' ),
        SEMINARGO_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'seminargo_customize_preview_js' );

/**
 * Output custom CSS based on customizer settings
 */
function seminargo_customizer_css() {
    ?>
    <style type="text/css">
        :root {
            --color-primary: <?php echo esc_attr( get_theme_mod( 'seminargo_primary_color', '#2563eb' ) ); ?>;
            --color-secondary: <?php echo esc_attr( get_theme_mod( 'seminargo_secondary_color', '#10b981' ) ); ?>;
            --color-accent: <?php echo esc_attr( get_theme_mod( 'seminargo_accent_color', '#f59e0b' ) ); ?>;
            --container-width: <?php echo esc_attr( get_theme_mod( 'seminargo_container_width', '1200' ) ); ?>px;
        }

        html {
            font-size: <?php echo esc_attr( get_theme_mod( 'seminargo_font_size', '16' ) ); ?>px;
        }

        <?php if ( get_theme_mod( 'seminargo_sidebar_position', 'right' ) === 'left' ) : ?>
        .content-wrapper {
            flex-direction: row-reverse;
        }
        <?php endif; ?>

        <?php if ( get_theme_mod( 'seminargo_sidebar_position', 'right' ) === 'none' ) : ?>
        .sidebar {
            display: none;
        }
        .primary-content {
            max-width: 100%;
        }
        <?php endif; ?>
    </style>
    <?php
}
add_action( 'wp_head', 'seminargo_customizer_css' );