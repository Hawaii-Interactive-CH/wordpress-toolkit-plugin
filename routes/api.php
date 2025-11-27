<?php

namespace Toolkit\routes;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\controllers\AuthController;
use Toolkit\controllers\ToolkitController;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;


$base_url = get_home_url();
$app_name = sanitize_title(get_bloginfo('name'));

/**
 * Register API routes
 */
 add_action('rest_api_init', function() use ($app_name, $base_url) {
    $namespace = 'api/v1';
    $toolkit_namespace = 'toolkit/v1';

    // Crée une instance des contrôleurs
    $authController = new AuthController();
    $toolkitController = new ToolkitController();

    // Enregistre la route pour générer un token transient
    register_rest_route($namespace, '/auth', array(
        'methods' => 'POST',
        'permission_callback' => array($authController, 'check_whitelist'),
        'callback' => array($authController, 'generate_transient_token'),
    ));

    // Calendar routes
    // GET /wp-json/toolkit/v1/events
    register_rest_route($toolkit_namespace, '/events', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_events'),
    ));

    // GET /wp-json/toolkit/v1/events/upcoming
    register_rest_route($toolkit_namespace, '/events/upcoming', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_upcoming'),
    ));

    // GET /wp-json/toolkit/v1/events/{id}
    register_rest_route($toolkit_namespace, '/events/(?P<id>\d+)', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_event'),
    ));
 });