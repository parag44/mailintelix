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
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="simple-mail-logger-card simple-mail-logger-settings-form">
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

			<?php submit_button( __( 'Save Settings', 'simple-mail-logger' ) ); ?>
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

		$retention_days = isset( $_POST['retention_days'] ) ? absint( wp_unslash( $_POST['retention_days'] ) ) : 0;
		if ( ! in_array( $retention_days, array( 0, 7, 30, 90 ), true ) ) {
			$retention_days = 0;
		}

		$settings = array(
			'logging_enabled'          => isset( $_POST['logging_enabled'] ) ? 1 : 0,
			'retention_days'           => $retention_days,
			'delete_data_on_uninstall' => isset( $_POST['delete_data_on_uninstall'] ) ? 1 : 0,
			'max_logs'                 => isset( $_POST['max_logs'] ) ? absint( wp_unslash( $_POST['max_logs'] ) ) : 5000,
		);

		update_option( SIMPLE_MAIL_LOGGER_SETTINGS_OPTION, $settings );
		Simple_Mail_Logger_Logger::maybe_apply_retention();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => 'simple-mail-logger-settings',
					'simple_mail_logger_message' => 'settings',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
