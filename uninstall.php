<?php
/**
 * Uninstall cleanup.
 *
 * @package Parag Mail Inspector
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$parag_mail_inspector_settings = get_option( 'parag_mail_inspector_settings', array() );

if ( is_array( $parag_mail_inspector_settings ) && ! empty( $parag_mail_inspector_settings['delete_data_on_uninstall'] ) ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup for plugin-owned custom table when the user enabled cleanup.
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'parag_mail_inspector_logs' );
	delete_option( 'parag_mail_inspector_settings' );
}
