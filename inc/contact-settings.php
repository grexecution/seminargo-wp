<?php
/**
 * Contact Form Settings
 *
 * Adds meta boxes on the Kontakt page for email configuration
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seminargo_Contact_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    /**
     * Add meta boxes to Kontakt page
     */
    public function add_meta_boxes() {
        // Get kontakt page ID
        $kontakt_page = get_page_by_path( 'kontakt' );

        // Also check for pages using the Kontakt template
        $pages_with_template = get_posts( [
            'post_type'   => 'page',
            'meta_key'    => '_wp_page_template',
            'meta_value'  => 'page-kontakt.php',
            'numberposts' => -1,
            'fields'      => 'ids',
        ] );

        $kontakt_pages = $pages_with_template;
        if ( $kontakt_page ) {
            $kontakt_pages[] = $kontakt_page->ID;
        }

        // Only show on Kontakt pages
        global $post;
        if ( ! $post || ! in_array( $post->ID, $kontakt_pages ) ) {
            // Check if current page uses the template
            if ( $post && get_page_template_slug( $post->ID ) !== 'page-kontakt.php' ) {
                return;
            }
        }

        add_meta_box(
            'contact_email_settings',
            __( 'E-Mail Einstellungen', 'seminargo' ),
            [ $this, 'render_email_settings' ],
            'page',
            'normal',
            'high'
        );

        add_meta_box(
            'contact_smtp_settings',
            __( 'SMTP Einstellungen (für zuverlässigen Versand)', 'seminargo' ),
            [ $this, 'render_smtp_settings' ],
            'page',
            'normal',
            'default'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return;
        }

        global $post;
        if ( ! $post || get_page_template_slug( $post->ID ) !== 'page-kontakt.php' ) {
            return;
        }

        wp_enqueue_script(
            'seminargo-contact-admin',
            SEMINARGO_ASSETS_URL . 'js/contact-admin.js',
            [ 'jquery' ],
            SEMINARGO_VERSION,
            true
        );

        wp_localize_script( 'seminargo-contact-admin', 'seminargoContact', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'seminargo_test_email' ),
            'strings' => [
                'sending' => __( 'Sende...', 'seminargo' ),
                'send'    => __( 'Test E-Mail senden', 'seminargo' ),
                'error'   => __( 'Fehler beim Senden', 'seminargo' ),
            ],
        ] );
    }

    /**
     * Render email settings meta box
     */
    public function render_email_settings( $post ) {
        wp_nonce_field( 'seminargo_contact_settings', 'seminargo_contact_nonce' );

        $recipient = get_post_meta( $post->ID, '_contact_recipient_email', true );
        $cc = get_post_meta( $post->ID, '_contact_cc_email', true );
        $from_email = get_post_meta( $post->ID, '_contact_from_email', true );
        $from_name = get_post_meta( $post->ID, '_contact_from_name', true );
        $subject = get_post_meta( $post->ID, '_contact_email_subject', true );
        ?>
        <style>
            .contact-settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .contact-settings-grid .full-width { grid-column: 1 / -1; }
            .contact-field { margin-bottom: 15px; }
            .contact-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .contact-field input { width: 100%; }
            .contact-field .description { color: #666; font-size: 12px; margin-top: 4px; }
            .test-email-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        </style>

        <div class="contact-settings-grid">
            <div class="contact-field">
                <label for="contact_recipient_email"><?php esc_html_e( 'Empfänger E-Mail', 'seminargo' ); ?></label>
                <input type="email" id="contact_recipient_email" name="contact_recipient_email"
                       value="<?php echo esc_attr( $recipient ); ?>"
                       placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                <p class="description"><?php esc_html_e( 'Wohin sollen Anfragen gesendet werden? Standard: Admin E-Mail', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field">
                <label for="contact_cc_email"><?php esc_html_e( 'CC E-Mail (optional)', 'seminargo' ); ?></label>
                <input type="email" id="contact_cc_email" name="contact_cc_email"
                       value="<?php echo esc_attr( $cc ); ?>"
                       placeholder="">
                <p class="description"><?php esc_html_e( 'Kopie an weitere Adresse senden', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field">
                <label for="contact_from_email"><?php esc_html_e( 'Absender E-Mail', 'seminargo' ); ?></label>
                <input type="email" id="contact_from_email" name="contact_from_email"
                       value="<?php echo esc_attr( $from_email ); ?>"
                       placeholder="noreply@<?php echo esc_attr( parse_url( home_url(), PHP_URL_HOST ) ); ?>">
                <p class="description"><?php esc_html_e( 'Von welcher Adresse soll gesendet werden?', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field">
                <label for="contact_from_name"><?php esc_html_e( 'Absender Name', 'seminargo' ); ?></label>
                <input type="text" id="contact_from_name" name="contact_from_name"
                       value="<?php echo esc_attr( $from_name ); ?>"
                       placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                <p class="description"><?php esc_html_e( 'Name der als Absender angezeigt wird', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field full-width">
                <label for="contact_email_subject"><?php esc_html_e( 'E-Mail Betreff', 'seminargo' ); ?></label>
                <input type="text" id="contact_email_subject" name="contact_email_subject"
                       value="<?php echo esc_attr( $subject ); ?>"
                       placeholder="<?php esc_attr_e( 'Neue Kontaktanfrage von {name}', 'seminargo' ); ?>">
                <p class="description"><?php esc_html_e( 'Verwende {name} für den Namen des Absenders', 'seminargo' ); ?></p>
            </div>
        </div>

        <div class="test-email-section">
            <button type="button" id="send-test-email" class="button button-secondary">
                <?php esc_html_e( 'Test E-Mail senden', 'seminargo' ); ?>
            </button>
            <span id="test-email-result" style="margin-left: 10px;"></span>
            <p class="description"><?php esc_html_e( 'Speichern Sie zuerst die Seite, dann testen Sie die E-Mail-Einstellungen.', 'seminargo' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render SMTP settings meta box
     */
    public function render_smtp_settings( $post ) {
        $enabled = get_post_meta( $post->ID, '_contact_smtp_enabled', true );
        $host = get_post_meta( $post->ID, '_contact_smtp_host', true );
        $port = get_post_meta( $post->ID, '_contact_smtp_port', true ) ?: '587';
        $encryption = get_post_meta( $post->ID, '_contact_smtp_encryption', true ) ?: 'tls';
        $username = get_post_meta( $post->ID, '_contact_smtp_username', true );
        $password = get_post_meta( $post->ID, '_contact_smtp_password', true );
        ?>
        <p class="description" style="margin-bottom: 15px;">
            <?php esc_html_e( 'Ohne SMTP verwendet WordPress PHP mail(), was auf vielen Servern nicht funktioniert. Für zuverlässigen Versand SMTP aktivieren.', 'seminargo' ); ?>
        </p>

        <div class="contact-field" style="margin-bottom: 20px;">
            <label>
                <input type="checkbox" name="contact_smtp_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
                <strong><?php esc_html_e( 'SMTP aktivieren', 'seminargo' ); ?></strong>
            </label>
        </div>

        <div class="contact-settings-grid">
            <div class="contact-field">
                <label for="contact_smtp_host"><?php esc_html_e( 'SMTP Host', 'seminargo' ); ?></label>
                <input type="text" id="contact_smtp_host" name="contact_smtp_host"
                       value="<?php echo esc_attr( $host ); ?>"
                       placeholder="smtp.gmail.com">
                <p class="description"><?php esc_html_e( 'z.B. smtp.gmail.com, smtp.office365.com', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field">
                <label for="contact_smtp_port"><?php esc_html_e( 'SMTP Port', 'seminargo' ); ?></label>
                <input type="text" id="contact_smtp_port" name="contact_smtp_port"
                       value="<?php echo esc_attr( $port ); ?>"
                       placeholder="587">
                <p class="description"><?php esc_html_e( '587 (TLS), 465 (SSL), oder 25', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field">
                <label for="contact_smtp_encryption"><?php esc_html_e( 'Verschlüsselung', 'seminargo' ); ?></label>
                <select id="contact_smtp_encryption" name="contact_smtp_encryption" style="width: 100%;">
                    <option value="tls" <?php selected( $encryption, 'tls' ); ?>>TLS</option>
                    <option value="ssl" <?php selected( $encryption, 'ssl' ); ?>>SSL</option>
                    <option value="none" <?php selected( $encryption, 'none' ); ?>><?php esc_html_e( 'Keine', 'seminargo' ); ?></option>
                </select>
            </div>

            <div class="contact-field">
                <label for="contact_smtp_username"><?php esc_html_e( 'SMTP Benutzername', 'seminargo' ); ?></label>
                <input type="text" id="contact_smtp_username" name="contact_smtp_username"
                       value="<?php echo esc_attr( $username ); ?>"
                       placeholder="your@email.com">
                <p class="description"><?php esc_html_e( 'Meist Ihre E-Mail-Adresse', 'seminargo' ); ?></p>
            </div>

            <div class="contact-field full-width">
                <label for="contact_smtp_password"><?php esc_html_e( 'SMTP Passwort', 'seminargo' ); ?></label>
                <input type="password" id="contact_smtp_password" name="contact_smtp_password"
                       value="<?php echo esc_attr( $password ); ?>"
                       placeholder="<?php esc_attr_e( 'App-Passwort oder E-Mail-Passwort', 'seminargo' ); ?>">
                <p class="description"><?php esc_html_e( 'Bei Gmail: App-Passwort erstellen unter myaccount.google.com/apppasswords', 'seminargo' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['seminargo_contact_nonce'] ) ||
             ! wp_verify_nonce( $_POST['seminargo_contact_nonce'], 'seminargo_contact_settings' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Email settings
        $fields = [
            'contact_recipient_email' => 'sanitize_email',
            'contact_cc_email'        => 'sanitize_email',
            'contact_from_email'      => 'sanitize_email',
            'contact_from_name'       => 'sanitize_text_field',
            'contact_email_subject'   => 'sanitize_text_field',
            'contact_smtp_host'       => 'sanitize_text_field',
            'contact_smtp_port'       => 'absint',
            'contact_smtp_encryption' => 'sanitize_text_field',
            'contact_smtp_username'   => 'sanitize_text_field',
            'contact_smtp_password'   => null, // Don't sanitize password
        ];

        foreach ( $fields as $field => $sanitize ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = $sanitize ? $sanitize( $_POST[ $field ] ) : $_POST[ $field ];
                update_post_meta( $post_id, '_' . $field, $value );
            }
        }

        // Checkbox
        $smtp_enabled = isset( $_POST['contact_smtp_enabled'] ) ? '1' : '';
        update_post_meta( $post_id, '_contact_smtp_enabled', $smtp_enabled );
    }

    /**
     * Get setting from page meta
     */
    public static function get_setting( $page_id, $key, $default = '' ) {
        $value = get_post_meta( $page_id, '_contact_' . $key, true );
        return ! empty( $value ) ? $value : $default;
    }
}

// Initialize
new Seminargo_Contact_Settings();

/**
 * Configure SMTP if enabled (from page meta)
 */
add_action( 'phpmailer_init', function( $phpmailer ) {
    // Find kontakt page with template
    $kontakt_pages = get_posts( [
        'post_type'   => 'page',
        'meta_key'    => '_wp_page_template',
        'meta_value'  => 'page-kontakt.php',
        'numberposts' => 1,
        'fields'      => 'ids',
    ] );

    if ( empty( $kontakt_pages ) ) {
        return;
    }

    $page_id = $kontakt_pages[0];
    $enabled = get_post_meta( $page_id, '_contact_smtp_enabled', true );

    if ( empty( $enabled ) ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = get_post_meta( $page_id, '_contact_smtp_host', true );
    $phpmailer->Port       = get_post_meta( $page_id, '_contact_smtp_port', true ) ?: 587;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = get_post_meta( $page_id, '_contact_smtp_username', true );
    $phpmailer->Password   = get_post_meta( $page_id, '_contact_smtp_password', true );

    $encryption = get_post_meta( $page_id, '_contact_smtp_encryption', true ) ?: 'tls';
    if ( $encryption !== 'none' ) {
        $phpmailer->SMTPSecure = $encryption;
    }
});

/**
 * AJAX handler for test email
 */
add_action( 'wp_ajax_seminargo_send_test_email', function() {
    check_ajax_referer( 'seminargo_test_email', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Keine Berechtigung', 'seminargo' ) );
    }

    $page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

    if ( ! $page_id ) {
        // Find kontakt page
        $kontakt_pages = get_posts( [
            'post_type'   => 'page',
            'meta_key'    => '_wp_page_template',
            'meta_value'  => 'page-kontakt.php',
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );
        $page_id = ! empty( $kontakt_pages ) ? $kontakt_pages[0] : 0;
    }

    $to = get_post_meta( $page_id, '_contact_recipient_email', true ) ?: get_option( 'admin_email' );
    $from_name = get_post_meta( $page_id, '_contact_from_name', true ) ?: get_bloginfo( 'name' );
    $from_email = get_post_meta( $page_id, '_contact_from_email', true ) ?: 'noreply@' . parse_url( home_url(), PHP_URL_HOST );

    $subject = __( 'Test E-Mail von seminargo Kontaktformular', 'seminargo' );
    $message = sprintf(
        __( "Dies ist eine Test-E-Mail von Ihrer Website %s.\n\nWenn Sie diese E-Mail erhalten haben, funktioniert Ihr Kontaktformular korrekt.\n\nZeitstempel: %s", 'seminargo' ),
        home_url(),
        current_time( 'mysql' )
    );

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    ];

    $cc = get_post_meta( $page_id, '_contact_cc_email', true );
    if ( ! empty( $cc ) ) {
        $headers[] = 'Cc: ' . $cc;
    }

    $sent = wp_mail( $to, $subject, $message, $headers );

    if ( $sent ) {
        wp_send_json_success( sprintf( __( 'Test E-Mail wurde an %s gesendet!', 'seminargo' ), $to ) );
    } else {
        wp_send_json_error( __( 'E-Mail konnte nicht gesendet werden. Überprüfen Sie die SMTP-Einstellungen.', 'seminargo' ) );
    }
});
