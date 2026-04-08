---
description: 'Generate a CustomPostType class that extends CustomPostType and implements JsonSerializable'
---

# Create Custom Post Type

Generate a new Custom Post Type class in the **current working directory** (i.e. the theme or project where the skill is invoked — not the Toolkit plugin itself).

## Instructions

Ask the user for the following information if not already provided in the arguments (`$ARGUMENTS`):

1. **Class name** (PascalCase, e.g. `Event`, `Product`, `TeamMember`)
2. **Post type slug** (lowercase with underscores, e.g. `event`, `product`, `team_member`) — default to lowercase of class name
3. **URL rewrite slug** (lowercase with hyphens, e.g. `events`, `products`, `team-members`) — default to plural of post type slug
4. **Singular label** (human-readable singular, e.g. "Event", "Product", "Team Member")
5. **Plural label** (human-readable plural, e.g. "Events", "Products", "Team Members")
6. **Dashicon** (WordPress dashicon name without the `dashicons-` prefix, e.g. `calendar-alt`, `products`, `groups`) — default to `admin-post`
7. **Supports** (comma-separated list from: `title`, `editor`, `thumbnail`, `excerpt`, `custom-fields`, `revisions`, `page-attributes`) — default to `title, editor, thumbnail, excerpt`
8. **Has archive** (`true` or `false`) — default to `true`
9. **Is public** (`true` or `false`) — default to `true`
10. **Create companion Category taxonomy?** (`yes` or `no`) — default to `no`

## Output

Generate the file at: `models/custom/{ClassName}.php` **relative to the current working directory** (the directory from which this skill was invoked, not the Toolkit plugin directory).

Use exactly this template, filling in the values:

```php
<?php

namespace Toolkit\models\custom;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\CustomPostType;

class {ClassName} extends CustomPostType implements \JsonSerializable
{
    const TYPE = '{post_type_slug}';
    const SLUG = '{rewrite_slug}';

    public static function type_settings()
    {
        return [
            'menu_position' => 2,
            'label' => __('{PluralLabel}', 'toolkit'),
            'labels' => [
                'name'          => __('{PluralLabel}', 'toolkit'),
                'singular_name' => __('{SingularLabel}', 'toolkit'),
                'menu_name'     => __('{PluralLabel}', 'toolkit'),
                'all_items'     => __('All {PluralLabel}', 'toolkit'),
                'add_new'       => __('Add New', 'toolkit'),
                'add_new_item'  => __('Add New {SingularLabel}', 'toolkit'),
                'edit_item'     => __('Edit {SingularLabel}', 'toolkit'),
                'new_item'      => __('New {SingularLabel}', 'toolkit'),
                'view_item'     => __('View {SingularLabel}', 'toolkit'),
                'view_items'    => __('View {PluralLabel}', 'toolkit'),
                'search_items'  => __('Search {PluralLabel}', 'toolkit'),
            ],
            'description'          => '',
            'public'               => {is_public},
            'publicly_queryable'   => {is_public},
            'show_ui'              => true,
            'show_in_rest'         => true,
            'show_in_nav_menus'    => true,
            'has_archive'          => {has_archive},
            'show_in_menu'         => true,
            'exclude_from_search'  => false,
            'capability_type'      => 'post',
            'map_meta_cap'         => true,
            'hierarchical'         => false,
            'rewrite'              => ['slug' => self::SLUG, 'with_front' => false],
            'query_var'            => true,
            'menu_icon'            => 'dashicons-{dashicon}',
            'supports'             => [{supports_array}],
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id'      => $this->id(),
            'title'   => $this->title(),
            'slug'    => $this->slug(),
            'link'    => $this->link(),
            'excerpt' => $this->excerpt(),
            'content' => $this->content(),
            'date'    => $this->date(),
        ];
    }
}
```

If the user requested a companion Category taxonomy, also generate `models/custom/{ClassName}Category.php` in the same current working directory:

```php
<?php

namespace Toolkit\models\custom;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\Taxonomy;
use Toolkit\models\custom\{ClassName};

class {ClassName}Category extends Taxonomy
{
    const TYPE = '{post_type_slug}_category';

    public static function register()
    {
        register_taxonomy(self::TYPE, {ClassName}::TYPE, [
            'hierarchical'      => true,
            'show_admin_column' => true,
            'publicly_queryable' => false,
            'show_in_rest'      => true,
            'labels' => [
                'name'              => __('Categories', 'toolkit'),
                'singular_name'     => __('Category', 'toolkit'),
                'search_items'      => __('Search Categories', 'toolkit'),
                'all_items'         => __('All Categories', 'toolkit'),
                'parent_item'       => __('Parent Category', 'toolkit'),
                'parent_item_colon' => __('Parent Category:', 'toolkit'),
                'edit_item'         => __('Edit Category', 'toolkit'),
                'update_item'       => __('Update Category', 'toolkit'),
                'add_new_item'      => __('Add New Category', 'toolkit'),
                'new_item_name'     => __('New Category Name', 'toolkit'),
                'menu_name'         => __('Categories', 'toolkit'),
            ],
        ]);
    }
}
```

## After generating the files

Remind the user to register the new post type in their theme's model registration (typically in `functions.php` or a service file), for example:

```php
add_action('init', function () {
    \Toolkit\models\custom\{ClassName}::register();
});
```

And if a category taxonomy was created:

```php
add_action('init', function () {
    \Toolkit\models\custom\{ClassName}Category::register();
});
```
