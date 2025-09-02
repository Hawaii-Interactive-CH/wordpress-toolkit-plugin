<?php

namespace Toolkit\utils;

defined('ABSPATH') or exit;

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
        'css' => [],
        'js' => [],
        'icons' => []
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
    private static $viteDevServer = 'http://localhost:5173';
    
    /**
     * Vite manifest data
     * 
     * @var array|null
     */
    private static $viteManifest = null;
    
    /**
     * Register the asset service
     */
    public static function register()
    {
        // Hook into WordPress asset enqueuing
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets'], 20);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets'], 20);
        add_action('wp_head', [AssetService::class, 'output_vite_assets'], 5);
        add_action("enqueue_block_editor_assets", [self::class, 'enqueue_block_editor_assets']);
        
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
    public static function css($handle, $src = null, $deps = [], $ver = null, $media = 'all') {
        // Auto-detect source if not provided
        if ($src === null) {
            $src = self::auto_detect_css_path($handle);
        }
        
        // Convert relative path to full URL
        if ($src && !self::is_url($src)) {
            $src = self::get_asset_url($src);
        }
        
        self::$assets['css'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?: WP_TOOLKIT_VERSION,
            'media' => $media
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
    public static function js($handle, $src = null, $deps = [], $ver = null, $in_footer = true) {
        // Auto-detect source if not provided
        if ($src === null) {
            $src = self::auto_detect_js_path($handle);
        }
        
        // Convert relative path to full URL
        if ($src && !self::is_url($src)) {
            $src = self::get_asset_url($src);
        }
        
        self::$assets['js'][$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver ?: WP_TOOLKIT_VERSION,
            'in_footer' => $in_footer
        ];
    }
    
    /**
     * Register icon set
     * 
     * @param string $name Icon set name
     * @param string $url Icon set URL
     */
    public static function register_icon_set(string $name, string $url) {
        self::$assets['icons'][$name] = $url;
    }
    
    /**
     * Get asset URL
     * 
     * @param string $path Asset path
     * @return string Asset URL
     */
    public static function url($path) {
        if (self::is_url($path)) {
            return $path;
        }
        
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        return WP_TOOLKIT_THEME_URL . '/public/' . $path;
    }
    
    /**
     * Enqueue all registered assets
     */
    public static function enqueue_assets(): void {
        if (!self::$autoEnqueue || self::$assetsEnqueued) {
            return;
        }
        
        // Enqueue CSS files
        foreach (self::$assets['css'] as $handle => $asset) {
            if ($asset['src']) {
                wp_enqueue_style($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['media']);
            }
        }
        
        // Enqueue JavaScript files
        foreach (self::$assets['js'] as $handle => $asset) {
            if ($asset['src']) {
                wp_enqueue_script($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['in_footer']);
            }
        }
        
        self::$assetsEnqueued = true;
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets(): void {
        // Always enqueue admin-specific assets
        wp_enqueue_style(
            "toolkit-admin-css",
            WP_TOOLKIT_URL . "/admin/assets/css/toolkit-admin.css",
            [],
            WP_TOOLKIT_VERSION
        );
        wp_enqueue_style(
            "toolkit-icomoon-style",
            WP_TOOLKIT_URL . "/admin/assets/css/toolkit-icomoon.css",
            [],
            WP_TOOLKIT_VERSION
        );
        wp_enqueue_style(
            "toolkit-md-style",
            WP_TOOLKIT_URL . "/admin/assets/css/toolkit-md.css",
            [],
            WP_TOOLKIT_VERSION
        );
        
        // Enqueue regular assets in admin too
        self::enqueue_assets();
    }
    
    /**
     * Enqueue block editor assets
     */
    public static function enqueue_block_editor_assets() {
        if (file_exists(WP_TOOLKIT_THEME_PATH . "/public/css/blocks.css")) {
            wp_enqueue_style(
                "custom-block-styles",
                WP_TOOLKIT_THEME_URL . "/public/css/blocks.css",
                ["wp-edit-blocks"],
                filemtime(WP_TOOLKIT_THEME_PATH . "/public/css/blocks.css")
            );
        }
    }
    
    /**
     * Output Vite assets
     */
    public static function output_vite_assets()
    {
        // In development mode
        if (self::is_dev_mode()) {
            self::output_vite_dev();
        }
        // In production mode
        elseif (self::$viteManifest) {
            self::output_vite_prod();
        }
    }
    
    /**
     * Auto-detect CSS file path
     * 
     * @param string $handle
     * @return string|null
     */
    private static function auto_detect_css_path($handle) {
        $possiblePaths = [
            "public/css/{$handle}.css",
            "public/css/{$handle}.min.css",
            "assets/css/{$handle}.css",
            "assets/css/{$handle}.min.css"
        ];
        
        foreach ($possiblePaths as $path) {
            $fullPath = WP_TOOLKIT_THEME_PATH . '/' . $path;
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
    private static function auto_detect_js_path($handle) {
        $possiblePaths = [
            "public/js/{$handle}.js",
            "public/js/{$handle}.min.js",
            "assets/js/{$handle}.js",
            "assets/js/{$handle}.min.js"
        ];
        
        foreach ($possiblePaths as $path) {
            $fullPath = WP_TOOLKIT_THEME_PATH . '/' . $path;
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
    private static function get_asset_url($path) {
        if (self::is_url($path)) {
            return $path;
        }
        
        return WP_TOOLKIT_THEME_URL . '/' . ltrim($path, '/');
    }
    
    /**
     * Check if string is a URL
     * 
     * @param string $str
     * @return bool
     */
    private static function is_url($str) {
        return filter_var($str, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Disable auto-enqueue
     */
    public static function disable_auto_enqueue() {
        self::$autoEnqueue = false;
    }
    
    /**
     * Enable auto-enqueue
     */
    public static function enable_auto_enqueue() {
        self::$autoEnqueue = true;
    }
    
    /**
     * Load Vite manifest file
     */
    public static function load_vite_manifest() {
        $manifestPaths = [
            WP_TOOLKIT_THEME_PATH . '/public/.vite/manifest.json',
            WP_TOOLKIT_THEME_PATH . '/public/manifest.json'
        ];
        
        foreach ($manifestPaths as $manifestPath) {
            if (file_exists($manifestPath)) {
                self::$viteManifest = json_decode(file_get_contents($manifestPath), true);
                break;
            }
        }
    }
    
    /**
     * Output Vite dev server assets
     */
    private static function output_vite_dev()
    {
        ?>
        <!-- Toolkit Vite Dev Server -->
        <script type="module">
            import RefreshRuntime from '<?php echo self::$viteDevServer; ?>/@react-refresh';
            RefreshRuntime.injectIntoGlobalHook(window);
            window.$RefreshReg$ = () => {};
            window.$RefreshSig$ = () => (type) => type;
            window.__vite_plugin_react_preamble_installed__ = true;
        </script>
        <script type="module" src="<?php echo self::$viteDevServer; ?>/@vite/client"></script>
        <script type="module" src="<?php echo self::$viteDevServer; ?>/src/javascript/app.js"></script>
        <!-- End Toolkit Vite Dev Server -->
        <?php
    }
    
    /**
     * Output Vite production assets
     */
    private static function output_vite_prod()
    {
        if (!isset(self::$viteManifest['src/javascript/app.js'])) {
            return;
        }
        
        $entry = self::$viteManifest['src/javascript/app.js'];
        $baseUrl = WP_TOOLKIT_THEME_URL . '/public/';
        
        // Enqueue CSS if exists
        if (isset($entry['css'])) {
            foreach ($entry['css'] as $cssFile) {
                echo '<link rel="stylesheet" href="' . esc_url($baseUrl . $cssFile) . '">' . "\n";
            }
        }
        
        // Add configuration for JavaScript
        echo '<script>window.toolkitConfig = ' . json_encode([
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('toolkit-nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
        ]) . ';</script>' . "\n";
        
        // Enqueue JS
        if (isset($entry['file'])) {
            echo '<script type="module" src="' . esc_url($baseUrl . $entry['file']) . '"></script>' . "\n";
        }
    }
    
    /**
     * Check if in development mode
     */
    private static function is_dev_mode() {
        // Never use dev mode if we're in WP-CLI context
        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }
        
        // Check if debug is enabled AND dev server is accessible
        $debugEnabled = (defined('WP_DEBUG') && WP_DEBUG) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
        
        if ($debugEnabled) {
            // Check if Vite dev server is actually running
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $response = @file_get_contents(self::$viteDevServer . '/@vite/client', false, $context);
            return $response !== false;
        }
        
        return false;
    }
    
    /**
     * Set Vite dev server URL
     */
    public static function set_vite_dev_server($url) {
        self::$viteDevServer = rtrim($url, '/');
    }
}