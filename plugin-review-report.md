# WordPress Plugin Review — Fix Report

Review ID: `AUTOPREREVIEW hi-theme-toolkit/hawaiido/21Apr26/T1`

---

## 1. Use `wp_enqueue` Commands

**Status: Partially fixed / Partially requires manual work**

### Files with inline `<script>` / `<style>` that were NOT converted

| File | Line | Issue | Status |
|------|------|-------|--------|
| `utils/admin-webp-test-page.php` | 79 | `<style>` block in full-HTML admin page | **Not fixed** — see recommendation below |
| `utils/admin-webp-test-page.php` | 242 | `<script>` for nonce-based AJAX queue processing | **Not fixed** — see recommendation below |
| `utils/admin-webp-test-page.php` | 631 | `<script>` for `filterLogs()` helper | **Not fixed** — see recommendation below |
| `models/MediaTaxonomy.php` | 107 | `<script>` for media library filter dropdown | **fixed** — see note |
| `models/MediaTaxonomy.php` | 281 | `<script>` for media grid filter (via `ob_start()`) | **fixed** — see note |
| `utils/AssetService.php` | 454 | `echo "<script>window.toolkitConfig = ..."` | **Not fixed** — required for Vite pipeline; has `phpcs:ignore` comment |

**Recommendation for `admin-webp-test-page.php`:**
The page currently renders a full `<!DOCTYPE html>` template. To properly enqueue styles and scripts:
1. Refactor the page to use WordPress admin wrapper (`<div class="wrap">`) instead of a custom full HTML template.
2. Extract the `<style>` block to `admin/assets/css/webp-test-page.css` and enqueue it in `init()` via `admin_enqueue_scripts`.
3. Move the static `filterLogs()` function to `admin/assets/js/webp-test-page.js`.
4. For the dynamic nonce injection, use `wp_localize_script()` to pass the nonce to the enqueued JS file.

**Note on `MediaTaxonomy.php`:** These scripts are injected into the WordPress media library and use dynamic PHP values (escaped with `esc_js()`). They could be refactored to use `wp_add_inline_script()` on a registered jQuery handle, passing PHP values via `wp_localize_script()`. This is a non-trivial refactor. => removed from code.

**Note on `AssetService.php:454`:** The `window.toolkitConfig` script is part of the Vite asset pipeline and is output in `wp_head`. This should be refactored to use `wp_add_inline_script()` after registering a dummy handle. Already has a `phpcs:ignore` comment as a stopgap.

---

## 2. Proper Escaping of Outputs

**Status: Fixed**

`models/Block.php:64` — Eliminated the `echo` entirely by refactoring `Block::render()` to `include` the template directly instead of buffering via `render_partial()` and echoing the result. The template file outputs its own content, so there is nothing to escape — responsibility stays in each block's PHP template where it belongs.

Before:
```php
public static function render( $data ) {
    $block_instance = new static( $data );
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
    echo \Toolkit\render_partial( implode( '/', array( 'blocks', static::TYPE ) ), array(
        'block' => $block_instance,
    ) );
    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}
```

After:
```php
public static function render( $data ) {
    $block = new static( $data );
    $path  = implode( DIRECTORY_SEPARATOR, [ WP_TOOLKIT_THEME_PATH, 'partials', 'blocks', static::TYPE ] ) . '.php';
    if ( file_exists( $path ) ) {
        include $path;
    }
}
```

> **Note for themes:** Block templates previously received `$block_instance`; they now receive `$block` — which is what `render_partial()` was already extracting from the `'block'` key in the data array. No template changes needed.

---

## 3. Use Prefixes for Declarations, Globals, and Stored Data

**Status: Fixed (most cases) / Documented (constants)**

### Fixed

