<?php
/**
 * Email logger.
 *
 * @package MailIntelix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into wp_mail outcomes and persists email logs.
 */
class MailIntelix_Logger {
	/**
	 * Register mail hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'log_success' ), 10, 1 );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_failure' ), 10, 1 );
	}

	/**
	 * Log successful PHPMailer processing.
	 *
	 * @param array $mail_data Mail data from WordPress.
	 * @return void
	 */
	public static function log_success( $mail_data ) {
		self::log(
			array(
				'status'      => 'sent',
				'to'          => isset( $mail_data['to'] ) ? $mail_data['to'] : '',
				'subject'     => isset( $mail_data['subject'] ) ? $mail_data['subject'] : '',
				'message'     => isset( $mail_data['message'] ) ? $mail_data['message'] : '',
				'headers'     => isset( $mail_data['headers'] ) ? $mail_data['headers'] : '',
				'attachments' => isset( $mail_data['attachments'] ) ? $mail_data['attachments'] : '',
			)
		);
	}

	/**
	 * Log failed send attempt.
	 *
	 * @param WP_Error $error Failure details.
	 * @return void
	 */
	public static function log_failure( $error ) {
		$mail_data = array();

		if ( is_wp_error( $error ) ) {
			$data = $error->get_error_data();
			if ( is_array( $data ) ) {
				$mail_data = $data;
			}
		}

		self::log(
			array(
				'status'        => 'failed',
				'to'            => isset( $mail_data['to'] ) ? $mail_data['to'] : '',
				'subject'       => isset( $mail_data['subject'] ) ? $mail_data['subject'] : '',
				'message'       => isset( $mail_data['message'] ) ? $mail_data['message'] : '',
				'headers'       => isset( $mail_data['headers'] ) ? $mail_data['headers'] : '',
				'attachments'   => isset( $mail_data['attachments'] ) ? $mail_data['attachments'] : '',
				'error_message' => is_wp_error( $error ) ? $error->get_error_message() : '',
			)
		);
	}

	/**
	 * Persist a log row.
	 *
	 * @param array $data Log data.
	 * @return int|false Insert ID or false.
	 */
	public static function log( $data ) {
		global $wpdb;

		if ( ! self::is_logging_enabled() ) {
			return false;
		}

		$now        = current_time( 'mysql' );
		$table_name = mailintelix_get_logs_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- MailIntelix stores email logs in its own custom table.
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'status'        => self::sanitize_status( isset( $data['status'] ) ? $data['status'] : 'sent' ),
				'sent_at'       => isset( $data['sent_at'] ) ? sanitize_text_field( $data['sent_at'] ) : $now,
				'to_email'      => self::normalize_value( isset( $data['to'] ) ? $data['to'] : '' ),
				'subject'       => self::clean_text( isset( $data['subject'] ) ? $data['subject'] : '' ),
				'message'       => self::clean_payload( isset( $data['message'] ) ? $data['message'] : '' ),
				'headers'       => self::normalize_value( isset( $data['headers'] ) ? $data['headers'] : '' ),
				'attachments'   => self::normalize_value( isset( $data['attachments'] ) ? $data['attachments'] : '' ),
				'error_message' => self::clean_payload( isset( $data['error_message'] ) ? $data['error_message'] : '' ),
				'created_at'    => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		self::maybe_apply_retention();

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Resend a stored email.
	 *
	 * @param int $log_id Log ID.
	 * @return bool|WP_Error
	 */
	public static function resend( $log_id ) {
		$log = self::get_log( $log_id );

		if ( ! $log ) {
			return new WP_Error( 'mailintelix_missing_log', __( 'Email log not found.', 'mailintelix' ) );
		}

		$to          = self::decode_value( $log->to_email );
		$headers     = self::decode_value( $log->headers );
		$attachments = self::decode_value( $log->attachments );

		return wp_mail( $to, $log->subject, $log->message, $headers, $attachments );
	}

	/**
	 * Get a single log by ID.
	 *
	 * @param int $log_id Log ID.
	 * @return object|null
	 */
	public static function get_log( $log_id ) {
		global $wpdb;

		$table_name = esc_sql( mailintelix_get_logs_table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Email logs are mutable admin data from a custom table.
		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from $wpdb->prefix and a plugin constant.
				"SELECT * FROM {$table_name} WHERE id = %d",
				absint( $log_id )
			)
		);
	}

	/**
	 * Delete one log.
	 *
	 * @param int $log_id Log ID.
	 * @return int|false
	 */
	public static function delete_log( $log_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting custom-table email log data.
		return $wpdb->delete(
			mailintelix_get_logs_table(),
			array( 'id' => absint( $log_id ) ),
			array( '%d' )
		);
	}

