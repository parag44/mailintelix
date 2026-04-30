<?php
/**
 * Admin controller.
 *
 * @package MailIntelix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus, assets, and log actions.
 */
class MailIntelix_Admin {
	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_admin_bar_link' ), 80 );
		add_action( 'in_admin_header', array( __CLASS__, 'suppress_admin_notices' ), 0 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_mailintelix_delete_log', array( __CLASS__, 'handle_delete_log' ) );
		add_action( 'admin_post_mailintelix_resend_log', array( __CLASS__, 'handle_resend_log' ) );
		add_action( 'wp_ajax_mailintelix_get_log', array( __CLASS__, 'ajax_get_log' ) );
		add_action( 'wp_ajax_mailintelix_delete_log', array( __CLASS__, 'ajax_delete_log' ) );
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
				'id'    => 'mailintelix-email-logs',
				'title' => __( 'MailIntelix Logs', 'mailintelix' ),
				'href'  => self::logs_url(),
				'meta'  => array(
					'title' => __( 'View MailIntelix email logs', 'mailintelix' ),
				),
			)
		);
	}

	/**
	 * Hide third-party admin notices on MailIntelix screens.
	 *
	 * @return void
	 */
	public static function suppress_admin_notices() {
		if ( ! self::is_mailintelix_screen() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_action( 'admin_notices', 'update_nag', 3 );
	}

	/**
	 * Check whether current admin page belongs to MailIntelix.
	 *
	 * @return bool
	 */
	private static function is_mailintelix_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = mailintelix_get_request_value( 'page' );

		return 0 === strpos( $page, 'mailintelix' );
	}

	/**
	 * Register MailIntelix admin menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'MailIntelix', 'mailintelix' ),
			__( 'MailIntelix', 'mailintelix' ),
			'manage_options',
			'mailintelix',
			array( __CLASS__, 'render_logs_page' ),
			'dashicons-email',
			80
		);

		add_submenu_page(
			'mailintelix',
			__( 'Email Logs', 'mailintelix' ),
			__( 'Email Logs', 'mailintelix' ),
			'manage_options',
			'mailintelix',
			array( __CLASS__, 'render_logs_page' )
		);

		add_submenu_page(
			'mailintelix',
			__( 'Settings', 'mailintelix' ),
			__( 'Settings', 'mailintelix' ),
			'manage_options',
			'mailintelix-settings',
			array( 'MailIntelix_Settings', 'render_page' )
		);

		add_submenu_page(
			'mailintelix',
			__( 'Tools', 'mailintelix' ),
			__( 'Tools', 'mailintelix' ),
			'manage_options',
			'mailintelix-tools',
			array( 'MailIntelix_Tools', 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on plugin screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'mailintelix' ) ) {
			return;
		}

		wp_enqueue_style(
			'mailintelix-admin',
			MAILINTELIX_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MAILINTELIX_VERSION
		);

		wp_enqueue_script(
			'mailintelix-admin',
			MAILINTELIX_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			MAILINTELIX_VERSION,
			true
		);

		wp_localize_script(
			'mailintelix-admin',
			'MailIntelixAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mailintelix_ajax' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Delete this email log?', 'mailintelix' ),
					'loading'       => __( 'Loading email preview...', 'mailintelix' ),
					'error'         => __( 'Could not load this email log.', 'mailintelix' ),
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mailintelix' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-table.php';

		$table = new MailIntelix_Table();
		$table->prepare_items();

		self::render_header( __( 'Email Logs', 'mailintelix' ) );
		self::render_notices();
		?>
		<div class="mailintelix-card">
			<form method="get" class="mailintelix-filters">
				<input type="hidden" name="page" value="mailintelix" />
				<div class="mailintelix-filter-bar">
					<div class="mailintelix-filter-group">
						<?php $table->views(); ?>
						<div class="mailintelix-filter-row">
							<label>
								<span><?php esc_html_e( 'From', 'mailintelix' ); ?></span>
								<input type="date" name="date_from" value="<?php echo esc_attr( mailintelix_get_request_value( 'date_from' ) ); ?>" />
							</label>
							<label>
								<span><?php esc_html_e( 'To', 'mailintelix' ); ?></span>
								<input type="date" name="date_to" value="<?php echo esc_attr( mailintelix_get_request_value( 'date_to' ) ); ?>" />
							</label>
						</div>
					</div>
					<div class="mailintelix-search-group">
						<?php $table->search_box( __( 'Search logs', 'mailintelix' ), 'mailintelix-search' ); ?>
					</div>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( self::logs_url() ); ?>">
				<?php $table->display(); ?>
			</form>
		</div>
		<div class="mailintelix-modal" id="mailintelix-preview-modal" aria-hidden="true">
			<div class="mailintelix-modal__panel" role="dialog" aria-modal="true" aria-labelledby="mailintelix-modal-title">
				<button type="button" class="mailintelix-modal__close" data-mailintelix-close aria-label="<?php esc_attr_e( 'Close preview', 'mailintelix' ); ?>">&times;</button>
				<div id="mailintelix-modal-content"></div>
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
		<div class="wrap mailintelix-wrap">
			<div class="mailintelix-hero">
				<div class="mailintelix-logo" aria-hidden="true">
					<span class="dashicons dashicons-email mailintelix-logo__icon"></span>
				</div>
				<div>
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php esc_html_e( 'Email logging, debugging, and delivery insights for WordPress.', 'mailintelix' ); ?></p>
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
		$message = mailintelix_get_request_value( 'mailintelix_message' );
		if ( empty( $message ) ) {
			return;
		}

		$messages = array(
			'deleted'   => __( 'Email log deleted.', 'mailintelix' ),
			'bulk'      => __( 'Selected email logs deleted.', 'mailintelix' ),
			'resent'    => __( 'Email resend requested.', 'mailintelix' ),
			'cleared'   => __( 'All email logs cleared.', 'mailintelix' ),
			'settings'  => __( 'Settings saved.', 'mailintelix' ),
			'test_sent' => __( 'Test email sent. Check Email Logs for the result.', 'mailintelix' ),
		);

		if ( isset( $messages[ $message ] ) ) {
			if ( 'bulk' === $message ) {
				$count = absint( mailintelix_get_request_value( 'mailintelix_deleted_count' ) );
				if ( $count > 0 ) {
					$messages[ $message ] = sprintf(
						/* translators: %d: number of deleted email logs. */
						_n( '%d email log deleted.', '%d email logs deleted.', $count, 'mailintelix' ),
						$count
					);
				}
			}

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $messages[ $message ] )
			);
		}
	}

	/**
	 * Handle single delete fallback action.
	 *
	 * @return void
	 */
	public static function handle_delete_log() {
		self::verify_action_request( 'mailintelix_delete_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		MailIntelix_Logger::delete_log( $log_id );

		wp_safe_redirect( self::logs_url( array( 'mailintelix_message' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Handle resend fallback action.
	 *
	 * @return void
	 */
	public static function handle_resend_log() {
		self::verify_action_request( 'mailintelix_resend_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		MailIntelix_Logger::resend( $log_id );

		wp_safe_redirect( self::logs_url( array( 'mailintelix_message' => 'resent' ) ) );
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
		$log    = MailIntelix_Logger::get_log( $log_id );

		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Email log not found.', 'mailintelix' ) ), 404 );
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
		MailIntelix_Logger::delete_log( $log_id );

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
		<div class="mailintelix-preview">
			<h2 id="mailintelix-modal-title"><?php echo esc_html( $log->subject ? $log->subject : __( '(No subject)', 'mailintelix' ) ); ?></h2>
			<div class="mailintelix-preview__meta">
				<span><?php echo wp_kses_post( mailintelix_status_badge( $log->status ) ); ?></span>
				<span><strong><?php esc_html_e( 'Sent at:', 'mailintelix' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->sent_at ) ); ?></span>
				<span><strong><?php esc_html_e( 'To:', 'mailintelix' ); ?></strong> <?php echo esc_html( $log->to_email ); ?></span>
			</div>
			<?php if ( ! empty( $log->error_message ) ) : ?>
				<div class="mailintelix-error-block"><?php echo esc_html( $log->error_message ); ?></div>
			<?php endif; ?>
			<div class="mailintelix-preview__grid">
				<div>
					<h3><?php esc_html_e( 'Headers', 'mailintelix' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->headers, __( 'No headers logged for this email.', 'mailintelix' ) ) ); ?>
				</div>
				<div>
					<h3><?php esc_html_e( 'Attachments', 'mailintelix' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->attachments, __( 'No attachments for this email.', 'mailintelix' ) ) ); ?>
				</div>
			</div>
			<div class="mailintelix-tabs">
				<button type="button" class="is-active" data-mailintelix-tab="html"><?php esc_html_e( 'HTML view', 'mailintelix' ); ?></button>
				<button type="button" data-mailintelix-tab="source"><?php esc_html_e( 'Text/source view', 'mailintelix' ); ?></button>
			</div>
			<div class="mailintelix-tab-panel is-active" data-mailintelix-panel="html">
				<iframe class="mailintelix-email-frame" title="<?php esc_attr_e( 'Email HTML preview', 'mailintelix' ); ?>" sandbox srcdoc="<?php echo esc_attr( $log->message ); ?>"></iframe>
			</div>
			<div class="mailintelix-tab-panel" data-mailintelix-panel="source">
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
				'<div class="mailintelix-empty-meta">%s</div>',
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mailintelix' ) );
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
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mailintelix' ) ), 403 );
		}

		check_ajax_referer( 'mailintelix_ajax', 'nonce' );
	}

	/**
	 * Logs page URL.
	 *
	 * @param array $args Extra args.
	 * @return string
	 */
	public static function logs_url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=mailintelix' ) );
	}
}

/**
 * Get a sanitized request value.
 *
 * @param string $key Request key.
 * @return string
 */
function mailintelix_get_request_value( $key ) {
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
function mailintelix_status_badge( $status ) {
	$status = sanitize_key( $status );
	$label  = __( 'Unknown', 'mailintelix' );

	if ( 'sent' === $status ) {
		$label = __( 'Sent', 'mailintelix' );
	} elseif ( 'failed' === $status ) {
		$label = __( 'Failed', 'mailintelix' );
	}

	return sprintf(
		'<span class="mailintelix-badge mailintelix-badge--%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( $label )
	);
}
