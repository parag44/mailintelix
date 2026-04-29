<?php
/**
 * Tools screen.
 *
 * @package MailIntelix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles operational tools.
 */
class MailIntelix_Tools {
	/**
	 * Register tool actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_mailintelix_send_test_email', array( __CLASS__, 'send_test_email' ) );
		add_action( 'admin_post_mailintelix_clear_logs', array( __CLASS__, 'clear_logs' ) );
		add_action( 'admin_post_mailintelix_export_csv', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Render tools page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mailintelix' ) );
		}

		MailIntelix_Admin::render_header( __( 'Tools', 'mailintelix' ) );
		MailIntelix_Admin::render_notices();
		?>
		<div class="mailintelix-tools-grid">
			<div class="mailintelix-card">
				<h2><?php esc_html_e( 'Send test email', 'mailintelix' ); ?></h2>
				<p><?php esc_html_e( 'Send a simple WordPress test email and inspect the result in Email Logs.', 'mailintelix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mailintelix_send_test_email" />
					<?php wp_nonce_field( 'mailintelix_send_test_email' ); ?>
					<div class="mailintelix-tool-field">
						<label for="mailintelix-test-recipient"><?php esc_html_e( 'Recipient', 'mailintelix' ); ?></label>
						<input id="mailintelix-test-recipient" type="email" name="recipient" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required />
					</div>
					<?php submit_button( __( 'Send Test Email', 'mailintelix' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<div class="mailintelix-card">
				<h2><?php esc_html_e( 'Export logs', 'mailintelix' ); ?></h2>
				<p><?php esc_html_e( 'Download all email logs as a CSV file for offline review.', 'mailintelix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mailintelix_export_csv" />
					<?php wp_nonce_field( 'mailintelix_export_csv' ); ?>
					<?php submit_button( __( 'Export CSV', 'mailintelix' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<div class="mailintelix-card mailintelix-danger-card">
				<h2><?php esc_html_e( 'Clear all logs', 'mailintelix' ); ?></h2>
				<p><?php esc_html_e( 'Remove every stored email log from the database.', 'mailintelix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mailintelix-confirm="<?php esc_attr_e( 'Clear all MailIntelix logs?', 'mailintelix' ); ?>">
					<input type="hidden" name="action" value="mailintelix_clear_logs" />
					<?php wp_nonce_field( 'mailintelix_clear_logs' ); ?>
					<?php submit_button( __( 'Clear Logs', 'mailintelix' ), 'delete', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
		MailIntelix_Admin::render_footer();
	}

	/**
	 * Send test email.
	 *
	 * @return void
	 */
	public static function send_test_email() {
		self::verify_request( 'mailintelix_send_test_email' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by verify_request() above.
		$recipient = isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : get_option( 'admin_email' );
		if ( ! is_email( $recipient ) ) {
			$recipient = get_option( 'admin_email' );
		}

		wp_mail(
			$recipient,
			__( 'MailIntelix test email', 'mailintelix' ),
			sprintf(
				/* translators: %s: site name. */
				__( 'This is a MailIntelix test email from %s.', 'mailintelix' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
			)
		);

		wp_safe_redirect( MailIntelix_Admin::logs_url( array( 'mailintelix_message' => 'test_sent' ) ) );
		exit;
	}

	/**
	 * Clear all logs.
	 *
	 * @return void
	 */
	public static function clear_logs() {
		self::verify_request( 'mailintelix_clear_logs' );
		MailIntelix_Logger::clear_logs();

		wp_safe_redirect( MailIntelix_Admin::logs_url( array( 'mailintelix_message' => 'cleared' ) ) );
		exit;
	}

	/**
	 * Export CSV.
	 *
	 * @return void
	 */
	public static function export_csv() {
		global $wpdb;

		self::verify_request( 'mailintelix_export_csv' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Exporting plugin-owned custom table; no user input is included.
		$rows = $wpdb->get_results( 'SELECT * FROM ' . mailintelix_get_logs_table() . ' ORDER BY sent_at DESC, id DESC', ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mailintelix-logs-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		if ( false !== $output ) {
			fputcsv( $output, array( 'id', 'status', 'sent_at', 'to_email', 'subject', 'message', 'headers', 'attachments', 'error_message', 'created_at' ) );
			foreach ( $rows as $row ) {
				fputcsv( $output, $row );
			}
		}

		exit;
	}

	/**
	 * Verify admin-post request.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private static function verify_request( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mailintelix' ) );
		}

		check_admin_referer( $action );
	}
}
