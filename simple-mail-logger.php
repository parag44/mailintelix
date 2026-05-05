<?php
/**
 * Plugin Name:       Simple Mail Logger – Log & Debug Emails
 * Description:       A simple email logging and debugging tool for WordPress. Log outgoing emails, inspect email details, and troubleshoot mail delivery issues.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Parag Das
 * Author URI:        https://profiles.wordpress.org/parag44/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-mail-logger
 * Domain Path:       /languages
 *
 * @package Simple_Mail_Logger
 */

defined( 'ABSPATH' ) || exit;

define( 'SIMPLE_MAIL_LOGGER_VERSION', '1.0.0' );
define( 'SIMPLE_MAIL_LOGGER_PLUGIN_FILE', __FILE__ );
define( 'SIMPLE_MAIL_LOGGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_MAIL_LOGGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLE_MAIL_LOGGER_LOGS_TABLE', 'simple_mail_logger_logs' );
define( 'SIMPLE_MAIL_LOGGER_SETTINGS_OPTION', 'simple_mail_logger_settings' );

require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-activator.php';
require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-logger.php';
require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-admin.php';
require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-settings.php';
require_once SIMPLE_MAIL_LOGGER_PLUGIN_DIR . 'includes/class-simple-mail-logger-tools.php';

register_activation_hook( __FILE__, array( 'Simple_Mail_Logger_Activator', 'activate' ) );

/**
 * Boot Simple Mail Logger after WordPress is loaded.
 *
 * @return void
 */
function simple_mail_logger_boot() {
	Simple_Mail_Logger_Logger::init();

	if ( is_admin() ) {
		Simple_Mail_Logger_Admin::init();
		Simple_Mail_Logger_Settings::init();
		Simple_Mail_Logger_Tools::init();
	}
}
add_action( 'plugins_loaded', 'simple_mail_logger_boot' );
