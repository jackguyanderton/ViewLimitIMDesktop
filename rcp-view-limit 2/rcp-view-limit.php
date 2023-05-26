<?php
/**
 * Plugin Name: Restrict Content Pro - View Limit
 * Plugin URI:  https://skillfulplugins.com/plugins/rcp-vew-limit
 * Description: Limit page views to non-members via access levels.
 * Version:     1.0.3
 * Author:      iThemes
 * Author URI:  https://ithemes.com/
 * License:     GPLv2+
 * Text Domain: rcpvl
 * Domain Path: languages
 * iThemes Package: rcp-view-limit
 */

if ( !defined( 'RCPVL_PLUGIN_DIR' ) ) {
	define( 'RCPVL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'RCPVL_PLUGIN_URL' ) ) {
	define( 'RCPVL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RCPVL_PLUGIN_FILE' ) ) {
	define( 'RCPVL_PLUGIN_FILE', __FILE__ );
}

// Useful global constants
define('RCPVL_VERSION', '1.0.3' );

// EDD Licensing constants
define('RCPVL_STORE_URL', 'https://skillfulplugins.com');
define('RCPVL_ITEM_NAME', 'Restrict Content Pro - View Limit');
define('RCPVL_SETTINGS_PAGE', 'rcpvl');

require_once( RCPVL_PLUGIN_DIR . 'vendor/autoload.php' );

RCP_VL\Setup::get_instance();

/**
 * Default initialization for the plugin
 **/
function rcpvlInit()
{
    $locale = apply_filters('plugin_locale', get_locale(), 'rcpvl');
    load_textdomain('rcpvl', WP_LANG_DIR . '/rcpvl/rcpvl-' . $locale . '.mo');
    load_plugin_textdomain('rcpvl', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'rcpvlInit');

/**
 * Activate
 **/
function rcpvlActivate()
{
    rcpvlInit();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'rcpvlActivate');

/**
 * Deactivate
 */
function rcpvlDeactivate()
{
}
register_deactivation_hook(__FILE__, 'rcpvlDeactivate');



if ( ! function_exists( 'ithemes_rcp_view_limit_updater_register' ) ) {
	function ithemes_rcp_view_limit_updater_register( $updater ) {
		$updater->register( 'rcp-view-limit', __FILE__ );
	}
	add_action( 'ithemes_updater_register', 'ithemes_rcp_view_limit_updater_register' );

	require( __DIR__ . '/lib/updater/load.php' );
}
