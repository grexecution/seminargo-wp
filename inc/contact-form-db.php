<?php
/**
 * Contact Form Database Storage
 *
 * Creates database table and handles storing contact form submissions
 *
 * @package Seminargo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create contact submissions database table on theme activation
 */
function seminargo_create_contact_submissions_table() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'contact_submissions';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		email varchar(255) NOT NULL,
		phone varchar(100) DEFAULT NULL,
		subject varchar(500) DEFAULT NULL,
		message text NOT NULL,
		privacy_accepted tinyint(1) NOT NULL DEFAULT 0,
		ip_address varchar(100) DEFAULT NULL,
		user_agent text DEFAULT NULL,
		submitted_at datetime NOT NULL,
		email_sent tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY email (email),
		KEY submitted_at (submitted_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Store the database version for future updates
	update_option( 'seminargo_contact_db_version', '1.0' );
}

// Create table on theme activation
add_action( 'after_switch_theme', 'seminargo_create_contact_submissions_table' );

// Also check and create table on init if it doesn't exist (for safety)
add_action( 'init', function() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'contact_submissions';

	// Check if table exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		seminargo_create_contact_submissions_table();
	}
}, 999 );

/**
 * Save contact form submission to database
 *
 * @param array $data Form data to save
 * @return int|false The number of rows inserted, or false on error
 */
