<?php

namespace Toolkit;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\GravityForm;

function render_partial( $view, $data = [] ) {
	$path = implode( DIRECTORY_SEPARATOR, [ WP_TOOLKIT_THEME_PATH, 'partials', $view ] ) . '.php';
	ob_start();
	( static function ( $__path, $__data ) {
		foreach ( $__data as $__key => $__value ) {
			$$__key = $__value;
		}
		include $__path;
	} )( $path, $data );
	return ob_get_clean();
}

// auto add select value for acf forms-tabs
add_filter( 'acf/load_field/name=forms', function ( $field ) {
	$forms = GravityForm::all_active();
	$field['choices'] = [];

	foreach ( $forms as $form ) {
		$field['choices'][ $form->id ] = $form->title;
	}

	return $field;
} );
