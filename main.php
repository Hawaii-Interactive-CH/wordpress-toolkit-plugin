<?php

namespace Toolkit;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\GravityForm;


function render_partial($view, $data = [])
{
    extract($data);
    $path = [WP_TOOLKIT_THEME_PATH, "partials", $view];
    ob_start();
    include implode(DIRECTORY_SEPARATOR, $path) . ".php";
    return ob_get_clean();
}

// auto add select value for acf forms-tabs
add_filter('acf/load_field/name=forms', function ($field) {
    $forms = GravityForm::all_active();
    $field['choices'] = [];

    foreach ($forms as $form) {
        $field['choices'][$form->id] = $form->title;
    }

    return $field;
});