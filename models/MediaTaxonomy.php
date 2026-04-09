<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

class MediaTaxonomy extends Taxonomy {
	const TYPE             = 'media_category';
	const DEFAULT_CATEGORY = 'Uncategorized';

	/**
	 * Register the media category taxonomy
	 *
	 * @return void
	 */
	public static function register() {
		// Register the taxonomy
		register_taxonomy(
			self::TYPE,
			'attachment',
			[
				'hierarchical'            => true,
				'show_admin_column'       => true,
				'publicly_queryable'      => true,
				'show_in_rest'            => true,
				'show_in_nav_menus'       => true,
				'show_ui'                 => true,
				'show_tagcloud'           => false,
				'update_count_callback'   => '_update_generic_term_count',
				'labels'                  => [
					'name'              => __( 'Categories', 'wp-theme-toolkit' ),
					'singular_name'     => __( 'Category', 'wp-theme-toolkit' ),
					'search_items'      => __( 'Search Categories', 'wp-theme-toolkit' ),
					'all_items'         => __( 'All Categories', 'wp-theme-toolkit' ),
					'parent_item'       => __( 'Parent Category', 'wp-theme-toolkit' ),
					'parent_item_colon' => __( 'Parent Category:', 'wp-theme-toolkit' ),
					'edit_item'         => __( 'Edit Category', 'wp-theme-toolkit' ),
					'update_item'       => __( 'Update Category', 'wp-theme-toolkit' ),
					'add_new_item'      => __( 'Add New Category', 'wp-theme-toolkit' ),
					'new_item_name'     => __( 'New Category Name', 'wp-theme-toolkit' ),
					'menu_name'         => __( 'Categories', 'wp-theme-toolkit' ),
				],
			]
		);

		// Make sure attachments support the taxonomy
		self::register_attachment_taxonomy_support();

		// Add list view filter to the media library
		add_action( 'restrict_manage_posts', [ self::class, 'add_media_category_filter' ] );

		// Add grid view filter to the media library
		add_action( 'admin_footer', [ self::class, 'add_media_grid_category_filter' ] );

		// Modify attachment query based on selected category
		add_filter( 'parse_query', [ self::class, 'filter_media_by_category' ] );

		// Change media filter dropdown labels
		add_filter( 'media_view_strings', [ self::class, 'change_media_filter_labels' ], 10, 1 );

		// Create default category after the taxonomy is registered
		add_action( 'init', [ self::class, 'create_default_category' ], 20 );

		// Set default category for new uploads
		add_action( 'add_attachment', [ self::class, 'set_default_category_for_new_uploads' ] );

		// Add category filter for modal media library
		add_action( 'admin_enqueue_scripts', [ self::class, 'add_grid_mode_filter' ] );

		// Make media terms available to JavaScript
		add_action( 'wp_prepare_attachment_for_js', [ self::class, 'include_categories_in_attachment_js' ], 10, 3 );
	}

