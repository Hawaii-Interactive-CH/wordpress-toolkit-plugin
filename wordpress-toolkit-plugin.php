<?php

/**
 * Plugin Name: Toolkit
 * Description: Hawaii Interactive Toolkit Theme Plugin
 * Plugin URI: https://git.hawai.li/hawai-li/wordpress-toolkit-plugin
 * Version: 1.8.1
 * Requires at least: 5.2
 * Requires PHP: 8.0
 * Author: Hawaii Interactive
 * Author URI: https://hawaii.do
 * Text Domain: toolkit
 * Domain Path: /languages
 */

namespace Toolkit;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

// Define plugin constants.
define( 'WP_TOOLKIT_DIR', plugin_dir_path(__FILE__) );
define( 'WP_TOOLKIT_URL', plugin_dir_url(__FILE__) );
define( 'WP_TOOLKIT_THEME_PATH', get_template_directory() );
define( 'WP_TOOLKIT_THEME_URL', get_template_directory_uri() );
define( 'WP_TOOLKIT_THEME_VIEWS_PATH', get_template_directory() . '/templates' );
define( 'JB_FLY_PLUGIN_PATH', WP_TOOLKIT_DIR . 'vendor/jb-fly' );

// Autoload classes.
spl_autoload_register(function ($class) {
    // Check if the class is within the Toolkit namespace
    if (strpos($class, 'Toolkit\\') === 0) {
        // Remove the namespace from the class to get the relative path
        $path = str_replace('Toolkit\\', '', $class);
        // Replace backslashes with directory separators to get the correct file path
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        // Construct the file path
        $file = WP_TOOLKIT_DIR . $path . '.php';
        
        // Check if the file exists and include it if it does
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

require 'utils/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-plugin',
	__FILE__,
	'wordpress-toolkit-plugin'
);

$updateChecker->setBranch('main');

// Register routes & main utils.
include(WP_TOOLKIT_DIR . "/main.php");
include(WP_TOOLKIT_DIR . "/routes/api.php");

// Register classes.
$to_register = [
    // Utils
    '\\Toolkit\\utils\\AssetService',
    '\\Toolkit\\utils\\MainService',
    '\\Toolkit\\utils\\ModelService',
    '\\Toolkit\\utils\\RegisterService',
    '\\Toolkit\\utils\\DocService',
    '\\Toolkit\\utils\\ApiAuthService',
    '\\Toolkit\\utils\\MenuService',
    // Models
    '\\Toolkit\\models\\MediaTaxonomy',
];

add_action('init', function () use ($to_register) {
    foreach ($to_register as $class) {
        $class::register();
    }
});
