<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

abstract class Pattern
{
	/*
		How to register a custom pattern

		const TYPE = 'pattern-hero';

		public static function settings() {
			return [
				'title'       => __( 'Hero', 'hi-theme-toolkit' ),
				'description' => __( 'A hero section with a title and a button.', 'hi-theme-toolkit' ),
				'keywords'    => [ 'hero', 'banner' ],
			];
		}
	*/

	public static function register() {
		$file = HITHTO_THEME_PATH . '/partials/patterns/' . static::TYPE . '.php';
		if ( ! file_exists( $file ) ) {
			throw new \Exception( esc_html( 'Missing pattern template ' . $file ) );
		}

		$setting = static::settings();
		if ( empty( $setting['categories'] ) ) {
			$setting['categories'] = [ 'hithto' ];
		}
		$setting['content'] = static::content( $file );

		register_block_pattern( 'hithto/' . static::TYPE, $setting );
	}

	/**
	 * Render the pattern template and return its markup.
	 *
	 * @param string $file The pattern template path.
	 * @return string
	 */
	protected static function content( string $file ): string {
		ob_start();
		include $file;
		return ob_get_clean();
	}
}
