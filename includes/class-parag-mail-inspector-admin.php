<?php
/**
 * Admin controller.
 *
 * @package Parag Mail Inspector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus, assets, and log actions.
 */
class Parag_Mail_Inspector_Admin {
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
		add_action( 'admin_post_parag_mail_inspector_delete_log', array( __CLASS__, 'handle_delete_log' ) );
		add_action( 'admin_post_parag_mail_inspector_resend_log', array( __CLASS__, 'handle_resend_log' ) );
		add_action( 'wp_ajax_parag_mail_inspector_get_log', array( __CLASS__, 'ajax_get_log' ) );
		add_action( 'wp_ajax_parag_mail_inspector_delete_log', array( __CLASS__, 'ajax_delete_log' ) );
	}

	/**
	 * Handle bulk log actions before wp-admin starts rendering.
	 *
	 * @return void
	 */
	public static function handle_bulk_log_action() {
		if ( ! is_admin() || 'parag-mail-inspector' !== parag_mail_inspector_get_request_value( 'page' ) ) {
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'parag-mail-inspector' ) );
		}

		check_admin_referer( 'bulk-parag_mail_inspector_logs' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by check_admin_referer() above.
		$ids           = isset( $_POST['log_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['log_ids'] ) ) : array();
		$deleted_count = Parag_Mail_Inspector_Logger::delete_logs( $ids );

		wp_safe_redirect(
			self::logs_url(
				array(
					'parag_mail_inspector_message'       => 'bulk',
					'parag_mail_inspector_deleted_count' => absint( $deleted_count ),
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
				'id'    => 'parag-mail-inspector-email-logs',
				'title' => __( 'Parag Mail Inspector Logs', 'parag-mail-inspector' ),
				'href'  => self::logs_url(),
				'meta'  => array(
					'title' => __( 'View Parag Mail Inspector email logs', 'parag-mail-inspector' ),
				),
			)
		);
	}

	/**
	 * Hide third-party admin notices on Parag Mail Inspector screens.
	 *
	 * @return void
	 */
	public static function suppress_admin_notices() {
		if ( ! self::is_parag_mail_inspector_screen() ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_action( 'admin_notices', 'update_nag', 3 );
	}

	/**
	 * Check whether current admin page belongs to Parag Mail Inspector.
	 *
	 * @return bool
	 */
	private static function is_parag_mail_inspector_screen() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = parag_mail_inspector_get_request_value( 'page' );

		return 0 === strpos( $page, 'parag-mail-inspector' );
	}

	/**
	 * Register Parag Mail Inspector admin menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Parag Mail Inspector', 'parag-mail-inspector' ),
			__( 'Parag Mail Inspector', 'parag-mail-inspector' ),
			'manage_options',
			'parag-mail-inspector',
			array( __CLASS__, 'render_logs_page' ),
			'dashicons-email',
			80
		);

		add_submenu_page(
			'parag-mail-inspector',
			__( 'Email Logs', 'parag-mail-inspector' ),
			__( 'Email Logs', 'parag-mail-inspector' ),
			'manage_options',
			'parag-mail-inspector',
			array( __CLASS__, 'render_logs_page' )
		);

		add_submenu_page(
			'parag-mail-inspector',
			__( 'Settings', 'parag-mail-inspector' ),
			__( 'Settings', 'parag-mail-inspector' ),
			'manage_options',
			'parag-mail-inspector-settings',
			array( 'Parag_Mail_Inspector_Settings', 'render_page' )
		);

		add_submenu_page(
			'parag-mail-inspector',
			__( 'Tools', 'parag-mail-inspector' ),
			__( 'Tools', 'parag-mail-inspector' ),
			'manage_options',
			'parag-mail-inspector-tools',
			array( 'Parag_Mail_Inspector_Tools', 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets only on plugin screens.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'parag-mail-inspector' ) ) {
			return;
		}

		wp_enqueue_style(
			'parag-mail-inspector-admin',
			PARAG_MAIL_INSPECTOR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PARAG_MAIL_INSPECTOR_VERSION
		);

		wp_enqueue_script(
			'parag-mail-inspector-admin',
			PARAG_MAIL_INSPECTOR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			PARAG_MAIL_INSPECTOR_VERSION,
			true
		);

		wp_localize_script(
			'parag-mail-inspector-admin',
			'ParagMailInspectorAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'parag_mail_inspector_ajax' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Delete this email log?', 'parag-mail-inspector' ),
					'loading'       => __( 'Loading email preview...', 'parag-mail-inspector' ),
					'error'         => __( 'Could not load this email log.', 'parag-mail-inspector' ),
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
			wp_die( esc_html__( 'You do not have permission to access this page.', 'parag-mail-inspector' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-table.php';

		$table = new Parag_Mail_Inspector_Table();
		$table->prepare_items();

		self::render_header( __( 'Email Logs', 'parag-mail-inspector' ) );
		self::render_notices();
		?>
		<div class="parag-mail-inspector-card">
			<form method="get" class="parag-mail-inspector-filters">
				<input type="hidden" name="page" value="parag-mail-inspector" />
				<div class="parag-mail-inspector-filter-bar">
					<div class="parag-mail-inspector-filter-group">
						<?php $table->views(); ?>
						<div class="parag-mail-inspector-filter-row">
							<label>
								<span><?php esc_html_e( 'From', 'parag-mail-inspector' ); ?></span>
								<input type="date" name="date_from" value="<?php echo esc_attr( parag_mail_inspector_get_request_value( 'date_from' ) ); ?>" />
							</label>
							<label>
								<span><?php esc_html_e( 'To', 'parag-mail-inspector' ); ?></span>
								<input type="date" name="date_to" value="<?php echo esc_attr( parag_mail_inspector_get_request_value( 'date_to' ) ); ?>" />
							</label>
						</div>
					</div>
					<div class="parag-mail-inspector-search-group">
						<?php $table->search_box( __( 'Search logs', 'parag-mail-inspector' ), 'parag-mail-inspector-search' ); ?>
					</div>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( self::logs_url() ); ?>">
				<input type="hidden" name="page" value="parag-mail-inspector" />
				<?php $table->display(); ?>
			</form>
		</div>
		<div class="parag-mail-inspector-modal" id="parag-mail-inspector-preview-modal" aria-hidden="true">
			<div class="parag-mail-inspector-modal__panel" role="dialog" aria-modal="true" aria-labelledby="parag-mail-inspector-modal-title">
				<button type="button" class="parag-mail-inspector-modal__close" data-parag-mail-inspector-close aria-label="<?php esc_attr_e( 'Close preview', 'parag-mail-inspector' ); ?>">&times;</button>
				<div id="parag-mail-inspector-modal-content"></div>
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
		<div class="wrap parag-mail-inspector-wrap">
			<div class="parag-mail-inspector-hero">
				<div class="parag-mail-inspector-logo" aria-hidden="true">
					<span class="dashicons dashicons-email parag-mail-inspector-logo__icon"></span>
				</div>
				<div class="parag-mail-inspector-hero__content">
					<h1><?php echo esc_html( $title ); ?></h1>
					<p><?php esc_html_e( 'Email logging, debugging, and delivery insights for WordPress.', 'parag-mail-inspector' ); ?></p>
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
		$message = parag_mail_inspector_get_request_value( 'parag_mail_inspector_message' );
		if ( empty( $message ) ) {
			return;
		}

		$messages = array(
			'deleted'   => __( 'Email log deleted.', 'parag-mail-inspector' ),
			'bulk'      => __( 'Selected email logs deleted.', 'parag-mail-inspector' ),
			'resent'    => __( 'Email resend requested.', 'parag-mail-inspector' ),
			'cleared'   => __( 'All email logs cleared.', 'parag-mail-inspector' ),
			'settings'  => __( 'Settings saved.', 'parag-mail-inspector' ),
			'test_sent' => __( 'Test email sent. Check Email Logs for the result.', 'parag-mail-inspector' ),
		);

		if ( isset( $messages[ $message ] ) ) {
			if ( 'bulk' === $message ) {
				$count = absint( parag_mail_inspector_get_request_value( 'parag_mail_inspector_deleted_count' ) );
				if ( $count > 0 ) {
					$messages[ $message ] = sprintf(
						/* translators: %d: number of deleted email logs. */
						_n( '%d email log deleted.', '%d email logs deleted.', $count, 'parag-mail-inspector' ),
						$count
					);
				}
			}

			echo '<div class="parag-mail-inspector-notices">';
			printf(
				'<div class="parag-mail-inspector-notice parag-mail-inspector-notice--success" role="status"><p>%s</p></div>',
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
		self::verify_action_request( 'parag_mail_inspector_delete_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		Parag_Mail_Inspector_Logger::delete_log( $log_id );

		wp_safe_redirect( self::logs_url( array( 'parag_mail_inspector_message' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Handle resend fallback action.
	 *
	 * @return void
	 */
	public static function handle_resend_log() {
		self::verify_action_request( 'parag_mail_inspector_resend_log' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by verify_action_request() above.
		$log_id = isset( $_GET['log_id'] ) ? absint( wp_unslash( $_GET['log_id'] ) ) : 0;
		Parag_Mail_Inspector_Logger::resend( $log_id );

		wp_safe_redirect( self::logs_url( array( 'parag_mail_inspector_message' => 'resent' ) ) );
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
		$log    = Parag_Mail_Inspector_Logger::get_log( $log_id );

		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( 'Email log not found.', 'parag-mail-inspector' ) ), 404 );
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
		Parag_Mail_Inspector_Logger::delete_log( $log_id );

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
		<div class="parag-mail-inspector-preview">
			<h2 id="parag-mail-inspector-modal-title"><?php echo esc_html( $log->subject ? $log->subject : __( '(No subject)', 'parag-mail-inspector' ) ); ?></h2>
			<div class="parag-mail-inspector-preview__meta">
				<span><?php echo wp_kses_post( parag_mail_inspector_status_badge( $log->status ) ); ?></span>
				<span><strong><?php esc_html_e( 'Sent at:', 'parag-mail-inspector' ); ?></strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->sent_at ) ); ?></span>
				<span><strong><?php esc_html_e( 'To:', 'parag-mail-inspector' ); ?></strong> <?php echo esc_html( $log->to_email ); ?></span>
			</div>
			<?php if ( ! empty( $log->error_message ) ) : ?>
				<div class="parag-mail-inspector-error-block"><?php echo esc_html( $log->error_message ); ?></div>
			<?php endif; ?>
			<div class="parag-mail-inspector-preview__grid">
				<div>
					<h3><?php esc_html_e( 'Headers', 'parag-mail-inspector' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->headers, __( 'No headers logged for this email.', 'parag-mail-inspector' ) ) ); ?>
				</div>
				<div>
					<h3><?php esc_html_e( 'Attachments', 'parag-mail-inspector' ); ?></h3>
					<?php echo wp_kses_post( self::render_meta_value( $log->attachments, __( 'No attachments for this email.', 'parag-mail-inspector' ) ) ); ?>
				</div>
			</div>
			<div class="parag-mail-inspector-tabs">
				<button type="button" class="is-active" data-parag-mail-inspector-tab="html"><?php esc_html_e( 'HTML view', 'parag-mail-inspector' ); ?></button>
				<button type="button" data-parag-mail-inspector-tab="source"><?php esc_html_e( 'Text/source view', 'parag-mail-inspector' ); ?></button>
			</div>
			<div class="parag-mail-inspector-tab-panel is-active" data-parag-mail-inspector-panel="html">
				<iframe class="parag-mail-inspector-email-frame" title="<?php esc_attr_e( 'Email HTML preview', 'parag-mail-inspector' ); ?>" sandbox srcdoc="<?php echo esc_attr( $log->message ); ?>"></iframe>
			</div>
			<div class="parag-mail-inspector-tab-panel" data-parag-mail-inspector-panel="source">
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
				'<div class="parag-mail-inspector-empty-meta">%s</div>',
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'parag-mail-inspector' ) );
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
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'parag-mail-inspector' ) ), 403 );
		}

		check_ajax_referer( 'parag_mail_inspector_ajax', 'nonce' );
	}

	/**
	 * Logs page URL.
	 *
	 * @param array $args Extra args.
	 * @return string
	 */
	public static function logs_url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=parag-mail-inspector' ) );
	}
}

/**
 * Get a sanitized request value.
 *
 * @param string $key Request key.
 * @return string
 */
function parag_mail_inspector_get_request_value( $key ) {
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
function parag_mail_inspector_status_badge( $status ) {
	$status = sanitize_key( $status );
	$label  = __( 'Unknown', 'parag-mail-inspector' );

	if ( 'sent' === $status ) {
		$label = __( 'Sent', 'parag-mail-inspector' );
	} elseif ( 'failed' === $status ) {
		$label = __( 'Failed', 'parag-mail-inspector' );
	}

	return sprintf(
		'<span class="parag-mail-inspector-badge parag-mail-inspector-badge--%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( $label )
	);
}
