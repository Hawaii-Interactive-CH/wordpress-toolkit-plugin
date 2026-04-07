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
- **CalendarAdminService** — Admin UI for Google Calendar settings, manual sync trigger, and connection testing.
- **ApiAuthService** — AES-256-CBC master token + transient token authentication, IP whitelist management, admin UI, and hourly cron cleanup of expired tokens.
- **CookieService** — Configurable cookie consent banner with accept/refuse actions and localStorage persistence.
- **DocService** — In-admin Markdown documentation viewer with table of contents and syntax highlighting.
- **PDFService** — External PDF generation link builder.
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

## Technical Documentation

### WordPress Requirements

Requires at least: 6.4
Requires PHP: 8.0

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
