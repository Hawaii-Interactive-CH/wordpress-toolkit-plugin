<?php

namespace Toolkit\models;

// Prevent direct access.
defined('ABSPATH') or exit;

class CalendarEvent extends CustomPostType
{
    const TYPE = 'calendar_event';

    /**
     * Register the calendar event custom post type
     */
    public static function register()
    {
        parent::register();
        
        // Add custom columns to admin list
        add_filter('manage_' . self::TYPE . '_posts_columns', [self::class, 'set_custom_columns']);
        add_action('manage_' . self::TYPE . '_posts_custom_column', [self::class, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-' . self::TYPE . '_sortable_columns', [self::class, 'sortable_columns']);
    }

    /**
     * Define post type settings
     */
    public static function type_settings()
    {
        return [
            'labels' => [
                'name' => __('Événements', 'toolkit'),
                'singular_name' => __('Événement', 'toolkit'),
                'menu_name' => __('Événements', 'toolkit'),
                'name_admin_bar' => __('Événement', 'toolkit'),
                'add_new' => __('Ajouter', 'toolkit'),
                'add_new_item' => __('Ajouter un événement', 'toolkit'),
                'new_item' => __('Nouvel événement', 'toolkit'),
                'edit_item' => __('Modifier l\'événement', 'toolkit'),
                'view_item' => __('Voir l\'événement', 'toolkit'),
                'all_items' => __('Tous les événements', 'toolkit'),
                'search_items' => __('Rechercher des événements', 'toolkit'),
                'not_found' => __('Aucun événement trouvé', 'toolkit'),
                'not_found_in_trash' => __('Aucun événement trouvé dans la corbeille', 'toolkit'),
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Hidden by default, visible in Toolkit Calendar menu
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'rest_base' => 'events',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'has_archive' => false,
            'rewrite' => [
                'slug' => 'evenement',
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'manage_options', // Only admins can create events manually
            ],
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => [
                'title',
                'editor',
                'custom-fields',
            ],
        ];
    }

    /**
     * Set custom columns for admin list
     */
    public static function set_custom_columns($columns)
    {
        $new_columns = [];
        
        // Checkbox
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        // Title
        $new_columns['title'] = __('Événement', 'toolkit');
        
        // Custom columns
        $new_columns['event_date'] = __('Date', 'toolkit');
        $new_columns['event_location'] = __('Lieu', 'toolkit');
        $new_columns['event_source'] = __('Source', 'toolkit');
        $new_columns['event_sync'] = __('Dernière synchro', 'toolkit');
        
        // Date (published date)
        if (isset($columns['date'])) {
            $new_columns['date'] = $columns['date'];
        }
        
        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public static function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'event_date':
                $start_date = get_post_meta($post_id, '_event_start_date', true);
                $end_date = get_post_meta($post_id, '_event_end_date', true);
                $is_all_day = get_post_meta($post_id, '_event_is_all_day', true);
                
                if ($start_date) {
                    $start = strtotime($start_date);
                    $end = $end_date ? strtotime($end_date) : null;
                    
                    if ($is_all_day) {
                        echo '<strong>' . date_i18n('j M Y', $start) . '</strong>';
                        if ($end && date('Y-m-d', $start) !== date('Y-m-d', $end)) {
                            echo ' → ' . date_i18n('j M Y', $end);
                        }
                        echo '<br><small>' . __('Toute la journée', 'toolkit') . '</small>';
                    } else {
                        echo '<strong>' . date_i18n('j M Y', $start) . '</strong>';
                        echo '<br><small>' . date_i18n('H:i', $start);
                        if ($end) {
                            echo ' → ' . date_i18n('H:i', $end);
                        }
                        echo '</small>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'event_location':
                $location = get_post_meta($post_id, '_event_location', true);
                if ($location) {
                    echo '<span class="dashicons dashicons-location" style="color: #999;"></span> ';
                    echo esc_html($location);
                } else {
                    echo '—';
                }
                break;
                
            case 'event_source':
                $google_id = get_post_meta($post_id, '_google_event_id', true);
                $google_link = get_post_meta($post_id, '_google_calendar_link', true);
                
                if ($google_id) {
                    if ($google_link) {
                        echo '<a href="' . esc_url($google_link) . '" target="_blank" title="' . __('Voir sur Google Calendar', 'toolkit') . '">';
                        echo '<span class="dashicons dashicons-google" style="color: #4285F4;"></span> Google Calendar';
                        echo '</a>';
                    } else {
                        echo '<span class="dashicons dashicons-google" style="color: #4285F4;"></span> Google Calendar';
                    }
                } else {
                    echo '<span class="dashicons dashicons-admin-post" style="color: #999;"></span> ' . __('Manuel', 'toolkit');
                }
                break;
                
            case 'event_sync':
                $last_synced = get_post_meta($post_id, '_last_synced', true);
                if ($last_synced) {
                    $synced_time = strtotime($last_synced);
                    $diff = time() - $synced_time;
                    
                    if ($diff < 3600) { // Less than 1 hour
                        echo '<span style="color: #46b450;">●</span> ';
                        echo sprintf(__('Il y a %d min', 'toolkit'), round($diff / 60));
                    } elseif ($diff < 86400) { // Less than 1 day
                        echo '<span style="color: #ffb900;">●</span> ';
                        echo sprintf(__('Il y a %d h', 'toolkit'), round($diff / 3600));
                    } else {
                        echo '<span style="color: #999;">●</span> ';
                        echo date_i18n('j M Y', $synced_time);
                    }
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public static function sortable_columns($columns)
    {
        $columns['event_date'] = 'event_start_date';
        $columns['event_location'] = 'event_location';
        return $columns;
    }

    /**
     * Get event start date
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string|false Event start date in MySQL format, or false if not found
     */
    public static function event_start_date($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_event_start_date', true);
    }

    /**
     * Get event end date
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string|false Event end date in MySQL format, or false if not found
     */
    public static function event_end_date($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_event_end_date', true);
    }

    /**
     * Get event location
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string Event location
     */
    public static function location($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_event_location', true);
    }

    /**
     * Check if event is all-day
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return bool True if all-day event
     */
    public static function is_all_day($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_event_is_all_day', true) === '1';
    }

    /**
     * Get Google event ID
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string|false Google event ID, or false if not synced from Google
     */
    public static function google_event_id($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_google_event_id', true);
    }

    /**
     * Get Google Calendar link
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string|false Google Calendar link, or false if not available
     */
    public static function google_link($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_google_calendar_link', true);
    }

    /**
     * Get last sync time
     * 
     * @param int|null $post_id Post ID (defaults to current post)
     * @return string|false Last sync time in MySQL format, or false if never synced
     */
    public static function last_synced($post_id = null)
    {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        return get_post_meta($post_id, '_last_synced', true);
    }
}
