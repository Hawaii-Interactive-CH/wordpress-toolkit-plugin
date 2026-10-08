<?php

namespace Toolkit\utils;

defined("ABSPATH") or exit();

/**
 * Toolkit Asset Service
 *
 * Handles CSS, JavaScript, and Vite assets for the toolkit
 * Based on AssetManager.php pattern from wordpress-ui
 */
class AssetService
{
    /**
     * Registered assets
     *
     * @var array
     */
    private static $assets = [
        "css" => [],
        "js" => [],
        "icons" => [],
    ];

    /**
     * Auto-enqueue enabled
     *
     * @var bool
     */
    private static $autoEnqueue = true;

    /**
     * Assets enqueued flag
     *
     * @var bool
     */
    private static $assetsEnqueued = false;

    /**
     * Vite dev server URL
     *
     * @var string
     */
    private static $viteDevServer = "http://localhost:5173";

    /**
     * Vite manifest data
     *
     * @var array|null
     */
    private static $viteManifest = null;

    /**
     * Dev mode cache
     *
     * @var bool|null
     */
    private static $isDevMode = null;

    /**
     * Register the asset service
     */
    public static function register()
    {
        // Hook into WordPress asset enqueuing
        add_action("wp_enqueue_scripts", [self::class, "enqueue_assets"], 20);
        add_action(
            "admin_enqueue_scripts",
            [self::class, "enqueue_admin_assets"],
            20,
        );
        add_action("wp_enqueue_scripts", [self::class, "enqueue_vite_assets"], 5);
        add_action("wp_head", [self::class, "output_vite_dev"], 5);
        add_filter("wp_preload_resources", [self::class, "preload_vite_fonts"]);
        add_action("enqueue_block_editor_assets", [
            self::class,
            "enqueue_block_editor_assets",
        ]);

        // Load Vite manifest
        self::load_vite_manifest();
    }

    /**
     * Register CSS file
     *
     * @param string $handle Handle name
     * @param string|null $src Source path
     * @param array $deps Dependencies
     * @param string|bool|null $ver Version
     * @param string $media Media type
     */
    public static function css(
        $handle,
        $src = null,
        $deps = [],
        $ver = null,
        $media = "all",
    ) {
        // Auto-detect source if not provided
        if ($src === null) {
            $src = self::auto_detect_css_path($handle);
        }

        // Convert relative path to full URL
        if ($src && !self::is_url($src)) {
            $src = self::get_asset_url($src);
        }

        self::$assets["css"][$handle] = [
            "src" => $src,
            "deps" => $deps,
            "ver" => $ver ?: HITHTO_VERSION,
            "media" => $media,
        ];
    }

    /**
     * Register JavaScript file
     *
     * @param string $handle Handle name
     * @param string|null $src Source path
     * @param array $deps Dependencies
     * @param string|bool|null $ver Version
     * @param bool $in_footer Load in footer
     */
    public static function js(
        $handle,
        $src = null,
        $deps = [],
        $ver = null,
        $in_footer = true,
    ) {
        // Auto-detect source if not provided
        if ($src === null) {
            $src = self::auto_detect_js_path($handle);
        }

        // Convert relative path to full URL
        if ($src && !self::is_url($src)) {
            $src = self::get_asset_url($src);
        }

        self::$assets["js"][$handle] = [
            "src" => $src,
            "deps" => $deps,
            "ver" => $ver ?: HITHTO_VERSION,
            "in_footer" => $in_footer,
        ];
    }

    /**
     * Register icon set
     *
     * @param string $name Icon set name
     * @param string $url Icon set URL
     */
    public static function register_icon_set(string $name, string $url)
    {
        self::$assets["icons"][$name] = $url;
    }

    /**
     * Get asset URL
     *
     * @param string $path Asset path
     * @return string Asset URL
     */
    public static function url($path)
    {
        if (self::is_url($path)) {
            return $path;
        }

        // Remove leading slash if present
        $path = ltrim($path, "/");

        return HITHTO_THEME_URL . "/public/" . $path;
    }

