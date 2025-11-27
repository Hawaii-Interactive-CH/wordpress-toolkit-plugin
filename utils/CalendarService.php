<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Toolkit Calendar Service
 * 
 * Manages calendar synchronization and provides calendar data access
 */
class CalendarService
{
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'toolkit_calendar_sync';
    
    /**
     * Register the calendar service
     */
    public static function register()
    {
        // Register cron schedules
        add_filter('cron_schedules', [self::class, 'add_cron_schedules']);
        
        // Hook cron action
        add_action(self::CRON_HOOK, [self::class, 'sync_all']);
        
        // Schedule cron on activation
        add_action('init', [self::class, 'maybe_schedule_cron']);
        
        // Cleanup on deactivation
        register_deactivation_hook(__FILE__, [self::class, 'unschedule_cron']);
    }
    
    /**
     * Add custom cron schedules
     * 
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function add_cron_schedules($schedules)
    {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = [
                'interval' => 604800,
                'display' => __('Once Weekly', 'toolkit')
            ];
        }
        
        return $schedules;
    }
    
    /**
     * Maybe schedule cron if not already scheduled
     */
    public static function maybe_schedule_cron()
    {
        $settings = self::get_settings();
        
        if (!isset($settings['google']['enabled']) || !$settings['google']['enabled']) {
            // If disabled, unschedule
            self::unschedule_cron();
            return;
        }
        
        $interval = $settings['google']['sync_interval'] ?? 'daily';
        
        // Check if already scheduled with correct interval
        $scheduled = wp_next_scheduled(self::CRON_HOOK);
        
        if ($scheduled) {
            $current_schedule = wp_get_schedule(self::CRON_HOOK);
            
            // If interval changed, reschedule
            if ($current_schedule !== $interval) {
                self::unschedule_cron();
                wp_schedule_event(time(), $interval, self::CRON_HOOK);
            }
        } else {
            // Not scheduled, schedule it
            wp_schedule_event(time(), $interval, self::CRON_HOOK);
        }
    }
    
    /**
     * Unschedule cron
     */
    public static function unschedule_cron()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
    
    /**
     * Get calendar settings from options
     * 
     * @return array Settings array
     */
    public static function get_settings()
    {
        return get_option('toolkit_calendar_settings', [
            'google' => [
                'enabled' => false,
                'api_key' => '',
                'calendar_id' => '',
                'sync_interval' => 'daily',
                'max_results' => 250,
                'time_min_offset' => -30,
                'time_max_offset' => 365,
            ]
        ]);
    }
    
    /**
     * Sync all enabled calendar sources
     * 
     * @return array Result of sync operations
     */
    public static function sync_all()
    {
        $settings = self::get_settings();
        $results = [];
        
        // Sync Google Calendar if enabled
        if (isset($settings['google']['enabled']) && $settings['google']['enabled']) {
            if (class_exists('Toolkit\utils\GoogleCalendarSource')) {
                $results['google'] = \Toolkit\utils\GoogleCalendarSource::sync();
            } else {
                $results['google'] = [
                    'success' => false,
                    'error' => 'GoogleCalendarSource class not found'
                ];
            }
        }
        
        // Update last sync time
        update_option('toolkit_calendar_last_sync', time());
        
        return $results;
    }
    
    /**
     * Get all calendar events
     * 
     * @param array $args Query arguments
     * @return array Array of calendar events
     */
    public static function get_all_events($args = [])
    {
        $defaults = [
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => '_event_date',
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $query = new \WP_Query($args);
        
        return $query->posts;
    }
    
    /**
     * Get upcoming events
     * 
     * @param int $limit Number of events to retrieve
     * @return array Array of upcoming events
     */
    public static function get_upcoming($limit = 10)
    {
        return self::get_all_events([
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_event_date',
                    'value' => current_time('mysql'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ]
            ]
        ]);
    }
    
    /**
     * Get events within date range
     * 
     * @param string $from Start date (Y-m-d format)
     * @param string $to End date (Y-m-d format)
     * @return array Array of events
     */
    public static function get_events_by_range($from, $to)
    {
        return self::get_all_events([
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_event_date',
                    'value' => $from,
                    'compare' => '>=',
                    'type' => 'DATE'
                ],
                [
                    'key' => '_event_date',
                    'value' => $to,
                    'compare' => '<=',
                    'type' => 'DATE'
                ]
            ]
        ]);
    }
    
    /**
     * Get event by Google Calendar ID
     * 
     * @param string $google_event_id Google Calendar event ID
     * @return \WP_Post|null Post object or null if not found
     */
    public static function get_event_by_google_id($google_event_id)
    {
        $args = [
            'post_type' => 'calendar_event',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_google_event_id',
                    'value' => $google_event_id,
                    'compare' => '='
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        
        return $query->have_posts() ? $query->posts[0] : null;
    }
    
    /**
     * Delete events that no longer exist in source
     * 
     * @param array $existing_google_ids Array of Google Calendar event IDs that exist
     */
    public static function cleanup_deleted_events($existing_google_ids = [])
    {
        // Get all calendar events with Google IDs
        $args = [
            'post_type' => 'calendar_event',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_google_event_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $google_id = get_post_meta($post->ID, '_google_event_id', true);
                
                // If this Google ID is not in the existing list, delete it
                if (!in_array($google_id, $existing_google_ids)) {
                    wp_trash_post($post->ID);
                }
            }
        }
    }
    
    /**
     * Get statistics about calendar events
     * 
     * @return array Statistics array
     */
    public static function get_statistics()
    {
        $total = wp_count_posts('calendar_event');
        
        return [
            'total' => $total->publish ?? 0,
            'draft' => $total->draft ?? 0,
            'upcoming' => count(self::get_upcoming(9999)),
            'last_sync' => get_option('toolkit_calendar_last_sync', null),
        ];
    }
}
