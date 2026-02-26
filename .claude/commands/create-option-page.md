---
description: 'Generate an ACF Option Page class extending Toolkit\models\OptionPage'
---

# Create Option Page

Generate a new ACF Option Page class for the WordPress Toolkit plugin.

## Instructions

Ask the user for the following information if not already provided in the arguments (`$ARGUMENTS`):

1. **Class name** (PascalCase, e.g. `SiteOptions`, `ThemeSettings`, `FooterOptions`)
2. **ID** (unique string slug, lowercase with hyphens, used as ACF post_id and menu slug, e.g. `site-options`, `theme-settings`) — default to kebab-case of class name
3. **Page title** (shown as the `<h1>` of the page, e.g. "Site Options", "Theme Settings")
4. **Menu title** (shown in the WordPress admin menu, e.g. "Options", "Settings") — default to page title
5. **Parent menu slug** (where to attach in the admin menu, e.g. `options-general.php`, `themes.php`, or a custom CPT menu slug) — default to `options-general.php`. If the user wants a top-level menu page, use `null`.
6. **Capability** (WordPress capability required to access the page, e.g. `manage_options`, `edit_posts`) — default to `manage_options`
7. **Position** (menu position integer, only relevant for top-level pages) — omit if sub-page
8. **ACF fields** — ask the user to list the field keys they plan to add (e.g. `phone`, `email`, `logo`). These will be scaffolded as static accessor methods.

## Output

Generate the file at: `models/custom/{ClassName}.php`

Use exactly this template:

```php
<?php

namespace Toolkit\models\custom;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\OptionPage;

class {ClassName} extends OptionPage
{
    const ID = '{id}';

    const PARAMS = [
        'page_title'  => '{Page Title}',
        'menu_title'  => '{Menu Title}',
        'parent_slug' => '{parent_slug}',
        'capability'  => '{capability}',
        'redirect'    => false,
    ];

    {accessor_methods}
}
```

**Notes on `PARAMS`:**
- If the user chose a top-level menu page, replace `'parent_slug'` with `'menu_slug'` and set its value to `'acf-' . self::ID`, and add `'position' => {position}` if provided
- `'redirect' => false` prevents ACF from redirecting to the first sub-page when there are none

**Accessor methods** — for each ACF field key provided, scaffold a typed static method:

```php
public static function {field_key}()
{
    return self::acf('{field_key}');
}
```

If no fields were provided, add a commented example:

```php
// Example: public static function phone() { return self::acf('phone'); }
```

## After generating the file

Remind the user to:

1. Register the option page in their theme (e.g. `functions.php`):

```php
add_action('acf/init', function () {
    \Toolkit\models\custom\{ClassName}::register();
});
```

2. Create the corresponding ACF field group in the WordPress admin and assign it to the option page with the rule: **Options Page** → **is equal to** → `{Page Title}`

3. Access field values anywhere in the theme via the static accessors:

```php
\Toolkit\models\custom\{ClassName}::phone();
// or
\Toolkit\models\custom\{ClassName}::acf('phone');
```
