<?php

namespace Toolkit;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\GravityForm;

function render_partial( $view, $data = [] ) {
	$partials_dir = realpath( HITHTO_THEME_PATH . DIRECTORY_SEPARATOR . 'partials' );
	$path         = realpath( implode( DIRECTORY_SEPARATOR, [ HITHTO_THEME_PATH, 'partials', $view ] ) . '.php' );

	// Only include existing files located inside the theme's partials directory
	if ( false === $partials_dir || false === $path || 0 !== strpos( $path, $partials_dir . DIRECTORY_SEPARATOR ) ) {
		return '';
	}

	ob_start();
	( static function ( $__path, $__data ) {
		foreach ( (array) $__data as $__key => $__value ) {
			// Skip invalid variable names and names that would overwrite this function's own variables
			if (
				! is_string( $__key )
				|| ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $__key )
				|| 0 === strpos( $__key, '__' )
				|| in_array( $__key, [ 'this', 'GLOBALS' ], true )
			) {
				continue;
			}
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
