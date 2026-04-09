# Wordpress Toolkit Plugin

## Description

WordPress Toolkit is a professional development framework for building complex WordPress sites. It provides a structured, object-oriented layer on top of WordPress with abstract model classes, a fluent query builder, ACF block and option page support, REST API endpoints, and a suite of utility services.

### Models & Content

- **PostType** — Base class for all post models. Provides fluent access to post data (title, content, excerpt, dates, author, thumbnail, terms), ACF field resolution (`acf()`, `acf_media()`, `acf_file()`, `acf_post()`, `acf_page()`), and pagination-aware static queries (`all()`, `current()`, `query()`).
- **CustomPostType** — Extends PostType with CPT registration, admin column management, and type settings.
- **Taxonomy** — Base class for custom taxonomies with `all()`, `find_by_id()`, `find_by_slug()`, and ACF support.
- **Block** — Base class for ACF blocks. Handles registration, template rendering, and ACF field access.
- **OptionPage** — Base class for ACF options pages with field accessors and WPML support.
- **QueryBuilder** — Chainable query builder: `where()`, `search()`, `order()`, `paginate()`, `add_meta_query()`, `add_tax_query()`, `after()`, `before()`, `find_all()`, `find_one()`.
- **AbstractMedia** — Image model with WebP output, `srcset()`, `picture()`, inline SVG support.
- **MediaTaxonomy** — Taxonomy for media library categorization with admin filter UI.
- **CalendarEvent** — Custom post type for calendar events with Google Calendar sync metadata.

### Services

- **MainService** — Maintenance mode, cookie consent banner, SVG upload support, emoji removal, comment system toggle, WordPress version hiding, login page customization, custom admin branding.
- **AssetService** — CSS/JS registration and enqueuing with Vite dev server and production manifest support.
- **ModelService** — Auto-scans `/models/custom/` and registers enabled post types via an admin toggle UI.
- **RegisterService** — Admin UI to scaffold new CPT model files and ACF block files with AJAX generation.
- **MenuService** — Register and auto-create default navigation menu locations.
- **CalendarService** — Google Calendar synchronization: fetch, create, update, and clean up events on a configurable cron schedule.
- **CalendarAdminService** — Calendar admin UI with four areas: (1) a **dashboard** showing Google Calendar and WordPress Events sync status, published event count, last sync time, and quick-action buttons; (2) a **Google Calendar settings** page (enable/disable, API key, Calendar ID, sync interval hourly/twice-daily/daily/weekly, max events up to 2500, past and future date-range offsets in days); (3) a **live connection test** that calls the Google Calendar API and reports the calendar name and event count; (4) a **WordPress Events** page to map any public CPT with an ACF date field — including date fields nested inside repeaters — to `calendar_event` posts. All pages include a nonce-protected manual sync trigger.
- **ApiAuthService** — AES-256-CBC master token + transient token authentication, IP whitelist management, admin UI, and hourly cron cleanup of expired tokens.
- **CookieService** — Configurable cookie consent banner with accept/refuse actions and localStorage persistence.
- **DocService** — In-admin Markdown documentation viewer with table of contents and syntax highlighting.
- **Size** — WebP image conversion and on-the-fly resizing with cron-based queue, adaptive quality, and fallback for SVG/AVIF/HEIC.
- **GravityForm** — Gravity Forms REST API wrapper: list forms, create, update, and format fields and notifications programmatically.
- **ShareLinks** — Social sharing URLs for Facebook, Twitter, and LinkedIn.
- **WPML** — Language list, post ID translation, and current language helpers.
- **Slugify** — String-to-slug conversion with currency symbol support and iconv transliteration.
- **Navigation** — Menu location resolver with item walker helper.
- **Icon** — Icon constants for admin UI selection.

### REST API

- `POST /wp-json/api/v1/auth` — Generate a transient token from a master token.
- `GET /wp-json/toolkit/v1/events` — List calendar events with date range and pagination.
- `GET /wp-json/toolkit/v1/events/upcoming` — Get the next N upcoming events.
- `GET /wp-json/toolkit/v1/events/{id}` — Get a single event.

### Security Model (Nonces and Auth)

- **Admin forms and admin AJAX** use WordPress nonces and must verify requests server-side (`wp_nonce_field()` / `check_admin_referer()` or `check_ajax_referer()`), plus capability checks (`current_user_can()`).
- **REST routes** in this plugin are not protected by WordPress nonce by default:
  - `/api/v1/auth` uses master token + IP whitelist checks.
  - `/toolkit/v1/events*` routes are intentionally public.
- If a REST endpoint is later intended for authenticated wp-admin users (cookie auth), use standard REST nonce flow with `wp_create_nonce('wp_rest')` and `X-WP-Nonce`.

### Integrations

