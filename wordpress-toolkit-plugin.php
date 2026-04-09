<?php

/**
 * HI Toolkit Plugin.
 *
 * @package   HI Theme Toolkit
 * @copyright Copyright (C) 2024-2026, Hawaii Interactive - hello@hawaii.do
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (GPL-2.0)
 *
 * @wordpress-plugin
 * Plugin Name: HI Theme Toolkit
 * Description: A developer toolkit for building WordPress themes — provides base models, ACF block registration, custom post types, taxonomies, REST API helpers, and utility services.
 * Plugin URI: https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-plugin
 * Version: 3.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.0
 * Author: Hawaii Interactive
 * Author URI: https://hawaii.do
 * Text Domain: hi-theme-toolkit
 * Domain Path: /languages
 * License: GPLv2 or later
 */

namespace Toolkit;

// Prevent direct access.
defined( 'ABSPATH' ) or exit();

// Define plugin constants.
if ( ! defined( 'WP_TOOLKIT_VERSION' ) )         define( 'WP_TOOLKIT_VERSION', '3.0.0' );
if ( ! defined( 'WP_TOOLKIT_DIR' ) )             define( 'WP_TOOLKIT_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'WP_TOOLKIT_URL' ) )             define( 'WP_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'WP_TOOLKIT_THEME_PATH' ) )      define( 'WP_TOOLKIT_THEME_PATH', get_template_directory() );
if ( ! defined( 'WP_TOOLKIT_THEME_URL' ) )       define( 'WP_TOOLKIT_THEME_URL', get_template_directory_uri() );
if ( ! defined( 'WP_TOOLKIT_THEME_VIEWS_PATH' ) ) define( 'WP_TOOLKIT_THEME_VIEWS_PATH', get_template_directory() . '/templates' );

// Autoload classes.
spl_autoload_register( function ( $class ) {
	// Check if the class is within the Toolkit namespace
	if ( 0 === strpos( $class, 'Toolkit\\' ) ) {
		// Remove the namespace from the class to get the relative path
		$path = str_replace( 'Toolkit\\', '', $class );
		// Replace backslashes with directory separators to get the correct file path
		$path = str_replace( '\\', DIRECTORY_SEPARATOR, $path );
		// Construct the file path
		$file = WP_TOOLKIT_DIR . $path . '.php';

		// Check if the file exists and include it if it does
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
} );



// Register routes & main utils.
include WP_TOOLKIT_DIR . '/main.php';
include WP_TOOLKIT_DIR . '/routes/api.php';


// Register other classes on init
$toolkit_to_register = [
	// Utils
	'\\Toolkit\\utils\\MainService',
	'\\Toolkit\\utils\\ModelService',
	'\\Toolkit\\utils\\RegisterService',
	'\\Toolkit\\utils\\DocService',
	'\\Toolkit\\utils\\ApiAuthService',
	'\\Toolkit\\utils\\MenuService',
	'\\Toolkit\\utils\\AssetService',
	// Models
	'\\Toolkit\\models\\MediaTaxonomy',
];

// Load WebP test admin page
require_once WP_TOOLKIT_DIR . 'utils/admin-webp-test-page.php';

add_action( 'init', function () use ( $toolkit_to_register ) {
	foreach ( $toolkit_to_register as $class ) {
		$class::register();
	}
} );

// Register deactivation hook for cron cleanup
register_deactivation_hook( __FILE__, [ '\\Toolkit\\utils\\Size', 'deactivate' ] );
