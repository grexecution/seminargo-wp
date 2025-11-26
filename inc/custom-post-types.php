<?php
/**
 * Custom Post Types
 *
 * @package Seminargo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Team Post Type
 */
function seminargo_register_team_post_type() {
    $labels = array(
        'name'                  => _x( 'Team Members', 'Post Type General Name', 'seminargo' ),
        'singular_name'         => _x( 'Team Member', 'Post Type Singular Name', 'seminargo' ),
        'menu_name'             => __( 'Team', 'seminargo' ),
        'name_admin_bar'        => __( 'Team Member', 'seminargo' ),
        'archives'              => __( 'Team Archives', 'seminargo' ),
        'attributes'            => __( 'Team Attributes', 'seminargo' ),
        'parent_item_colon'     => __( 'Parent Team Member:', 'seminargo' ),
        'all_items'             => __( 'All Team Members', 'seminargo' ),
        'add_new_item'          => __( 'Add New Team Member', 'seminargo' ),
        'add_new'               => __( 'Add New', 'seminargo' ),
        'new_item'              => __( 'New Team Member', 'seminargo' ),
        'edit_item'             => __( 'Edit Team Member', 'seminargo' ),
        'update_item'           => __( 'Update Team Member', 'seminargo' ),
        'view_item'             => __( 'View Team Member', 'seminargo' ),
        'view_items'            => __( 'View Team Members', 'seminargo' ),
        'search_items'          => __( 'Search Team Member', 'seminargo' ),
        'not_found'             => __( 'Not found', 'seminargo' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'seminargo' ),
        'featured_image'        => __( 'Team Member Photo', 'seminargo' ),
        'set_featured_image'    => __( 'Set team member photo', 'seminargo' ),
        'remove_featured_image' => __( 'Remove team member photo', 'seminargo' ),
        'use_featured_image'    => __( 'Use as team member photo', 'seminargo' ),
        'insert_into_item'      => __( 'Insert into team member', 'seminargo' ),
        'uploaded_to_this_item' => __( 'Uploaded to this team member', 'seminargo' ),
        'items_list'            => __( 'Team members list', 'seminargo' ),
        'items_list_navigation' => __( 'Team members list navigation', 'seminargo' ),
        'filter_items_list'     => __( 'Filter team members list', 'seminargo' ),
    );

    $args = array(
        'label'                 => __( 'Team Member', 'seminargo' ),
        'description'           => __( 'Team Members', 'seminargo' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail', 'page-attributes' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-groups',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );

    register_post_type( 'team', $args );
}
add_action( 'init', 'seminargo_register_team_post_type', 0 );

/**
 * Register FAQ Post Type
 */
function seminargo_register_faq_post_type() {
    $labels = array(
        'name'                  => _x( 'FAQs', 'Post Type General Name', 'seminargo' ),
        'singular_name'         => _x( 'FAQ', 'Post Type Singular Name', 'seminargo' ),
        'menu_name'             => __( 'FAQs', 'seminargo' ),
        'name_admin_bar'        => __( 'FAQ', 'seminargo' ),
        'archives'              => __( 'FAQ Archives', 'seminargo' ),
        'attributes'            => __( 'FAQ Attributes', 'seminargo' ),
        'parent_item_colon'     => __( 'Parent FAQ:', 'seminargo' ),
        'all_items'             => __( 'All FAQs', 'seminargo' ),
        'add_new_item'          => __( 'Add New FAQ', 'seminargo' ),
        'add_new'               => __( 'Add New', 'seminargo' ),
        'new_item'              => __( 'New FAQ', 'seminargo' ),
        'edit_item'             => __( 'Edit FAQ', 'seminargo' ),
        'update_item'           => __( 'Update FAQ', 'seminargo' ),
        'view_item'             => __( 'View FAQ', 'seminargo' ),
        'view_items'            => __( 'View FAQs', 'seminargo' ),
        'search_items'          => __( 'Search FAQ', 'seminargo' ),
        'not_found'             => __( 'Not found', 'seminargo' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'seminargo' ),
        'insert_into_item'      => __( 'Insert into FAQ', 'seminargo' ),
        'uploaded_to_this_item' => __( 'Uploaded to this FAQ', 'seminargo' ),
        'items_list'            => __( 'FAQs list', 'seminargo' ),
        'items_list_navigation' => __( 'FAQs list navigation', 'seminargo' ),
        'filter_items_list'     => __( 'Filter FAQs list', 'seminargo' ),
    );

    $args = array(
        'label'                 => __( 'FAQ', 'seminargo' ),
        'description'           => __( 'Frequently Asked Questions', 'seminargo' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'page-attributes' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 21,
        'menu_icon'             => 'dashicons-editor-help',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
    );

    register_post_type( 'faq', $args );
}
add_action( 'init', 'seminargo_register_faq_post_type', 0 );

/**
 * Register FAQ Category Taxonomy
 */
function seminargo_register_faq_category_taxonomy() {
    $labels = array(
        'name'                       => _x( 'FAQ Categories', 'Taxonomy General Name', 'seminargo' ),
        'singular_name'              => _x( 'FAQ Category', 'Taxonomy Singular Name', 'seminargo' ),
        'menu_name'                  => __( 'FAQ Categories', 'seminargo' ),
        'all_items'                  => __( 'All Categories', 'seminargo' ),
        'parent_item'                => __( 'Parent Category', 'seminargo' ),
        'parent_item_colon'          => __( 'Parent Category:', 'seminargo' ),
        'new_item_name'              => __( 'New Category Name', 'seminargo' ),
        'add_new_item'               => __( 'Add New Category', 'seminargo' ),
        'edit_item'                  => __( 'Edit Category', 'seminargo' ),
        'update_item'                => __( 'Update Category', 'seminargo' ),
        'view_item'                  => __( 'View Category', 'seminargo' ),
        'separate_items_with_commas' => __( 'Separate categories with commas', 'seminargo' ),
        'add_or_remove_items'        => __( 'Add or remove categories', 'seminargo' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'seminargo' ),
        'popular_items'              => __( 'Popular Categories', 'seminargo' ),
        'search_items'               => __( 'Search Categories', 'seminargo' ),
        'not_found'                  => __( 'Not Found', 'seminargo' ),
        'no_terms'                   => __( 'No categories', 'seminargo' ),
        'items_list'                 => __( 'Categories list', 'seminargo' ),
        'items_list_navigation'      => __( 'Categories list navigation', 'seminargo' ),
    );

    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => false,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
        'show_in_rest'               => true,
    );

    register_taxonomy( 'faq_category', array( 'faq' ), $args );
}
add_action( 'init', 'seminargo_register_faq_category_taxonomy', 0 );

/**
 * Create default FAQ categories on theme activation
 */
function seminargo_create_default_faq_categories() {
    // Check if categories already exist
    $buchende = term_exists( 'buchende', 'faq_category' );
    $hotels = term_exists( 'hotels', 'faq_category' );

    // Create "Für Buchende" category
    if ( ! $buchende ) {
        wp_insert_term(
            'Für Buchende',
            'faq_category',
            array(
                'slug'        => 'buchende',
                'description' => 'FAQs für Buchende',
            )
        );
    }

    // Create "Für Hotels" category
    if ( ! $hotels ) {
        wp_insert_term(
            'Für Hotels',
            'faq_category',
            array(
                'slug'        => 'hotels',
                'description' => 'FAQs für Hotels',
            )
        );
    }
}
add_action( 'after_switch_theme', 'seminargo_create_default_faq_categories' );

/**
 * Add custom meta boxes for Team post type
 */
function seminargo_add_team_meta_boxes() {
    add_meta_box(
        'team_details',
        __( 'Team Member Details', 'seminargo' ),
        'seminargo_team_meta_box_callback',
        'team',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'seminargo_add_team_meta_boxes' );

/**
 * Team meta box callback
 */
function seminargo_team_meta_box_callback( $post ) {
    // Add nonce for security
    wp_nonce_field( 'seminargo_team_meta_box', 'seminargo_team_meta_box_nonce' );

    // Get current values
    $position = get_post_meta( $post->ID, 'position', true );
    $team = get_post_meta( $post->ID, 'team', true );

    ?>
    <table class="form-table">
        <tr>
            <th>
                <label for="team_position"><?php _e( 'Position', 'seminargo' ); ?></label>
            </th>
            <td>
                <input type="text" id="team_position" name="team_position" value="<?php echo esc_attr( $position ); ?>" class="regular-text" />
                <p class="description"><?php _e( 'Job title/position (e.g., "CEO", "Sales Manager")', 'seminargo' ); ?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="team_team"><?php _e( 'Team Group', 'seminargo' ); ?></label>
            </th>
            <td>
                <select id="team_team" name="team_team" class="regular-text">
                    <option value=""><?php _e( 'Select Team Group', 'seminargo' ); ?></option>
                    <option value="ceo" <?php selected( $team, 'ceo' ); ?>>CEO</option>
                    <option value="key account" <?php selected( $team, 'key account' ); ?>>Key Account Manager</option>
                    <option value="sales" <?php selected( $team, 'sales' ); ?>>Sales</option>
                    <option value="accounting" <?php selected( $team, 'accounting' ); ?>>Accounting</option>
                    <option value="meeting planner" <?php selected( $team, 'meeting planner' ); ?>>Meeting Planner</option>
                    <option value="innovation" <?php selected( $team, 'innovation' ); ?>>Innovation</option>
                    <option value="marketing" <?php selected( $team, 'marketing' ); ?>>Marketing</option>
                    <option value="online marketing" <?php selected( $team, 'online marketing' ); ?>>Online Marketing</option>
                    <option value="other" <?php selected( $team, 'other' ); ?>>Other</option>
                </select>
                <p class="description"><?php _e( 'Which team group this member belongs to (determines display order on Team page)', 'seminargo' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save team meta box data
 */
function seminargo_save_team_meta_box_data( $post_id ) {
    // Check if nonce is set
    if ( ! isset( $_POST['seminargo_team_meta_box_nonce'] ) ) {
        return;
    }

    // Verify nonce
    if ( ! wp_verify_nonce( $_POST['seminargo_team_meta_box_nonce'], 'seminargo_team_meta_box' ) ) {
        return;
    }

    // Check if autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check user permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save position field
    if ( isset( $_POST['team_position'] ) ) {
        update_post_meta( $post_id, 'position', sanitize_text_field( $_POST['team_position'] ) );
    }

    // Save team field
    if ( isset( $_POST['team_team'] ) ) {
        update_post_meta( $post_id, 'team', sanitize_text_field( $_POST['team_team'] ) );
    }
}
add_action( 'save_post_team', 'seminargo_save_team_meta_box_data' );
