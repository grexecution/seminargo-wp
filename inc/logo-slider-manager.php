<?php
/**
 * Logo Slider Manager
 * Allows editing logos on the homepage
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seminargo_Logo_Slider_Manager {

    public function __construct() {
        // Add meta box to front page only
        add_action( 'add_meta_boxes', [ $this, 'add_logo_slider_meta_box' ] );

        // Save logos
        add_action( 'save_post', [ $this, 'save_logo_slider' ] );

        // AJAX handlers
        add_action( 'wp_ajax_seminargo_upload_logo', [ $this, 'ajax_upload_logo' ] );
        add_action( 'wp_ajax_seminargo_remove_logo', [ $this, 'ajax_remove_logo' ] );
        add_action( 'wp_ajax_seminargo_reorder_logos', [ $this, 'ajax_reorder_logos' ] );
    }

    /**
     * Add meta box to front page
     */
    public function add_logo_slider_meta_box() {
        $front_page_id = get_option( 'page_on_front' );

        if ( $front_page_id ) {
            add_meta_box(
                'logo_slider_manager',
                '🏢 ' . __( 'Logo Slider Manager', 'seminargo' ),
                [ $this, 'render_meta_box' ],
                'page',
                'normal',
                'high'
            );
        }
    }

    /**
     * Render meta box
     */
    public function render_meta_box( $post ) {
        // Only show on front page
        if ( $post->ID != get_option( 'page_on_front' ) ) {
            return;
        }

        wp_nonce_field( 'logo_slider_save', 'logo_slider_nonce' );

        $logos = get_post_meta( $post->ID, 'logo_slider_logos', true );
        if ( ! is_array( $logos ) ) {
            $logos = [];
        }

        ?>
        <div class="logo-slider-manager">
            <p style="margin-bottom: 16px; color: #6b7280;">
                <?php esc_html_e( 'Manage client logos displayed in the logo slider section on the homepage.', 'seminargo' ); ?>
            </p>

            <button type="button" id="add-logo-btn" class="button button-primary" style="margin-bottom: 20px;">
                ➕ <?php esc_html_e( 'Add Logo', 'seminargo' ); ?>
            </button>

            <div id="logos-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                <?php if ( ! empty( $logos ) ) : ?>
                    <?php foreach ( $logos as $index => $logo ) : ?>
                        <div class="logo-item-card" data-index="<?php echo esc_attr( $index ); ?>" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px; position: relative;">
                            <div style="width: 100%; height: 100px; display: flex; align-items: center; justify-content: center; background: white; border-radius: 4px; margin-bottom: 8px; overflow: hidden;">
                                <img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['name'] ); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <input type="text" class="logo-name-input" value="<?php echo esc_attr( $logo['name'] ); ?>" placeholder="Logo Name" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; margin-bottom: 8px;">
                            <input type="hidden" class="logo-id-input" value="<?php echo esc_attr( $logo['id'] ); ?>">
                            <input type="hidden" class="logo-url-input" value="<?php echo esc_url( $logo['url'] ); ?>">
                            <button type="button" class="button button-small remove-logo-btn" style="width: 100%; background: #dc2626; color: white; border-color: #dc2626;">
                                🗑️ <?php esc_html_e( 'Remove', 'seminargo' ); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="color: #9ca3af; grid-column: 1 / -1; text-align: center; padding: 40px;"><?php esc_html_e( 'No logos added yet. Click "Add Logo" to get started.', 'seminargo' ); ?></p>
                <?php endif; ?>
            </div>

            <input type="hidden" id="logo_slider_data" name="logo_slider_data" value='<?php echo esc_attr( wp_json_encode( $logos ) ); ?>'>
        </div>

        <style>
            .logo-item-card {
                cursor: move;
            }
            .logo-item-card:hover {
                border-color: #AC2A6E;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Add logo
            $('#add-logo-btn').on('click', function() {
                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Select Logos', 'seminargo' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Add Logos', 'seminargo' ) ); ?>' },
                    multiple: true,  // Allow multiple selection
                    library: { type: 'image' }
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    selection.each(function(attachment) {
                        attachment = attachment.toJSON();
                        addLogoToList(attachment);
                    });
                });

                frame.open();
            });

            function addLogoToList(attachment) {
                var $list = $('#logos-list');

                // Remove "no logos" message if exists
                $list.find('p').remove();

                var index = Date.now();
                var html = '<div class="logo-item-card" data-index="' + index + '" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 12px; position: relative;">';
                html += '<div style="width: 100%; height: 100px; display: flex; align-items: center; justify-content: center; background: white; border-radius: 4px; margin-bottom: 8px; overflow: hidden;">';
                html += '<img src="' + attachment.url + '" alt="' + attachment.title + '" style="max-width: 100%; max-height: 100%; object-fit: contain;">';
                html += '</div>';
                html += '<input type="text" class="logo-name-input" value="' + attachment.title + '" placeholder="Logo Name" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; margin-bottom: 8px;">';
                html += '<input type="hidden" class="logo-id-input" value="' + attachment.id + '">';
                html += '<input type="hidden" class="logo-url-input" value="' + attachment.url + '">';
                html += '<button type="button" class="button button-small remove-logo-btn" style="width: 100%; background: #dc2626; color: white; border-color: #dc2626;">🗑️ <?php echo esc_js( __( 'Remove', 'seminargo' ) ); ?></button>';
                html += '</div>';

                $list.append(html);
                updateLogosData();
            }

            // Remove logo
            $(document).on('click', '.remove-logo-btn', function() {
                if (confirm('<?php echo esc_js( __( 'Remove this logo?', 'seminargo' ) ); ?>')) {
                    $(this).closest('.logo-item-card').fadeOut(300, function() {
                        $(this).remove();
                        updateLogosData();

                        // Show "no logos" message if empty
                        if ($('#logos-list .logo-item-card').length === 0) {
                            $('#logos-list').html('<p style="color: #9ca3af; grid-column: 1 / -1; text-align: center; padding: 40px;"><?php echo esc_js( __( 'No logos added yet. Click "Add Logo" to get started.', 'seminargo' ) ); ?></p>');
                        }
                    });
                }
            });

            // Update logo name
            $(document).on('change', '.logo-name-input', function() {
                updateLogosData();
            });

            // Make logos sortable (drag & drop)
            if (typeof $.fn.sortable !== 'undefined') {
                $('#logos-list').sortable({
                    cursor: 'move',
                    opacity: 0.7,
                    update: function() {
                        updateLogosData();
                    }
                });
            }

            // Update hidden field with current logos data
            function updateLogosData() {
                var logos = [];
                $('#logos-list .logo-item-card').each(function() {
                    var $card = $(this);
                    logos.push({
                        id: $card.find('.logo-id-input').val(),
                        url: $card.find('.logo-url-input').val(),
                        name: $card.find('.logo-name-input').val()
                    });
                });
                $('#logo_slider_data').val(JSON.stringify(logos));
            }
        });
        </script>
        <?php
    }

    /**
     * Save logo slider data
     */
    public function save_logo_slider( $post_id ) {
        // Security checks
        if ( ! isset( $_POST['logo_slider_nonce'] ) || ! wp_verify_nonce( $_POST['logo_slider_nonce'], 'logo_slider_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Only save on front page
        if ( $post_id != get_option( 'page_on_front' ) ) {
            return;
        }

        // Save logos data
        if ( isset( $_POST['logo_slider_data'] ) ) {
            $logos = json_decode( stripslashes( $_POST['logo_slider_data'] ), true );
            if ( is_array( $logos ) ) {
                update_post_meta( $post_id, 'logo_slider_logos', $logos );
            }
        }
    }
}

// Initialize
new Seminargo_Logo_Slider_Manager();