	/**
	 * Add category filter directly to the media grid toolbar
	 *
	 * @return void
	 */
	public static function add_media_grid_category_filter() {
		global $pagenow;
		$get_data = wp_unslash( $_GET );
		$mode     = isset( $get_data['mode'] ) ? sanitize_key( $get_data['mode'] ) : '';

		// Only on media library page in grid mode
		if ( 'upload.php' !== $pagenow || 'grid' !== $mode ) {
			return;
		}

		// Get all media categories
		$media_categories = get_terms( [
			'taxonomy'   => self::TYPE,
			'hide_empty' => false,
		] );

		// Don't display if there are no categories
		if ( empty( $media_categories ) || is_wp_error( $media_categories ) ) {
			return;
		}

		// Get the current filter value
		$current = isset( $get_data[ self::TYPE ] ) ? sanitize_text_field( $get_data[ self::TYPE ] ) : '';

		// Add the category dropdown script
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Create a hidden form that will be submitted
			var filterForm = $('<form></form>')
				.attr('method', 'get')
				.attr('action', '<?php echo admin_url( 'upload.php' ); ?>')
				.css('display', 'inline')
				.css('margin', '0')
				.css('padding', '0');

			// Create filter dropdown
			var categoryFilter = $('<select></select>')
				.attr('name', '<?php echo self::TYPE; ?>')
				.addClass('attachment-filters')
				.css('margin-left', '8px');

			// Add the "All Categories" option
			$('<option></option>')
				.attr('value', '')
				.text('<?php echo esc_js( __( 'All Categories', 'wp-theme-toolkit' ) ); ?>')
				.prop('selected', <?php echo empty( $current ) ? 'true' : 'false'; ?>)
				.appendTo(categoryFilter);

			// Add each category
			<?php foreach ( $media_categories as $category ) : ?>
			$('<option></option>')
				.attr('value', '<?php echo esc_js( $category->slug ); ?>')
				.text('<?php echo esc_js( $category->name ); ?> (<?php echo esc_js( $category->count ); ?>)')
				.prop('selected', <?php echo $current === $category->slug ? 'true' : 'false'; ?>)
				.appendTo(categoryFilter);
			<?php endforeach; ?>

			// Add change listener to refresh when changed
			categoryFilter.on('change', function() {
				// When selecting a category, submit the form
				filterForm.submit();
			});

			// Add all necessary hidden inputs to preserve current state
			<?php
			// Keep all current GET parameters (except our taxonomy) to preserve filters
			foreach ( $get_data as $key => $value ) {
				if ( $key !== self::TYPE && 'paged' !== $key ) {
					$safe_key = sanitize_key( $key );
					$safe_val = sanitize_text_field( $value );
					echo 'filterForm.append($(\'<input type="hidden" name="' . esc_js( $safe_key ) . '" value="' . esc_js( $safe_val ) . '">\'));' . "\n";
				}
			}
			?>

			// Add the filter to the media toolbar (wait for DOM to be ready)
			var intervalId = setInterval(function() {
				var toolbar = $('.wp-filter .filter-items, .media-toolbar-secondary');

				if (toolbar.length) {
					clearInterval(intervalId);

					// Add filter label
					var filterLabel = $('<span></span>')
						.text('<?php echo esc_js( __( 'Categories:', 'wp-theme-toolkit' ) ); ?>')
						.css('margin-left', '12px')
						.css('margin-right', '4px');

					// Add a container for our form and elements
					var filterContainer = $('<div></div>')
						.addClass('media-category-filter-container')
						.css('display', 'inline-block');

					// Add the dropdown to the form
					filterForm.append(categoryFilter);

					// Add the form to the container
					filterContainer.append(filterForm);

					// Add everything to the toolbar
					toolbar.append(filterLabel);
					toolbar.append(filterContainer);
				}
			}, 100);
		});
		</script>
		<?php
	}

	/**
	 * Include category information in attachment JS data
	 *
	 * @param array    $response   Attachment JS data.
	 * @param \WP_Post $attachment Attachment post.
	 * @param array    $meta       Attachment metadata.
	 * @return array
	 */
	public static function include_categories_in_attachment_js( $response, $attachment, $meta ) {
		// Get the terms for this attachment
		$terms = get_the_terms( $attachment->ID, self::TYPE );

		// Add terms to the response
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$response[ self::TYPE ] = [];

			foreach ( $terms as $term ) {
				$response[ self::TYPE ][] = [
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				];
			}
		}

		return $response;
	}

	/**
	 * Add category filter for grid mode
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function add_grid_mode_filter( $hook ) {
		// Only on media library page
		if ( 'upload.php' !== $hook ) {
			return;
		}

		// Get all categories for the media filter
		$terms = get_terms( [
			'taxonomy'   => self::TYPE,
			'hide_empty' => false,
		] );

		// Prepare terms for JS
		$categories = [];
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = [
					'id'    => $term->term_id,
					'slug'  => $term->slug,
					'name'  => $term->name,
					'count' => $term->count,
				];
			}
		}

		// Add script
		wp_enqueue_script(
			'media-category-filter',
			WP_TOOLKIT_URL . 'admin/assets/js/media-category-filter.js',
			[ 'media-views' ],
			null,
			true
		);

		// Pass data to script
		wp_localize_script( 'media-category-filter', 'MediaCategories', [
			'categories' => $categories,
			'taxonomy'   => self::TYPE,
			'labels'     => [
				'filterBy' => __( 'Categories', 'wp-theme-toolkit' ),
				'all'      => __( 'All Categories', 'wp-theme-toolkit' ),
			],
		] );

		// Add inline script for immediate execution
		self::add_inline_media_grid_script();
	}

	/**
	 * Add inline script for media grid filter
	 *
	 * @return void
	 */
	private static function add_inline_media_grid_script() {
		ob_start();
		?>
<script>
(function($) {
	// Wait for WordPress to be ready
	$(document).ready(function() {
		// Only run once WordPress media is loaded
		if (typeof wp === 'undefined' || typeof wp.media === 'undefined') return;

		var taxonomy = '<?php echo self::TYPE; ?>';

		// Filter attachments in media grid
		var originalFilterAttachments = wp.media.view.AttachmentFilters.All.prototype.filterAttachments;
		wp.media.view.AttachmentFilters.All.prototype.filterAttachments = function() {
			var collection = originalFilterAttachments.apply(this, arguments);

			// If a category filter is applied
			if (this.model.get(taxonomy)) {
				var selectedCategory = this.model.get(taxonomy);

				collection = new wp.media.model.Attachments(collection.models.filter(function(model) {
					var modelCategories = model.get(taxonomy) || [];
					return modelCategories.some(function(cat) {
						return cat.slug === selectedCategory;
					});
				}));
			}

			return collection;
		};

		// Add category filter to media grid
		var AttachmentCategoryFilter = wp.media.view.AttachmentFilters.extend({
			id: 'media-attachment-category-filter',

			createFilters: function() {
				var filters = {};

				// Add "All" option
				filters.all = {
					text: MediaCategories.labels.all,
					props: { },
					priority: 10
				};

				// Add each category
				_.each(MediaCategories.categories, function(category) {
					var categoryFilter = {};
					categoryFilter[taxonomy] = category.slug;

					filters[category.slug] = {
						text: category.name + ' (' + category.count + ')',
						props: categoryFilter,
						priority: 20
					};
				});

				this.filters = filters;
			}
		});

		// Extended media grid controller to add category filter
		var oldMediaGridController = wp.media.controller.Library;
		wp.media.controller.Library = oldMediaGridController.extend({
			defaults: _.defaults({
				filterable: 'uploaded',
			}, oldMediaGridController.prototype.defaults),
		});

		// Add filter to media grid view
		var oldMediaLibraryBrowser = wp.media.view.MediaFrame.Post;
		wp.media.view.MediaFrame.Post = oldMediaLibraryBrowser.extend({
			initialize: function() {
				oldMediaLibraryBrowser.prototype.initialize.apply(this, arguments);

				// Listen for changes to the taxonomy filter
				this.on('content:activate:browse', function() {
					var categoryFilter = this.content.get().toolbar.get('media-category-filter');
					if (!categoryFilter) {
						this.content.get().toolbar.set('media-category-filter', new AttachmentCategoryFilter({
							controller: this,
							model: this.state().get('library'),
							priority: -75,
							className: 'attachment-filters'
						}).render());
					}
				}, this);
			}
		});
	});
})(jQuery);
</script>
		<?php
		$script = ob_get_clean();

		// Add the script directly
		wp_add_inline_script( 'media-category-filter', $script );
	}

	/**
	 * Create default category if it doesn't exist
	 *
	 * @return void
	 */
	public static function create_default_category() {
		// Get all terms to check if default category exists
		$terms = get_terms( [
			'taxonomy'   => self::TYPE,
			'hide_empty' => false,
		] );

		// Return if there's an error or if terms already exist (no need to create default)
		if ( is_wp_error( $terms ) || ! empty( $terms ) ) {
			return;
		}

		// Create the default category
		wp_insert_term(
			__( self::DEFAULT_CATEGORY, 'wp-theme-toolkit' ),
			self::TYPE,
			[
				'slug'        => sanitize_title( self::DEFAULT_CATEGORY ),
				'description' => __( 'Default category for media attachments', 'wp-theme-toolkit' ),
			]
		);
	}

	/**
	 * Set default category for new media uploads
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return void
	 */
	public static function set_default_category_for_new_uploads( $attachment_id ) {
		// Skip if attachment already has categories
		$has_terms = wp_get_object_terms( $attachment_id, self::TYPE, [ 'fields' => 'ids' ] );
		if ( ! empty( $has_terms ) && ! is_wp_error( $has_terms ) ) {
			return;
		}

		// Get default category
		$default_term = get_term_by( 'name', __( self::DEFAULT_CATEGORY, 'wp-theme-toolkit' ), self::TYPE );

		// If default category exists, assign it to the attachment
		if ( $default_term && ! is_wp_error( $default_term ) ) {
			wp_set_object_terms( $attachment_id, [ $default_term->term_id ], self::TYPE );
		}
	}

	/**
	 * Register taxonomy support for attachments
	 *
	 * @return void
	 */
	public static function register_attachment_taxonomy_support() {
		// Ensure the attachment post type supports taxonomies
		add_post_type_support( 'attachment', 'thumbnail' );

		// Register attachment in the post types that support our taxonomy
		register_taxonomy_for_object_type( self::TYPE, 'attachment' );

		// Add attachment to post types shown in category admin
		add_action( 'admin_init', function () {
			global $wp_taxonomies;

			// Ensure the taxonomy is registered before trying to modify it
			if ( isset( $wp_taxonomies[ self::TYPE ] ) ) {
				// Make sure 'attachment' is in the object_type array
				if ( ! in_array( 'attachment', $wp_taxonomies[ self::TYPE ]->object_type, true ) ) {
					$wp_taxonomies[ self::TYPE ]->object_type[] = 'attachment';
				}
			}
		} );
	}

	/**
	 * Change the media filter dropdown labels
	 *
	 * @param array $strings Media view strings.
	 * @return array
	 */
	public static function change_media_filter_labels( $strings ) {
		// Change the filter type label (the main dropdown that shows "Toutes")
		$strings['filterByType'] = __( 'Categories', 'wp-theme-toolkit' );

		// Change the "All" text in the dropdown
		$strings['all'] = __( 'All Categories', 'wp-theme-toolkit' );

		return $strings;
	}

	/**
	 * Add the category filter dropdown to media library
	 *
	 * @return void
	 */
	public static function add_media_category_filter() {
		global $pagenow;
		$get_data = wp_unslash( $_GET );
		$mode     = isset( $get_data['mode'] ) ? sanitize_key( $get_data['mode'] ) : '';

		// Only add on the media library page in list mode
		if ( 'upload.php' !== $pagenow || 'grid' === $mode ) {
			return;
		}

		// Get all media categories
		$media_categories = get_terms( [
			'taxonomy'   => self::TYPE,
			'hide_empty' => false,
		] );

		// Don't display the dropdown if there are no categories
		if ( empty( $media_categories ) || is_wp_error( $media_categories ) ) {
			return;
		}

		// Get the current filter value
		$current = isset( $get_data[ self::TYPE ] ) ? sanitize_text_field( $get_data[ self::TYPE ] ) : '';

		// Display dropdown
		echo '<label for="media-category-filter" class="screen-reader-text">' . esc_html__( 'Filter by category', 'wp-theme-toolkit' ) . '</label>';
		echo '<select name="' . esc_attr( self::TYPE ) . '" id="media-category-filter" class="postform">';
		echo '<option value="">' . esc_html__( 'All Categories', 'wp-theme-toolkit' ) . '</option>';

		foreach ( $media_categories as $category ) {
			printf(
				'<option value="%s" %s>%s (%d)</option>',
				esc_attr( $category->slug ),
				selected( $current, $category->slug, false ),
				esc_html( $category->name ),
				esc_html( $category->count )
			);
		}

		echo '</select>';
	}

	/**
	 * Filter the media library by the selected category
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 * @return \WP_Query
	 */
	public static function filter_media_by_category( $query ) {
		global $pagenow;
		$get_data = wp_unslash( $_GET );

		// Only filter on the media library page
		if ( 'upload.php' !== $pagenow || ! isset( $get_data[ self::TYPE ] ) || empty( $get_data[ self::TYPE ] ) ) {
			return $query;
		}

		// Get the selected category
		$category = sanitize_text_field( $get_data[ self::TYPE ] );

		// Set tax query for filtering
		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = [];
		}

		$tax_query[] = [
			'taxonomy' => self::TYPE,
			'field'    => 'slug',
			'terms'    => $category,
		];

		$query->set( 'tax_query', $tax_query );

		return $query;
	}
}
