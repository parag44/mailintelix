<?php
/**
 * Settings screen.
 *
 * @package Parag Mail Inspector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin settings.
 */
class Parag_Mail_Inspector_Settings {
	/**
	 * Register settings actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_parag_mail_inspector_save_settings', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'parag-mail-inspector' ) );
		}

		$settings = parag_mail_inspector_get_settings();

		Parag_Mail_Inspector_Admin::render_header( __( 'Settings', 'parag-mail-inspector' ) );
		Parag_Mail_Inspector_Admin::render_notices();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="parag-mail-inspector-card parag-mail-inspector-settings-form">
			<input type="hidden" name="action" value="parag_mail_inspector_save_settings" />
			<?php wp_nonce_field( 'parag_mail_inspector_save_settings' ); ?>

			<div class="parag-mail-inspector-field">
				<label>
					<input type="checkbox" name="logging_enabled" value="1" <?php checked( 1, absint( $settings['logging_enabled'] ) ); ?> />
					<span><?php esc_html_e( 'Enable email logging', 'parag-mail-inspector' ); ?></span>
				</label>
			</div>

			<div class="parag-mail-inspector-field">
				<label for="parag-mail-inspector-retention"><?php esc_html_e( 'Retention period', 'parag-mail-inspector' ); ?></label>
				<select id="parag-mail-inspector-retention" name="retention_days">
					<option value="0" <?php selected( 0, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( 'Forever', 'parag-mail-inspector' ); ?></option>
					<option value="7" <?php selected( 7, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '7 days', 'parag-mail-inspector' ); ?></option>
					<option value="30" <?php selected( 30, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '30 days', 'parag-mail-inspector' ); ?></option>
					<option value="90" <?php selected( 90, absint( $settings['retention_days'] ) ); ?>><?php esc_html_e( '90 days', 'parag-mail-inspector' ); ?></option>
				</select>
			</div>

			<div class="parag-mail-inspector-field">
				<label for="parag-mail-inspector-max-logs"><?php esc_html_e( 'Maximum logs to keep', 'parag-mail-inspector' ); ?></label>
				<input id="parag-mail-inspector-max-logs" type="number" min="0" step="1" name="max_logs" value="<?php echo esc_attr( absint( $settings['max_logs'] ) ); ?>" />
				<p class="description"><?php esc_html_e( 'Use 0 for no maximum limit.', 'parag-mail-inspector' ); ?></p>
			</div>

			<div class="parag-mail-inspector-field">
				<label>
					<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( 1, absint( $settings['delete_data_on_uninstall'] ) ); ?> />
					<span><?php esc_html_e( 'Delete logs and settings on uninstall', 'parag-mail-inspector' ); ?></span>
				</label>
			</div>

			<?php submit_button( __( 'Save Settings', 'parag-mail-inspector' ) ); ?>
		</form>
		<?php
		Parag_Mail_Inspector_Admin::render_footer();
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'parag-mail-inspector' ) );
		}

		check_admin_referer( 'parag_mail_inspector_save_settings' );

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

		update_option( PARAG_MAIL_INSPECTOR_SETTINGS_OPTION, $settings );
		Parag_Mail_Inspector_Logger::maybe_apply_retention();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => 'parag-mail-inspector-settings',
					'parag_mail_inspector_message' => 'settings',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