    /**
     * Enqueue all registered assets
     */
    public static function enqueue_assets(): void
    {
        if (!self::$autoEnqueue || self::$assetsEnqueued) {
            return;
        }

        // Enqueue CSS files
        foreach (self::$assets["css"] as $handle => $asset) {
            if ($asset["src"]) {
                wp_enqueue_style(
                    $handle,
                    $asset["src"],
                    $asset["deps"],
                    $asset["ver"],
                    $asset["media"],
                );
            }
        }

        // Enqueue JavaScript files
        foreach (self::$assets["js"] as $handle => $asset) {
            if ($asset["src"]) {
                wp_enqueue_script(
                    $handle,
                    $asset["src"],
                    $asset["deps"],
                    $asset["ver"],
                    $asset["in_footer"],
                );
            }
        }

        self::$assetsEnqueued = true;
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_admin_assets($hook = ''): void
    {
        // Always enqueue admin-specific assets
        wp_enqueue_style(
            "toolkit-admin-css",
            HITHTO_URL . "/admin/assets/css/toolkit-admin.css",
            [],
            HITHTO_VERSION,
        );
        wp_enqueue_style(
            "toolkit-icomoon-style",
            HITHTO_URL . "/admin/assets/css/toolkit-icomoon.css",
            [],
            HITHTO_VERSION,
        );

        /**
         * Theme assets registered with AssetService::css()/js() are front-end only.
         * A theme can opt in to load them on some admin pages:
         *
         *   add_filter( 'hithto_enqueue_theme_assets_in_admin', fn( $load, $hook ) => 'post.php' === $hook, 10, 2 );
         *
         * @param bool   $load Whether to load the theme assets on this admin page. Default false.
         * @param string $hook Current admin page hook.
         */
        if (apply_filters('hithto_enqueue_theme_assets_in_admin', false, $hook)) {
            self::enqueue_assets();
        }
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets()
    {
        if (file_exists(HITHTO_THEME_PATH . "/public/css/blocks.css")) {
            wp_enqueue_style(
                "hithto-block-styles",
                HITHTO_THEME_URL . "/public/css/blocks.css",
                ["wp-edit-blocks"],
                filemtime(HITHTO_THEME_PATH . "/public/css/blocks.css"),
            );
        }
    }

    /**
     * Enqueue Vite production assets (dev server assets are printed by output_vite_dev)
     */
    public static function enqueue_vite_assets()
    {
        if (self::is_dev_mode() || !isset(self::$viteManifest["src/javascript/app.js"])) {
            return;
        }

        $entry = self::$viteManifest["src/javascript/app.js"];
        $baseUrl = HITHTO_THEME_URL . "/public/";

        // CSS (file names are hashed by Vite, no version needed)
        foreach ($entry["css"] ?? [] as $index => $cssFile) {
            wp_enqueue_style("hithto-vite-app-" . $index, $baseUrl . $cssFile, [], null);
        }

        // Configuration for JavaScript, printed in <head> before the app module
        wp_register_script("hithto-toolkit-config", false, [], HITHTO_VERSION, false);
        wp_enqueue_script("hithto-toolkit-config");
        wp_add_inline_script(
            "hithto-toolkit-config",
            "window.toolkitConfig = " .
                wp_json_encode([
                    "ajaxUrl" => admin_url("admin-ajax.php"),
                    "debug" => defined("WP_DEBUG") && WP_DEBUG,
                ]) .
                ";",
        );

        // App entry, loaded as <script type="module">
        if (isset($entry["file"])) {
            wp_enqueue_script_module("hithto-vite-app", $baseUrl . $entry["file"], [], null);
        }
    }

    /**
     * Preload Vite font assets
     *
     * @param array $resources Resources to preload
     * @return array
     */
    public static function preload_vite_fonts($resources)
    {
        if (is_admin() || self::is_dev_mode() || !isset(self::$viteManifest["src/javascript/app.js"])) {
            return $resources;
        }

        $baseUrl = HITHTO_THEME_URL . "/public/";

        foreach (self::$viteManifest["src/javascript/app.js"]["assets"] ?? [] as $assetFile) {
            if (preg_match('/\.(woff|woff2|ttf|otf|eot)$/', $assetFile)) {
                $resources[] = [
                    "href" => $baseUrl . $assetFile,
                    "as" => "font",
                    "type" => "font/" . pathinfo($assetFile, PATHINFO_EXTENSION),
                    "crossorigin" => "anonymous",
                ];
            }
        }

        return $resources;
    }

    /**
     * Auto-detect CSS file path
     *
     * @param string $handle
     * @return string|null
     */
    private static function auto_detect_css_path($handle)
    {
        $possiblePaths = [
            "public/css/{$handle}.css",
            "public/css/{$handle}.min.css",
            "assets/css/{$handle}.css",
            "assets/css/{$handle}.min.css",
        ];

        foreach ($possiblePaths as $path) {
            $fullPath = HITHTO_THEME_PATH . "/" . $path;
            if (file_exists($fullPath)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Auto-detect JavaScript file path
     *
     * @param string $handle
     * @return string|null
     */
    private static function auto_detect_js_path($handle)
    {
        $possiblePaths = [
            "public/js/{$handle}.js",
            "public/js/{$handle}.min.js",
            "assets/js/{$handle}.js",
            "assets/js/{$handle}.min.js",
        ];

        foreach ($possiblePaths as $path) {
            $fullPath = HITHTO_THEME_PATH . "/" . $path;
            if (file_exists($fullPath)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get asset URL from path
     *
     * @param string $path
     * @return string
     */
    private static function get_asset_url($path)
    {
        if (self::is_url($path)) {
            return $path;
        }

        return HITHTO_THEME_URL . "/" . ltrim($path, "/");
    }

    /**
     * Check if string is a URL
     *
     * @param string $str
     * @return bool
     */
    private static function is_url($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Disable auto-enqueue
     */
    public static function disable_auto_enqueue()
    {
        self::$autoEnqueue = false;
    }

    /**
     * Enable auto-enqueue
     */
    public static function enable_auto_enqueue()
    {
        self::$autoEnqueue = true;
    }

    /**
     * Load Vite manifest file
     */
    public static function load_vite_manifest()
    {
        $manifestPaths = [
            HITHTO_THEME_PATH . "/public/.vite/manifest.json",
            HITHTO_THEME_PATH . "/public/manifest.json",
        ];

        foreach ($manifestPaths as $manifestPath) {
            if (file_exists($manifestPath)) {
                self::$viteManifest = json_decode(
                    file_get_contents($manifestPath),
                    true,
                );
                break;
            }
        }
    }

    /**
     * Output Vite dev server assets (local development only)
     *
     * The React refresh preamble must run before the Vite client and the entry,
     * so the tags are printed in order in <head> with the WordPress tag helpers.
     */
    public static function output_vite_dev()
    {
        if (!self::is_dev_mode()) {
            return;
        }

        $vite_dev_server = untrailingslashit(self::$viteDevServer);
        $refresh_runtime = $vite_dev_server . "/@react-refresh";

        wp_print_inline_script_tag(
            "import RefreshRuntime from " . wp_json_encode($refresh_runtime) . ";\n" .
            "RefreshRuntime.injectIntoGlobalHook(window);\n" .
            "window.\$RefreshReg\$ = () => {};\n" .
            "window.\$RefreshSig\$ = () => (type) => type;\n" .
            "window.__vite_plugin_react_preamble_installed__ = true;",
            ["type" => "module"],
        );
        wp_print_script_tag([
            "type" => "module",
            "src" => esc_url($vite_dev_server . "/@vite/client"),
        ]);
        wp_print_script_tag([
            "type" => "module",
            "src" => esc_url($vite_dev_server . "/src/javascript/app.js"),
        ]);
    }

    /**
     * Check if in development mode (assets served by the Vite dev server)
     *
     * @return bool
     */
    public static function is_dev_mode()
    {
        // Return cached result if available
        if (self::$isDevMode !== null) {
            return self::$isDevMode;
        }

        // Never use dev mode in WP-CLI context
        if (defined("WP_CLI") && \WP_CLI) {
            return self::$isDevMode = false;
        }

        // Only check dev server in a local environment or when debug mode is enabled
        $is_local = defined("WP_ENVIRONMENT_TYPE") && WP_ENVIRONMENT_TYPE === "local";
        $is_debug = defined("WP_DEBUG") && WP_DEBUG;
        if (!$is_local && !$is_debug) {
            return self::$isDevMode = false;
        }

        // Check if Vite dev server is running
        $context = stream_context_create(["http" => ["timeout" => 1]]);
        $response = @file_get_contents(
            self::$viteDevServer . "/@vite/client",
            false,
            $context,
        );

        return self::$isDevMode = $response !== false;
    }

    /**
     * Set Vite dev server URL
     */
    public static function set_vite_dev_server($url)
    {
        self::$viteDevServer = rtrim($url, "/");
    }
}