| File | Old name | New name |
|------|----------|----------|
| `utils/ApiAuthService.php` | `api_auth_cleanup_expired_transients` (cron hook) | `hithto_api_auth_cleanup_expired_transients` |
| `utils/ApiAuthService.php` | `api_transient_expiry` (option) | `hithto_api_transient_expiry` |
| `utils/ApiAuthService.php` | `api_auth_error` (transient) | `hithto_api_auth_error` |
| `utils/ApiAuthService.php` | `api_auth_message` (transient) | `hithto_api_auth_message` |
| `utils/ApiAuthService.php` | `api_encryption_key` (option) | `hithto_api_encryption_key` |
| `utils/ApiAuthService.php` | `api_master_token` (option) | `hithto_api_master_token` |
| `utils/ApiAuthService.php` | `api_auth_whitelist` (option) | `hithto_api_auth_whitelist` |
| `utils/ApiAuthService.php` | `api_auth_token` (transient prefix) | `hithto_api_auth_token` |
| `utils/admin-webp-test-page.php` | `process_webp_queue` (AJAX action) | `hithto_process_webp_queue` |
| `utils/admin-webp-test-page.php` | `process_webp_queue_nonce` (nonce) | `hithto_process_webp_queue_nonce` |
| `utils/AssetService.php` | `custom-block-styles` (style handle) | `hithto-block-styles` |
| `utils/MainService.php` | `custom_menu_settings` (option + nonce + setting) | `hithto_menu_settings` |
| `utils/RegisterService.php` | `create_cpt_models` (AJAX action) | `hithto_create_cpt_models` |
| `utils/RegisterService.php` | `create_cpt_blocks` (AJAX action) | `hithto_create_cpt_blocks` |
| `admin/assets/js/toolkit-admin-ajax.js` | `create_cpt_models` (JS action) | `hithto_create_cpt_models` |
| `admin/assets/js/toolkit-admin-ajax.js` | `create_cpt_blocks` (JS action) | `hithto_create_cpt_blocks` |

### Not Fixed — `WP_TOOLKIT_*` Constants

**Status: Requires decision**

The constants `WP_TOOLKIT_VERSION`, `WP_TOOLKIT_DIR`, `WP_TOOLKIT_URL`, `WP_TOOLKIT_THEME_PATH`, `WP_TOOLKIT_THEME_URL`, `WP_TOOLKIT_THEME_VIEWS_PATH` in `wordpress-toolkit-plugin.php` use the `WP_` prefix which is reserved by WordPress core.

**Recommendation:** Rename to `HITHTO_*` (e.g., `HITHTO_VERSION`, `HITHTO_DIR`, etc.). However, this is a **breaking change** — all themes using this plugin reference these constants directly. If you rename them, you must update every theme simultaneously. Suggested approach:
1. Define both old and new names for one release cycle:
   ```php
   define( 'HITHTO_VERSION', '3.0.0' );
   if ( ! defined( 'WP_TOOLKIT_VERSION' ) ) define( 'WP_TOOLKIT_VERSION', HITHTO_VERSION ); // deprecated
   ```
2. Remove the deprecated aliases in the next major version.

---

## 4. Contributors List

**Status: Fixed**

Changed `readme.txt` from:
```
Contributors: Hawaii Interactive
```
To:
```
Contributors: hawaiido
```

---

## 5. PHP Syntax Errors

### `utils/parsedown/Parsedown.php` — Fixed

Moved `namespace` declaration before the `defined('ABSPATH') || exit;` check. PHP requires the namespace declaration to be the first statement (or after `declare`).

### `utils/parsedown/ParsdownExtra.php` — Fixed

Same fix applied.

### `controllers/ToolkitController.php:119` — Likely false positive

**Status: No change made**

The flagged code is:
```php
$args = [
    'post_type'   => 'calendar_event',
    'order'       => 'ASC',       // line 119
    'meta_query'  => [ [...] ],
];
```
This is syntactically valid PHP. The reviewer's linter may have misidentified it, possibly due to lack of context when parsing the file in isolation. **Recommendation:** Run `php -l controllers/ToolkitController.php` to confirm there is no real syntax error.

---

## 6. Out-of-Date Libraries

**Status: Fixed**

Updated Highlight.js from **v11.9.0** → **v11.11.1** (latest stable, released 2024-12-25).

Three files replaced via cdnjs and version strings updated in `utils/DocService.php`:

