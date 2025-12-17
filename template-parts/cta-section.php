<?php
/**
 * Template part for displaying CTA section
 *
 * @param string $eyebrow - Small text above heading (optional)
 * @param string $heading - Main heading text
 * @param string $description - Description paragraph
 * @param array $benefits - Array of benefit strings (optional)
 * @param array $buttons - Array of button configs: ['text', 'url', 'icon', 'style']
 * @param string $style - Section style: 'gradient' (default) or 'light'
 *
 * @package Seminargo
 */

// Default values
$eyebrow = $args['eyebrow'] ?? '';
$heading = $args['heading'] ?? 'Ihre Traum-Location in 24 Stunden';
$description = $args['description'] ?? '';
$benefits = $args['benefits'] ?? [];
$buttons = $args['buttons'] ?? [];
$style = $args['style'] ?? 'gradient';

// Default buttons if none provided
if ( empty( $buttons ) ) {
    $buttons = [
        [
            'text' => 'Sofort Anrufen',
            'url' => 'tel:+43190858',
            'icon' => 'phone',
            'style' => 'white'
        ],
        [
            'text' => 'Beratung anfragen',
            'url' => 'mailto:info@seminargo.com',
            'icon' => 'email',
            'style' => 'outline-white'
        ]
    ];
}

// SVG Icons
$icons = [
    'phone' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
    'email' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
    'checkmark' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'arrow-right' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>'
];
?>

<section class="location-finder-cta location-finder-cta--<?php echo esc_attr( $style ); ?>">
    <div class="container">
        <div class="cta-content">
            <div class="cta-text">
                <?php if ( ! empty( $eyebrow ) ) : ?>
                    <span class="cta-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
                <?php endif; ?>

                <h2><?php echo esc_html( $heading ); ?></h2>

                <?php if ( ! empty( $description ) ) : ?>
                    <p><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $benefits ) ) : ?>
                    <div class="cta-benefits">
                        <?php foreach ( $benefits as $benefit ) : ?>
                            <div class="cta-benefit">
                                <?php echo $icons['checkmark']; ?>
                                <span><?php echo esc_html( $benefit ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cta-actions">
                <?php foreach ( $buttons as $button ) :
                    $button_style = $button['style'] ?? 'white';
                    $button_icon = $button['icon'] ?? 'arrow-right';
                ?>
                    <a href="<?php echo esc_url( $button['url'] ); ?>" class="button button-<?php echo esc_attr( $button_style ); ?>">
                        <?php if ( isset( $icons[ $button_icon ] ) ) {
                            echo $icons[ $button_icon ];
                        } ?>
                        <?php echo esc_html( $button['text'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
