<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

abstract class CustomPostType extends PostType {

	/**
	 * Define post type settings.
	 *
	 * @return array
	 */
	abstract public static function type_settings();

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public static function register() {
		register_post_type( static::TYPE, static::type_settings() );
	}

	/**
	 * Add custom columns to the admin list page.
	 *
	 * Each entry in $columns must have:
	 *   - 'label'    string              Column header text
	 *   - 'render'   callable            Receives the model instance, echoes the cell content
	 *   - 'sortable' bool|string|array   Optional. Makes the column sortable.
	 *                                    true          → sort by meta key = column slug (string)
	 *                                    'meta_key'    → sort by that meta key (string)
	 *                                    ['key' => 'meta_key', 'numeric' => true]  → numeric sort
	 *
	 * Example:
	 *   MyPost::add_columns([
	 *       'my_field' => [
	 *           'label'    => __('My Field', 'theme'),
	 *           'render'   => fn($post) => esc_html($post->acf('my_field')),
	 *           'sortable' => true,
	 *       ],
	 *       'price' => [
	 *           'label'    => __('Price', 'theme'),
	 *           'render'   => fn($post) => esc_html($post->acf('price')),
	 *           'sortable' => ['key' => 'price', 'numeric' => true],
	 *       ],
	 *   ]);
	 *
	 * @param array $columns Associative array keyed by column slug.
	 * @return void
	 */
	public static function add_columns( array $columns ): void {
		static $registered = [];
		if ( in_array( static::TYPE, $registered, true ) ) {
			return;
		}
		$registered[] = static::TYPE;

		foreach ( $columns as &$column ) {
			if ( ! isset( $column['render'] ) && isset( $column['format'] ) ) {
				$format            = $column['format'];
				$column['render']  = fn( $post ) => esc_html( $post->date( $format ) );
			}
		}
		unset( $column );

		add_filter( 'manage_' . static::TYPE . '_posts_columns', function ( array $existing ) use ( $columns ): array {
			$new = [];
			foreach ( $existing as $key => $label ) {
				$new[ $key ] = $label;
				if ( 'title' === $key ) {
					foreach ( $columns as $col_key => $column ) {
						$new[ $col_key ] = $column['label'];
					}
				}
			}
			return $new;
		} );

		add_action( 'manage_' . static::TYPE . '_posts_custom_column', function ( string $column, int $post_id ) use ( $columns ): void {
			if ( ! isset( $columns[ $column ] ) ) {
				return;
			}
			$model = new static( $post_id );
			echo $columns[ $column ]['render']( $model );
		}, 10, 2 );

		$sortable = array_filter( $columns, fn( $col ) => ! empty( $col['sortable'] ) );

		if ( empty( $sortable ) ) {
			return;
		}

		add_filter( 'manage_edit-' . static::TYPE . '_sortable_columns', function ( array $existing ) use ( $sortable ): array {
			foreach ( $sortable as $col_key => $column ) {
				$existing[ $col_key ] = $col_key;
			}
			return $existing;
		} );

		add_action( 'pre_get_posts', function ( \WP_Query $query ) use ( $sortable ): void {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$orderby = $query->get( 'orderby' );

			if ( ! isset( $sortable[ $orderby ] ) ) {
				return;
			}

			$config   = $sortable[ $orderby ]['sortable'];
			$meta_key = is_array( $config ) ? $config['key'] : ( is_string( $config ) ? $config : $orderby );
			$numeric  = is_array( $config ) && ! empty( $config['numeric'] );

			$query->set( 'meta_key', $meta_key );
			$query->set( 'orderby', $numeric ? 'meta_value_num' : 'meta_value' );
		} );
	}

	/**
	 * Remove columns from the admin list page.
	 *
	 * Example:
	 *   MyPost::remove_columns(['title', 'date']);
	 *
	 * @param string[] $slugs Column slugs to remove.
	 * @return void
	 */
	public static function remove_columns( array $slugs ): void {
		add_filter( 'manage_' . static::TYPE . '_posts_columns', function ( array $existing ) use ( $slugs ): array {
			return array_diff_key( $existing, array_flip( $slugs ) );
		} );
	}
}
