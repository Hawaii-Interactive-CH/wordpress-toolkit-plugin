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

The partial `partials/patterns/pattern-demo.php` is written in plain HTML, with the theme classes. The toolkit converts it into blocks when the pattern is registered.

```php
<section class="section-demo" data-name="Demo section">

    <h2 class="green" data-name="Section title" data-placeholder="Enter the section title…"></h2>

    <p class="bigger">Lorem ipsum dolor sit amet.</p>

    <ul>
        <li>First point</li>
        <li>Second point</li>
    </ul>

    <div data-acf="block-demo" data-name="Banner" data-field-titre="Welcome"></div>

</section>
```

The partial is a PHP file, so translation functions and constants such as `HITHTO_THEME_URL` can be used. Plain texts are enough for the editor labels (`data-name`, `data-placeholder`); use `__()` only if the editors of a site work in another language.

### Elements

| HTML | Block |
|---|---|
| `h1` to `h6` | Heading |
| `p` | Paragraph |
| `ul`, `ol` with `li` | List |
| `img` | Image |
| `div`, `section`, `header`, `footer`, `article`, `aside`, `main` | Group (the tag is kept) |
| any element with `data-acf` | ACF block |
| any other tag | Custom HTML |

`class` is kept as the block additional CSS class and `id` as its anchor. Other attributes are removed.

### Data attributes

| Attribute | Use |
|---|---|
| `data-name` | Name of the element in the `Content` panel and the list view |
| `data-placeholder` | Text displayed in an empty heading or paragraph, in the editor only |
| `data-acf` | `TYPE` of the ACF block |
| `data-field-{name}` | Initial value of the ACF field `{name}` |
| `data-locked` | Element that non-administrators can't edit (see `Locked Elements`) |

### Rules

- The pattern has a **single root element** (a `section` or a `div` holding the other elements), so the layout lock works.
- A text written directly inside a `section` or a `div` is ignored: put it in a `p`.
- A `li` only contains text and inline tags (`strong`, `em`, `a`, `br`, `span`): no `p` and no nested list, or the block is invalid.
- ACF field names are lowercase with underscores (`data-field-titre_bouton`): the HTML parser turns attribute names to lowercase.
- A value starting with `[` or `{` is read as JSON (for a `link` field for example). Always write it with `esc_attr( wp_json_encode( ... ) )`, which handles quotes and apostrophes:

```php
<div data-acf="block-demo" data-field-lien="<?php echo esc_attr( wp_json_encode( [ 'url' => '#', 'title' => "Voir l'agenda", 'target' => '' ] ) ); ?>"></div>
```

- Only simple fields can get an initial value. Repeater, group, flexible content, image, file and relational fields can't be filled from the pattern (an image ID is not the same from one site to another): they are empty when the pattern is inserted, and the user fills them in the editor. For an image, plan a default image in the block partial.
- The Custom HTML block (any other tag) can't be edited by non-administrators in a locked pattern, and its content is filtered when a user without the `unfiltered_html` capability saves the page (authors, or editors on a multisite): `svg`, `iframe`, `script` and some attributes are removed. For an icon, use an ACF block.

### Block markup

A partial that already contains block markup (`<!-- wp:`) is kept as is. You can compose a layout in the editor, use `Copy all blocks` and paste the result in the partial.

## Naming the Elements

By default, the `Content` panel and the list view show the block type (`Paragraph`, `Image`...). Give each element a name with `data-name` to show what it is used for:

```html
<h2 data-name="Section title"></h2>
<p data-name="Introduction">Lorem ipsum dolor sit amet.</p>
<div data-acf="block-demo" data-name="Banner"></div>
```

## Placeholders

Instead of a sample text, a placeholder tells the user what to write. It is only displayed in the editor while the heading or paragraph is empty:

```html
<h2 data-placeholder="Event title…"></h2>
<p data-placeholder="Write a short introduction…"></p>
```

If the user leaves an element empty, its empty tag (`<p></p>`) is still printed on the front-end: hide it in the theme styles if needed (`p:empty { display: none; }`).

## Settings

`settings()` accepts the arguments of [register_block_pattern](https://developer.wordpress.org/reference/functions/register_block_pattern/) (`title`, `description`, `categories`, `keywords`, `blockTypes`, `viewportWidth`, `inserter`...). The `content` is generated from the partial.

The pattern is registered as `hithto/{TYPE}`.

## Inserter Preview

The inserter shows a preview of each pattern, rendered with the theme block styles.

- `viewportWidth` sets the width (in pixels) used to render the preview, so a full-width pattern looks like on a desktop screen.
- `description` and `keywords` are used by the inserter search.

```php
public static function settings()
{
  return array(
    'title' => 'Demo',
    'description' => 'A section with a title, a text and a banner.',
    'keywords' => array( 'section', 'banner' ),
    'viewportWidth' => 1400,
  );
}
```

ACF blocks inside a pattern are previewed with the values set with `data-field-{name}`. To give an ACF block a filled preview in the block inserter, see `example` in the Block documentation.

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

Once inserted in a page, these users can still edit texts, images and links, and move or remove the pattern, but they can't change its styles (colors, font sizes, spacing...) or add, remove and reorder the blocks inside it. This also applies in the `Edit pattern` mode. The `Edit pattern` button of the block settings sidebar is hidden, so the sidebar only shows the `Content` panel. Administrators are not affected.

The pattern must have a single root block (a group for example): WordPress only keeps the pattern name on the inserted blocks in this case, and the lock relies on it.

The lock is applied in the editor only, nothing is saved in the post content.

## Locked Elements

By default, the texts, images and links of a locked pattern stay editable. Add `data-locked` to an element to freeze it too, for example an imposed section title:

```html
<section class="section-demo">
    <h2 data-locked>Our next events</h2>
    <p data-placeholder="Write a short introduction…"></p>
</section>
```

For non-administrators, a locked element is still displayed but can't be selected or edited, and it is not listed in the `Content` panel. Inside a locked group, every element is locked.

- It only works when `Only administrators can edit the layout of patterns` is checked. Administrators are never affected.
- Don't put `data-locked` on the root element: the whole pattern would be frozen, and the user could not select it to move or remove it.

## Only Toolkit Patterns

In the `Patterns` section of the `Toolkit` page, check `Only show toolkit patterns in the editor` to hide every pattern that is not a toolkit pattern (`hithto/*`): patterns from WordPress, from the wordpress.org directory, from the theme `patterns` folder and from other plugins.

Patterns created by users in the editor (`My patterns`) are not affected.

## Category

When `categories` is not set, the pattern is added to the toolkit category (`hithto`). Its name, displayed in the editor, can be changed in the `Patterns` section of the `Toolkit` page (default: `Hawaii`).
