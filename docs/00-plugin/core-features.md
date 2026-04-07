# Plugin Usage

This plugin loads the core features of a WordPress theme.

- [x] Maintenance mode activation
- [x] Custom post type (CPT) creation and activation/deactivation
- [x] Gutenberg block creation
- [x] WordPress menu management (hidden in the admin menu)
- [ ] Cookie banner (TODO)
- [ ] More default CPTs (e.g. FAQ, Team, ...) (TODO)

## Available Constants

```php
// Absolute path to the plugin
define( 'WP_TOOLKIT_DIR', plugin_dir_path(__FILE__) );

// Plugin URL
define( 'WP_TOOLKIT_URL', plugin_dir_url(__FILE__) );

// Absolute path to the theme
define( 'WP_TOOLKIT_THEME_PATH', get_template_directory() );

// Theme URL
define( 'WP_TOOLKIT_THEME_URL', get_template_directory_uri() );

// Absolute path to the theme's view files
define( 'WP_TOOLKIT_THEME_VIEWS_PATH', get_template_directory() . '/templates' );
```

## Customizing the Login Page — `MainService::customize_login()`

Allows replacing the logo, background color, and button color on the `/wp-login.php` page.

### Options

| Key | Type | Description |
|---|---|---|
| `logo` | `string` | URL of the logo image (replaces the WordPress logo) |
| `background` | `string` | CSS background color (e.g. `'#f5f5f5'`) |
| `button_color` | `string` | CSS color for the Submit button |
| `button_hover` | `string` | *(optional)* CSS hover color — defaults to `button_color` |

### Example

```php
use Toolkit\utils\MainService;

MainService::customize_login([
    'logo'         => get_template_directory_uri() . '/assets/images/logo.svg',
    'background'   => '#f5f5f5',
    'button_color' => '#e63946',
    'button_hover' => '#c1121f',
]);
```

Call this from the theme's `functions.php` file or inside an `init` hook.

---

## Intelephense (VSCode)

For Intelephense to work correctly, you need to declare your username in the theme's `autoload.js` file.

This script loads the theme's classes and functions into Intelephense.