function seminargo_save_contact_submission( $data ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'contact_submissions';

	$insert_data = [
		'name'             => sanitize_text_field( $data['name'] ),
		'email'            => sanitize_email( $data['email'] ),
		'phone'            => ! empty( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : null,
		'subject'          => ! empty( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : null,
		'message'          => sanitize_textarea_field( $data['message'] ),
		'privacy_accepted' => ! empty( $data['privacy_accepted'] ) ? 1 : 0,
		'ip_address'       => seminargo_get_user_ip(),
		'user_agent'       => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 500 ) : null,
		'submitted_at'     => current_time( 'mysql' ),
		'email_sent'       => ! empty( $data['email_sent'] ) ? 1 : 0,
	];

	$result = $wpdb->insert(
		$table_name,
		$insert_data,
		[ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d' ]
	);

	return $result;
}

/**
 * Get user IP address (GDPR-compliant - can be anonymized if needed)
 *
 * @return string IP address or empty string
 */
function seminargo_get_user_ip() {
	$ip = '';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Handle proxy
		$ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	// Validate IP
	$ip = filter_var( trim( $ip ), FILTER_VALIDATE_IP );

	return $ip ? $ip : '';
}

/**
 * Get all contact submissions (for admin viewing)
 *
 * @param array $args Query arguments
 * @return array Array of submissions
 */
function seminargo_get_contact_submissions( $args = [] ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'contact_submissions';

	$defaults = [
		'limit'    => 50,
		'offset'   => 0,
		'order_by' => 'submitted_at',
		'order'    => 'DESC',
	];

	$args = wp_parse_args( $args, $defaults );

	$sql = $wpdb->prepare(
		"SELECT * FROM $table_name ORDER BY {$args['order_by']} {$args['order']} LIMIT %d OFFSET %d",
		$args['limit'],
		$args['offset']
	);

	return $wpdb->get_results( $sql );
}

/**
 * Add admin menu for viewing contact submissions
 */
add_action( 'admin_menu', function() {
	add_menu_page(
		__( 'Kontaktanfragen', 'seminargo' ),
		__( 'Kontaktanfragen', 'seminargo' ),
		'manage_options',
		'contact-submissions',
		'seminargo_render_contact_submissions_page',
		'dashicons-email',
		30
	);
} );

/**
 * Render contact submissions admin page
 */
function seminargo_render_contact_submissions_page() {
	global $wpdb;

	// Handle delete action
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && check_admin_referer( 'delete-submission-' . $_GET['id'] ) ) {
		$id = absint( $_GET['id'] );
		$wpdb->delete( $wpdb->prefix . 'contact_submissions', [ 'id' => $id ], [ '%d' ] );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Anfrage gelöscht.', 'seminargo' ) . '</p></div>';
	}

	$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$per_page = 20;
	$offset   = ( $page - 1 ) * $per_page;

	$submissions = seminargo_get_contact_submissions( [
		'limit'  => $per_page,
		'offset' => $offset,
	] );

	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}contact_submissions" );
	$total_pages = ceil( $total / $per_page );

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kontaktanfragen', 'seminargo' ); ?></h1>
		<p><?php printf( esc_html__( 'Insgesamt %d Anfragen', 'seminargo' ), $total ); ?></p>

		<?php if ( empty( $submissions ) ) : ?>
			<p><?php esc_html_e( 'Noch keine Kontaktanfragen vorhanden.', 'seminargo' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 5%;"><?php esc_html_e( 'ID', 'seminargo' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Name', 'seminargo' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'E-Mail', 'seminargo' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Telefon', 'seminargo' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Betreff', 'seminargo' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Datum', 'seminargo' ); ?></th>
						<th style="width: 8%;"><?php esc_html_e( 'Gesendet', 'seminargo' ); ?></th>
						<th style="width: 8%;"><?php esc_html_e( 'Datenschutz', 'seminargo' ); ?></th>
						<th style="width: 9%;"><?php esc_html_e( 'Aktionen', 'seminargo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $submissions as $submission ) : ?>
						<tr>
							<td><?php echo esc_html( $submission->id ); ?></td>
							<td><?php echo esc_html( $submission->name ); ?></td>
							<td><a href="mailto:<?php echo esc_attr( $submission->email ); ?>"><?php echo esc_html( $submission->email ); ?></a></td>
							<td><?php echo esc_html( $submission->phone ?: '-' ); ?></td>
							<td><?php echo esc_html( $submission->subject ?: '-' ); ?></td>
							<td><?php echo esc_html( mysql2date( 'd.m.Y H:i', $submission->submitted_at ) ); ?></td>
							<td>
								<?php if ( $submission->email_sent ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;" title="<?php esc_attr_e( 'E-Mail erfolgreich gesendet', 'seminargo' ); ?>"></span>
								<?php else : ?>
									<span class="dashicons dashicons-no" style="color: red;" title="<?php esc_attr_e( 'E-Mail nicht gesendet', 'seminargo' ); ?>"></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $submission->privacy_accepted ) : ?>
									<span class="dashicons dashicons-yes" style="color: green;" title="<?php esc_attr_e( 'Datenschutz akzeptiert', 'seminargo' ); ?>"></span>
								<?php else : ?>
									<span class="dashicons dashicons-no" style="color: orange;" title="<?php esc_attr_e( 'Datenschutz nicht akzeptiert', 'seminargo' ); ?>"></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="#" class="button button-small" onclick="event.preventDefault(); var modal = document.getElementById('submission-<?php echo esc_attr( $submission->id ); ?>'); modal.style.display = 'block';"><?php esc_html_e( 'Ansehen', 'seminargo' ); ?></a>
							</td>
						</tr>

						<!-- Modal for viewing full submission -->
						<div id="submission-<?php echo esc_attr( $submission->id ); ?>" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
							<div style="background-color:#fff; margin:5% auto; padding:30px; border:1px solid #888; width:80%; max-width:700px; border-radius:8px;">
								<span onclick="document.getElementById('submission-<?php echo esc_attr( $submission->id ); ?>').style.display='none'" style="float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
								<h2><?php esc_html_e( 'Kontaktanfrage Details', 'seminargo' ); ?></h2>
								<p><strong><?php esc_html_e( 'Name:', 'seminargo' ); ?></strong> <?php echo esc_html( $submission->name ); ?></p>
								<p><strong><?php esc_html_e( 'E-Mail:', 'seminargo' ); ?></strong> <a href="mailto:<?php echo esc_attr( $submission->email ); ?>"><?php echo esc_html( $submission->email ); ?></a></p>
								<p><strong><?php esc_html_e( 'Telefon:', 'seminargo' ); ?></strong> <?php echo esc_html( $submission->phone ?: '-' ); ?></p>
								<p><strong><?php esc_html_e( 'Betreff:', 'seminargo' ); ?></strong> <?php echo esc_html( $submission->subject ?: '-' ); ?></p>
								<p><strong><?php esc_html_e( 'Nachricht:', 'seminargo' ); ?></strong></p>
								<div style="background: #f5f5f5; padding: 15px; border-radius: 4px; white-space: pre-wrap;"><?php echo esc_html( $submission->message ); ?></div>
								<p style="margin-top: 20px;"><strong><?php esc_html_e( 'IP-Adresse:', 'seminargo' ); ?></strong> <?php echo esc_html( $submission->ip_address ?: '-' ); ?></p>
								<p><strong><?php esc_html_e( 'Datum:', 'seminargo' ); ?></strong> <?php echo esc_html( mysql2date( 'd.m.Y H:i:s', $submission->submitted_at ) ); ?></p>
								<p><strong><?php esc_html_e( 'E-Mail gesendet:', 'seminargo' ); ?></strong> <?php echo $submission->email_sent ? esc_html__( 'Ja', 'seminargo' ) : esc_html__( 'Nein', 'seminargo' ); ?></p>
								<p><strong><?php esc_html_e( 'Datenschutz akzeptiert:', 'seminargo' ); ?></strong> <?php echo $submission->privacy_accepted ? esc_html__( 'Ja', 'seminargo' ) : esc_html__( 'Nein', 'seminargo' ); ?></p>
								<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
									<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=contact-submissions&action=delete&id=' . $submission->id ), 'delete-submission-' . $submission->id ); ?>" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Sind Sie sicher, dass Sie diese Anfrage löschen möchten?', 'seminargo' ); ?>');"><?php esc_html_e( 'Löschen', 'seminargo' ); ?></a>
									<button onclick="document.getElementById('submission-<?php echo esc_attr( $submission->id ); ?>').style.display='none'" class="button button-primary"><?php esc_html_e( 'Schließen', 'seminargo' ); ?></button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo paginate_links( [
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $page,
							'total'   => $total_pages,
						] );
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
