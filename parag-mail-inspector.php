<?php
/**
 * Plugin Name:       MailTally – Email Logger
 * Description:       A simple email logging and debugging tool for WordPress. Log outgoing emails, inspect email details, and troubleshoot mail delivery issues.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Parag Das
 * Author URI:        https://profiles.wordpress.org/parag44/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       parag-mail-inspector
 * Domain Path:       /languages
 *
 * @package Parag Mail Inspector
 */

defined( 'ABSPATH' ) || exit;

define( 'PARAG_MAIL_INSPECTOR_VERSION', '1.0.0' );
define( 'PARAG_MAIL_INSPECTOR_PLUGIN_FILE', __FILE__ );
define( 'PARAG_MAIL_INSPECTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PARAG_MAIL_INSPECTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PARAG_MAIL_INSPECTOR_LOGS_TABLE', 'parag_mail_inspector_logs' );
define( 'PARAG_MAIL_INSPECTOR_SETTINGS_OPTION', 'parag_mail_inspector_settings' );

require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-activator.php';
require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-logger.php';
require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-admin.php';
require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-settings.php';
require_once PARAG_MAIL_INSPECTOR_PLUGIN_DIR . 'includes/class-parag-mail-inspector-tools.php';

register_activation_hook( __FILE__, array( 'Parag_Mail_Inspector_Activator', 'activate' ) );

/**
 * Boot Parag Mail Inspector after WordPress is loaded.
 *
 * @return void
 */
function parag_mail_inspector_boot() {
	Parag_Mail_Inspector_Logger::init();

	if ( is_admin() ) {
		Parag_Mail_Inspector_Admin::init();
		Parag_Mail_Inspector_Settings::init();
		Parag_Mail_Inspector_Tools::init();
	}
}
add_action( 'plugins_loaded', 'parag_mail_inspector_boot' );
