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
    }

    /**
     * Register admin menu and submenus
     */
    public static function register_menu()
    {
        // Main menu page
        add_menu_page(
            __('Calendrier', 'toolkit'),           // Page title
            __('Calendrier', 'toolkit'),           // Menu title
            'manage_options',                      // Capability
            self::MENU_SLUG,                       // Menu slug
            [self::class, 'render_main_page'],    // Callback
            'dashicons-calendar-alt',              // Icon
            25                                     // Position
        );

        // Submenu: Paramètres (settings)
        add_submenu_page(
            self::MENU_SLUG,
            __('Paramètres de Google Calendar', 'toolkit'),
            __('Lier Google Calendar', 'toolkit'),
            'manage_options',
            self::MENU_SLUG . '-google-calendar',
            [self::class, 'render_settings_page']
        );

        // Submenu: Configuration Événements WordPress
        add_submenu_page(
            self::MENU_SLUG,
            __('Configuration Événements WordPress', 'toolkit'),
            __('Lier Événements WordPress', 'toolkit'),
            'manage_options',
            self::MENU_SLUG . '-wordpress-events',
            [self::class, 'render_wordpress_events_page']
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
            ],
            'wordpress_events' => [
                'enabled' => false,
                'custom_post_type' => 'calendar_event',
                'acf_field_group' => '',
            ]
        ];
    }

    /**
     * Sanitize settings
     */
    public static function sanitize_settings($input)
    {
        // Get existing settings to preserve values not in the current form
        $existing = get_option(self::OPTION_NAME, self::get_default_settings());
        $sanitized = $existing;

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

        if (isset($input['wordpress_events'])) {
            $sanitized['wordpress_events'] = [
                'enabled' => !empty($input['wordpress_events']['enabled']),
                'custom_post_type' => sanitize_text_field($input['wordpress_events']['custom_post_type'] ?? 'calendar_event'),
                'acf_field_group' => sanitize_text_field($input['wordpress_events']['acf_field_group'] ?? ''),
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
        $wp_events_enabled = $settings['wordpress_events']['enabled'] ?? false;
        $wp_events_cpt = $settings['wordpress_events']['custom_post_type'] ?? '';
        $last_sync = get_option('toolkit_calendar_last_sync');

        // Get stats
        $total_events = wp_count_posts('calendar_event');
        $published_events = $total_events->publish ?? 0;

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; margin-right: 10px;"></span>
                <?php _e('Calendrier', 'toolkit'); ?>
            </h1>

            <?php settings_errors('toolkit_calendar_messages'); ?>

            <div class="toolkit-calendar-dashboard">
                
                <!-- Status Card -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Statut', 'toolkit'); ?></h2>
                    <table class="widefat" style="border: none;">
                        <tr>
                            <td style="padding: 15px;">
                                <strong><?php _e('Google Calendar', 'toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($google_enabled): ?>
                                    <span style="color: #46b450; font-weight: bold;">
                                        ✓ <?php _e('Activé', 'toolkit'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc3232; font-weight: bold;">
                                        ✗ <?php _e('Désactivé', 'toolkit'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td style="padding: 15px;">
                                <strong><?php _e('Liaison Événements WordPress', 'toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($wp_events_enabled && !empty($wp_events_cpt)): ?>
                                    <span style="color: #46b450; font-weight: bold;">
                                        ✓ <?php _e('Activé', 'toolkit'); ?>
                                    </span>
                                    <span style="color: #666; font-size: 0.9em;">
                                        (<?php echo esc_html($wp_events_cpt); ?>)
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc3232; font-weight: bold;">
                                        ✗ <?php _e('Désactivé', 'toolkit'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 15px;">
                                <strong><?php _e('Événements publiés', 'toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php echo esc_html($published_events); ?>
                            </td>
                        </tr>
                        <tr style="background: #f9f9f9;">
                            <td style="padding: 15px;">
                                <strong><?php _e('Dernière synchronisation', 'toolkit'); ?>:</strong>
                            </td>
                            <td style="padding: 15px;">
                                <?php 
                                if ($last_sync) {
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync));
                                } else {
                                    _e('Jamais', 'toolkit');
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Actions rapides', 'toolkit'); ?></h2>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-wordpress-events'); ?>" class="button">
                            <span class="dashicons dashicons-calendar" style="margin-top: 3px;"></span>
                            <?php _e('Configurer liaison événements WordPress', 'toolkit'); ?>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=' . self::MENU_SLUG . '-google-calendar'); ?>" class="button">
                            <span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
                            <?php _e('Configurer Google Calendar', 'toolkit'); ?>
                        </a>
                        
                        <?php if ($google_enabled || $wp_events_enabled): ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('toolkit_calendar_sync_now', 'toolkit_calendar_sync_nonce'); ?>
                                <input type="hidden" name="action" value="toolkit_calendar_sync_now">
                                <button type="submit" class="button button-secondary">
                                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                    <?php _e('Synchroniser maintenant', 'toolkit'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=calendar_event'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-list-view" style="margin-top: 3px;"></span>
                            <?php _e('Voir tous les événements', 'toolkit'); ?>
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
                <?php _e('Paramètres du Calendrier', 'toolkit'); ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('toolkit_calendar_settings_group');
                ?>

                <!-- Google Calendar Settings -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Configuration Google Calendar', 'toolkit'); ?></h2>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="google_enabled">
                                    <?php _e('Activer Google Calendar', 'toolkit'); ?>
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
                                    <?php _e('Synchroniser avec Google Calendar', 'toolkit'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Active la synchronisation automatique des événements depuis Google Calendar.', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_api_key">
                                    <?php _e('Clé API Google', 'toolkit'); ?> <span style="color: red;">*</span>
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
                                    <?php _e('Votre clé API Google Calendar. Obtenir une clé sur', 'toolkit'); ?> 
                                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                                        Google Cloud Console
                                    </a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_calendar_id">
                                    <?php _e('ID du Calendrier', 'toolkit'); ?> <span style="color: red;">*</span>
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
                                    <?php _e('L\'ID de votre calendrier Google (ex: votre-email@gmail.com ou calendar-id@group.calendar.google.com)', 'toolkit'); ?>
                                    <br>
                                    <?php _e('Trouvez-le dans Google Calendar → Paramètres → Intégrer le calendrier', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_sync_interval">
                                    <?php _e('Intervalle de synchronisation', 'toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <select 
                                    name="<?php echo self::OPTION_NAME; ?>[google][sync_interval]" 
                                    id="google_sync_interval"
                                >
                                    <option value="hourly" <?php selected($google['sync_interval'], 'hourly'); ?>>
                                        <?php _e('Toutes les heures', 'toolkit'); ?>
                                    </option>
                                    <option value="twicedaily" <?php selected($google['sync_interval'], 'twicedaily'); ?>>
                                        <?php _e('Deux fois par jour', 'toolkit'); ?>
                                    </option>
                                    <option value="daily" <?php selected($google['sync_interval'], 'daily'); ?>>
                                        <?php _e('Une fois par jour', 'toolkit'); ?>
                                    </option>
                                    <option value="weekly" <?php selected($google['sync_interval'], 'weekly'); ?>>
                                        <?php _e('Une fois par semaine', 'toolkit'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php _e('Fréquence de synchronisation automatique des événements.', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_max_results">
                                    <?php _e('Nombre maximum d\'événements', 'toolkit'); ?>
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
                                    <?php _e('Nombre maximum d\'événements à synchroniser (max: 2500).', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_time_min_offset">
                                    <?php _e('Période passée (jours)', 'toolkit'); ?>
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
                                    <?php _e('Nombre de jours dans le passé à synchroniser (ex: -30 pour les 30 derniers jours).', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="google_time_max_offset">
                                    <?php _e('Période future (jours)', 'toolkit'); ?>
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
                                    <?php _e('Nombre de jours dans le futur à synchroniser (ex: 365 pour la prochaine année).', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Enregistrer les paramètres', 'toolkit'), 'primary', 'submit', true); ?>
            </form>

            <!-- Test Connection -->
            <?php if ($google['enabled'] && !empty($google['api_key']) && !empty($google['calendar_id'])): ?>
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2><?php _e('Tester la connexion', 'toolkit'); ?></h2>
                <p>
                    <?php _e('Vérifiez que les paramètres sont corrects en testant la connexion à Google Calendar.', 'toolkit'); ?>
                </p>
                <button type="button" class="button" id="test-google-connection">
                    <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span>
                    <?php _e('Tester la connexion', 'toolkit'); ?>
                </button>
                <div id="test-result" style="margin-top: 15px;"></div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#test-google-connection').on('click', function() {
                    var button = $(this);
                    var result = $('#test-result');
                    
                    button.prop('disabled', true).text('<?php _e('Test en cours...', 'toolkit'); ?>');
                    result.html('<p><?php _e('Connexion à Google Calendar...', 'toolkit'); ?></p>');
                    
                    var apiKey = '<?php echo esc_js($google['api_key']); ?>';
                    var calendarId = '<?php echo esc_js($google['calendar_id']); ?>';
                    var testUrl = 'https://www.googleapis.com/calendar/v3/calendars/' + 
                                  encodeURIComponent(calendarId) + 
                                  '/events?key=' + apiKey + 
                                  '&maxResults=1&singleEvents=true&orderBy=startTime&' +
                                  'timeMin=' + new Date().toISOString();
                    
                    fetch(testUrl)
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => Promise.reject(err));
                            }
                            return response.json();
                        })
                        .then(data => {
                            result.html(
                                '<div class="notice notice-success inline">' +
                                '<p><strong><?php _e('✓ Connexion réussie!', 'toolkit'); ?></strong></p>' +
                                '<p><?php _e('Calendrier:', 'toolkit'); ?> ' + (data.summary || calendarId) + '</p>' +
                                '<p><?php _e('Événements trouvés:', 'toolkit'); ?> ' + (data.items ? data.items.length : 0) + '</p>' +
                                '</div>'
                            );
                        })
                        .catch(error => {
                            var errorMsg = error.error ? error.error.message : error.message || '<?php _e('Erreur inconnue', 'toolkit'); ?>';
                            result.html(
                                '<div class="notice notice-error inline">' +
                                '<p><strong><?php _e('✗ Échec de la connexion', 'toolkit'); ?></strong></p>' +
                                '<p>' + errorMsg + '</p>' +
                                '</div>'
                            );
                        })
                        .finally(() => {
                            button.prop('disabled', false).html(
                                '<span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span> ' +
                                '<?php _e('Tester la connexion', 'toolkit'); ?>'
                            );
                        });
                });
            });
            </script>
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
        if (!isset($_POST['toolkit_calendar_sync_nonce']) || 
            !wp_verify_nonce($_POST['toolkit_calendar_sync_nonce'], 'toolkit_calendar_sync_now')) {
            wp_die(__('Vérification de sécurité échouée.', 'toolkit'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'toolkit'));
        }

        // Run sync
        if (class_exists('Toolkit\utils\CalendarService')) {
            $results = \Toolkit\utils\CalendarService::sync_all();
            
            $success_messages = [];
            $error_messages = [];
            
            // Check Google Calendar results
            if (!empty($results['google'])) {
                if (!empty($results['google']['success'])) {
                    $success_messages[] = $results['google']['message'] ?? __('Google Calendar synchronisé', 'toolkit');
                } else {
                    $error_messages[] = $results['google']['message'] ?? __('Erreur Google Calendar', 'toolkit');
                }
            }
            
            // Check WordPress Events results
            if (!empty($results['wordpress_events'])) {
                if (!empty($results['wordpress_events']['success'])) {
                    $success_messages[] = $results['wordpress_events']['message'] ?? __('Événements WordPress synchronisés', 'toolkit');
                } else {
                    $error_messages[] = $results['wordpress_events']['message'] ?? __('Erreur Événements WordPress', 'toolkit');
                }
            }
            
            // Add success messages
            if (!empty($success_messages)) {
                add_settings_error(
                    'toolkit_calendar_messages',
                    'toolkit_calendar_sync_success',
                    implode('<br>', $success_messages),
                    'success'
                );
            }
            
            // Add error messages
            if (!empty($error_messages)) {
                add_settings_error(
                    'toolkit_calendar_messages',
                    'toolkit_calendar_sync_error',
                    implode('<br>', $error_messages),
                    'error'
                );
            }
            
            // If no results at all
            if (empty($results)) {
                add_settings_error(
                    'toolkit_calendar_messages',
                    'toolkit_calendar_sync_error',
                    __('Aucune source de calendrier n\'est activée.', 'toolkit'),
                    'error'
                );
            }
        }

        set_transient('settings_errors', get_settings_errors(), 30);

        wp_redirect(admin_url('admin.php?page=' . self::MENU_SLUG . '&settings-updated=true'));
        exit;
    }

    /**
     * Get all ACF fields recursively including sub-fields and repeater fields
     */
    private static function get_all_acf_fields($parent_field = null, $prefix = '')
    {
        $all_fields = [];
        
        if ($parent_field) {
            // Get sub-fields from parent
            $fields = [];
            if (function_exists('acf_get_fields')) {
                $fields = acf_get_fields($parent_field);
            }
        } else {
            // Get all field groups
            $field_groups = [];
            if (function_exists('acf_get_field_groups')) {
                $field_groups = acf_get_field_groups();
            }
            
            $fields = [];
            foreach ($field_groups as $group) {
                if (function_exists('acf_get_fields')) {
                    $group_fields = acf_get_fields($group['key']);
                    if ($group_fields) {
                        foreach ($group_fields as $field) {
                            $field['_group_title'] = $group['title'];
                            $fields[] = $field;
                        }
                    }
                }
            }
        }
        
        if (empty($fields)) {
            return $all_fields;
        }
        
        foreach ($fields as $field) {
            $field_label = $prefix . $field['label'];
            $group_title = isset($field['_group_title']) ? $field['_group_title'] . ' → ' : '';
            
            $all_fields[] = [
                'key' => $field['key'],
                'name' => $field['name'],
                'label' => $field_label,
                'type' => $field['type'],
                'display_label' => $group_title . $field_label . ' (' . $field['type'] . ')'
            ];
            
            // If field has sub-fields (repeater, group, flexible content, etc.)
            if (in_array($field['type'], ['repeater', 'group', 'flexible_content', 'clone'])) {
                $sub_fields = self::get_all_acf_fields($field['key'], $prefix . $field['label'] . ' → ');
                $all_fields = array_merge($all_fields, $sub_fields);
            }
        }
        
        return $all_fields;
    }

    /**
     * Render WordPress Events configuration page
     */
    public static function render_wordpress_events_page()
    {
        $settings = get_option(self::OPTION_NAME, self::get_default_settings());
        $wp_events = $settings['wordpress_events'] ?? self::get_default_settings()['wordpress_events'];

        // Get available post types
        $post_types = get_post_types(['public' => true], 'objects');
        
        // Get all ACF fields if ACF is active
        $all_acf_fields = [];
        if (function_exists('acf_get_field_groups') && function_exists('acf_get_fields')) {
            $all_acf_fields = self::get_all_acf_fields();
        }

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-calendar" style="font-size: 32px; margin-right: 10px;"></span>
                <?php _e('Lier un Événements WordPress', 'toolkit'); ?>
            </h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('toolkit_calendar_settings_group');
                ?>

                <!-- WordPress Events Settings -->
                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2><?php _e('Configuration Événements WordPress', 'toolkit'); ?></h2>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="wordpress_events_enabled">
                                    <?php _e('Activer Événements WordPress', 'toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo self::OPTION_NAME; ?>[wordpress_events][enabled]" 
                                        id="wordpress_events_enabled"
                                        value="1"
                                        <?php checked($wp_events['enabled'], true); ?>
                                    >
                                    <?php _e('Activer la gestion des événements WordPress', 'toolkit'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Active une liaison personnalisée pour les événements WordPress.', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wordpress_events_custom_post_type">
                                    <?php _e('Custom Post Type', 'toolkit'); ?> <span style="color: red;">*</span>
                                </label>
                            </th>
                            <td>
                                <select 
                                    name="<?php echo self::OPTION_NAME; ?>[wordpress_events][custom_post_type]" 
                                    id="wordpress_events_custom_post_type"
                                    class="regular-text"
                                >
                                    <option value=""><?php _e('Sélectionner un Custom Post Type', 'toolkit'); ?></option>
                                    <?php foreach ($post_types as $post_type): ?>
                                        <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($wp_events['custom_post_type'], $post_type->name); ?>>
                                            <?php echo esc_html($post_type->label . ' (' . $post_type->name . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Sélectionnez le Custom Post Type à utiliser pour les événements WordPress.', 'toolkit'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="wordpress_events_acf_field_group">
                                    <?php _e('Champs ACF', 'toolkit'); ?>
                                </label>
                            </th>
                            <td>
                                <?php if (!empty($all_acf_fields)): ?>
                                    <select 
                                        name="<?php echo self::OPTION_NAME; ?>[wordpress_events][acf_field_group]" 
                                        id="wordpress_events_acf_field_group"
                                        class="regular-text"
                                        style="width: 100%; max-width: 600px;"
                                    >
                                        <option value=""><?php _e('Aucun champ ACF sélectionné', 'toolkit'); ?></option>
                                        <?php foreach ($all_acf_fields as $field): ?>
                                            <option value="<?php echo esc_attr($field['key']); ?>" <?php selected($wp_events['acf_field_group'], $field['key']); ?>>
                                                <?php echo esc_html($field['display_label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Sélectionnez le champ ACF (incluant les sous-champs et champs répétés) relatif à une date associé à ce Custom Post Type. (ne prendra en compte que les champs de type DATE, marche si c\'est une date dans un repeater)', 'toolkit'); ?>
                                        <br>
                                        <strong><?php echo sprintf(__('%d champs disponibles', 'toolkit'), count($all_acf_fields)); ?></strong>
                                    </p>
                                <?php else: ?>
                                    <p class="description">
                                        <strong style="color: #dc3232;">
                                            <?php _e('ACF n\'est pas installé ou aucun champ n\'a été créé.', 'toolkit'); ?>
                                        </strong>
                                        <br>
                                        <?php _e('Installez et activez Advanced Custom Fields pour utiliser cette fonctionnalité.', 'toolkit'); ?>
                                    </p>
                                    <input type="hidden" name="<?php echo self::OPTION_NAME; ?>[wordpress_events][acf_field_group]" value="<?php echo esc_attr($wp_events['acf_field_group']); ?>">
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Enregistrer les paramètres', 'toolkit'), 'primary', 'submit', true); ?>
            </form>
        </div>
        <?php
    }
}
