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

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject   = __( 'MailIntelix test email', 'mailintelix' );
		$message   = self::get_test_email_html( $site_name );
		$headers   = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail(
			$recipient,
			$subject,
			$message,
			$headers
		);

		wp_safe_redirect( MailIntelix_Admin::logs_url( array( 'mailintelix_message' => 'test_sent' ) ) );
		exit;
	}

	/**
	 * Build the HTML body for the test email.
	 *
	 * @param string $site_name Site name.
	 * @return string
	 */
	private static function get_test_email_html( $site_name ) {
		$site_name = esc_html( $site_name );
		$home_url  = esc_url( home_url( '/' ) );

		return '<!doctype html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>MailIntelix test email</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:32px 16px;">
		<tr>
			<td align="center">
				<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
					<tr>
						<td style="background:#0f766e;padding:28px 32px;color:#ffffff;">
							<div style="font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">MailIntelix</div>
							<h1 style="margin:8px 0 0;font-size:26px;line-height:1.3;">Test email delivered to WordPress mail flow</h1>
						</td>
					</tr>
					<tr>
						<td style="padding:32px;">
							<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hi there,</p>
							<p style="margin:0 0 18px;font-size:16px;line-height:1.6;">This is a polished HTML test email from <strong>' . $site_name . '</strong>. If you can see this in MailIntelix, your WordPress email logging and preview flow is working.</p>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
								<tr>
									<td style="padding:16px;font-size:14px;line-height:1.6;">
										<strong>Status:</strong> Test message generated<br />
										<strong>Source:</strong> wp_mail()<br />
										<strong>Site:</strong> ' . $site_name . '
									</td>
								</tr>
							</table>
							<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">Use this message to confirm HTML rendering, headers, logging status, and source preview behavior.</p>
							<a href="' . $home_url . '" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:700;">Visit site</a>
						</td>
					</tr>
					<tr>
						<td style="padding:18px 32px;background:#f9fafb;color:#6b7280;font-size:13px;">Generated by MailIntelix for email logging diagnostics.</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>';
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
