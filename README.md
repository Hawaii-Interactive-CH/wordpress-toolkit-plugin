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

- **MainService** — Coming-soon mode, cookie consent banner, SVG upload support, emoji removal, comment system toggle, WordPress version hiding, login page customization, custom admin branding.
- **AssetService** — CSS/JS registration and enqueuing with Vite dev server and production manifest support.
- **ModelService** — Auto-scans `/models/custom/` and registers enabled post types via an admin toggle UI.
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

Menu locations are declared by the theme with the WordPress API (the plugin does not create menus nor change menu assignments):

```php
add_action( 'after_setup_theme', function () {
    register_nav_menus( [
        'primary'   => __( 'Primary Navigation', 'your-theme' ),
        'footer'    => __( 'Footer Navigation', 'your-theme' ),
        'languages' => __( 'Language Switcher', 'your-theme' ),
    ] );
} );
```

## Technical Documentation

### WordPress Requirements

Requires at least: 6.8
Requires PHP: 8.0

### Getting Started

To start a new theme using this toolkit, use the official boilerplate:

**[wordpress-toolkit-boilerplate](https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-boilerplate)** — A ready-to-use theme starter that integrates this plugin out of the box.

### Installation

The plugin is available on the WordPress.org Plugin Directory as [HI Theme Toolkit](https://wordpress.org/plugins/hi-theme-toolkit/).

- **WordPress admin**: go to **Plugins → Add New**, search for "HI Theme Toolkit", then install and activate it.
- **WP-CLI**:

```bash
wp plugin install hi-theme-toolkit --activate
```

- **Composer** (via [WPackagist](https://wpackagist.org)):

```bash
composer require wpackagist-plugin/hi-theme-toolkit
```

Updates are then delivered through the standard WordPress update system.


### Publishing a new version

The plugin is distributed through the WordPress.org Plugin Directory (`hi-theme-toolkit`). Sites using it receive updates from WordPress.org, so a release is only available once it has been committed to SVN.

#### Prerequisites

- Subversion installed: `brew install subversion`
- Commit access to `https://plugins.svn.wordpress.org/hi-theme-toolkit`
- An SVN password, generated from your WordPress.org profile (**Edit Profile → Account & Security**). Your WordPress.org account password does not work for SVN.

#### 1. Bump the version

The version must be identical in three places, otherwise the deploy script stops:

```php
// wordpress-toolkit-plugin.php
 * Version: 3.x.x
if ( ! defined( 'HITHTO_VERSION' ) ) define( 'HITHTO_VERSION', '3.x.x' );
```

```text
// readme.txt
Stable tag: 3.x.x
```

Follow semantic versioning: patch (`3.0.1`) for fixes, minor (`3.1.0`) for new backward-compatible features, major (`4.0.0`) for breaking changes.

#### 2. Update `readme.txt`

- Add an entry at the top of `== Changelog ==`:

```text
= 3.x.x =
* Fix: ...
* Feat: ...
```

- Optionally add an entry to `== Upgrade Notice ==`. It is displayed in wp-admin next to the update button, so use it for breaking changes or security fixes.
- Update `Tested up to:` if you tested against a newer WordPress version.

#### 3. Commit and tag on GitHub

```bash
git add .
git commit -m "RELEASE : 3.x.x"
git tag v3.x.x
git push origin main --tags
```

#### 4. Deploy to WordPress.org

```bash
npm run deploy-svn -- <wordpress-org-username>
```

The script (`bin/deploy-svn.sh`):

1. Checks that the version, `HITHTO_VERSION` and `Stable tag` match, and that the tag does not already exist in SVN.
2. Checks out or updates the SVN working copy in `../hi-theme-toolkit-svn` (override with `SVN_DIR=/path`).
3. Syncs the plugin into `trunk/`, excluding development files (`.git`, `bin`, `docs`, `screenshots`, `package.json`, ...).
4. Copies `screenshots/screenshotNN.png`, `banner-*` and `icon-*` into `assets/`.
5. Creates `tags/3.x.x` from `trunk/`.
6. Shows `svn status` and asks for confirmation before running `svn commit`.

Answering anything other than `y` leaves the changes in the local SVN working copy without publishing them.

#### 5. Verify

- After a few minutes (up to ~15), check that https://wordpress.org/plugins/hi-theme-toolkit/ shows the new version.
- Sites check for updates about every 12 hours. To force it, use **Dashboard → Updates → Check again**, or run `wp plugin update hi-theme-toolkit`.

#### Rules

- Never modify a published tag. Fix it in a new version instead.
- To roll back, set `Stable tag` back to the previous version and commit. Sites that already updated are not downgraded, so publish a fix version quickly.
- **Readme-only changes** (for example `Tested up to`) do not need a new version. Edit `readme.txt` in both `trunk/` and the current `tags/3.x.x/` in the SVN working copy, then run `svn commit`.
- `register_activation_hook` does not run on updates. If a release needs a data migration, compare a stored version option with `HITHTO_VERSION` on `plugins_loaded`.

#### Assets

Banner and icon sources are in `screenshots/banner.svg` and `screenshots/icon.svg`. Regenerate the PNG files with:

```bash
cd screenshots
rsvg-convert -w 256 -h 256 icon.svg -o icon-256x256.png
rsvg-convert -w 128 -h 128 icon.svg -o icon-128x128.png
rsvg-convert -w 772 -h 250 banner.svg -o banner-772x250.png
rsvg-convert -w 1544 -h 500 banner.svg -o banner-1544x500.png
```

Screenshot captions come from the `== Screenshots ==` section of `readme.txt`, in the same order as the numbered files.

## Documentation

Documentation is available in the WordPress admin under the `Toolkit` > `Docs` menu.

To update it, simply edit the markdown files in the plugin's `docs` folder and update the table of contents.

## Contributing

Contributions are welcome! Fork the project, make your changes, and open a pull request. Make sure to follow the code conventions and update the documentation if necessary.
