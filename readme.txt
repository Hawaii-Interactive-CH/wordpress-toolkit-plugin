=== WP Theme Toolkit ===
Contributors: hawaiiinteractive
Tags: toolkit, theme, custom post type, gutenberg, acf, blocks, developer
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A developer toolkit for building WordPress themes — base models, ACF block registration, custom post types, taxonomies, REST API helpers, and utility services.

== Description ==

WP Theme Toolkit gives developers a structured foundation for building robust, maintainable WordPress themes. Rather than reinventing common patterns on every project, WP Theme Toolkit provides a consistent architecture out of the box.

**Features:**

* Abstract base models for posts, pages, media, galleries, and custom post types
* ACF-powered block and option page registration
* Custom post type and taxonomy scaffolding
* REST API routing with token-based authentication
* Utility services: asset loading (Vite), menus, navigation, PDF generation, image resizing (WebP), cookie consent
* Google Calendar integration
* WooCommerce optional support
* Maintenance mode view
* Gravity Forms integration helpers
* WPML compatibility helpers

This plugin is designed to work alongside a custom theme, not replace it. It is intended for developers building bespoke WordPress themes.

== Installation ==

1. Upload the `wp-theme-toolkit` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugin screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. The toolkit registers its services automatically on `init`. Configure your theme to extend the base models and register your blocks, CPTs, and taxonomies.

== Frequently Asked Questions ==

= Does this plugin work without ACF? =

Some features (block registration, option pages) require Advanced Custom Fields (ACF) or ACF Pro. The plugin will not throw fatal errors if ACF is absent, but those features will be unavailable.

= Does this plugin work without a custom theme? =

WP Theme Toolkit is designed to complement a custom theme. It will activate on any theme but most features are only useful when your theme extends the provided base models.

= Is WooCommerce support enabled by default? =

No. WooCommerce support is disabled by default and can be enabled via the plugin configuration file.

== External Services ==

This plugin may connect to the following external services depending on your configuration:

* **Google Calendar API** — used by `GoogleCalendarSource` to fetch calendar events. This connection is only made when the Google Calendar integration is configured and active. See [Google's Privacy Policy](https://policies.google.com/privacy).
* **Highlight.js (cdnjs.cloudflare.com)** — used in the admin Docs page to syntax-highlight code blocks. Loaded from `https://cdnjs.cloudflare.com` only when the Docs admin page is visited. See [Cloudflare's Privacy Policy](https://www.cloudflare.com/privacypolicy/).

No data is sent to any external service by default. External connections are opt-in and initiated by your theme code.

== Third-Party Libraries ==

This plugin includes the following third-party libraries:

* **Parsedown** and **ParsedownExtra** — Markdown parser by Emanuil Rusev. Licensed under the MIT License. [Source](https://github.com/erusev/parsedown).

== Screenshots ==

1. No admin UI screenshots currently available. This is a developer toolkit plugin.

== Changelog ==

= 3.0.0 =
* Security: Encryption key is now stored in wp_options — no longer writes to wp-config.php
* Security: Fixed bug where get_encryption_key() returned a boolean instead of the actual key value
* Security: All inline &lt;script&gt; and &lt;style&gt; tags replaced with wp_add_inline_script() and wp_add_inline_style()
* Security: Nonce protection added across admin form submissions
* Security: Improved data sanitization and output escaping throughout
* Security: Removed third-party update checker
* Feat: Block model — new ACF helpers: acf_media(), acf_file(), acf_post(), acf_page(), acf_publication()
* Compatibility: Full WordPress coding standards compliance across all models, controllers, and services
* Compatibility: Removed unused vendor/jb-fly library
* Compatibility: Added if(!defined()) guards for all plugin constants
* Compatibility: Plugin name and text domain unified to wp-theme-toolkit
* Compatibility: Disclosed Highlight.js external CDN usage in readme.txt
* Docs: English translation of internal documentation and file names
* Docs: GPL license file added
* Docs: Code conventions documented

= 2.1.5 =
* Security: Added nonce protection
* Security: Improved data sanitization

= 2.1.4 =
* Feat: Add `add_columns()` helper to CustomPostType for admin list columns with sorting support
* Feat: Add `remove_columns()` helper to CustomPostType to hide admin list columns
* Feat: Add `MainService::customize_login()` to customize wp-login.php logo, background and button color

= 2.1.3 =
* Fix: Better WebP conversion

= 2.0.0 =
* Feat: Add Google Calendar integration
* Feat: Convert PNG to WebP using WP cron

= 1.9.2 =
* Fix: Remove duplicate cookie register by default

= 1.9.1 =
* Fix: Correct asset paths for fonts

= 1.9.0 =
* Feat: Rewrite assets loading to use Vite

= 1.8.4 =
* Fix: Block CSS

= 1.8.3 =
* Fix: Typo in import

= 1.8.2 =
* Fix: Correctly load block themes in editor

= 1.8.1 =
* Fix: Exclude SVG, AVIF, HEIC, HEIF from image resize

= 1.8.0 =
* Feat: Add support for wp-i18n in JS scripts

= 1.7.3 =
* Fix: CPT creation

= 1.7.2 =
* Fix: Asset URL path for block.css

= 1.7.1 =
* Fix: Add blocks.css to display custom block styles in admin

= 1.7.0 =
* Feat: Add category support for media files

= 1.6.6 =
* Fix: Remove default custom CPT, keep only those defined in the theme

= 1.6.5 =
* Fix: Typo

= 1.6.3 =
* Fix: SVG size processing

= 1.6.0 =
* Feat: Add WebP support for image upload
* Feat: Add menu service to manage menus programmatically

= 1.5.1 =
* Fix: Get template

= 1.5.0 =
* Feat: Add REST API authentication

= 1.4.1 =
* Fix: Gravity Forms notifications builder

= 1.4.0 =
* Feat: Allow max upload size update from admin

= 1.3.5 =
* Update: Gravity Forms fields — added select, radio, checkbox format

= 1.3.0 =
* Update: Cookie consent banner message

= 1.2.10 =
* Fix: Missing class import check and return value of jsonSerialize

= 1.2.0 =
* Feat: Add cookie consent banner

= 1.1.0 =
* Feat: Event model update with default ACF fields

= 1.0.6 =
* Feat: Add local documentation generated from local markdown files
* Fix: Better local Vite detection, avoid using Vite in production and staging environments

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 3.0.0 =
Major release. Full WordPress coding standards compliance, security overhaul (encryption key no longer modifies wp-config.php), and new ACF helpers for the Block model. Review your theme's Block subclasses after upgrading.

= 2.1.5 =
Security improvements: nonce protection and data sanitization added. Update recommended for all users.
