<?php

namespace Toolkit\models;

use Toolkit\models\Media;
use Toolkit\models\File;
use Toolkit\models\Page;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

abstract class Block
{
	/*
		How to register a custom block

		const TYPE = 'numbers';

		public static function settings() {
			return [
				'title'       => __( 'Key Numbers', 'wordpress-toolkit-plugin' ),
				'description' => __( 'A custom number block.', 'wordpress-toolkit-plugin' ),
				'mode'        => 'auto',
				'align'       => 'full',
				'icon'        => 'admin-comments',
				'keywords'    => [ 'numbers', 'quote' ],
			];
		}
	*/

	protected $_data;

	public function __construct( $data ) {
		$this->_data = $data;
	}

	public static function register() {
		if ( ! function_exists( 'acf_register_block' ) ) {
			trigger_error( 'Plug-in ACF is not installed.', E_USER_WARNING );
		} else {
			$setting                    = static::settings();
			$setting['name']            = static::TYPE;
			$setting['render_callback'] = array( static::class, 'render' );

			acf_register_block( $setting );

			$file = WP_TOOLKIT_THEME_PATH . '/partials/blocks/' . static::TYPE . '.php';
			if ( ! file_exists( $file ) ) {
				throw new \Exception( esc_html( 'Missing block template ' . $file ) );
			}
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array $data The block data.
	 * @return void
	 */
	public static function render( $data ) {
		$block_instance = new static( $data );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_partial returns escaped template output
		echo \Toolkit\render_partial( implode( '/', array( 'blocks', static::TYPE ) ), array(
			'block' => $block_instance,
		) );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the block ID.
	 *
	 * @return string
	 */
	public function id() {
		return $this->_data['id'];
	}

	/**
	 * Get an ACF field value by key.
	 *
	 * @param string $key The ACF field key.
	 * @return mixed
	 */
	public function acf( string $key ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $key, $this->id() );
		} else {
			trigger_error( 'Plug-in ACF is not installed.', E_USER_WARNING );
		}
	}

	/**
	 * Check if an ACF field has a value.
	 *
	 * @param string $key The ACF field key.
	 * @return bool
	 */
	public function has_acf( string $key ): bool {
		return ! empty( $this->acf( $key ) );
	}

	/**
	 * Resolve an ACF field into a model instance and pass it to a callback.
	 *
	 * @param string   $name     The ACF field name.
	 * @param string   $class    The model class to instantiate.
	 * @param callable $callback Callback receiving the model instance.
	 * @return mixed|null
	 */
	public function acf_publication( string $name, string $class, callable $callback ) {
		$data = $this->acf( $name );
		$id   = 'object' === gettype( $data ) ? $data->ID : $data;

		if ( ! $id ) {
			return;
		}

		return $callback( new $class( $id ) );
	}

	/**
	 * Resolve an ACF media field and pass the Media instance to a callback.
	 *
	 * @param string   $name     The ACF field name.
	 * @param callable $callback Callback receiving the Media instance.
	 * @return mixed|null
	 */
	public function acf_media( string $name, callable $callback ) {
		return $this->acf_publication( $name, Media::class, $callback );
	}

	/**
	 * Resolve an ACF file field and pass the File instance to a callback.
	 *
	 * @param string   $name     The ACF field name.
	 * @param callable $callback Callback receiving the File instance.
	 * @return mixed|null
	 */
	public function acf_file( string $name, callable $callback ) {
		return $this->acf_publication( $name, File::class, $callback );
	}

	/**
	 * Resolve an ACF post field and pass the Page instance to a callback.
	 *
	 * @param string   $name     The ACF field name.
	 * @param callable $callback Callback receiving the Page instance.
	 * @return mixed|null
	 */
	public function acf_post( string $name, callable $callback ) {
		return $this->acf_publication( $name, Page::class, $callback );
	}

	/**
	 * Resolve an ACF page field and pass the Page instance to a callback.
	 *
	 * @param string   $name     The ACF field name.
	 * @param callable $callback Callback receiving the Page instance.
	 * @return mixed|null
	 */
	public function acf_page( string $name, callable $callback ) {
		return $this->acf_publication( $name, Page::class, $callback );
	}
}
