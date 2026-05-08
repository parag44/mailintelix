<?php
/**
 * SMTP mailer configuration.
 *
 * @package Simple_Mail_Logger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configures PHPMailer to use saved SMTP details when enabled.
 */
class Simple_Mail_Logger_SMTP {
	/**
	 * Register mailer hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ) );
	}

	/**
	 * Configure PHPMailer for SMTP if enabled.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @return void
	 */
	public static function configure_phpmailer( $phpmailer ) {
		$settings = simple_mail_logger_get_settings();

		if ( empty( $settings['smtp_enabled'] ) ) {
			return;
		}

		self::apply_settings_to_phpmailer( $phpmailer, $settings );
	}

	/**
	 * Apply SMTP settings to a PHPMailer instance.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 * @param array                         $settings  Plugin settings.
	 * @return void
	 */
	public static function apply_settings_to_phpmailer( $phpmailer, $settings ) {
		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->Port       = absint( $settings['smtp_port'] );
		$phpmailer->SMTPAuth   = ! empty( $settings['smtp_auth'] );
		$phpmailer->Username   = isset( $settings['smtp_username'] ) ? $settings['smtp_username'] : '';
		$phpmailer->Password   = isset( $settings['smtp_password'] ) ? $settings['smtp_password'] : '';
		$phpmailer->SMTPSecure = self::get_phpmailer_encryption( isset( $settings['smtp_encryption'] ) ? $settings['smtp_encryption'] : 'none' );
		$phpmailer->SMTPAutoTLS = 'tls' === $settings['smtp_encryption'];

		if ( ! empty( $settings['smtp_from_email'] ) ) {
			$phpmailer->From   = $settings['smtp_from_email'];
			$phpmailer->Sender = $settings['smtp_from_email'];
		}

		if ( ! empty( $settings['smtp_from_name'] ) ) {
			$phpmailer->FromName = $settings['smtp_from_name'];
		}
	}

	/**
	 * Test the saved SMTP connection.
	 *
	 * @return true|WP_Error
	 */
	public static function test_connection() {
		$settings = simple_mail_logger_get_settings();

		if ( empty( $settings['smtp_enabled'] ) ) {
			return new WP_Error( 'simple_mail_logger_smtp_disabled', __( 'Enable SMTP sending before checking the connection.', 'simple-mail-logger' ) );
		}

		if ( empty( $settings['smtp_host'] ) ) {
			return new WP_Error( 'simple_mail_logger_smtp_missing_host', __( 'SMTP host is required.', 'simple-mail-logger' ) );
		}

		self::load_phpmailer_classes();

		try {
			$mailer = new PHPMailer\PHPMailer\PHPMailer( true );
			self::apply_settings_to_phpmailer( $mailer, $settings );
			$mailer->Timeout   = 15;
			$mailer->SMTPDebug = 0;

			if ( $mailer->smtpConnect() ) {
				$mailer->smtpClose();

				return true;
			}

			return new WP_Error( 'simple_mail_logger_smtp_connection_failed', __( 'SMTP connection could not be established.', 'simple-mail-logger' ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'simple_mail_logger_smtp_exception', sanitize_text_field( $e->getMessage() ) );
		}
	}

	/**
	 * Sanitize SMTP values and merge them into existing settings.
	 *
	 * @param array $input    Raw input values.
	 * @param array $existing Existing settings.
	 * @return array
	 */
	public static function sanitize_settings( $input, $existing ) {
		$settings = is_array( $existing ) ? $existing : array();
		$port     = isset( $input['smtp_port'] ) ? absint( $input['smtp_port'] ) : 587;

		if ( $port < 1 || $port > 65535 ) {
			$port = 587;
		}

		$encryption = isset( $input['smtp_encryption'] ) ? sanitize_key( $input['smtp_encryption'] ) : 'none';
		if ( ! in_array( $encryption, array( 'none', 'ssl', 'tls' ), true ) ) {
			$encryption = 'none';
		}

		$password = isset( $input['smtp_password'] ) ? sanitize_text_field( $input['smtp_password'] ) : '';
		if ( '' === $password && ! empty( $settings['smtp_password'] ) ) {
			$password = $settings['smtp_password'];
		}

		$settings['smtp_enabled']    = ! empty( $input['smtp_enabled'] ) ? 1 : 0;
		$settings['smtp_host']       = isset( $input['smtp_host'] ) ? sanitize_text_field( $input['smtp_host'] ) : '';
		$settings['smtp_port']       = $port;
		$settings['smtp_encryption'] = $encryption;
		$settings['smtp_auth']       = ! empty( $input['smtp_auth'] ) ? 1 : 0;
		$settings['smtp_username']   = isset( $input['smtp_username'] ) ? sanitize_text_field( $input['smtp_username'] ) : '';
		$settings['smtp_password']   = $password;
		$settings['smtp_from_email'] = isset( $input['smtp_from_email'] ) ? sanitize_email( $input['smtp_from_email'] ) : '';
		$settings['smtp_from_name']  = isset( $input['smtp_from_name'] ) ? sanitize_text_field( $input['smtp_from_name'] ) : '';

		return $settings;
	}

	/**
	 * Convert stored encryption value to PHPMailer value.
	 *
	 * @param string $encryption Stored encryption setting.
	 * @return string
	 */
	private static function get_phpmailer_encryption( $encryption ) {
		$encryption = sanitize_key( $encryption );

		return 'none' === $encryption ? '' : $encryption;
	}

	/**
	 * Load PHPMailer classes when a direct connection test runs before wp_mail().
	 *
	 * @return void
	 */
	private static function load_phpmailer_classes() {
		if ( class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
			return;
		}

		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
	}
}
