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
                'message' => __('Les événements WordPress ne sont pas activés.', 'toolkit'),
                'count' => 0
            ];
        }
        
        // Check if custom post type is set
        if (empty($wp_events['custom_post_type'])) {
            return [
                'success' => false,
                'message' => __('Aucun Custom Post Type n\'a été sélectionné.', 'toolkit'),
                'count' => 0
            ];
        }
        
        // Check if ACF field is set
        if (empty($wp_events['acf_field_group'])) {
            return [
                'success' => false,
                'message' => __('Aucun champ ACF n\'a été sélectionné.', 'toolkit'),
                'count' => 0
            ];
        }
        
        // Check if ACF is available
        if (!function_exists('get_field') || !function_exists('acf_get_field')) {
            return [
                'success' => false,
                'message' => __('ACF n\'est pas installé ou activé.', 'toolkit'),
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
                'message' => __('Le champ ACF sélectionné n\'existe pas.', 'toolkit'),
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
            return [
                'success' => true,
                'message' => sprintf(__('Aucun post trouvé pour le type %s.', 'toolkit'), $custom_post_type),
                'count' => 0
            ];
        }
        
        $event_count = 0;
        $is_repeater = ($acf_field['type'] === 'repeater');
        
        // Loop through each post
        foreach ($posts as $post) {
            $post_id = $post->ID;
            
            if ($is_repeater) {
                // Handle repeater field
                $repeater_rows = get_field($acf_field['name'], $post_id);
                
                if ($repeater_rows && is_array($repeater_rows)) {
                    // Create one event for each repeater row
                    foreach ($repeater_rows as $row_index => $row_data) {
                        $saved = self::save_event($post, $acf_field, $row_data, $row_index);
                        if ($saved) {
                            $event_count++;
                        }
                    }
                }
            } else {
                // Handle simple field (not repeater)
                $field_value = get_field($acf_field['name'], $post_id);
                
                if ($field_value) {
                    $saved = self::save_event($post, $acf_field, $field_value, null);
                    if ($saved) {
                        $event_count++;
                    }
                }
            }
        }
        
        // Update last sync time
        update_option('toolkit_calendar_last_sync', time());
        
        return [
            'success' => true,
            'message' => sprintf(__('%d événement(s) synchronisé(s) depuis %s.', 'toolkit'), $event_count, $custom_post_type),
            'count' => $event_count
        ];
    }
    
    /**
     * Save or update a WordPress post as a calendar_event
     * 
     * @param WP_Post $source_post The source WordPress post
     * @param array $acf_field The ACF field configuration
     * @param mixed $field_value The ACF field value (can be a single value or repeater row data)
     * @param int|null $row_index If it's a repeater, the row index
     * @return int|false Post ID on success, false on failure
     */
    private static function save_event($source_post, $acf_field, $field_value, $row_index = null)
    {
        if (empty($source_post) || empty($field_value)) {
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
            'meta_key' => '_wp_event_source_id',
            'meta_value' => $unique_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        $post_id = !empty($existing_posts) ? $existing_posts[0]->ID : 0;
        
        // Extract date from field value
        $event_date = null;
        
        // If it's a repeater, $field_value is an array of sub-field values
        if ($acf_field['type'] === 'repeater' && is_array($field_value)) {
            // Look for a date field in the repeater sub-fields
            foreach ($acf_field['sub_fields'] as $sub_field) {
                if (in_array($sub_field['type'], ['date_picker', 'date_time_picker'])) {
                    $sub_field_name = $sub_field['name'];
                    if (isset($field_value[$sub_field_name])) {
                        $event_date = self::extract_date_from_field($field_value[$sub_field_name], $sub_field['type']);
                        if ($event_date) {
                            break; // Use the first date field found
                        }
                    }
                }
            }
        } else {
            // Simple field
            $event_date = self::extract_date_from_field($field_value, $acf_field['type']);
        }
        
        if (!$event_date) {
            error_log('EventWPCalendar: Unable to extract date from field value for post ' . $source_post->ID);
            return false;
        }
        
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
        
        if (is_wp_error($result)) {
            error_log('EventWPCalendar: Error saving event: ' . $result->get_error_message());
            return false;
        }
        
        return $result;
    }
    
    /**
     * Extract date from ACF field value based on field type
     * 
     * @param mixed $field_value The field value
     * @param string $field_type The ACF field type
     * @return string|false Date in Y-m-d H:i:s format or false on failure
     */
    private static function extract_date_from_field($field_value, $field_type)
    {
        // Handle only date_picker and date_time_picker types
        switch ($field_type) {
            case 'date_picker':
                // Date picker can return various formats
                
                // Unix timestamp (numeric)
                if (is_numeric($field_value)) {
                    return date('Y-m-d H:i:s', intval($field_value));
                }
                
                // Ymd format (20240127)
                if (is_string($field_value) && strlen($field_value) === 8) {
                    return date('Y-m-d H:i:s', strtotime($field_value));
                }
                
                // Y-m-d or other string format
                if (is_string($field_value)) {
                    $timestamp = strtotime($field_value);
                    if ($timestamp) {
                        return date('Y-m-d H:i:s', $timestamp);
                    }
                }
                break;
                
            case 'date_time_picker':
                // Date time picker can return timestamp or formatted string
                
                // Unix timestamp (numeric)
                if (is_numeric($field_value)) {
                    return date('Y-m-d H:i:s', intval($field_value));
                }
                
                // Y-m-d H:i:s or other string format
                if (is_string($field_value)) {
                    $timestamp = strtotime($field_value);
                    if ($timestamp) {
                        return date('Y-m-d H:i:s', $timestamp);
                    }
                }
                break;
        }
        
        // If not a date field type, return false
        return false;
    }
}
