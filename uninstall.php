<?php
/**
 * Uninstall cleanup.
 *
 * @package Simple Mail Logger
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$simple_mail_logger_settings = get_option( 'simple_mail_logger_settings', array() );

if ( is_array( $simple_mail_logger_settings ) && ! empty( $simple_mail_logger_settings['delete_data_on_uninstall'] ) ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup for plugin-owned custom table when the user enabled cleanup.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'simple_mail_logger_logs' );
	delete_option( 'simple_mail_logger_settings' );
}
