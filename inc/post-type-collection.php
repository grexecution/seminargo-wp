<?php
/**
 * Collection Post Type
 *
 * Custom post type for SEO landing pages (e.g., "Seminarhotels Wien")
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seminargo_Collection_Post_Type {

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_collection', [ $this, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'wp_ajax_search_hotels_for_collection', [ $this, 'ajax_search_hotels' ] );

        // Disable Gutenberg for this post type - use classic editor
        add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
    }

    /**
     * Disable Gutenberg for collection post type
     */
    public function disable_gutenberg( $use_block_editor, $post_type ) {
        if ( $post_type === 'collection' ) {
            return false;
        }
        return $use_block_editor;
    }

    /**
     * Register the Collection post type
     */
    public function register_post_type() {
        $labels = [
            'name'               => __( 'Collections', 'seminargo' ),
            'singular_name'      => __( 'Collection', 'seminargo' ),
            'add_new'            => __( 'Add New', 'seminargo' ),
            'add_new_item'       => __( 'Add New Collection', 'seminargo' ),
            'edit_item'          => __( 'Edit Collection', 'seminargo' ),
            'new_item'           => __( 'New Collection', 'seminargo' ),
            'view_item'          => __( 'View Collection', 'seminargo' ),
            'search_items'       => __( 'Search Collections', 'seminargo' ),
            'not_found'          => __( 'No collections found', 'seminargo' ),
            'not_found_in_trash' => __( 'No collections found in Trash', 'seminargo' ),
            'menu_name'          => __( 'Collections', 'seminargo' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'seminarhotels', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 21,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
            'show_in_rest'       => false,
        ];

        register_post_type( 'collection', $args );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Hero Settings
        add_meta_box(
            'collection_hero',
            '🎨 ' . __( 'Hero Section', 'seminargo' ),
            [ $this, 'render_hero_meta_box' ],
            'collection',
            'normal',
            'high'
        );

        // SEO Settings
        add_meta_box(
            'collection_seo',
            '🔍 ' . __( 'SEO Settings', 'seminargo' ),
            [ $this, 'render_seo_meta_box' ],
            'collection',
            'normal',
            'high'
        );

        // CTA Sidebar
        add_meta_box(
            'collection_cta',
            '📢 ' . __( 'Sidebar CTA', 'seminargo' ),
            [ $this, 'render_cta_meta_box' ],
            'collection',
            'normal',
            'default'
        );

        // Linked Hotels
        add_meta_box(
            'collection_hotels',
            '🏨 ' . __( 'Linked Hotels', 'seminargo' ),
            [ $this, 'render_hotels_meta_box' ],
            'collection',
            'normal',
            'default'
        );
    }

    /**
     * Render Hero meta box
     */
    public function render_hero_meta_box( $post ) {
        wp_nonce_field( 'collection_meta_nonce', 'collection_meta_nonce_field' );

        $hero_subtitle = get_post_meta( $post->ID, 'hero_subtitle', true );
        $hero_image = get_post_meta( $post->ID, 'hero_image', true );
        $hero_overlay_opacity = get_post_meta( $post->ID, 'hero_overlay_opacity', true ) ?: '50';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="hero_subtitle"><?php esc_html_e( 'Hero Subtitle', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="hero_subtitle" name="hero_subtitle" value="<?php echo esc_attr( $hero_subtitle ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g., Finden Sie die besten Tagungshotels in Wien', 'seminargo' ); ?>">
                    <p class="description"><?php esc_html_e( 'Displayed below the page title in the hero section.', 'seminargo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="hero_image"><?php esc_html_e( 'Hero Background Image', 'seminargo' ); ?></label></th>
                <td>
                    <div class="hero-image-preview" style="margin-bottom: 10px;">
                        <?php if ( $hero_image ) : ?>
                            <img src="<?php echo esc_url( $hero_image ); ?>" style="max-width: 400px; height: auto; border-radius: 8px;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="hero_image" name="hero_image" value="<?php echo esc_url( $hero_image ); ?>">
                    <button type="button" class="button button-secondary" id="upload_hero_image"><?php esc_html_e( 'Select Image', 'seminargo' ); ?></button>
                    <button type="button" class="button" id="remove_hero_image" <?php echo ! $hero_image ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'seminargo' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Recommended size: 1920x600px. If not set, featured image will be used.', 'seminargo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="hero_overlay_opacity"><?php esc_html_e( 'Overlay Opacity', 'seminargo' ); ?></label></th>
                <td>
                    <input type="range" id="hero_overlay_opacity" name="hero_overlay_opacity" min="0" max="100" value="<?php echo esc_attr( $hero_overlay_opacity ); ?>" style="width: 200px;">
                    <span id="opacity_value"><?php echo esc_html( $hero_overlay_opacity ); ?>%</span>
                    <p class="description"><?php esc_html_e( 'Dark overlay opacity for better text readability.', 'seminargo' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render SEO meta box
     */
    public function render_seo_meta_box( $post ) {
        $seo_title = get_post_meta( $post->ID, 'seo_title', true );
        $seo_description = get_post_meta( $post->ID, 'seo_description', true );
        $seo_keywords = get_post_meta( $post->ID, 'seo_keywords', true );
        $canonical_url = get_post_meta( $post->ID, 'canonical_url', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="seo_title"><?php esc_html_e( 'SEO Title', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="seo_title" name="seo_title" value="<?php echo esc_attr( $seo_title ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Leave empty to use page title', 'seminargo' ); ?>">
                    <p class="description"><?php esc_html_e( 'Recommended: 50-60 characters.', 'seminargo' ); ?> <span class="char-count">(<span id="seo_title_count"><?php echo strlen( $seo_title ); ?></span>/60)</span></p>
                </td>
            </tr>
            <tr>
                <th><label for="seo_description"><?php esc_html_e( 'Meta Description', 'seminargo' ); ?></label></th>
                <td>
                    <textarea id="seo_description" name="seo_description" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Brief description for search engine results...', 'seminargo' ); ?>"><?php echo esc_textarea( $seo_description ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Recommended: 150-160 characters.', 'seminargo' ); ?> <span class="char-count">(<span id="seo_desc_count"><?php echo strlen( $seo_description ); ?></span>/160)</span></p>
                </td>
            </tr>
            <tr>
                <th><label for="seo_keywords"><?php esc_html_e( 'Focus Keywords', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="seo_keywords" name="seo_keywords" value="<?php echo esc_attr( $seo_keywords ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g., Seminarhotel Wien, Tagungshotel Wien, Konferenzhotel', 'seminargo' ); ?>">
                    <p class="description"><?php esc_html_e( 'Comma-separated keywords for internal reference.', 'seminargo' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="canonical_url"><?php esc_html_e( 'Canonical URL', 'seminargo' ); ?></label></th>
                <td>
                    <input type="url" id="canonical_url" name="canonical_url" value="<?php echo esc_url( $canonical_url ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Leave empty to use default URL', 'seminargo' ); ?>">
                    <p class="description"><?php esc_html_e( 'Only set if this page has duplicate content elsewhere.', 'seminargo' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render CTA meta box
     */
    public function render_cta_meta_box( $post ) {
        $cta_enabled = get_post_meta( $post->ID, 'cta_enabled', true );
        $cta_title = get_post_meta( $post->ID, 'cta_title', true ) ?: __( 'Kostenlose Beratung', 'seminargo' );
        $cta_description = get_post_meta( $post->ID, 'cta_description', true ) ?: __( 'Lassen Sie sich von unseren Experten beraten und finden Sie das perfekte Seminarhotel.', 'seminargo' );
        $cta_button_text = get_post_meta( $post->ID, 'cta_button_text', true ) ?: __( 'Jetzt anfragen', 'seminargo' );
        $cta_button_url = get_post_meta( $post->ID, 'cta_button_url', true ) ?: home_url( '/kontakt' );
        $cta_phone = get_post_meta( $post->ID, 'cta_phone', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cta_enabled"><?php esc_html_e( 'Enable Sidebar CTA', 'seminargo' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="cta_enabled" name="cta_enabled" value="1" <?php checked( $cta_enabled, '1' ); ?>>
                        <?php esc_html_e( 'Show sticky CTA box in sidebar', 'seminargo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="cta_title"><?php esc_html_e( 'CTA Title', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="cta_title" name="cta_title" value="<?php echo esc_attr( $cta_title ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="cta_description"><?php esc_html_e( 'CTA Description', 'seminargo' ); ?></label></th>
                <td>
                    <textarea id="cta_description" name="cta_description" rows="3" class="large-text"><?php echo esc_textarea( $cta_description ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="cta_button_text"><?php esc_html_e( 'Button Text', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="cta_button_text" name="cta_button_text" value="<?php echo esc_attr( $cta_button_text ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="cta_button_url"><?php esc_html_e( 'Button URL', 'seminargo' ); ?></label></th>
                <td>
                    <input type="url" id="cta_button_url" name="cta_button_url" value="<?php echo esc_url( $cta_button_url ); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="cta_phone"><?php esc_html_e( 'Phone Number', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="cta_phone" name="cta_phone" value="<?php echo esc_attr( $cta_phone ); ?>" class="regular-text" placeholder="+43 1 90 858">
                    <p class="description"><?php esc_html_e( 'Optional phone number to display.', 'seminargo' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Hotels meta box
     */
    public function render_hotels_meta_box( $post ) {
        $linked_hotels = get_post_meta( $post->ID, 'linked_hotels', true ) ?: [];
        $hotels_title = get_post_meta( $post->ID, 'hotels_section_title', true ) ?: __( 'Empfohlene Hotels', 'seminargo' );
        $hotels_subtitle = get_post_meta( $post->ID, 'hotels_section_subtitle', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="hotels_section_title"><?php esc_html_e( 'Section Title', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="hotels_section_title" name="hotels_section_title" value="<?php echo esc_attr( $hotels_title ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="hotels_section_subtitle"><?php esc_html_e( 'Section Tagline', 'seminargo' ); ?></label></th>
                <td>
                    <input type="text" id="hotels_section_subtitle" name="hotels_section_subtitle" value="<?php echo esc_attr( $hotels_subtitle ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Unsere Top-Empfehlungen', 'seminargo' ); ?>">
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 20px;"><?php esc_html_e( 'Select Hotels', 'seminargo' ); ?></h4>

        <div class="hotel-search-container" style="margin-bottom: 15px;">
            <input type="text" id="hotel_search_input" class="regular-text" placeholder="<?php esc_attr_e( 'Search hotels by name...', 'seminargo' ); ?>" style="width: 300px;">
            <div id="hotel_search_results" style="border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none; position: absolute; background: white; z-index: 1000; width: 300px;"></div>
        </div>

        <div id="selected_hotels" class="selected-hotels-list" style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; min-height: 100px; background: #f9f9f9;">
            <?php if ( ! empty( $linked_hotels ) ) : ?>
                <?php foreach ( $linked_hotels as $hotel_id ) :
                    $hotel = get_post( $hotel_id );
                    if ( ! $hotel ) continue;
                    $location = get_post_meta( $hotel_id, 'business_city', true );
                    $thumb = get_the_post_thumbnail_url( $hotel_id, 'thumbnail' );
                ?>
                    <div class="selected-hotel-item" data-hotel-id="<?php echo esc_attr( $hotel_id ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 3px;">
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <strong><?php echo esc_html( $hotel->post_title ); ?></strong>
                            <?php if ( $location ) : ?>
                                <br><small style="color: #666;"><?php echo esc_html( $location ); ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button button-small remove-hotel" data-hotel-id="<?php echo esc_attr( $hotel_id ); ?>">×</button>
                        <input type="hidden" name="linked_hotels[]" value="<?php echo esc_attr( $hotel_id ); ?>">
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="no-hotels-message" style="color: #666; text-align: center; margin: 20px 0;"><?php esc_html_e( 'No hotels selected. Use the search above to add hotels.', 'seminargo' ); ?></p>
            <?php endif; ?>
        </div>
        <p class="description"><?php esc_html_e( 'Drag to reorder. Hotels will be displayed in a grid at the bottom of the page.', 'seminargo' ); ?></p>
        <?php
    }

    /**
     * Save meta fields
     */
    public function save_meta( $post_id ) {
        // Check nonce
        if ( ! isset( $_POST['collection_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['collection_meta_nonce_field'], 'collection_meta_nonce' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Hero fields
        $hero_fields = [ 'hero_subtitle', 'hero_image', 'hero_overlay_opacity' ];
        foreach ( $hero_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // SEO fields
        $seo_fields = [ 'seo_title', 'seo_description', 'seo_keywords', 'canonical_url' ];
        foreach ( $seo_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                if ( $field === 'canonical_url' ) {
                    update_post_meta( $post_id, $field, esc_url_raw( $_POST[ $field ] ) );
                } else {
                    update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
                }
            }
        }

        // CTA fields
        update_post_meta( $post_id, 'cta_enabled', isset( $_POST['cta_enabled'] ) ? '1' : '0' );
        $cta_fields = [ 'cta_title', 'cta_description', 'cta_button_text', 'cta_button_url', 'cta_phone' ];
        foreach ( $cta_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                if ( $field === 'cta_button_url' ) {
                    update_post_meta( $post_id, $field, esc_url_raw( $_POST[ $field ] ) );
                } else {
                    update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
                }
            }
        }

        // Hotels section
        $hotels_fields = [ 'hotels_section_title', 'hotels_section_subtitle' ];
        foreach ( $hotels_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // Linked hotels
        if ( isset( $_POST['linked_hotels'] ) && is_array( $_POST['linked_hotels'] ) ) {
            $hotel_ids = array_map( 'absint', $_POST['linked_hotels'] );
            update_post_meta( $post_id, 'linked_hotels', $hotel_ids );
        } else {
            update_post_meta( $post_id, 'linked_hotels', [] );
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        global $post_type;

        if ( ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'collection' ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-sortable' );

            wp_add_inline_script( 'jquery', '
                jQuery(document).ready(function($) {
                    // Media uploader for hero image
                    var mediaUploader;
                    $("#upload_hero_image").on("click", function(e) {
                        e.preventDefault();
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        mediaUploader = wp.media({
                            title: "Select Hero Image",
                            button: { text: "Use this image" },
                            multiple: false
                        });
                        mediaUploader.on("select", function() {
                            var attachment = mediaUploader.state().get("selection").first().toJSON();
                            $("#hero_image").val(attachment.url);
                            $(".hero-image-preview").html("<img src=\"" + attachment.url + "\" style=\"max-width: 400px; height: auto; border-radius: 8px;\">");
                            $("#remove_hero_image").show();
                        });
                        mediaUploader.open();
                    });

                    $("#remove_hero_image").on("click", function() {
                        $("#hero_image").val("");
                        $(".hero-image-preview").html("");
                        $(this).hide();
                    });

                    // Opacity slider
                    $("#hero_overlay_opacity").on("input", function() {
                        $("#opacity_value").text($(this).val() + "%");
                    });

                    // SEO character counts
                    $("#seo_title").on("input", function() {
                        $("#seo_title_count").text($(this).val().length);
                    });
                    $("#seo_description").on("input", function() {
                        $("#seo_desc_count").text($(this).val().length);
                    });

                    // Hotel search
                    var searchTimeout;
                    $("#hotel_search_input").on("input", function() {
                        var query = $(this).val();
                        clearTimeout(searchTimeout);

                        if (query.length < 2) {
                            $("#hotel_search_results").hide();
                            return;
                        }

                        searchTimeout = setTimeout(function() {
                            $.post(ajaxurl, {
                                action: "search_hotels_for_collection",
                                query: query
                            }, function(response) {
                                if (response.success && response.data.length > 0) {
                                    var html = "";
                                    response.data.forEach(function(hotel) {
                                        html += "<div class=\"hotel-result-item\" data-hotel-id=\"" + hotel.id + "\" data-hotel-title=\"" + hotel.title + "\" data-hotel-location=\"" + hotel.location + "\" data-hotel-thumb=\"" + hotel.thumb + "\" style=\"padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;\">";
                                        html += "<strong>" + hotel.title + "</strong>";
                                        if (hotel.location) html += "<br><small style=\"color: #666;\">" + hotel.location + "</small>";
                                        html += "</div>";
                                    });
                                    $("#hotel_search_results").html(html).show();
                                } else {
                                    $("#hotel_search_results").html("<div style=\"padding: 8px; color: #666;\">No hotels found</div>").show();
                                }
                            });
                        }, 300);
                    });

                    // Select hotel from results
                    $(document).on("click", ".hotel-result-item", function() {
                        var hotelId = $(this).data("hotel-id");
                        var hotelTitle = $(this).data("hotel-title");
                        var hotelLocation = $(this).data("hotel-location");
                        var hotelThumb = $(this).data("hotel-thumb");

                        // Check if already added
                        if ($("#selected_hotels").find("[data-hotel-id=\"" + hotelId + "\"]").length > 0) {
                            return;
                        }

                        $(".no-hotels-message").remove();

                        var html = "<div class=\"selected-hotel-item\" data-hotel-id=\"" + hotelId + "\" style=\"display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 5px;\">";
                        if (hotelThumb) {
                            html += "<img src=\"" + hotelThumb + "\" style=\"width: 50px; height: 35px; object-fit: cover; border-radius: 3px;\">";
                        }
                        html += "<div style=\"flex: 1;\"><strong>" + hotelTitle + "</strong>";
                        if (hotelLocation) html += "<br><small style=\"color: #666;\">" + hotelLocation + "</small>";
                        html += "</div>";
                        html += "<button type=\"button\" class=\"button button-small remove-hotel\" data-hotel-id=\"" + hotelId + "\">×</button>";
                        html += "<input type=\"hidden\" name=\"linked_hotels[]\" value=\"" + hotelId + "\">";
                        html += "</div>";

                        $("#selected_hotels").append(html);
                        $("#hotel_search_input").val("");
                        $("#hotel_search_results").hide();
                    });

                    // Remove hotel
                    $(document).on("click", ".remove-hotel", function() {
                        $(this).closest(".selected-hotel-item").remove();
                        if ($("#selected_hotels .selected-hotel-item").length === 0) {
                            $("#selected_hotels").html("<p class=\"no-hotels-message\" style=\"color: #666; text-align: center; margin: 20px 0;\">No hotels selected. Use the search above to add hotels.</p>");
                        }
                    });

                    // Hide results when clicking outside
                    $(document).on("click", function(e) {
                        if (!$(e.target).closest("#hotel_search_input, #hotel_search_results").length) {
                            $("#hotel_search_results").hide();
                        }
                    });

                    // Make sortable
                    $("#selected_hotels").sortable({
                        items: ".selected-hotel-item",
                        handle: ".selected-hotel-item",
                        cursor: "move",
                        placeholder: "sortable-placeholder",
                        opacity: 0.7
                    });
                });
            ' );
        }
    }

    /**
     * AJAX search hotels
     */
    public function ajax_search_hotels() {
        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

        if ( strlen( $query ) < 2 ) {
            wp_send_json_success( [] );
        }

        $hotels = new WP_Query( [
            'post_type'      => 'hotel',
            'post_status'    => 'publish',
            's'              => $query,
            'posts_per_page' => 10,
        ] );

        $results = [];

        if ( $hotels->have_posts() ) {
            while ( $hotels->have_posts() ) {
                $hotels->the_post();
                $results[] = [
                    'id'       => get_the_ID(),
                    'title'    => get_the_title(),
                    'location' => get_post_meta( get_the_ID(), 'business_city', true ),
                    'thumb'    => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ) ?: '',
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success( $results );
    }
}

// Initialize
new Seminargo_Collection_Post_Type();

/**
 * Add SEO meta tags for collections
 */
add_action( 'wp_head', function() {
    if ( ! is_singular( 'collection' ) ) {
        return;
    }

    $post_id = get_the_ID();
    $seo_title = get_post_meta( $post_id, 'seo_title', true );
    $seo_description = get_post_meta( $post_id, 'seo_description', true );
    $canonical_url = get_post_meta( $post_id, 'canonical_url', true );

    if ( $seo_description ) {
        echo '<meta name="description" content="' . esc_attr( $seo_description ) . '">' . "\n";
    }

    if ( $canonical_url ) {
        echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '">' . "\n";
    }

    // Open Graph
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $seo_title ?: get_the_title() ) . '">' . "\n";

    if ( $seo_description ) {
        echo '<meta property="og:description" content="' . esc_attr( $seo_description ) . '">' . "\n";
    }

    $hero_image = get_post_meta( $post_id, 'hero_image', true );
    if ( ! $hero_image ) {
        $hero_image = get_the_post_thumbnail_url( $post_id, 'large' );
    }
    if ( $hero_image ) {
        echo '<meta property="og:image" content="' . esc_url( $hero_image ) . '">' . "\n";
    }
}, 5 );

/**
 * Filter document title for collections
 */
add_filter( 'document_title_parts', function( $title ) {
    if ( is_singular( 'collection' ) ) {
        $seo_title = get_post_meta( get_the_ID(), 'seo_title', true );
        if ( $seo_title ) {
            $title['title'] = $seo_title;
        }
    }
    return $title;
} );
