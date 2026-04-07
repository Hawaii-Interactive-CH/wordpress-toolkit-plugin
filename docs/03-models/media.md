# Media

## Introduction

The `AbstractMedia` model is the base class for WordPress attachments (images, SVGs, etc.). It provides methods to retrieve the optimised URL of an image, generate `srcset` attributes, render a full `<picture>` element, and insert an SVG inline.

To use it, create a class in `models/custom/` that extends `AbstractMedia`:

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\AbstractMedia;

class Media extends AbstractMedia {}
```

---

## Available Methods

### src()

Returns the optimised URL of an image for a given size. Priority: WebP → PNG/JPG → original. If the requested size does not exist, returns the original image URL.

```php
$media = new Media($attachment_id);

// URL of the WebP version generated for the "image-xl" size
echo $media->src('image-xl');

// URL of the original image
echo $media->src('full');
```

Example in a template:

```php
<img
    src="<?= $media->src('image-xl') ?>"
    alt="<?= $media->alt() ?>">
```

---

### srcset()

Builds the `srcset` attribute value from an associative array `[size name => width descriptor]`. Only already-generated sizes are included.

```php
$media = new Media($attachment_id);

echo $media->srcset([
    'image-s'  => '640w',
    'image-m'  => '960w',
    'image-l'  => '1280w',
    'image-xl' => '1920w',
]);

// → "https://…/image-640x….webp 640w, https://…/image-960x….webp 960w, …"
```

Example in a template:

```php
<img
    src="<?= $media->src('image-xl') ?>"
    srcset="<?= $media->srcset(['image-s' => '640w', 'image-l' => '1280w', 'image-xl' => '1920w']) ?>"
    sizes="100vw"
    alt="<?= $media->alt() ?>">
```

---

### picture()

Generates a full `<picture>` element with `<source>` elements and a fallback `<img>`. Each entry in the `$sources` array corresponds to a `<source>`. Supports retina variants (2x) and media queries.

**Parameters:**

| Parameter | Type    | Description |
|-----------|---------|-------------|
| `$sources` | `array` | Array of source definitions (see below) |
| `$class`  | `string` | Optional CSS class added to the `<img>` element |
| `$lazy`   | `bool`  | Adds `loading="lazy"` to the `<img>`. Default: `true` |

Each entry in `$sources` is an associative array:

| Key      | Type     | Description |
|----------|----------|-------------|
| `size`   | `string` | **Required.** Registered size name (e.g. `'image-xl'`) |
| `media`  | `string` | Optional. CSS media query (e.g. `'(min-width: 1280px)'`) |
| `size2x` | `string` | Optional. Size name for the retina 2x variant |

The last resolved `<source>` is also used as the `src` of the fallback `<img>`.

```php
$media = new Media($attachment_id);

// Simple example: one source with retina
echo $media->picture([
    ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
]);
```

```html
<!-- Output -->
<picture>
    <source srcset="https://…/image-xl.webp 1x, https://…/image-xl-2x.webp 2x">
    <img src="https://…/image-xl.webp" alt="…" loading="lazy">
</picture>
```

Example with multiple breakpoints:

```php
echo $media->picture([
    ['size' => 'image-s',  'media' => '(max-width: 640px)',  'size2x' => 'image-s-2x'],
    ['size' => 'image-m',  'media' => '(max-width: 1280px)', 'size2x' => 'image-m-2x'],
    ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
], 'hero__image');
```

```html
<!-- Output -->
<picture>
    <source media="(max-width: 640px)"  srcset="https://…/image-s.webp 1x, https://…/image-s-2x.webp 2x">
    <source media="(max-width: 1280px)" srcset="https://…/image-m.webp 1x, https://…/image-m-2x.webp 2x">
    <source srcset="https://…/image-xl.webp 1x, https://…/image-xl-2x.webp 2x">
    <img src="https://…/image-xl.webp" alt="…" class="hero__image" loading="lazy">
</picture>
```

---

### inline_svg()

Returns the raw SVG file content for direct inline insertion in HTML. Useful for controlling icons or illustrations via CSS/JS. Only works for attachments with the `.svg` extension.

The `<?xml ?>` declaration and DOCTYPE are automatically stripped.

```php
$icon = new Media($svg_attachment_id);

echo $icon->inline_svg();

// → <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">…</svg>
```

Example in a template:

```php
<button class="btn">
    <?= $icon->inline_svg() ?>
    Download
</button>
```

---

### alt()

Returns the alternative text defined in the WordPress media library.

```php
echo $media->alt();
// → "Team presentation photo"
```

---

### caption()

Returns the caption defined in the WordPress media library.

```php
echo $media->caption();
// → "Photo taken at the 2024 annual event"
```

---

## Full Example

```php
<?php
// In functions.php or a theme file

use Toolkit\models\custom\Media;

// From an ACF field (returns an ID)
$attachment_id = get_field('hero_image');

if ($attachment_id) :
    $media = new Media($attachment_id);
?>

<figure class="hero">
    <?= $media->picture([
        ['size' => 'image-s',  'media' => '(max-width: 640px)',  'size2x' => 'image-s-2x'],
        ['size' => 'image-m',  'media' => '(max-width: 1280px)', 'size2x' => 'image-m-2x'],
        ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
    ], 'hero__img') ?>

    <?php if ($media->caption()) : ?>
        <figcaption><?= $media->caption() ?></figcaption>
    <?php endif ?>
</figure>

<?php endif ?>
```

---

## Notes

- Image sizes must be registered via `Size::add()` in `functions.php`. The original image is returned if a size has not yet been generated (background processing via cron).
- `picture()` and `srcset()` silently omit sizes that have not yet been generated.
- `inline_svg()` reads the file directly from disk. Do not use with SVGs from untrusted sources.
