# Media Taxonomy

The Media Taxonomy feature allows you to categorize and organize your media library files using a hierarchical taxonomy system similar to post categories.

## Features

- Categorize media files with a hierarchical taxonomy system
- Filter media in the WordPress media library by category
- Bulk add/remove media items to/from categories
- Template functions for displaying media by category
- REST API support for integration with the block editor

## Admin Interface

### Adding Categories to Media Items

You can assign categories to media items in several ways:

1. **When uploading files**: Categories can be selected in the upload form.
2. **In the media library**: Edit a media item and select categories from the dropdown.
3. **Bulk actions**: Select multiple media items in the list view and use the bulk actions dropdown to add or remove categories.

### Managing Media Categories

To manage your media categories:

1. Go to Media > Media Categories in the WordPress admin.
2. Add, edit, or delete categories just like post categories.

## Template Functions

The plugin provides several helper functions to work with media categories in your themes:

### Get Media by Category

```php
// Get all media items in a specific category
$media_items = get_media_by_category($term_id, 'id');

// Get media items by category slug
$media_items = get_media_by_category('featured-images', 'slug');

// With additional arguments
$media_items = get_media_by_category($term_id, 'id', [
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Loop through the results
foreach ($media_items as $media_item) {
    echo wp_get_attachment_image($media_item->ID, 'thumbnail');
}
```

### Get All Media Categories

```php
// Get all media categories
$categories = get_media_categories();

// With additional arguments
$categories = get_media_categories([
    'orderby' => 'name',
    'order' => 'ASC',
    'hide_empty' => true
]);

// Loop through the categories
foreach ($categories as $category) {
    echo '<h3>' . $category->name . '</h3>';
    
    // Get media for this category
    $media_items = get_media_by_category($category->term_id);
    
    // Display the media
    foreach ($media_items as $media_item) {
        echo wp_get_attachment_image($media_item->ID, 'thumbnail');
    }
}
```

### Get Categories for a Media Item

```php
// Get all categories for a specific media item
$categories = get_media_item_categories($attachment_id);

// Output the category names
foreach ($categories as $category) {
    echo $category->name . ', ';
}
```

### Check if Media is in a Category

```php
// Check if a media item is in a specific category
if (media_in_category($attachment_id, $category_id)) {
    echo 'This media item is in the category';
}

// Check by slug
if (media_in_category($attachment_id, 'featured-images', 'slug')) {
    echo 'This media item is a featured image';
}
```

### Display a Gallery from a Category

```php
// Display a gallery of images from a category
echo media_category_gallery($category_id);

// With custom settings
echo media_category_gallery('gallery-images', 'slug', [], [
    'size' => 'medium',
    'columns' => 4,
    'link' => 'file',
    'class' => 'my-custom-gallery'
]);
```

## Advanced Usage

### Direct Access to Classes

You can also directly use the classes:

```php
use Toolkit\models\MediaTaxonomy;
use Toolkit\utils\MediaTaxonomyHelper;

// Using the model directly
$attachments = MediaTaxonomy::get_attachments_by_term($term_id);

// Using the helper class
$gallery_html = MediaTaxonomyHelper::media_category_gallery($term_id);
```

### Adding Media to Categories Programmatically

```php
// Using the helper function (added in the global namespace)
MediaTaxonomyHelper::add_media_to_category($attachment_id, $term_id);

// Add to multiple categories
MediaTaxonomyHelper::add_media_to_category($attachment_id, [$term_id_1, $term_id_2]);

// Replace existing categories instead of appending
MediaTaxonomyHelper::add_media_to_category($attachment_id, $term_id, false);
```

### Removing Media from Categories Programmatically

```php
// Remove from a category
MediaTaxonomyHelper::remove_media_from_category($attachment_id, $term_id);

// Remove from multiple categories
MediaTaxonomyHelper::remove_media_from_category($attachment_id, [$term_id_1, $term_id_2]);
```

## REST API Support

The Media Categories taxonomy supports the WordPress REST API, making it compatible with the block editor and other applications that use the REST API.

Example endpoint: `/wp-json/wp/v2/media-categories`

## Filters and Hooks

The implementation respects WordPress core filters and hooks for taxonomies, so you can use standard WordPress filters to modify the behavior.

For example:

```php
// Modify the registration arguments for the media category taxonomy
add_filter('register_taxonomy_args', function($args, $taxonomy, $object_type) {
    if ($taxonomy === 'media_category') {
        // Modify args here
        $args['label'] = 'Media Tags';
    }
    return $args;
}, 10, 3);
``` 