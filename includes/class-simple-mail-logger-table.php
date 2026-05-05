<?php
/**
 * Email logs list table.
 *
 * @package Simple Mail Logger
 */

defined( 'ABSPATH' ) || exit;

/**
 * WP_List_Table implementation for email logs.
 */
class Simple_Mail_Logger_Table extends WP_List_Table {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'simple_mail_logger_log',
				'plural'   => 'simple_mail_logger_logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Prepare rows.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		$where        = array( '1=1' );
		$params       = array();
		$table_name   = esc_sql( simple_mail_logger_get_logs_table() );

		$status = simple_mail_logger_get_request_value( 'status' );
		if ( in_array( $status, array( 'sent', 'failed' ), true ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$date_from = simple_mail_logger_get_request_value( 'date_from' );
		if ( $date_from ) {
			$where[]  = 'sent_at >= %s';
			$params[] = $date_from . ' 00:00:00';
		}

		$date_to = simple_mail_logger_get_request_value( 'date_to' );
		if ( $date_to ) {
			$where[]  = 'sent_at <= %s';
			$params[] = $date_to . ' 23:59:59';
		}

		$search = simple_mail_logger_get_request_value( 's' );
		if ( $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(to_email LIKE %s OR subject LIKE %s OR message LIKE %s OR error_message LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$total_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
		$query_sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY sent_at DESC, id DESC LIMIT %d OFFSET %d";

		$total_params = $params;
		$query_params = array_merge( $params, array( $per_page, $offset ) );

		$total_items = empty( $total_params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is assembled from fixed fragments and plugin-owned table name; no user input without placeholders.
			? (int) $wpdb->get_var( $total_sql )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is assembled from fixed fragments and plugin-owned table name; dynamic values are prepared.
			: (int) $wpdb->get_var( $wpdb->prepare( $total_sql, $total_params ) );

		$this->items = empty( $query_params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is assembled from fixed fragments and plugin-owned table name; pagination values are constants here.
			? $wpdb->get_results( $query_sql )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL is assembled from fixed fragments and plugin-owned table name; dynamic values are prepared.
			: $wpdb->get_results( $wpdb->prepare( $query_sql, $query_params ) );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'sent_at' );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'status'        => __( 'Status', 'simple-mail-logger' ),
			'sent_at'       => __( 'Sent at', 'simple-mail-logger' ),
			'to_email'      => __( 'To', 'simple-mail-logger' ),
			'subject'       => __( 'Subject', 'simple-mail-logger' ),
			'error_message' => __( 'Error', 'simple-mail-logger' ),
			'actions'       => __( 'Actions', 'simple-mail-logger' ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'simple-mail-logger' ),
		);
	}

	/**
	 * Get status views.
	 *
	 * @return array
	 */
	protected function get_views() {
		global $wpdb;

		$table_name = esc_sql( simple_mail_logger_get_logs_table() );
		$current    = simple_mail_logger_get_request_value( 'status' );
		$counts     = array(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counting plugin-owned email log rows.
			'all'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ),
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counting plugin-owned email log rows.
			'sent'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'sent' ) ),
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counting plugin-owned email log rows.
			'failed' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE status = %s", 'failed' ) ),
		);

		return array(
			'all'    => $this->view_link( __( 'All', 'simple-mail-logger' ), '', empty( $current ), $counts['all'] ),
			'sent'   => $this->view_link( __( 'Successful', 'simple-mail-logger' ), 'sent', 'sent' === $current, $counts['sent'] ),
			'failed' => $this->view_link( __( 'Failed', 'simple-mail-logger' ), 'failed', 'failed' === $current, $counts['failed'] ),
		);
	}

	/**
	 * Default column render.
	 *
	 * @param object $item Log row.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
				return simple_mail_logger_status_badge( $item->status );
			case 'sent_at':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->sent_at ) );
			case 'to_email':
				return esc_html( wp_trim_words( $item->to_email, 12, '...' ) );
			case 'subject':
				return esc_html( $item->subject ? $item->subject : __( '(No subject)', 'simple-mail-logger' ) );
			case 'error_message':
				return esc_html( wp_trim_words( $item->error_message, 10, '...' ) );
			case 'actions':
				return $this->render_actions( $item );
			default:
				return '';
		}
	}

	/**
	 * Checkbox column.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * Subject column with row actions.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	protected function column_subject( $item ) {
		$title = esc_html( $item->subject ? $item->subject : __( '(No subject)', 'simple-mail-logger' ) );

		return '<strong>' . $title . '</strong>';
	}

	/**
	 * Process bulk delete.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'simple-mail-logger' ) );
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by check_admin_referer() above.
		$ids           = isset( $_REQUEST['log_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['log_ids'] ) ) : array();
		$deleted_count = Simple_Mail_Logger_Logger::delete_logs( $ids );

		wp_safe_redirect(
			Simple_Mail_Logger_Admin::logs_url(
				array(
					'simple_mail_logger_message'       => 'bulk',
					'simple_mail_logger_deleted_count' => absint( $deleted_count ),
				)
			)
		);
		exit;
	}

	/**
	 * Render compact action buttons column.
	 *
	 * @param object $item Log row.
	 * @return string
	 */
	private function render_actions( $item ) {
		$links = $this->row_action_links( $item );

		return '<div class="simple-mail-logger-actions">' . implode( '', $links ) . '</div>';
	}

	/**
	 * Row action links.
	 *
	 * @param object $item Log row.
	 * @return array
	 */
	private function row_action_links( $item ) {
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'simple_mail_logger_delete_log',
					'log_id' => absint( $item->id ),
				),
				admin_url( 'admin-post.php' )
			),
			'simple_mail_logger_delete_log'
		);

		$resend_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'simple_mail_logger_resend_log',
					'log_id' => absint( $item->id ),
				),
				admin_url( 'admin-post.php' )
			),
			'simple_mail_logger_resend_log'
		);

		return array(
			'view'   => sprintf(
				'<button type="button" class="button button-small simple-mail-logger-view-log" data-log-id="%d">%s</button>',
				absint( $item->id ),
				esc_html__( 'View email', 'simple-mail-logger' )
			),
			'resend' => sprintf(
				'<a class="button button-small" href="%s">%s</a>',
				esc_url( $resend_url ),
				esc_html__( 'Resend', 'simple-mail-logger' )
			),
			'delete' => sprintf(
				'<a class="button button-small simple-mail-logger-delete-link" href="%s">%s</a>',
				esc_url( $delete_url ),
				esc_html__( 'Delete', 'simple-mail-logger' )
			),
		);
	}

	/**
	 * Build view link.
	 *
	 * @param string $label Label.
	 * @param string $status Status.
	 * @param bool   $current Whether active.
	 * @param int    $count Count.
	 * @return string
	 */
	private function view_link( $label, $status, $current, $count ) {
		$args = array( 'page' => 'simple-mail-logger' );
		if ( $status ) {
			$args['status'] = $status;
		}

		return sprintf(
			'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
			esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) ),
			$current ? 'current' : '',
			esc_html( $label ),
			absint( $count )
		);
	}
}
