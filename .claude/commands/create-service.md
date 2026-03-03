---
description: 'Generate a static Service class in utils/ following the Toolkit service pattern'
---

# Create Service

Generate a new Service class in the **current working directory** (i.e. the theme or project where the skill is invoked — not the Toolkit plugin itself).

## Instructions

Ask the user for the following information if not already provided in the arguments (`$ARGUMENTS`):

1. **Service name** (PascalCase without the `Service` suffix, e.g. `Seo`, `Analytics`, `Newsletter`) — the file will be named `{Name}Service.php`
2. **Purpose** — one sentence describing what the service does (used as the class docblock)
3. **WordPress hooks** — ask which hooks the service needs. For each hook, collect:
   - Hook type: `add_action` or `add_filter`
   - Hook name (e.g. `init`, `wp_head`, `the_content`, `save_post`)
   - Method name to create (e.g. `register_scripts`, `inject_meta`, `modify_content`)
   - Priority (default: `10`)
4. **Additional methods** — any other static methods the user wants scaffolded (name + one-line description each)

## Output

Generate the file at: `utils/{Name}Service.php` **relative to the current working directory** (the directory from which this skill was invoked, not the Toolkit plugin directory).

Use exactly this template:

```php
<?php

namespace Toolkit\utils;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

/**
 * {Purpose}
 *
 * @class {Name}Service
 */
class {Name}Service
{
    /**
     * Register hooks for this service.
     */
    public static function register()
    {
        {hooks}
    }

    {methods}
}
```

**Hook format** — each hook becomes one line in `register()`:
```php
add_action('{hook_name}', [self::class, '{method_name}'], {priority});
// or
add_filter('{hook_name}', [self::class, '{method_name}'], {priority});
```

**Method scaffold** — generate a static method stub for each registered hook and each additional method:
```php
public static function {method_name}()
{
    // TODO: implement
}
```

For filter methods, add the appropriate parameter:
```php
public static function {method_name}($value)
{
    // TODO: implement
    return $value;
}
```

## After generating the file

Remind the user to call `register()` in their theme bootstrap (e.g. `functions.php`):

```php
\Toolkit\utils\{Name}Service::register();
```

Or inside an existing service's `register()` method if it logically belongs there.
