<?php
/**
 * Activation tasks.
 *
 * @package Simple Mail Logger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates database schema and default settings.
 */
class Simple_Mail_Logger_Activator {
	/**
	 * Run plugin activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_table();

		if ( false === get_option( SIMPLE_MAIL_LOGGER_SETTINGS_OPTION, false ) ) {
			add_option(
				SIMPLE_MAIL_LOGGER_SETTINGS_OPTION,
				array(
					'logging_enabled'         => 1,
					'retention_days'          => 0,
					'delete_data_on_uninstall' => 0,
					'max_logs'                => 5000,
					'smtp_enabled'            => 0,
					'smtp_host'               => '',
					'smtp_port'               => 587,
					'smtp_encryption'         => 'none',
					'smtp_auth'               => 1,
					'smtp_username'           => '',
					'smtp_password'           => '',
					'smtp_from_email'         => '',
					'smtp_from_name'          => '',
				)
			);
		}
	}

	/**
	 * Create or update the logs table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = simple_mail_logger_get_logs_table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			status varchar(20) NOT NULL DEFAULT 'sent',
			sent_at datetime NOT NULL,
			to_email longtext NULL,
			subject text NULL,
			message longtext NULL,
			headers longtext NULL,
			attachments longtext NULL,
			error_message longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY sent_at (sent_at),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
