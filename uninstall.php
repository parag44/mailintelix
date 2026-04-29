<?php
/**
 * Uninstall cleanup.
 *
 * @package MailIntelix
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mailintelix_settings = get_option( 'mailintelix_settings', array() );

if ( is_array( $mailintelix_settings ) && ! empty( $mailintelix_settings['delete_data_on_uninstall'] ) ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup for plugin-owned custom table when the user enabled cleanup.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mailintelix_logs' );
	delete_option( 'mailintelix_settings' );
}
