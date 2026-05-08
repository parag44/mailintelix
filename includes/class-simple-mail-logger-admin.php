<?php
/**
 * Admin controller.
 *
 * @package Simple Mail Logger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus, assets, and log actions.
 */
class Simple_Mail_Logger_Admin {
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
		add_action( 'admin_post_simple_mail_logger_delete_log', array( __CLASS__, 'handle_delete_log' ) );
		add_action( 'admin_post_simple_mail_logger_resend_log', array( __CLASS__, 'handle_resend_log' ) );
		add_action( 'wp_ajax_simple_mail_logger_get_log', array( __CLASS__, 'ajax_get_log' ) );
		add_action( 'wp_ajax_simple_mail_logger_delete_log', array( __CLASS__, 'ajax_delete_log' ) );
	}

	/**
	 * Handle bulk log actions before wp-admin starts rendering.
	 *
	 * @return void
	 */
	public static function handle_bulk_log_action() {
		if ( ! is_admin() || 'simple-mail-logger' !== simple_mail_logger_get_request_value( 'page' ) ) {
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'simple-mail-logger' ) );
		}

		check_admin_referer( 'bulk-simple_mail_logger_logs' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by check_admin_referer() above.
		$ids           = isset( $_POST['log_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['log_ids'] ) ) : array();
		$deleted_count = Simple_Mail_Logger_Logger::delete_logs( $ids );

		wp_safe_redirect(
			self::logs_url(
				array(
					'simple_mail_logger_message'       => 'bulk',
					'simple_mail_logger_deleted_count' => absint( $deleted_count ),
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
				'id'    => 'simple-mail-logger-email-logs',
				'title' => __( 'Simple Mail Logger Logs', 'simple-mail-logger' ),
				'href'  => self::logs_url(),
				'meta'  => array(
					'title' => __( 'View Simple Mail Logger email logs', 'simple-mail-logger' ),
				),
			)
		);
	}

	/**
	 * Hide third-party admin notices on Simple Mail Logger screens.
	 *
	 * @return void
	 */
	public static function suppress_admin_notices() {
		if ( ! self::is_simple_mail_logger_screen() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_action( 'admin_notices', 'update_nag', 3 );
	}

	/**
	 * Check whether current admin page belongs to Simple Mail Logger.
	 *
	 * @return bool
	 */
	private static function is_simple_mail_logger_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = simple_mail_logger_get_request_value( 'page' );

		return 0 === strpos( $page, 'simple-mail-logger' );
	}

	/**
	 * Register Simple Mail Logger admin menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Simple Mail Logger', 'simple-mail-logger' ),
			__( 'Simple Mail Logger', 'simple-mail-logger' ),
			'manage_options',
			'simple-mail-logger',
			array( __CLASS__, 'render_logs_page' ),
			'dashicons-email',
			80
		);

		add_submenu_page(
			'simple-mail-logger',
			__( 'Email Logs', 'simple-mail-logger' ),
			__( 'Email Logs', 'simple-mail-logger' ),
			'manage_options',
			'simple-mail-logger',
			array( __CLASS__, 'render_logs_page' )
		);

		add_submenu_page(
			'simple-mail-logger',
			__( 'Settings', 'simple-mail-logger' ),
			__( 'Settings', 'simple-mail-logger' ),
			'manage_options',
			'simple-mail-logger-settings',
			array( 'Simple_Mail_Logger_Settings', 'render_page' )
		);

		add_submenu_page(
			'simple-mail-logger',
			__( 'Tools', 'simple-mail-logger' ),
			__( 'Tools', 'simple-mail-logger' ),
			'manage_options',
			'simple-mail-logger-tools',
			array( 'Simple_Mail_Logger_Tools', 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on plugin screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'simple-mail-logger' ) ) {
			return;
		}

		wp_enqueue_style(
			'simple-mail-logger-admin',
			SIMPLE_MAIL_LOGGER_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SIMPLE_MAIL_LOGGER_VERSION
		);

		wp_enqueue_script(
			'simple-mail-logger-admin',
			SIMPLE_MAIL_LOGGER_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SIMPLE_MAIL_LOGGER_VERSION,
			true
		);

		wp_localize_script(
			'simple-mail-logger-admin',
			'SimpleMailLoggerAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'simple_mail_logger_ajax' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Delete this email log?', 'simple-mail-logger' ),
					'loading'       => __( 'Loading email preview...', 'simple-mail-logger' ),
					'error'         => __( 'Could not load this email log.', 'simple-mail-logger' ),
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'simple-mail-logger' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-table.php';

		$table = new Simple_Mail_Logger_Table();
		$table->prepare_items();

		self::render_header( __( 'Email Logs', 'simple-mail-logger' ) );
		self::render_notices();
		?>
		<div class="simple-mail-logger-card">
			<form method="get" class="simple-mail-logger-filters">
				<input type="hidden" name="page" value="simple-mail-logger" />
				<div class="simple-mail-logger-filter-bar">
					<div class="simple-mail-logger-filter-group">
						<?php $table->views(); ?>
						<div class="simple-mail-logger-filter-row">
							<label>
								<span><?php esc_html_e( 'From', 'simple-mail-logger' ); ?></span>
								<input type="date" name="date_from" value="<?php echo esc_attr( simple_mail_logger_get_request_value( 'date_from' ) ); ?>" />
							</label>
							<label>
								<span><?php esc_html_e( 'To', 'simple-mail-logger' ); ?></span>
								<input type="date" name="date_to" value="<?php echo esc_attr( simple_mail_logger_get_request_value( 'date_to' ) ); ?>" />
							</label>
						</div>
					</div>
					<div class="simple-mail-logger-search-group">
						<?php $table->search_box( __( 'Search logs', 'simple-mail-logger' ), 'simple-mail-logger-search' ); ?>
					</div>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( self::logs_url() ); ?>">
				<input type="hidden" name="page" value="simple-mail-logger" />
				<?php $table->display(); ?>
			</form>
		</div>
		<div class="simple-mail-logger-modal" id="simple-mail-logger-preview-modal" aria-hidden="true">
			<div class="simple-mail-logger-modal__panel" role="dialog" aria-modal="true" aria-labelledby="simple-mail-logger-modal-title">
				<button type="button" class="simple-mail-logger-modal__close" data-simple-mail-logger-close aria-label="<?php esc_attr_e( 'Close preview', 'simple-mail-logger' ); ?>">&times;</button>
				<div id="simple-mail-logger-modal-content"></div>
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
		<div class="wrap simple-mail-logger-wrap">
			<div class="simple-mail-logger-hero">
				<div class="simple-mail-logger-logo" aria-hidden="true">
					<span class="dashicons dashicons-email simple-mail-logger-logo__icon"></span>
				</div>
				<div class="simple-mail-logger-hero__content">
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php esc_html_e( 'Email logging, debugging, and delivery insights for WordPress.', 'simple-mail-logger' ); ?></p>
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
		$message = simple_mail_logger_get_request_value( 'simple_mail_logger_message' );
		if ( empty( $message ) ) {
			return;
		}

		$messages = array(
			'deleted'      => __( 'Email log deleted.', 'simple-mail-logger' ),
			'bulk'         => __( 'Selected email logs deleted.', 'simple-mail-logger' ),
			'resent'       => __( 'Email resend requested.', 'simple-mail-logger' ),
			'cleared'      => __( 'All email logs cleared.', 'simple-mail-logger' ),
			'settings'     => __( 'Settings saved.', 'simple-mail-logger' ),
			'smtp_success' => __( 'SMTP connection established successfully.', 'simple-mail-logger' ),
			'test_sent'    => __( 'Test email sent. Check Email Logs for the result.', 'simple-mail-logger' ),
		);

		if ( 'smtp_failed' === $message ) {
			$messages[ $message ] = simple_mail_logger_get_request_value( 'simple_mail_logger_notice' );
			if ( empty( $messages[ $message ] ) ) {
				$messages[ $message ] = __( 'SMTP connection could not be established.', 'simple-mail-logger' );
			}
		}

		if ( isset( $messages[ $message ] ) ) {
			if ( 'bulk' === $message ) {
				$count = absint( simple_mail_logger_get_request_value( 'simple_mail_logger_deleted_count' ) );
				if ( $count > 0 ) {
					$messages[ $message ] = sprintf(
						/* translators: %d: number of deleted email logs. */
						_n( '%d email log deleted.', '%d email logs deleted.', $count, 'simple-mail-logger' ),
						$count
					);
				}
			}

			echo '<div class="simple-mail-logger-notices">';
			printf(
				'<div class="simple-mail-logger-notice simple-mail-logger-notice--%1$s" role="status"><p>%2$s</p></div>',
				'smtp_failed' === $message ? 'error' : 'success',
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
		self::verify_action_request( 'simple_mail_logger_delete_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		Simple_Mail_Logger_Logger::delete_log( $log_id );

		wp_safe_redirect( self::logs_url( array( 'simple_mail_logger_message' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Handle resend fallback action.
	 *
	 * @return void
	 */
	public static function handle_resend_log() {
		self::verify_action_request( 'simple_mail_logger_resend_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		Simple_Mail_Logger_Logger::resend( $log_id );

		wp_safe_redirect( self::logs_url( array( 'simple_mail_logger_message' => 'resent' ) ) );
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
		$log    = Simple_Mail_Logger_Logger::get_log( $log_id );

		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Email log not found.', 'simple-mail-logger' ) ), 404 );
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
		Simple_Mail_Logger_Logger::delete_log( $log_id );

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
		<div class="simple-mail-logger-preview">
			<h2 id="simple-mail-logger-modal-title"><?php echo esc_html( $log->subject ? $log->subject : __( '(No subject)', 'simple-mail-logger' ) ); ?></h2>
			<div class="simple-mail-logger-preview__meta">
				<span><?php echo wp_kses_post( simple_mail_logger_status_badge( $log->status ) ); ?></span>
				<span><strong><?php esc_html_e( 'Sent at:', 'simple-mail-logger' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->sent_at ) ); ?></span>
				<span><strong><?php esc_html_e( 'To:', 'simple-mail-logger' ); ?></strong> <?php echo esc_html( $log->to_email ); ?></span>
			</div>
			<?php if ( ! empty( $log->error_message ) ) : ?>
				<div class="simple-mail-logger-error-block"><?php echo esc_html( $log->error_message ); ?></div>
			<?php endif; ?>
			<div class="simple-mail-logger-preview__grid">
				<div>
					<h3><?php esc_html_e( 'Headers', 'simple-mail-logger' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->headers, __( 'No headers logged for this email.', 'simple-mail-logger' ) ) ); ?>
				</div>
				<div>
					<h3><?php esc_html_e( 'Attachments', 'simple-mail-logger' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->attachments, __( 'No attachments for this email.', 'simple-mail-logger' ) ) ); ?>
				</div>
			</div>
			<div class="simple-mail-logger-tabs">
				<button type="button" class="is-active" data-simple-mail-logger-tab="html"><?php esc_html_e( 'HTML view', 'simple-mail-logger' ); ?></button>
				<button type="button" data-simple-mail-logger-tab="source"><?php esc_html_e( 'Text/source view', 'simple-mail-logger' ); ?></button>
			</div>
			<div class="simple-mail-logger-tab-panel is-active" data-simple-mail-logger-panel="html">
				<iframe class="simple-mail-logger-email-frame" title="<?php esc_attr_e( 'Email HTML preview', 'simple-mail-logger' ); ?>" sandbox srcdoc="<?php echo esc_attr( $log->message ); ?>"></iframe>
			</div>
			<div class="simple-mail-logger-tab-panel" data-simple-mail-logger-panel="source">
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
				'<div class="simple-mail-logger-empty-meta">%s</div>',
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'simple-mail-logger' ) );
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
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-mail-logger' ) ), 403 );
		}

		check_ajax_referer( 'simple_mail_logger_ajax', 'nonce' );
	}

	/**
	 * Logs page URL.
	 *
	 * @param array $args Extra args.
	 * @return string
	 */
	public static function logs_url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=simple-mail-logger' ) );
	}
}

/**
 * Get a sanitized request value.
 *
 * @param string $key Request key.
 * @return string
 */
function simple_mail_logger_get_request_value( $key ) {
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
function simple_mail_logger_status_badge( $status ) {
	$status = sanitize_key( $status );
	$label  = __( 'Unknown', 'simple-mail-logger' );

	if ( 'sent' === $status ) {
		$label = __( 'Sent', 'simple-mail-logger' );
	} elseif ( 'failed' === $status ) {
		$label = __( 'Failed', 'simple-mail-logger' );
	}

	return sprintf(
		'<span class="simple-mail-logger-badge simple-mail-logger-badge--%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( $label )
	);
}