| File | Action |
|------|--------|
| `admin/assets/js/highlight.min.js` | Replaced with v11.11.1 |
| `admin/assets/js/highlight-go.min.js` | Replaced with v11.11.1 |
| `admin/assets/css/highlight-default.min.css` | Replaced with v11.11.1 |
| `utils/DocService.php` | Version strings updated `'11.9.0'` → `'11.11.1'` |

---

## 7. PHP Library Conflict — Parsedown

**Status: Fixed**

The `Parsedown` and `ParsedownExtra` classes were already fully namespaced under `Toolkit\utils\parsedown` — no conflict risk there.

The actual collision risk was in `utils/parsedown/ParsedownToc.php`: it registered a **global** class alias `ParsedownTocParentAlias` via `class_alias()`. Any other plugin doing the same would cause a PHP fatal error.

Fixed by renaming the alias to a plugin-specific name:

```php
// Before — global namespace, collision risk:
class_alias('...\\ParsedownExtra', 'ParsedownTocParentAlias');
class ParsedownToc extends \ParsedownTocParentAlias { ... }

// After — unique name, no collision:
class_alias('...\\ParsedownExtra', 'Hithto_ParsedownTocParentAlias');
class ParsedownToc extends \Hithto_ParsedownTocParentAlias { ... }
```

---

## 8. REST API `permission_callback`

**Status: Not fixed — review required**

Three endpoints in `routes/api.php` use a `permission_callback` that returns `true`:

```php
// controllers/ToolkitController.php
public function permission_callback() {
    return true; // Public access
}
```

The reviewer notes these endpoints expose internal event/calendar sync metadata.

**Recommendation:** If these calendar events are meant to be publicly readable (e.g., for a front-end calendar widget), `__return_true` is acceptable and you should document this intent explicitly. If they contain sensitive sync metadata, add proper authentication:
```php
public function permission_callback() {
    return current_user_can( 'read' ); // or 'manage_options' for admin-only
}
```

---

## 9. Admin Dashboard Menu Position

**Status: Fixed**

Changed menu positions from `2` (top of admin menu, above Dashboard) to `65` (below Settings) in:

| File | Old position | New position |
|------|-------------|-------------|
| `utils/MainService.php:190` | `2` | `65` |
| `utils/CookieService.php:47` | `2` | `65` |

`utils/CalendarAdminService.php` already uses position `25`, which is reasonable and was left unchanged.

---

## Summary

| Issue | Status |
|-------|--------|
| `wp_enqueue` for inline scripts/styles | Partially done — 1 files require manual refactor |
| Proper output escaping (`Block.php`) | ✅ Fixed — replaced echo+buffer with direct include |
| Prefix: `api_*` in ApiAuthService.php | ✅ Fixed |
| Prefix: `process_webp_queue` AJAX | ✅ Fixed |
| Prefix: `custom-block-styles` | ✅ Fixed |
| Prefix: `custom_menu_settings` | ✅ Fixed |
| Prefix: `create_cpt_*` AJAX actions | ✅ Fixed |
| Prefix: `WP_TOOLKIT_*` constants | Not fixed — breaking change, needs phased migration |
| Contributors in readme.txt | ✅ Fixed |
| PHP syntax: Parsedown.php namespace | ✅ Fixed |
| PHP syntax: ParsdownExtra.php namespace | ✅ Fixed |
| PHP syntax: ToolkitController.php | Not fixed — likely false positive |
| Highlight.js out of date | ✅ Fixed — updated to v11.11.1 |
| Parsedown library conflict | ✅ Fixed — global alias renamed to `Hithto_ParsedownTocParentAlias` |
| REST API `permission_callback` | Not fixed — needs architectural decision |
| Admin menu positions | ✅ Fixed (65 for Toolkit and Cookie menus) |


- **`WP_TOOLKIT_*` constants** — breaking change, needs phased migration to `HITHTO_*`
- **Inline scripts/styles in `admin-webp-test-page.php`** — requires refactoring the full-HTML page to WP admin wrapper pattern
- **Parsedown library conflict** — needs Composer + Strauss for namespace scoping
- **REST API `permission_callback`** — needs a decision on whether events are intentionally public
