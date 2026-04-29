<?php
/**
 * Settings screen.
 *
 * @package MailIntelix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin settings.
 */
class MailIntelix_Settings {
	/**
	 * Register settings actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_mailintelix_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mailintelix' ) );
		}

		$settings = mailintelix_get_settings();

		MailIntelix_Admin::render_header( __( 'Settings', 'mailintelix' ) );
		MailIntelix_Admin::render_notices();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mailintelix-card mailintelix-settings-form">
			<input type="hidden" name="action" value="mailintelix_save_settings" />
			<?php wp_nonce_field( 'mailintelix_save_settings' ); ?>

			<div class="mailintelix-field">
				<label>
					<input type="checkbox" name="logging_enabled" value="1" <?php checked( 1, absint( $settings['logging_enabled'] ) ); ?> />
					<span><?php esc_html_e( 'Enable email logging', 'mailintelix' ); ?></span>
				</label>
			</div>

			<div class="mailintelix-field">
				<label for="mailintelix-retention"><?php esc_html_e( 'Retention period', 'mailintelix' ); ?></label>
				<select id="mailintelix-retention" name="retention_days">
					<option value="0" <?php selected( 0, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( 'Forever', 'mailintelix' ); ?></option>
					<option value="7" <?php selected( 7, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '7 days', 'mailintelix' ); ?></option>
					<option value="30" <?php selected( 30, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '30 days', 'mailintelix' ); ?></option>
					<option value="90" <?php selected( 90, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '90 days', 'mailintelix' ); ?></option>
				</select>
			</div>

			<div class="mailintelix-field">
				<label for="mailintelix-max-logs"><?php esc_html_e( 'Maximum logs to keep', 'mailintelix' ); ?></label>
				<input id="mailintelix-max-logs" type="number" min="0" step="1" name="max_logs" value="<?php echo esc_attr( absint( $settings['max_logs'] ) ); ?>" />
				<p class="description"><?php esc_html_e( 'Use 0 for no maximum limit.', 'mailintelix' ); ?></p>
			</div>

			<div class="mailintelix-field">
				<label>
					<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( 1, absint( $settings['delete_data_on_uninstall'] ) ); ?> />
					<span><?php esc_html_e( 'Delete logs and settings on uninstall', 'mailintelix' ); ?></span>
				</label>
			</div>

			<?php submit_button( __( 'Save Settings', 'mailintelix' ) ); ?>
		</form>
		<?php
		MailIntelix_Admin::render_footer();
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mailintelix' ) );
		}

		check_admin_referer( 'mailintelix_save_settings' );

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

		update_option( MAILINTELIX_SETTINGS_OPTION, $settings );
		MailIntelix_Logger::maybe_apply_retention();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => 'mailintelix-settings',
					'mailintelix_message' => 'settings',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