- **ACF** (Advanced Custom Fields) — Block registration, option pages, field resolution throughout all models.
- **Google Calendar API** — Full event sync with deduplication, all-day event support, and configurable date range.
- **Gravity Forms** — Programmatic form creation and management via REST API.
- **WPML** — Multilingual post ID resolution and language switching.
- **WooCommerce** — Optional theme support flag.
- **Vite** — Frontend asset pipeline with HMR in development and hashed manifests in production.
- **MainWP** — Auto-updates disabled for compatibility.

## Code Examples

### Custom Post Type

```php
// models/custom/Project.php
namespace Toolkit\models\custom;

use Toolkit\models\CustomPostType;

class Project extends CustomPostType implements \JsonSerializable {
    const TYPE = 'project';

    public static function type_settings(): array {
        return [
            'labels'    => [
                'name'          => __( 'Projects', 'your-theme' ),
                'singular_name' => __( 'Project', 'your-theme' ),
            ],
            'supports'  => [ 'title', 'editor', 'thumbnail' ],
            'menu_icon' => 'dashicons-portfolio',
            'has_archive' => true,
        ];
    }

    public function client(): string {
        return $this->acf( 'client' ) ?? '';
    }

    public function cover( callable $callback ) {
        return $this->acf_media( 'cover', $callback );
    }

    public function jsonSerialize(): mixed {
        return [
            'id'     => $this->id(),
            'title'  => $this->title(),
            'slug'   => $this->slug(),
            'client' => $this->client(),
            'link'   => $this->link(),
        ];
    }
}
```

Register it in your theme's `functions.php`:

```php
add_action( 'init', function () {
    \Toolkit\models\custom\Project::register();
} );
```

---

### Querying Posts

```php
// All projects
$projects = Project::all();

// Current post (inside The Loop or a single template)
$project = Project::current();

// Chainable QueryBuilder
$results = Project::query()
    ->where( 'client', 'Acme Corp' )
    ->order( 'date', 'DESC' )
    ->paginate( 12 )
    ->find_all();

// Single item
$project = Project::query()->find_by_id( 42 );

// Pagination HTML (uses WP's paginate_links)
echo Project::query()->paginate( 12 )->pagination();
```

---

### ACF Fields & Media

```php
// In a template
$project = Project::current();

echo $project->title();
echo $project->acf( 'tagline' );

// Render cover image at a registered size
$project->cover( function ( $media ) {
    echo $media->src( 'project-cover' );
    // Or a responsive <picture> element
    echo $media->picture( [
        '(max-width: 768px)' => 'project-cover-sm',
        '(max-width: 1280px)' => 'project-cover-md',
    ], 'project-cover' );
} );

// Resolve a related post ACF field
$project->acf_post( 'related_case_study', function ( $post ) {
    echo $post->title();
    echo $post->link();
} );
```

---

### Custom Taxonomy

```php
// models/custom/ProjectCategory.php
namespace Toolkit\models\custom;

use Toolkit\models\Taxonomy;

class ProjectCategory extends Taxonomy {
    const TYPE = 'project_category';

    public static function type_settings(): array {
        return [
            'label'        => __( 'Categories', 'your-theme' ),
            'hierarchical' => true,
            'post_types'   => [ Project::TYPE ],
        ];
    }
}
```

```php
// Get all categories
$categories = ProjectCategory::all();

// Get categories for the current post
$project->terms( ProjectCategory::TYPE, function ( $terms ) {
    foreach ( $terms as $term ) {
        echo $term->title();
    }
} );
```

---

### ACF Block

```php
// models/blocks/HeroBlock.php
namespace Toolkit\models\blocks;

use Toolkit\models\Block;

class HeroBlock extends Block {
    const TYPE = 'hero';

    public static function settings(): array {
        return [
            'title'       => __( 'Hero', 'your-theme' ),
            'description' => __( 'Full-width hero section.', 'your-theme' ),
            'icon'        => 'cover-image',
            'keywords'    => [ 'hero', 'banner' ],
        ];
    }

    public function heading(): string {
        return $this->acf( 'heading' ) ?? '';
    }

    public function background( callable $callback ) {
        return $this->acf_media( 'background', $callback );
    }
}
```

```php
// partials/blocks/hero.php
/** @var \Toolkit\models\blocks\HeroBlock $block */
?>
<section class="hero">
    <h1><?php echo esc_html( $block->heading() ); ?></h1>
    <?php $block->background( fn( $img ) => print $img->picture( [
        '(max-width: 768px)' => 'hero-sm',
    ], 'hero-lg' ) ); ?>
</section>
```

Register in `functions.php`:

```php
add_action( 'acf/init', function () {
    \Toolkit\models\blocks\HeroBlock::register();
} );
```

---

### ACF Option Page

