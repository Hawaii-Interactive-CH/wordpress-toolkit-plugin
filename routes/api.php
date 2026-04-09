<?php

namespace Toolkit\routes;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\controllers\AuthController;
use Toolkit\controllers\ToolkitController;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;


$toolkit_base_url = get_home_url();
$toolkit_app_name = sanitize_title(get_bloginfo('name'));

/**
 * Register API routes
 */
 add_action('rest_api_init', function() use ($toolkit_app_name, $toolkit_base_url) {
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

    $sanitize_int = function ($value) {
        return absint($value);
    };
    $validate_positive_int = function ($value) {
        return is_numeric($value) && (int) $value >= 1;
    };
    $sanitize_datetime = function ($value) {
        return sanitize_text_field(wp_unslash((string) $value));
    };
    $validate_datetime = function ($value) {
        $sanitized = sanitize_text_field(wp_unslash((string) $value));
        return false !== strtotime($sanitized);
    };

    // Calendar routes
    // GET /wp-json/toolkit/v1/events
    register_rest_route($toolkit_namespace, '/events', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_events'),
        'args' => array(
            'per_page' => array(
                'sanitize_callback' => $sanitize_int,
                'validate_callback' => $validate_positive_int,
            ),
            'start_date' => array(
                'sanitize_callback' => $sanitize_datetime,
                'validate_callback' => $validate_datetime,
            ),
            'end_date' => array(
                'sanitize_callback' => $sanitize_datetime,
                'validate_callback' => $validate_datetime,
            ),
        ),
    ));

    // GET /wp-json/toolkit/v1/events/upcoming
    register_rest_route($toolkit_namespace, '/events/upcoming', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_upcoming'),
        'args' => array(
            'limit' => array(
                'sanitize_callback' => $sanitize_int,
                'validate_callback' => $validate_positive_int,
            ),
        ),
    ));

    // GET /wp-json/toolkit/v1/events/upcoming-period
    register_rest_route($toolkit_namespace, '/events/upcoming-period', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_upcoming_with_period'),
    ));

    // GET /wp-json/toolkit/v1/events/{id}
    register_rest_route($toolkit_namespace, '/events/(?P<id>\d+)', array(
        'methods' => 'GET',
        'permission_callback' => array($toolkitController, 'permission_callback'),
        'callback' => array($toolkitController, 'get_event'),
        'args' => array(
            'id' => array(
                'sanitize_callback' => $sanitize_int,
                'validate_callback' => $validate_positive_int,
            ),
        ),
    ));
 });