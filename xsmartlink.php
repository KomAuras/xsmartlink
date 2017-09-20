<?php
/**
 * Plugin Name:       xSmart Link
 * Plugin URI:        http://www.claruionlife.net
 * Description:       Smart link posts plugin
 * Version:           2.0.1
 * Author:            Evgeny Stefanenko
 * Author URI:        http://www.claruionlife.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       xsmartlink
 * Domain Path:       /languages
 */

namespace SmartLink;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The class that contains the plugin info.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-info.php';

/**
 * The code that runs during plugin activation.
 */
function activation() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	Activator::activate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activation' );

/**
 * Check for updates.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/plugin-update-checker/plugin-update-checker.php';
$plugin_slug     = Info::SLUG;
$update_url      = Info::UPDATE_URL;
$myUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
	$update_url.$plugin_slug,
	__FILE__,
	'unique-plugin-or-theme-slug'
);

//Optional: If you're using a private repository, create an OAuth consumer
//and set the authentication credentials like this:
$myUpdateChecker->setAuthentication(array(
	'consumer_key' => Info::CONSUMER_KEY,
	'consumer_secret' => Info::CONSUMER_SECRET,
));

//Optional: Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');
/*
require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/plugin-update-checker/plugin-update-checker.php';
$plugin_slug     = Info::SLUG;
$update_url      = Info::UPDATE_URL;
$myUpdateChecker = \Puc_v4_Factory::buildUpdateChecker(
	$update_url . '?action=get_metadata&slug=' . $plugin_slug,
	__FILE__,
	$plugin_slug
);
*/

/**
 * Run the plugin.
 */
function run() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';
	$plugin = new Plugin();
	$plugin->run();
}

if ( ! function_exists( '_log' ) ) {
	function _log( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}

run();
