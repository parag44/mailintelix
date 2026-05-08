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
 * Text Domain:       mailtally
 * Domain Path:       /languages
 *
 * @package MailTally
 */

defined( 'ABSPATH' ) || exit;

define( 'MAILTALLY_VERSION', '1.0.0' );
define( 'MAILTALLY_PLUGIN_FILE', __FILE__ );
define( 'MAILTALLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAILTALLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAILTALLY_LOGS_TABLE', 'mailtally_logs' );
define( 'MAILTALLY_SETTINGS_OPTION', 'mailtally_settings' );

require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-activator.php';
require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-logger.php';
require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-admin.php';
require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-settings.php';
require_once MAILTALLY_PLUGIN_DIR . 'includes/class-mailtally-tools.php';

register_activation_hook( __FILE__, array( 'MailTally_Activator', 'activate' ) );

/**
 * Boot MailTally after WordPress is loaded.
 *
 * @return void
 */
function mailtally_boot() {
	MailTally_Logger::init();

	if ( is_admin() ) {
		MailTally_Admin::init();
		MailTally_Settings::init();
		MailTally_Tools::init();
	}
}
add_action( 'plugins_loaded', 'mailtally_boot' );
