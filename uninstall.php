<?php
/**
 * Uninstall cleanup.
 *
 * @package MailTally
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mailtally_settings = get_option( 'mailtally_settings', array() );

if ( is_array( $mailtally_settings ) && ! empty( $mailtally_settings['delete_data_on_uninstall'] ) ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup for plugin-owned custom table when the user enabled cleanup.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mailtally_logs' );
	delete_option( 'mailtally_settings' );
}
