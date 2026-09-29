<?php

namespace Toolkit\utils;

/**
 * Event WP Calendar Source
 * 
 * Handles synchronization of events from WordPress Custom Post Types with ACF fields to calendar_event
 * 
 * @package Toolkit\utils
 */
class EventWPCalendar
{
    /**
     * Sync events from WordPress Custom Post Type with ACF fields
     * 
     * @return array Result with success status, message, and event count
     */
    public static function sync()
    {
        // Get settings
        $settings = get_option('toolkit_calendar_settings', []);
        $wp_events = $settings['wordpress_events'] ?? [];
        
        // Check if WordPress events are enabled
        if (empty($wp_events['enabled'])) {
            return [
                'success' => false,
                'message' => __('WordPress events are not enabled.', 'hi-theme-toolkit'),
                'count' => 0
            ];
        }
        
        // Check if custom post type is set
        if (empty($wp_events['custom_post_type'])) {
            return [
                'success' => false,
                'message' => __('No Custom Post Type has been selected.', 'hi-theme-toolkit'),
                'count' => 0
            ];
        }
        
        // Check if ACF field is set
        if (empty($wp_events['acf_field_group'])) {
            return [
                'success' => false,
                'message' => __('No ACF field has been selected.', 'hi-theme-toolkit'),
                'count' => 0
            ];
        }
        
        // Check if ACF is available
        if (!function_exists('get_field') || !function_exists('acf_get_field')) {
            return [
                'success' => false,
                'message' => __('ACF is not installed or activated.', 'hi-theme-toolkit'),
                'count' => 0
            ];
        }
        
        $custom_post_type = $wp_events['custom_post_type'];
        $acf_field_key = $wp_events['acf_field_group'];
        
        // Get ACF field info
        $acf_field = acf_get_field($acf_field_key);
        if (!$acf_field) {
            return [
                'success' => false,
                'message' => __('The selected ACF field does not exist.', 'hi-theme-toolkit'),
                'count' => 0
            ];
        }
        
        // Get all posts of the selected Custom Post Type
        $posts = get_posts([
            'post_type' => $custom_post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if (empty($posts)) {
            // Every source post is gone: remove all previously synced events
            CalendarService::cleanup_deleted_events('_wp_event_source_id');

            return [
                'success' => true,
                // translators: %s is the custom post type slug.
                'message' => sprintf(__('No post found for type %s.', 'hi-theme-toolkit'), $custom_post_type),
                'count' => 0
            ];
        }

        // Where to read the date: the selected field can be a date field, a repeater/group
        // containing date sub-fields, or a date sub-field inside a repeater/group
        $container = null;
        $date_fields = [];
        $date_types = ['date_picker', 'date_time_picker'];

        if (in_array($acf_field['type'], ['repeater', 'group'], true)) {
            $container = $acf_field;
            foreach ($acf_field['sub_fields'] ?? [] as $sub_field) {
                if (in_array($sub_field['type'], $date_types, true)) {
                    $date_fields[] = $sub_field;
                }
            }
        } else {
            $date_fields[] = $acf_field;
            $parent_field = !empty($acf_field['parent']) ? acf_get_field($acf_field['parent']) : false;
            if ($parent_field && in_array($parent_field['type'], ['repeater', 'group'], true)) {
                $container = $parent_field;
            }
        }

        $event_count = 0;
        $synced_ids = [];

        // Loop through each post
        foreach ($posts as $post) {
            // Raw DB values (format = false): always Ymd for date_picker and Y-m-d H:i:s for
            // date_time_picker, whatever the field's return format is. Sub-fields are keyed by field key.
            if ($container) {
                $raw = get_field($container['name'], $post->ID, false);
                if (empty($raw) || !is_array($raw)) {
                    continue;
                }
                $rows = $container['type'] === 'repeater' ? $raw : [$raw];
            } else {
                $rows = [[$acf_field['key'] => get_field($acf_field['name'], $post->ID, false)]];
            }

            foreach ($rows as $row_index => $row) {
                // Use the first date field with a valid value
                $event_date = false;
                foreach ($date_fields as $date_field) {
                    $value = $row[$date_field['key']] ?? $row[$date_field['name']] ?? null;
                    $event_date = self::extract_date_from_field($value, $date_field['type']);
                    if ($event_date) {
                        break;
                    }
                }

                if (!$event_date) {
                    continue;
                }

                $saved = self::save_event($post, $acf_field, $event_date, $container && $container['type'] === 'repeater' ? $row_index : null);
                if ($saved) {
                    $synced_ids[] = $saved;
                    $event_count++;
                }
            }
        }

        // Remove events whose source post, repeater row or date no longer exists
        $deleted_count = CalendarService::cleanup_deleted_events('_wp_event_source_id', $synced_ids);

        // Update last sync time
        update_option('toolkit_calendar_last_sync', time());

        return [
            'success' => true,
            // translators: %1$d is the number of events synchronized, %2$s is the custom post type slug, %3$d is the number of deleted events.
            'message' => sprintf(__('%1$d event(s) synchronized from %2$s, %3$d deleted.', 'hi-theme-toolkit'), $event_count, $custom_post_type, $deleted_count),
            'count' => $event_count
        ];
    }
    
    /**
     * Save or update a WordPress post as a calendar_event
     * 
     * @param WP_Post $source_post The source WordPress post
     * @param array $acf_field The selected ACF field configuration
     * @param string $event_date Event date in Y-m-d H:i:s format
     * @param int|null $row_index If it's a repeater, the row index
     * @return int|false Post ID on success, false on failure
     */
    private static function save_event($source_post, $acf_field, $event_date, $row_index = null)
    {
        if (empty($source_post) || empty($event_date)) {
            return false;
        }
        
        // Generate unique identifier for this event
        $unique_id = $source_post->ID . '_' . $acf_field['key'];
        if ($row_index !== null) {
            $unique_id .= '_row_' . $row_index;
        }
        
        // Check if event already exists
        $existing_posts = get_posts([
            'post_type' => 'calendar_event',
            'meta_key' => '_wp_event_source_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_value' => $unique_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        $post_id = !empty($existing_posts) ? $existing_posts[0]->ID : 0;

        // Build event title (without row number)
        $title = $source_post->post_title;
        
        // Prepare post data
        $post_data = [
            'ID' => $post_id,
            'post_title' => sanitize_text_field($title),
            'post_content' => $source_post->post_content,
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'meta_input' => [
                '_wp_event_source_id' => $unique_id,
                '_wp_event_source_post_id' => $source_post->ID,
                '_wp_event_source_post_type' => $source_post->post_type,
                '_wp_event_acf_field_key' => $acf_field['key'],
                '_wp_event_row_index' => $row_index !== null ? $row_index : '',
                '_event_start_date' => $event_date,
                '_event_end_date' => $event_date, // Same as start date by default
                '_event_is_all_day' => '1', // All day by default
                '_last_synced' => current_time('mysql')
            ]
        ];
        
        // Insert or update post
        if ($post_id) {
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
        }

        // Assign WPML language to the calendar event (same as source post)
        if (class_exists('SitePress')) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own hook names
            $details = apply_filters('wpml_post_language_details', null, $source_post->ID);

            if (!empty($details['language_code'])) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'EventWPCalendar: Setting event ' . $result . ' language to: ' . $details['language_code'] . ' (from source post ' . $source_post->ID . ')' );
                }
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own hook names
                do_action('wpml_set_element_language_details', [
                    'element_id'    => $result,
                    'element_type'  => 'post_calendar_event',
                    'trid'          => $details['trid'] ?? false,
                    'language_code' => $details['language_code'],
                ]);
            }
        }
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'EventWPCalendar: Saved event for post ' . $source_post->ID . ' with event ID ' . $result );
        }

        if (is_wp_error($result)) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log( 'EventWPCalendar: Error saving event: ' . $result->get_error_message() );
            }
            return false;
        }
        
        return $result;
    }
    
    /**
     * Extract date from ACF field value based on field type
     * 
     * @param mixed $field_value The raw (unformatted) field value
     * @param string $field_type The ACF field type
     * @return string|false Date in Y-m-d H:i:s format or false on failure
     */
    private static function extract_date_from_field($field_value, $field_type)
    {
        if (!in_array($field_type, ['date_picker', 'date_time_picker'], true)) {
            return false;
        }

        if (empty($field_value) || !is_scalar($field_value)) {
            return false;
        }

        $field_value = trim((string) $field_value);

        // ACF storage formats: Ymd (date_picker) and Y-m-d H:i:s (date_time_picker).
        // Checked before is_numeric(), otherwise 20261201 would be read as a timestamp (1970).
        foreach (['!Ymd', 'Y-m-d H:i:s', '!Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat($format, $field_value);
            if ($date && $date->format(ltrim($format, '!')) === $field_value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Legacy values saved as a Unix timestamp
        if (ctype_digit($field_value)) {
            return gmdate('Y-m-d H:i:s', intval($field_value));
        }

        return false;
    }
}
