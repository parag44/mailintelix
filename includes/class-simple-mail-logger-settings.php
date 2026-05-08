<?php
/**
 * Settings screen.
 *
 * @package Simple Mail Logger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin settings.
 */
class Simple_Mail_Logger_Settings {
	/**
	 * Register settings actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_simple_mail_logger_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-mail-logger' ) );
		}

		$settings = simple_mail_logger_get_settings();

		Simple_Mail_Logger_Admin::render_header( __( 'Settings', 'simple-mail-logger' ) );
		Simple_Mail_Logger_Admin::render_notices();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="simple-mail-logger-card simple-mail-logger-settings-form <?php echo empty( $settings['smtp_enabled'] ) ? '' : 'is-smtp-enabled'; ?>">
			<input type="hidden" name="action" value="simple_mail_logger_save_settings" />
			<?php wp_nonce_field( 'simple_mail_logger_save_settings' ); ?>

			<div class="simple-mail-logger-field">
				<label>
					<input type="checkbox" name="logging_enabled" value="1" <?php checked( 1, absint( $settings['logging_enabled'] ) ); ?> />
					<span><?php esc_html_e( 'Enable email logging', 'simple-mail-logger' ); ?></span>
				</label>
			</div>

			<div class="simple-mail-logger-field">
				<label for="simple-mail-logger-retention"><?php esc_html_e( 'Retention period', 'simple-mail-logger' ); ?></label>
				<select id="simple-mail-logger-retention" name="retention_days">
					<option value="0" <?php selected( 0, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( 'Forever', 'simple-mail-logger' ); ?></option>
					<option value="7" <?php selected( 7, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '7 days', 'simple-mail-logger' ); ?></option>
					<option value="30" <?php selected( 30, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '30 days', 'simple-mail-logger' ); ?></option>
					<option value="90" <?php selected( 90, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '90 days', 'simple-mail-logger' ); ?></option>
				</select>
			</div>

			<div class="simple-mail-logger-field">
				<label for="simple-mail-logger-max-logs"><?php esc_html_e( 'Maximum logs to keep', 'simple-mail-logger' ); ?></label>
				<input id="simple-mail-logger-max-logs" type="number" min="0" step="1" name="max_logs" value="<?php echo esc_attr( absint( $settings['max_logs'] ) ); ?>" />
				<p class="description"><?php esc_html_e( 'Use 0 for no maximum limit.', 'simple-mail-logger' ); ?></p>
			</div>

			<div class="simple-mail-logger-field">
				<label>
					<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( 1, absint( $settings['delete_data_on_uninstall'] ) ); ?> />
					<span><?php esc_html_e( 'Delete logs and settings on uninstall', 'simple-mail-logger' ); ?></span>
				</label>
			</div>

			<hr class="simple-mail-logger-divider" />

			<h2><?php esc_html_e( 'SMTP Settings', 'simple-mail-logger' ); ?></h2>
			<p class="description"><?php esc_html_e( 'When enabled, WordPress emails are sent through your SMTP server. Email logging remains active in both SMTP and default mail modes.', 'simple-mail-logger' ); ?></p>

			<div class="simple-mail-logger-field">
				<label>
					<input id="simple-mail-logger-smtp-enabled" type="checkbox" name="smtp_enabled" value="1" <?php checked( 1, absint( $settings['smtp_enabled'] ) ); ?> />
					<span><?php esc_html_e( 'Enable SMTP sending', 'simple-mail-logger' ); ?></span>
				</label>
			</div>

			<div class="simple-mail-logger-smtp-fields" data-simple-mail-logger-smtp-fields>
				<div class="simple-mail-logger-settings-grid">
					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-host"><?php esc_html_e( 'SMTP host', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-host" type="text" name="smtp_host" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>" placeholder="smtp.example.com" />
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-port"><?php esc_html_e( 'SMTP port', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-port" type="number" min="1" max="65535" step="1" name="smtp_port" value="<?php echo esc_attr( absint( $settings['smtp_port'] ) ); ?>" />
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-encryption"><?php esc_html_e( 'Encryption', 'simple-mail-logger' ); ?></label>
						<select id="simple-mail-logger-smtp-encryption" name="smtp_encryption">
							<option value="none" <?php selected( 'none', $settings['smtp_encryption'] ); ?>><?php esc_html_e( 'None', 'simple-mail-logger' ); ?></option>
							<option value="ssl" <?php selected( 'ssl', $settings['smtp_encryption'] ); ?>><?php esc_html_e( 'SSL', 'simple-mail-logger' ); ?></option>
							<option value="tls" <?php selected( 'tls', $settings['smtp_encryption'] ); ?>><?php esc_html_e( 'TLS', 'simple-mail-logger' ); ?></option>
						</select>
					</div>

					<div class="simple-mail-logger-field simple-mail-logger-field--checkbox-control">
						<label>
							<input type="checkbox" name="smtp_auth" value="1" <?php checked( 1, absint( $settings['smtp_auth'] ) ); ?> />
							<span><?php esc_html_e( 'Use SMTP authentication', 'simple-mail-logger' ); ?></span>
						</label>
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-username"><?php esc_html_e( 'SMTP username', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-username" type="text" name="smtp_username" value="<?php echo esc_attr( $settings['smtp_username'] ); ?>" autocomplete="username" />
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-password"><?php esc_html_e( 'SMTP password', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-password" type="password" name="smtp_password" value="<?php echo esc_attr( empty( $settings['smtp_password'] ) ? '' : Simple_Mail_Logger_SMTP::PASSWORD_MASK ); ?>" autocomplete="new-password" />
						<p class="description"><?php esc_html_e( 'The saved password is masked. Type a new password to replace it.', 'simple-mail-logger' ); ?></p>
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-from-email"><?php esc_html_e( 'From email', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-from-email" type="email" name="smtp_from_email" value="<?php echo esc_attr( $settings['smtp_from_email'] ); ?>" />
					</div>

					<div class="simple-mail-logger-field">
						<label for="simple-mail-logger-smtp-from-name"><?php esc_html_e( 'From name', 'simple-mail-logger' ); ?></label>
						<input id="simple-mail-logger-smtp-from-name" type="text" name="smtp_from_name" value="<?php echo esc_attr( $settings['smtp_from_name'] ); ?>" />
					</div>
				</div>

			</div>

			<div class="simple-mail-logger-settings-actions">
				<button type="submit" name="simple_mail_logger_settings_action" value="save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'simple-mail-logger' ); ?></button>
				<button type="submit" name="simple_mail_logger_settings_action" value="test_smtp_connection" class="button button-secondary simple-mail-logger-smtp-action"><?php esc_html_e( 'Check SMTP Connection', 'simple-mail-logger' ); ?></button>
			</div>
		</form>
		<?php
		Simple_Mail_Logger_Admin::render_footer();
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'simple-mail-logger' ) );
		}

		check_admin_referer( 'simple_mail_logger_save_settings' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Values are sanitized by settings-specific handlers below.
		$posted = wp_unslash( $_POST );

		$settings       = simple_mail_logger_get_settings();
		$retention_days = isset( $posted['retention_days'] ) ? absint( $posted['retention_days'] ) : 0;
		if ( ! in_array( $retention_days, array( 0, 7, 30, 90 ), true ) ) {
			$retention_days = 0;
		}

		$settings['logging_enabled']          = isset( $posted['logging_enabled'] ) ? 1 : 0;
		$settings['retention_days']           = $retention_days;
		$settings['delete_data_on_uninstall'] = isset( $posted['delete_data_on_uninstall'] ) ? 1 : 0;
		$settings['max_logs']                 = isset( $posted['max_logs'] ) ? absint( $posted['max_logs'] ) : 5000;

		$settings = Simple_Mail_Logger_SMTP::sanitize_settings( $posted, $settings );

		update_option( SIMPLE_MAIL_LOGGER_SETTINGS_OPTION, $settings );
		Simple_Mail_Logger_Logger::maybe_apply_retention();

		$settings_action = isset( $posted['simple_mail_logger_settings_action'] ) ? sanitize_key( $posted['simple_mail_logger_settings_action'] ) : 'save';
		if ( 'test_smtp_connection' === $settings_action ) {
			$result = Simple_Mail_Logger_SMTP::test_connection();
			if ( is_wp_error( $result ) ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'                      => 'simple-mail-logger-settings',
							'simple_mail_logger_message' => 'smtp_failed',
							'simple_mail_logger_notice'  => $result->get_error_message(),
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'                       => 'simple-mail-logger-settings',
						'simple_mail_logger_message' => 'smtp_success',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                       => 'simple-mail-logger-settings',
					'simple_mail_logger_message' => 'settings',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
