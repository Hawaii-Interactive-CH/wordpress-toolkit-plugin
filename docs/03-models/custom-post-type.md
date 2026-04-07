# Custom Post Type

## Creating a Custom Post Type

To create a `Custom Post Type`, add a file in the theme's `models/custom` folder, or generate one via `Toolkit > Models` in the admin.

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\CustomPostType;

class Demo extends CustomPostType implements \JsonSerializable
{
  const TYPE = 'demo';
  const SLUG = 'demos';

  public static function type_settings()
  {
    return array(
      'menu_position' => 2,
      'label' => 'Demos',
      'labels' =>
      array(
        'name' => 'Demos',
        'singular_name' => 'Demo',
        'menu_name' => 'Demos',
        'all_items' => 'All demos',
        'add_new' => 'Add new',
        'add_new_item' => 'Add new demo',
        'edit_item' => 'Edit demo',
        'new_item' => 'New demo',
        'view_item' => 'View demo',
        'view_items' => 'View demos',
        'search_items' => 'Search demo',
      ),
      'description' => '',
      'public' => true,
      'publicly_queryable' => true,
      'show_ui' => true,
      'show_in_rest' => true,
      'show_in_nav_menus' => true,
      'rest_base' => '',
      'has_archive' => true,
      'show_in_menu' => true,
      'exclude_from_search' => false,
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'hierarchical' => false,
      'rewrite' =>
      array(
        'slug' => 'demos',
        'with_front' => false,
      ),
      'query_var' => true,
      'menu_icon' => 'dashicons-icon-default',
      'supports' =>
      array(
        0 => 'title',
        1 => 'editor',
        2 => 'thumbnail',
        3 => 'excerpt',
      ),
    );
  }

  public function jsonSerialize(): mixed
  {
    return [
      "id" => $this->id(),
      "title" => $this->title(),
      "slug" => $this->slug(),
      "link" => $this->link(),
      "excerpt" => $this->excerpt(),
      "content" => $this->content(),
      "date" => $this->date(),
    ];
  }
}
```

---

## Admin List Columns

### Adding columns — `add_columns()`

Inserts custom columns immediately after the **Title** column. Idempotent: calling `add_columns()` multiple times for the same type only registers the hooks once.

#### Column options

| Key | Type | Description |
|---|---|---|
| `label` | `string` | Column header label |
| `render` | `callable` | Receives the model instance, must output (echo) the content |
| `format` | `string` | *(optional)* Date shortcut — replaces `render` with `$post->date($format)` |
| `sortable` | `bool\|string\|array` | *(optional)* Makes the column sortable |

> `format` and `render` are mutually exclusive. If `format` is set, `render` is ignored.

#### `sortable` values

| Value | Behaviour |
|---|---|
| `true` | Sort by meta_key = column slug (alphabetical) |
| `'my_key'` | Sort by the specified meta_key (alphabetical) |
| `['key' => 'my_key', 'numeric' => true]` | Numeric sort by the specified meta_key |

#### Example

```php
// In functions.php or the theme initialisation
Demo::add_columns([
    'illustration' => [
        'label'  => __('Illustration', 'theme'),
        'render' => fn($post) => $post->thumbnail(
            fn($img) => '<img src="' . esc_url($img->src('thumbnail')) . '" width="60">'
        ),
    ],
    'category' => [
        'label'    => __('Category', 'theme'),
        'render'   => fn($post) => esc_html(
            implode(', ', $post->terms(DemoCategory::class, fn($t) => $t->name()))
        ),
        'sortable' => true,
    ],
    'price' => [
        'label'    => __('Price', 'theme'),
        'render'   => fn($post) => esc_html($post->acf('price')) . ' CHF',
        'sortable' => ['key' => 'price', 'numeric' => true],
    ],
    'publish_date' => [
        'label'  => __('Date', 'theme'),
        'format' => 'd.m.Y',
    ],
]);
```

### Removing columns — `remove_columns()`

Removes one or more columns by their slug.

```php
Demo::remove_columns(['title', 'date']);
```

Common slugs: `cb` (checkbox), `title`, `author`, `date`, `comments`.
