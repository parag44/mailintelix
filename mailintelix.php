<?php
/**
 * Plugin Name:       MailIntelix
 * Plugin URI:        https://wordpress.org/plugins/mailintelix
 * Description:       Email logging, debugging, and delivery insights for WordPress.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            MailIntelix
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mailintelix
 * Domain Path:       /languages
 *
 * @package MailIntelix
 */

defined( 'ABSPATH' ) || exit;

define( 'MAILINTELIX_VERSION', '1.0.0' );
define( 'MAILINTELIX_PLUGIN_FILE', __FILE__ );
define( 'MAILINTELIX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAILINTELIX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAILINTELIX_LOGS_TABLE', 'mailintelix_logs' );
define( 'MAILINTELIX_SETTINGS_OPTION', 'mailintelix_settings' );

require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-activator.php';
require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-logger.php';
require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-admin.php';
require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-settings.php';
require_once MAILINTELIX_PLUGIN_DIR . 'includes/class-mailintelix-tools.php';

register_activation_hook( __FILE__, array( 'MailIntelix_Activator', 'activate' ) );

/**
 * Boot MailIntelix after WordPress is loaded.
 *
 * @return void
 */
function mailintelix_boot() {
	MailIntelix_Logger::init();

	if ( is_admin() ) {
		MailIntelix_Admin::init();
		MailIntelix_Settings::init();
		MailIntelix_Tools::init();
	}
}
add_action( 'plugins_loaded', 'mailintelix_boot' );
