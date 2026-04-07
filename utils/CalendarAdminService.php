<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

class CalendarAdminService
{
    const OPTION_NAME = 'toolkit_calendar_settings';
    const MENU_SLUG = 'toolkit-calendar';

    public static function register()
    {
        add_action('admin_menu', [self::class, 'register_menu'], 10);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_toolkit_calendar_sync_now', [self::class, 'handle_sync_now']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function enqueue_scripts($hook)
    {
        if ('toolkit-calendar_page_toolkit-calendar-settings' !== $hook) {
            return;
        }

        $settings = get_option(self::OPTION_NAME, self::get_default_settings());
        $google = $settings['google'] ?? self::get_default_settings()['google'];
        if (!$google['enabled'] || empty($google['api_key']) || empty($google['calendar_id'])) {
            return;
        }

        wp_enqueue_script('toolkit-calendar-test', false, ['jquery'], false, true);
        wp_localize_script('toolkit-calendar-test', 'toolkitCalendarTest', [
            'apiKey'     => $google['api_key'],
            'calendarId' => $google['calendar_id'],
            'i18n'       => [
                'testing'         => __('Testing...', 'wp-theme-toolkit'),
                'connecting'      => __('Connecting to Google Calendar...', 'wp-theme-toolkit'),
                'successTitle'    => __('✓ Connection successful!', 'wp-theme-toolkit'),
                'calendarLabel'   => __('Calendar:', 'wp-theme-toolkit'),
                'eventsFoundLabel'=> __('Events found:', 'wp-theme-toolkit'),
                'unknownError'    => __('Unknown error', 'wp-theme-toolkit'),
                'failureTitle'    => __('✗ Connection failed', 'wp-theme-toolkit'),
                'testButton'      => __('Test Connection', 'wp-theme-toolkit'),
            ],
        ]);
        wp_add_inline_script('toolkit-calendar-test', '
            jQuery(document).ready(function($) {
                var cfg = toolkitCalendarTest;
                $("#test-google-connection").on("click", function() {
                    var button = $(this);
                    var result = $("#test-result");
                    button.prop("disabled", true).text(cfg.i18n.testing);
                    result.empty().append($("<p>").text(cfg.i18n.connecting));
                    var testUrl = "https://www.googleapis.com/calendar/v3/calendars/" +
                        encodeURIComponent(cfg.calendarId) +
                        "/events?key=" + cfg.apiKey +
                        "&maxResults=1&singleEvents=true&orderBy=startTime&" +
                        "timeMin=" + new Date().toISOString();
                    fetch(testUrl)
                        .then(function(response) {
                            if (!response.ok) { return response.json().then(function(err) { return Promise.reject(err); }); }
                            return response.json();
                        })
                        .then(function(data) {
                            var box = $("<div>").addClass("notice notice-success inline");
                            $("<p>").append($("<strong>").text(cfg.i18n.successTitle)).appendTo(box);
                            $("<p>").text(cfg.i18n.calendarLabel + " " + (data.summary || cfg.calendarId)).appendTo(box);
                            $("<p>").text(cfg.i18n.eventsFoundLabel + " " + (data.items ? data.items.length : 0)).appendTo(box);
                            result.empty().append(box);
                        })
                        .catch(function(error) {
                            var msg = error && error.error ? error.error.message : error && error.message ? error.message : cfg.i18n.unknownError;
                            var box = $("<div>").addClass("notice notice-error inline");
                            $("<p>").append($("<strong>").text(cfg.i18n.failureTitle)).appendTo(box);
                            $("<p>").text(msg).appendTo(box);
                            result.empty().append(box);
                        })
                        .finally(function() {
                            button.prop("disabled", false).html(
                                "<span class=\"dashicons dashicons-yes-alt\" style=\"margin-top:3px\"></span> " + cfg.i18n.testButton
                            );
                        });
                });
            });
        ');
    }

    /**
     * Register admin menu and submenus
     */
    public static function register_menu()
    {
        // Main menu page
        add_menu_page(
            __('Calendar', 'wp-theme-toolkit'),           // Page title
            __('Calendar', 'wp-theme-toolkit'),           // Menu title
            'manage_options',                      // Capability
            self::MENU_SLUG,                       // Menu slug
            [self::class, 'render_main_page'],    // Callback
            'dashicons-calendar-alt',              // Icon
            25                                     // Position
        );

        // Submenu: Paramètres (settings)
        add_submenu_page(
            self::MENU_SLUG,
            __('Calendar Settings', 'wp-theme-toolkit'),
            __('Settings', 'wp-theme-toolkit'),
            'manage_options',
            self::MENU_SLUG . '-settings',
            [self::class, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public static function register_settings()
    {
        register_setting(
            'toolkit_calendar_settings_group',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize_settings'],
                'default' => self::get_default_settings()
            ]
        );
    }

    /**
     * Get default settings
     */
    private static function get_default_settings()
    {
        return [
            'google' => [
                'enabled' => false,
                'api_key' => '',
                'calendar_id' => '',
                'sync_interval' => 'daily',
                'max_results' => 250,
                'time_min_offset' => '-30',  // days in the past
                'time_max_offset' => '365',  // days in the future
            ]
        ];
    }

    /**
     * Sanitize settings
     */
    public static function sanitize_settings($input)
    {
        $sanitized = [];

        if (isset($input['google'])) {
            $sanitized['google'] = [
                'enabled' => !empty($input['google']['enabled']),
                'api_key' => sanitize_text_field($input['google']['api_key'] ?? ''),
                'calendar_id' => sanitize_text_field($input['google']['calendar_id'] ?? ''),
                'sync_interval' => sanitize_text_field($input['google']['sync_interval'] ?? 'daily'),
                'max_results' => absint($input['google']['max_results'] ?? 250),
                'time_min_offset' => intval($input['google']['time_min_offset'] ?? -30),
                'time_max_offset' => intval($input['google']['time_max_offset'] ?? 365),
            ];
        }

        return $sanitized;
    }

    /**
     * Render main page (overview)
     */
    public static function render_main_page()
    {
        $settings = get_option(self::OPTION_NAME, self::get_default_settings());
        $google_enabled = $settings['google']['enabled'] ?? false;
        $last_sync = get_option('toolkit_calendar_last_sync');

        // Get stats
        $total_events = wp_count_posts('calendar_event');
        $published_events = $total_events->publish ?? 0;

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; margin-right: 10px;"></span>
                <?php _e('Calendar', 'wp-theme-toolkit'); ?>
            </h1>

            <?php settings_errors('toolkit_calendar_messages'); ?>

            <div class="toolkit-calendar-dashboard">
                
                <!-- Status Card -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Status', 'wp-theme-toolkit'); ?></h2>
                    <table class="widefat" style="border: none;">
                        <tr>
                            <td style="padding: 15px;">
                                <strong><?php _e('Google Calendar', 'wp-theme-toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($google_enabled): ?>
                                    <span style="color: #46b450; font-weight: bold;">
                                        ✓ <?php _e('Enabled', 'wp-theme-toolkit'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc3232; font-weight: bold;">
                                        ✗ <?php _e('Disabled', 'wp-theme-toolkit'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td style="padding: 15px;">
                                <strong><?php _e('Published Events', 'wp-theme-toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php echo esc_html($published_events); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 15px;">
                                <strong><?php _e('Last Sync', 'wp-theme-toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php 
                                if ($last_sync) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync));
                                } else {
                                    _e('Never', 'wp-theme-toolkit');
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Quick Actions', 'wp-theme-toolkit'); ?></h2>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-settings'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                            <?php _e('Configure Google Calendar', 'wp-theme-toolkit'); ?>
                        </a>
                        
                        <?php if ($google_enabled): ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('toolkit_calendar_sync_now', 'toolkit_calendar_sync_nonce'); ?>
                                <input type="hidden" name="action" value="toolkit_calendar_sync_now">
                                <button type="submit" class="button button-secondary">
                                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                    <?php _e('Sync Now', 'wp-theme-toolkit'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=calendar_event'); ?>" class="button">
                            <span class="dashicons dashicons-list-view" style="margin-top: 3px;"></span>
                            <?php _e('View All Events', 'wp-theme-toolkit'); ?>
                        </a>
                    </p>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public static function render_settings_page()
    {
        $settings = get_option(self::OPTION_NAME, self::get_default_settings());
        $google = $settings['google'] ?? self::get_default_settings()['google'];

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="font-size: 32px; margin-right: 10px;"></span>
                <?php _e('Calendar Settings', 'wp-theme-toolkit'); ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('toolkit_calendar_settings_group');
                ?>

                <!-- Google Calendar Settings -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Google Calendar Configuration', 'wp-theme-toolkit'); ?></h2>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="google_enabled">
                                    <?php _e('Enable Google Calendar', 'wp-theme-toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo self::OPTION_NAME; ?>[google][enabled]" 
                                        id="google_enabled"
                                        value="1"
                                        <?php checked($google['enabled'], true); ?>
                                    >
                                    <?php _e('Sync with Google Calendar', 'wp-theme-toolkit'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Enables automatic event synchronization from Google Calendar.', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_api_key">
                                    <?php _e('Google API Key', 'wp-theme-toolkit'); ?> <span style="color: red;">*</span>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo self::OPTION_NAME; ?>[google][api_key]" 
                                    id="google_api_key"
                                    value="<?php echo esc_attr($google['api_key']); ?>"
                                    class="regular-text"
                                    placeholder="AIzaSy..."
                                >
                                <p class="description">
                                    <?php _e('Your Google Calendar API key. Get a key at', 'wp-theme-toolkit'); ?> 
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                                        Google Cloud Console
                                    </a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_calendar_id">
                                    <?php _e('Calendar ID', 'wp-theme-toolkit'); ?> <span style="color: red;">*</span>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="<?php echo self::OPTION_NAME; ?>[google][calendar_id]" 
                                    id="google_calendar_id"
                                    value="<?php echo esc_attr($google['calendar_id']); ?>"
                                    class="regular-text"
                                    placeholder="events@fordif.ch"
                                >
                                <p class="description">
                                    <?php _e('Your Google Calendar ID (e.g. your-email@gmail.com or calendar-id@group.calendar.google.com)', 'wp-theme-toolkit'); ?>
                                    <br>
                                    <?php _e('Find it in Google Calendar → Settings → Integrate calendar', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_sync_interval">
                                    <?php _e('Sync Interval', 'wp-theme-toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <select 
                                    name="<?php echo self::OPTION_NAME; ?>[google][sync_interval]" 
                                    id="google_sync_interval"
                                >
                                    <option value="hourly" <?php selected($google['sync_interval'], 'hourly'); ?>>
                                        <?php _e('Every Hour', 'wp-theme-toolkit'); ?>
                                    </option>
                                    <option value="twicedaily" <?php selected($google['sync_interval'], 'twicedaily'); ?>>
                                        <?php _e('Twice Daily', 'wp-theme-toolkit'); ?>
                                    </option>
                                    <option value="daily" <?php selected($google['sync_interval'], 'daily'); ?>>
                                        <?php _e('Once Daily', 'wp-theme-toolkit'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($google['sync_interval'], 'weekly'); ?>>
                                        <?php _e('Once Weekly', 'wp-theme-toolkit'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Automatic event synchronization frequency.', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_max_results">
                                    <?php _e('Maximum Events', 'wp-theme-toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="number" 
                                    name="<?php echo self::OPTION_NAME; ?>[google][max_results]" 
                                    id="google_max_results"
                                    value="<?php echo esc_attr($google['max_results']); ?>"
                                    class="small-text"
                                    min="1"
                                    max="2500"
                                >
                                <p class="description">
                                    <?php _e('Maximum number of events to sync (max: 2500).', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_time_min_offset">
                                    <?php _e('Past Period (days)', 'wp-theme-toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="number" 
                                    name="<?php echo self::OPTION_NAME; ?>[google][time_min_offset]" 
                                    id="google_time_min_offset"
                                    value="<?php echo esc_attr($google['time_min_offset']); ?>"
                                    class="small-text"
                                >
                                <p class="description">
                                    <?php _e('Number of days in the past to sync (e.g. -30 for the last 30 days).', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_time_max_offset">
                                    <?php _e('Future Period (days)', 'wp-theme-toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="number" 
                                    name="<?php echo self::OPTION_NAME; ?>[google][time_max_offset]" 
                                    id="google_time_max_offset"
                                    value="<?php echo esc_attr($google['time_max_offset']); ?>"
                                    class="small-text"
                                    min="1"
                                >
                                <p class="description">
                                    <?php _e('Number of days in the future to sync (e.g. 365 for the next year).', 'wp-theme-toolkit'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'wp-theme-toolkit'), 'primary', 'submit', true); ?>
            </form>

            <!-- Test Connection -->
            <?php if ($google['enabled'] && !empty($google['api_key']) && !empty($google['calendar_id'])): ?>
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2><?php _e('Test Connection', 'wp-theme-toolkit'); ?></h2>
                <p>
                    <?php _e('Verify your settings are correct by testing the connection to Google Calendar.', 'wp-theme-toolkit'); ?>
                </p>
                <button type="button" class="button" id="test-google-connection">
                    <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span>
                    <?php _e('Test Connection', 'wp-theme-toolkit'); ?>
                </button>
                <div id="test-result" style="margin-top: 15px;"></div>
            </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle manual sync
     */
    public static function handle_sync_now()
    {
        // Verify nonce
        $sync_nonce = isset($_POST['toolkit_calendar_sync_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['toolkit_calendar_sync_nonce']))
            : '';

        if (!isset($_POST['toolkit_calendar_sync_nonce']) || 
            !wp_verify_nonce($sync_nonce, 'toolkit_calendar_sync_now')) {
            wp_die(__('Security check failed.', 'wp-theme-toolkit'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'wp-theme-toolkit'));
        }

        // Run sync
        if (class_exists('Toolkit\utils\CalendarService')) {
            $results = \Toolkit\utils\CalendarService::sync_all();
            
            if (!empty($results['google']['success'])) {
                $message = $results['google']['message'] ?? __('Sync successful', 'wp-theme-toolkit');
                add_settings_error(
                    'toolkit_calendar_messages',
                    'toolkit_calendar_sync_success',
                    $message,
                    'success'
                );
            } else {
                $error = $results['google']['message'] ?? __('Sync error', 'wp-theme-toolkit');
                add_settings_error(
                    'toolkit_calendar_messages',
                    'toolkit_calendar_sync_error',
                    $error,
                    'error'
                );
            }
        }

        set_transient('settings_errors', get_settings_errors(), 30);

        wp_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&settings-updated=true'));
        exit;
    }
}
