<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\WPML;

abstract class OptionPage {

	/**
	 * Register the options page.
	 *
	 * @return void
	 */
	public static function register() {
		if ( function_exists( 'acf_add_options_page' ) && function_exists( 'get_field' ) ) {
			$params = array_merge( static::PARAMS, [
				'menu_slug' => 'acf-' . static::ID,
				'post_id'   => static::ID . WPML::current_language(),
			] );

			acf_add_options_page( $params );
		} else {
			trigger_error( 'Plug-in ACF is not installed.', E_USER_WARNING );
		}
	}

	/**
	 * Get an ACF field value.
	 *
	 * @param string $name Field name.
	 * @return mixed
	 */
	public static function acf( string $name ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $name, static::ID . WPML::current_language() );
		} else {
			trigger_error( 'Plug-in ACF is not installed.', E_USER_WARNING );
		}
	}

	/**
	 * Check if ACF have_rows exists for a key.
	 *
	 * @param string $key Field key.
	 * @return bool|null
	 */
	public static function have_rows( $key ) {
		if ( function_exists( 'have_rows' ) ) {
			return have_rows( $key, static::ID . WPML::current_language() );
		} else {
			trigger_error( 'Plug-in ACF is not installed.', E_USER_WARNING );
		}
	}
}