```php
// models/options/ThemeOptions.php
namespace Toolkit\models\options;

use Toolkit\models\OptionPage;

class ThemeOptions extends OptionPage {
    const SLUG = 'theme-options';

    public static function settings(): array {
        return [
            'page_title' => __( 'Theme Options', 'your-theme' ),
            'menu_title' => __( 'Theme Options', 'your-theme' ),
            'capability' => 'manage_options',
        ];
    }

    public static function phone(): string {
        return static::acf( 'contact_phone' ) ?? '';
    }

    public static function logo( callable $callback ) {
        $id = static::acf( 'logo' );
        if ( $id ) {
            $callback( new \Toolkit\models\Media( $id ) );
        }
    }
}
```

```php
// In a template
echo ThemeOptions::phone();
ThemeOptions::logo( fn( $img ) => print $img->src( 'logo' ) );
```

---

### Image Sizes (WebP)

Register sizes in your theme's `functions.php`. The toolkit will automatically generate WebP versions via a background cron queue on upload.

```php
use Toolkit\utils\Size;

add_action( 'init', function () {
    Size::add( 'hero-lg',     1920, 1080, true );
    Size::add( 'hero-sm',      768,  432, true );
    Size::add( 'project-cover', 800, 600, true );
    Size::add( 'thumb',         400, 300, true );
} );
```

Use in templates:

```php
// URL of the WebP image (falls back to original if not yet converted)
echo Size::src( $attachment_id, 'hero-lg' )[0];
```

---

### Assets (Vite)

```php
use Toolkit\utils\AssetService;

// Register a Vite-managed bundle
AssetService::register( 'theme', get_template_directory_uri() . '/dist' );

// Conditionally enqueue on the front end
add_action( 'wp_enqueue_scripts', function () {
    AssetService::enqueue( 'theme', 'main' ); // loads main.css + main.js
} );
```

---

### Navigation Menus

```php
use Toolkit\utils\MenuService;

// Declare menu locations and create them if they don't exist
MenuService::register( [
    'primary'   => __( 'Primary Navigation', 'your-theme' ),
    'footer'    => __( 'Footer Navigation', 'your-theme' ),
    'languages' => __( 'Language Switcher', 'your-theme' ),
] );
```

## Technical Documentation

### WordPress Requirements

Requires at least: 6.4
Requires PHP: 8.0

### Getting Started

To start a new theme using this toolkit, use the official boilerplate:

**[wordpress-toolkit-boilerplate](https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-boilerplate)** — A ready-to-use theme starter that integrates this plugin out of the box.

### Installation

Download the [wordpress-toolkit-plugin](https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-plugin) as a zip and install it via the WordPress admin.


### Publishing a new version

1. Update the version number in `wordpress-toolkit-plugin.php` (header and constant):

```php
 * Version: 2.x.x
define("WP_TOOLKIT_VERSION", "2.x.x");
```

2. Commit and push the changes:

```bash
git add wordpress-toolkit-plugin.php
git commit -m "update version to 2.x.x"
git push origin main
```

3. Create a tag and a GitHub Release:

```bash
git tag v2.x.x
git push origin v2.x.x
```

Then on GitHub, go to **Releases → Draft a new release**, select the tag `v2.x.x` and publish.

The plugin will automatically detect the new release on sites using it and prompt for an update.

## Documentation

Documentation is available in the WordPress admin under the `Toolkit` > `Docs` menu.

To update it, simply edit the markdown files in the plugin's `docs` folder and update the table of contents.

## Claude Code Skills

This project includes [Claude Code](https://claude.ai/claude-code) skills to speed up development. Skills are slash commands (`/skill-name`) that guide Claude in generating code following the project's conventions.

### Available skills

| Command | Description |
|---|---|
| `/create-cpt` | Generates a `CustomPostType` class with `JsonSerializable` and optionally a category taxonomy |
| `/create-taxonomy` | Generates a standalone `Taxonomy` class with `register()` and `jsonSerialize()` |
| `/create-block` | Generates an ACF `Block` class and its PHP template in `partials/blocks/` |
| `/create-service` | Generates a static service in `utils/` with `register()` and WordPress hooks |
| `/create-option-page` | Generates an ACF `OptionPage` class with static field accessors |

### Usage

In Claude Code, invoke the command directly:

```
/create-cpt
```

Claude will ask the necessary questions (class name, slug, labels, icon, supports, etc.) and generate the files **in the current directory** (the theme or project from which the command is invoked).

You can also pass arguments directly:

```
/create-cpt Product "Products" "Product"
```

### Installing skills globally

A script is provided to copy all skills into `~/.claude/commands/toolkit/`, making them available in all your projects via `/toolkit:create-cpt`, `/toolkit:create-block`, etc.

```bash
bash bin/install-skills.sh
```

Re-run this script after each plugin update to sync the skills.

## Contributing

Contributions are welcome! Fork the project, make your changes, and open a pull request. Make sure to follow the code conventions and update the documentation if necessary.
