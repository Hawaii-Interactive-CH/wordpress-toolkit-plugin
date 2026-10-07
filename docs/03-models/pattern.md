# Custom Pattern

## Creating a Custom Pattern

To create a `Pattern`, add a file in the theme's `models/custom` folder and create its partial in the `partials/patterns` folder with the same name as the `TYPE`.

Copy and modify the following code:

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\Pattern;

class PatternDemo extends Pattern
{
  const TYPE = 'pattern-demo';

  public static function settings()
  {
    return array(
      'title' => 'Demo',
      'description' => 'Demo',
      'keywords' =>
      array(
        0 => 'section',
        1 => 'hi-pattern',
      ),
    );
  }
}
```

Then enable it in `Toolkit > Models`.

## Pattern Content

The partial `partials/patterns/pattern-demo.php` contains the block markup of the pattern. The easiest way to get it is to compose the layout in the editor, then use `Copy all blocks` (or the code editor) and paste the result in the partial.

```php
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
  <!-- wp:heading -->
  <h2 class="wp-block-heading"><?php esc_html_e( 'Title', 'hi-theme-toolkit' ); ?></h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>Lorem ipsum dolor sit amet.</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

The partial is a PHP file, so translation functions and constants such as `HITHTO_THEME_URL` can be used.

ACF blocks can be used in a pattern too:

```html
<!-- wp:acf/block-demo {"name":"acf/block-demo","data":{"title":"Demo"},"mode":"preview"} /-->
```

## Settings

`settings()` accepts the arguments of [register_block_pattern](https://developer.wordpress.org/reference/functions/register_block_pattern/) (`title`, `description`, `categories`, `keywords`, `blockTypes`, `viewportWidth`, `inserter`...). The `content` is generated from the partial.

The pattern is registered as `hithto/{TYPE}`.

## Starter Patterns

When a new page is created, WordPress opens a modal to choose a pattern as a starting point. To add a pattern to this modal, set `blockTypes` to `core/post-content` and list the post types in `postTypes`:

```php
public static function settings()
{
  return array(
    'title' => 'Demo',
    'blockTypes' => array( 'core/post-content' ),
    'postTypes' => array( 'page', 'event' ),
  );
}
```

The modal is only displayed when the content is empty, and users can disable it in the editor preferences (`Show starter patterns`).

## Template Patterns

A pattern can be added by default to every new post of a post type, through the `template` argument of its `type_settings()`:

```php
class Event extends CustomPostType
{
  const TYPE = 'event';

  public static function type_settings()
  {
    return array(
      // ...
      'template' => array(
        array( 'core/pattern', array( 'slug' => 'hithto/pattern-demo' ) ),
      ),
    );
  }
}
```

The template only applies to new posts. Both models must be enabled in `Toolkit > Models`.

If the pattern sets `postTypes`, it must include the post type (here `event`), otherwise the pattern is not loaded in the editor and the template stays empty.

## Hide Pattern

To keep a pattern out of the inserter, set `inserter` to `false`. It can still be used in a template, which is useful for patterns only meant to be added by default to a post type.

```php
class PatternDemo extends Pattern
{
  const TYPE = 'pattern-demo';

  public static function settings()
  {
    return array(
      'title' => 'Demo',
      'inserter' => false,
    );
  }
}
```

## Locking the Layout

In the `Patterns` section of the `Toolkit` page, check `Only administrators can edit the layout of patterns` to lock the toolkit patterns (`hithto/*`) for users who can't edit the theme options (editors, authors...).

Once inserted in a page, these users can still edit texts, images and links, and move or remove the pattern, but they can't change its styles (colors, font sizes, spacing...) or add, remove and reorder the blocks inside it. Administrators are not affected.

The pattern must have a single root block (a group for example): WordPress only keeps the pattern name on the inserted blocks in this case, and the lock relies on it.

## Only Toolkit Patterns

In the `Patterns` section of the `Toolkit` page, check `Only show toolkit patterns in the editor` to hide every pattern that is not a toolkit pattern (`hithto/*`): patterns from WordPress, from the wordpress.org directory, from the theme `patterns` folder and from other plugins.

Patterns created by users in the editor (`My patterns`) are not affected.

## Category

When `categories` is not set, the pattern is added to the toolkit category (`hithto`). Its name, displayed in the editor, can be changed in the `Patterns` section of the `Toolkit` page (default: `Hawaii`).
