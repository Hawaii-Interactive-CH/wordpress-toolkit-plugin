---
description: 'Generate a standalone Taxonomy class extending Toolkit\models\Taxonomy'
---

# Create Taxonomy

Generate a new Taxonomy class for the WordPress Toolkit plugin.

## Instructions

Ask the user for the following information if not already provided in the arguments (`$ARGUMENTS`):

1. **Class name** (PascalCase, e.g. `ProjectCategory`, `ArticleTag`, `Region`)
2. **Taxonomy slug** (lowercase with underscores, e.g. `project_category`, `article_tag`, `region`) — default to snake_case of class name
3. **Singular label** (human-readable singular, e.g. "Category", "Tag", "Region")
4. **Plural label** (human-readable plural, e.g. "Categories", "Tags", "Regions")
5. **Is hierarchical?** (`true` for category-like, `false` for tag-like) — default to `true`
6. **Attached post type(s)** — one or more `TYPE` constants to attach to, e.g. `post`, `page`, or a custom type slug like `project`. If a custom post type from this toolkit is used, the class reference will be included via `use`.
7. **Show in REST?** (`true` or `false`) — default to `true`
8. **Publicly queryable?** (`true` or `false`) — default to `false`

## Output

Generate the file at: `models/custom/{ClassName}.php`

Use exactly this template:

```php
<?php

namespace Toolkit\models\custom;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\Taxonomy;

class {ClassName} extends Taxonomy
{
    const TYPE = '{taxonomy_slug}';

    public static function register()
    {
        register_taxonomy(self::TYPE, [{post_types}], [
            'hierarchical'       => {hierarchical},
            'show_admin_column'  => true,
            'publicly_queryable' => {publicly_queryable},
            'show_in_rest'       => {show_in_rest},
            'labels' => [
                'name'              => __('{PluralLabel}', 'toolkit'),
                'singular_name'     => __('{SingularLabel}', 'toolkit'),
                'search_items'      => __('Search {PluralLabel}', 'toolkit'),
                'all_items'         => __('All {PluralLabel}', 'toolkit'),
                'parent_item'       => __('Parent {SingularLabel}', 'toolkit'),
                'parent_item_colon' => __('Parent {SingularLabel}:', 'toolkit'),
                'edit_item'         => __('Edit {SingularLabel}', 'toolkit'),
                'update_item'       => __('Update {SingularLabel}', 'toolkit'),
                'add_new_item'      => __('Add New {SingularLabel}', 'toolkit'),
                'new_item_name'     => __('New {SingularLabel} Name', 'toolkit'),
                'menu_name'         => __('{PluralLabel}', 'toolkit'),
            ],
        ]);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id'    => $this->id(),
            'title' => $this->title(),
            'slug'  => $this->slug(),
            'link'  => $this->link(),
        ];
    }
}
```

**Notes on `{post_types}`:**
- If attached to a built-in type, use the string directly: `'post'`, `'page'`
- If attached to a custom toolkit type (e.g. `Project`), add a `use` statement and reference the constant: `Project::TYPE`
- Multiple types: `[Project::TYPE, 'post']`

## After generating the file

Remind the user to register the taxonomy in their theme (e.g. `functions.php`):

```php
add_action('init', function () {
    \Toolkit\models\custom\{ClassName}::register();
});
```

And to use it in a post type's `type_settings()` if desired:

```php
'taxonomies' => [{ClassName}::TYPE],
```
