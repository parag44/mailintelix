<?php
/**
 * Admin controller.
 *
 * @package MailTally
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus, assets, and log actions.
 */
class MailTally_Admin {
	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_bulk_log_action' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_admin_bar_link' ), 80 );
		add_action( 'in_admin_header', array( __CLASS__, 'suppress_admin_notices' ), 0 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_mailtally_delete_log', array( __CLASS__, 'handle_delete_log' ) );
		add_action( 'admin_post_mailtally_resend_log', array( __CLASS__, 'handle_resend_log' ) );
		add_action( 'wp_ajax_mailtally_get_log', array( __CLASS__, 'ajax_get_log' ) );
		add_action( 'wp_ajax_mailtally_delete_log', array( __CLASS__, 'ajax_delete_log' ) );
	}

	/**
	 * Handle bulk log actions before wp-admin starts rendering.
	 *
	 * @return void
	 */
	public static function handle_bulk_log_action() {
		if ( ! is_admin() || 'mailtally' !== mailtally_get_request_value( 'page' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately after unslashing.
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== strtoupper( $request_method ) ) {
			return;
		}

		$bulk_action = self::get_requested_bulk_action();
		if ( 'delete' !== $bulk_action ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mailtally' ) );
		}

		check_admin_referer( 'bulk-mailtally_logs' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by check_admin_referer() above.
		$ids           = isset( $_POST['log_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['log_ids'] ) ) : array();
		$deleted_count = MailTally_Logger::delete_logs( $ids );

		wp_safe_redirect(
			self::logs_url(
				array(
					'mailtally_message'       => 'bulk',
					'mailtally_deleted_count' => absint( $deleted_count ),
				)
			)
		);
		exit;
	}

	/**
	 * Read the selected bulk action from the top or bottom list-table controls.
	 *
	 * @return string
	 */
	private static function get_requested_bulk_action() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by the caller before action execution.
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		if ( $action && '-1' !== $action ) {
			return $action;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by the caller before action execution.
		$action2 = isset( $_POST['action2'] ) ? sanitize_key( wp_unslash( $_POST['action2'] ) ) : '';

		return ( $action2 && '-1' !== $action2 ) ? $action2 : '';
	}

	/**
	 * Add a quick Email Logs shortcut to the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public static function register_admin_bar_link( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'mailtally-email-logs',
				'title' => __( 'MailTally Logs', 'mailtally' ),
				'href'  => self::logs_url(),
				'meta'  => array(
					'title' => __( 'View MailTally email logs', 'mailtally' ),
				),
			)
		);
	}

	/**
	 * Hide third-party admin notices on MailTally screens.
	 *
	 * @return void
	 */
	public static function suppress_admin_notices() {
		if ( ! self::is_mailtally_screen() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_action( 'admin_notices', 'update_nag', 3 );
	}

	/**
	 * Check whether current admin page belongs to MailTally.
	 *
	 * @return bool
	 */
	private static function is_mailtally_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = mailtally_get_request_value( 'page' );

		return 0 === strpos( $page, 'mailtally' );
	}

	/**
	 * Register MailTally admin menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'MailTally', 'mailtally' ),
			__( 'MailTally', 'mailtally' ),
			'manage_options',
			'mailtally',
			array( __CLASS__, 'render_logs_page' ),
			'dashicons-email',
			80
		);

		add_submenu_page(
			'mailtally',
			__( 'Email Logs', 'mailtally' ),
			__( 'Email Logs', 'mailtally' ),
			'manage_options',
			'mailtally',
			array( __CLASS__, 'render_logs_page' )
		);

		add_submenu_page(
			'mailtally',
			__( 'Settings', 'mailtally' ),
			__( 'Settings', 'mailtally' ),
			'manage_options',
			'mailtally-settings',
			array( 'MailTally_Settings', 'render_page' )
		);

		add_submenu_page(
			'mailtally',
			__( 'Tools', 'mailtally' ),
			__( 'Tools', 'mailtally' ),
			'manage_options',
			'mailtally-tools',
			array( 'MailTally_Tools', 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on plugin screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'mailtally' ) ) {
			return;
		}

		wp_enqueue_style(
			'mailtally-admin',
			MAILTALLY_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MAILTALLY_VERSION
		);

		wp_enqueue_script(
			'mailtally-admin',
			MAILTALLY_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MAILTALLY_VERSION,
			true
		);

		wp_localize_script(
			'mailtally-admin',
			'SimpleMailLoggerAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mailtally_ajax' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Delete this email log?', 'mailtally' ),
					'loading'       => __( 'Loading email preview...', 'mailtally' ),
					'error'         => __( 'Could not load this email log.', 'mailtally' ),
				),
			)
		);
	}

	/**
	 * Render the logs screen.
	 *
	 * @return void
	 */
	public static function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mailtally' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-table.php';

		$table = new MailTally_Table();
		$table->prepare_items();

		self::render_header( __( 'Email Logs', 'mailtally' ) );
		self::render_notices();
		?>
		<div class="mailtally-card">
			<form method="get" class="mailtally-filters">
				<input type="hidden" name="page" value="mailtally" />
				<div class="mailtally-filter-bar">
					<div class="mailtally-filter-group">
						<?php $table->views(); ?>
						<div class="mailtally-filter-row">
							<label>
								<span><?php esc_html_e( 'From', 'mailtally' ); ?></span>
								<input type="date" name="date_from" value="<?php echo esc_attr( mailtally_get_request_value( 'date_from' ) ); ?>" />
							</label>
							<label>
								<span><?php esc_html_e( 'To', 'mailtally' ); ?></span>
								<input type="date" name="date_to" value="<?php echo esc_attr( mailtally_get_request_value( 'date_to' ) ); ?>" />
							</label>
						</div>
					</div>
					<div class="mailtally-search-group">
						<?php $table->search_box( __( 'Search logs', 'mailtally' ), 'mailtally-search' ); ?>
					</div>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( self::logs_url() ); ?>">
				<input type="hidden" name="page" value="mailtally" />
				<?php $table->display(); ?>
			</form>
		</div>
		<div class="mailtally-modal" id="mailtally-preview-modal" aria-hidden="true">
			<div class="mailtally-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mailtally-modal-title">
				<button type="button" class="mailtally-modal__close" data-mailtally-close aria-label="<?php esc_attr_e( 'Close preview', 'mailtally' ); ?>">&times;</button>
				<div id="mailtally-modal-content"></div>
			</div>
		</div>
		<?php
		self::render_footer();
	}

	/**
	 * Render shared page header.
	 *
	 * @param string $title Page title.
	 * @return void
	 */
	public static function render_header( $title ) {
		?>
		<div class="wrap mailtally-wrap">
			<div class="mailtally-hero">
				<div class="mailtally-logo" aria-hidden="true">
					<span class="dashicons dashicons-email mailtally-logo__icon"></span>
				</div>
				<div class="mailtally-hero__content">
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php esc_html_e( 'Email logging, debugging, and delivery insights for WordPress.', 'mailtally' ); ?></p>
				</div>
			</div>
		<?php
	}

	/**
	 * Close shared page wrapper.
	 *
	 * @return void
	 */
	public static function render_footer() {
		echo '</div>';
	}

	/**
	 * Show action notices.
	 *
	 * @return void
	 */
	public static function render_notices() {
		$message = mailtally_get_request_value( 'mailtally_message' );
		if ( empty( $message ) ) {
			return;
		}

		$messages = array(
			'deleted'   => __( 'Email log deleted.', 'mailtally' ),
			'bulk'      => __( 'Selected email logs deleted.', 'mailtally' ),
			'resent'    => __( 'Email resend requested.', 'mailtally' ),
			'cleared'   => __( 'All email logs cleared.', 'mailtally' ),
			'settings'  => __( 'Settings saved.', 'mailtally' ),
			'test_sent' => __( 'Test email sent. Check Email Logs for the result.', 'mailtally' ),
		);

		if ( isset( $messages[ $message ] ) ) {
			if ( 'bulk' === $message ) {
				$count = absint( mailtally_get_request_value( 'mailtally_deleted_count' ) );
				if ( $count > 0 ) {
					$messages[ $message ] = sprintf(
						/* translators: %d: number of deleted email logs. */
						_n( '%d email log deleted.', '%d email logs deleted.', $count, 'mailtally' ),
						$count
					);
				}
			}

			echo '<div class="mailtally-notices">';
			printf(
				'<div class="mailtally-notice mailtally-notice--success" role="status"><p>%s</p></div>',
				esc_html( $messages[ $message ] )
			);
			echo '</div>';
		}
	}

	/**
	 * Handle single delete fallback action.
	 *
	 * @return void
	 */
	public static function handle_delete_log() {
		self::verify_action_request( 'mailtally_delete_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		MailTally_Logger::delete_log( $log_id );

		wp_safe_redirect( self::logs_url( array( 'mailtally_message' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Handle resend fallback action.
	 *
	 * @return void
	 */
	public static function handle_resend_log() {
		self::verify_action_request( 'mailtally_resend_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		MailTally_Logger::resend( $log_id );

		wp_safe_redirect( self::logs_url( array( 'mailtally_message' => 'resent' ) ) );
		exit;
	}

	/**
	 * Return log preview data for modal.
	 *
	 * @return void
	 */
	public static function ajax_get_log() {
		self::verify_ajax_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- AJAX nonce is verified by verify_ajax_request() above.
		$log_id = isset( $_POST['log_id'] ) ? absint( wp_unslash( $_POST['log_id'] ) ) : 0;
		$log    = MailTally_Logger::get_log( $log_id );

		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Email log not found.', 'mailtally' ) ), 404 );
		}

		wp_send_json_success(
			array(
				'html' => self::render_preview_html( $log ),
			)
		);
	}

	/**
	 * Delete log over AJAX.
	 *
	 * @return void
	 */
	public static function ajax_delete_log() {
		self::verify_ajax_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- AJAX nonce is verified by verify_ajax_request() above.
		$log_id = isset( $_POST['log_id'] ) ? absint( wp_unslash( $_POST['log_id'] ) ) : 0;
		MailTally_Logger::delete_log( $log_id );

		wp_send_json_success();
	}

	/**
	 * Build preview modal markup.
	 *
	 * @param object $log Log row.
	 * @return string
	 */
	private static function render_preview_html( $log ) {
		ob_start();
		?>
		<div class="mailtally-preview">
			<h2 id="mailtally-modal-title"><?php echo esc_html( $log->subject ? $log->subject : __( '(No subject)', 'mailtally' ) ); ?></h2>
			<div class="mailtally-preview__meta">
				<span><?php echo wp_kses_post( mailtally_status_badge( $log->status ) ); ?></span>
				<span><strong><?php esc_html_e( 'Sent at:', 'mailtally' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->sent_at ) ); ?></span>
				<span><strong><?php esc_html_e( 'To:', 'mailtally' ); ?></strong> <?php echo esc_html( $log->to_email ); ?></span>
			</div>
			<?php if ( ! empty( $log->error_message ) ) : ?>
				<div class="mailtally-error-block"><?php echo esc_html( $log->error_message ); ?></div>
			<?php endif; ?>
			<div class="mailtally-preview__grid">
				<div>
					<h3><?php esc_html_e( 'Headers', 'mailtally' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->headers, __( 'No headers logged for this email.', 'mailtally' ) ) ); ?>
				</div>
				<div>
					<h3><?php esc_html_e( 'Attachments', 'mailtally' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->attachments, __( 'No attachments for this email.', 'mailtally' ) ) ); ?>
				</div>
			</div>
			<div class="mailtally-tabs">
				<button type="button" class="is-active" data-mailtally-tab="html"><?php esc_html_e( 'HTML view', 'mailtally' ); ?></button>
				<button type="button" data-mailtally-tab="source"><?php esc_html_e( 'Text/source view', 'mailtally' ); ?></button>
			</div>
			<div class="mailtally-tab-panel is-active" data-mailtally-panel="html">
				<iframe class="mailtally-email-frame" title="<?php esc_attr_e( 'Email HTML preview', 'mailtally' ); ?>" sandbox srcdoc="<?php echo esc_attr( $log->message ); ?>"></iframe>
			</div>
			<div class="mailtally-tab-panel" data-mailtally-panel="source">
				<pre><?php echo esc_html( $log->message ); ?></pre>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render email metadata with a friendly empty state.
	 *
	 * @param string $value Stored metadata value.
	 * @param string $empty_message Empty-state message.
	 * @return string
	 */
	private static function render_meta_value( $value, $empty_message ) {
		$trimmed = trim( (string) $value );

		if ( '' === $trimmed || '[]' === $trimmed || '{}' === $trimmed ) {
			return sprintf(
				'<div class="mailtally-empty-meta">%s</div>',
				esc_html( $empty_message )
			);
		}

		return sprintf(
			'<pre>%s</pre>',
			esc_html( $trimmed )
		);
	}

	/**
	 * Verify admin-post request.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private static function verify_action_request( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mailtally' ) );
		}

		check_admin_referer( $action );
	}

	/**
	 * Verify AJAX requests.
	 *
	 * @return void
	 */
	private static function verify_ajax_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mailtally' ) ), 403 );
		}

		check_ajax_referer( 'mailtally_ajax', 'nonce' );
	}

	/**
	 * Logs page URL.
	 *
	 * @param array $args Extra args.
	 * @return string
	 */
	public static function logs_url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=mailtally' ) );
	}
}

/**
 * Get a sanitized request value.
 *
 * @param string $key Request key.
 * @return string
 */
function mailtally_get_request_value( $key ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request helper for admin filters and screen detection.
	if ( ! isset( $_REQUEST[ $key ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately below after unslashing.
	$value = wp_unslash( $_REQUEST[ $key ] );

	return is_scalar( $value ) ? sanitize_text_field( $value ) : '';
}

/**
 * Render status badge.
 *
 * @param string $status Log status.
 * @return string
 */
function mailtally_status_badge( $status ) {
	$status = sanitize_key( $status );
	$label  = __( 'Unknown', 'mailtally' );

	if ( 'sent' === $status ) {
		$label = __( 'Sent', 'mailtally' );
	} elseif ( 'failed' === $status ) {
		$label = __( 'Failed', 'mailtally' );
	}

	return sprintf(
		'<span class="mailtally-badge mailtally-badge--%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( $label )
	);
}