	/**
	 * Delete several logs.
	 *
	 * @param array $ids Log IDs.
	 * @return int Number deleted.
	 */
	public static function delete_logs( $ids ) {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', (array) $ids ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting custom-table email log data.
		return (int) $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name is plugin-owned and placeholders are generated from sanitized IDs.
				"DELETE FROM " . mailintelix_get_logs_table() . " WHERE id IN ({$placeholders})",
				$ids
			)
		);
	}

	/**
	 * Clear all logs.
	 *
	 * @return int|false
	 */
	public static function clear_logs() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clearing plugin-owned custom table; no user input is included.
		return $wpdb->query( 'TRUNCATE TABLE ' . mailintelix_get_logs_table() );
	}

	/**
	 * Apply retention settings.
	 *
	 * @return void
	 */
	public static function maybe_apply_retention() {
		global $wpdb;

		$settings   = mailintelix_get_settings();
		$table_name = esc_sql( mailintelix_get_logs_table() );

		if ( ! empty( $settings['retention_days'] ) ) {
			$days = absint( $settings['retention_days'] );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention cleanup for plugin-owned email log table.
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from $wpdb->prefix and a plugin constant.
					"DELETE FROM {$table_name} WHERE created_at < DATE_SUB(%s, INTERVAL %d DAY)",
					current_time( 'mysql' ),
					$days
				)
			);
		}

		if ( ! empty( $settings['max_logs'] ) ) {
			$max_logs = absint( $settings['max_logs'] );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counting plugin-owned email log rows.
			$count    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

			if ( $count > $max_logs ) {
				$offset = $max_logs - 1;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Finding retention cutoff in plugin-owned email log table.
				$cutoff = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from $wpdb->prefix and a plugin constant.
						"SELECT id FROM {$table_name} ORDER BY id DESC LIMIT 1 OFFSET %d",
						max( 0, $offset )
					)
				);

				if ( $cutoff ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Retention cleanup for plugin-owned email log table.
					$wpdb->query(
						$wpdb->prepare(
							// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is generated from $wpdb->prefix and a plugin constant.
							"DELETE FROM {$table_name} WHERE id < %d",
							absint( $cutoff )
						)
					);
				}
			}
		}
	}

	/**
	 * Is logging enabled?
	 *
	 * @return bool
	 */
	private static function is_logging_enabled() {
		$settings = mailintelix_get_settings();

		return ! empty( $settings['logging_enabled'] );
	}

	/**
	 * Normalize arrays to JSON strings while preserving simple strings.
	 *
	 * @param mixed $value Value to normalize.
	 * @return string
	 */
	public static function normalize_value( $value ) {
		if ( is_array( $value ) ) {
			$value = wp_json_encode( array_map( array( __CLASS__, 'clean_text' ), wp_unslash( $value ) ) );
		}

		return self::clean_payload( $value );
	}

	/**
	 * Clean a single-line string.
	 *
	 * @param mixed $value Value to clean.
	 * @return string
	 */
	private static function clean_text( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( wp_check_invalid_utf8( (string) $value ) );
	}

	/**
	 * Clean multiline payloads while preserving email source content.
	 *
	 * @param mixed $value Value to clean.
	 * @return string
	 */
	private static function clean_payload( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return wp_check_invalid_utf8( (string) $value );
	}

	/**
	 * Decode JSON list values when possible.
	 *
	 * @param string $value Stored value.
	 * @return array|string
	 */
	public static function decode_value( $value ) {
		$decoded = json_decode( (string) $value, true );

		return is_array( $decoded ) ? $decoded : $value;
	}

	/**
	 * Validate log status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private static function sanitize_status( $status ) {
		$status = sanitize_key( $status );

		return in_array( $status, array( 'sent', 'failed', 'unknown' ), true ) ? $status : 'unknown';
	}
}

/**
 * Get the full log table name.
 *
 * @return string
 */
function mailintelix_get_logs_table() {
	global $wpdb;

	return $wpdb->prefix . MAILINTELIX_LOGS_TABLE;
}

/**
 * Get normalized settings.
 *
 * @return array
 */
function mailintelix_get_settings() {
	$defaults = array(
		'logging_enabled'          => 1,
		'retention_days'           => 0,
		'delete_data_on_uninstall' => 0,
		'max_logs'                 => 5000,
	);

	$settings = get_option( MAILINTELIX_SETTINGS_OPTION, array() );

	return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
}
