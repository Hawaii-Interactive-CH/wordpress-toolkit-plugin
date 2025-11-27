<?php

namespace Toolkit\utils;

/**
 * Google Calendar Source
 * 
 * Handles synchronization of events from Google Calendar API to WordPress
 * 
 * @package Toolkit\utils
 */
class GoogleCalendarSource
{
    /**
     * Sync events from Google Calendar
     * 
     * @return array Result with success status, message, and event count
     */
    public static function sync()
    {
        $settings = get_option('toolkit_calendar_settings', []);
        $google_settings = $settings['google'] ?? [];
        
        // Validate required settings
        if (empty($google_settings['api_key'])) {
            return [
                'success' => false,
                'message' => 'Google API Key not configured',
                'events_synced' => 0
            ];
        }
        
        if (empty($google_settings['calendar_id'])) {
            return [
                'success' => false,
                'message' => 'Google Calendar ID not configured',
                'events_synced' => 0
            ];
        }
        
        try {
            // Get events from Google Calendar API
            $events = self::fetch_google_events($google_settings);
            
            if (empty($events)) {
                return [
                    'success' => true,
                    'message' => 'No events found',
                    'events_synced' => 0
                ];
            }
            
            // Process and save each event
            $synced_count = 0;
            foreach ($events as $google_event) {
                if (self::save_event($google_event)) {
                    $synced_count++;
                }
            }
            
            return [
                'success' => true,
                'message' => sprintf('%d events synced successfully', $synced_count),
                'events_synced' => $synced_count
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
                'events_synced' => 0
            ];
        }
    }
    
    /**
     * Fetch events from Google Calendar API
     * 
     * @param array $settings Calendar settings
     * @return array Array of Google Calendar events
     * @throws \Exception If API request fails
     */
    private static function fetch_google_events($settings)
    {
        $api_key = $settings['api_key'];
        $calendar_id = urlencode($settings['calendar_id']);
        $max_results = !empty($settings['max_results']) ? intval($settings['max_results']) : 250;
        
        // Calculate time range
        $time_min_offset = !empty($settings['time_min_offset']) ? intval($settings['time_min_offset']) : -30;
        $time_max_offset = !empty($settings['time_max_offset']) ? intval($settings['time_max_offset']) : 365;
        
        $time_min = gmdate('c', strtotime("{$time_min_offset} days")); // Past date
        $time_max = gmdate('c', strtotime("+{$time_max_offset} days")); // Future date
        
        // Build API URL
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id}/events";
        $params = [
            'key' => $api_key,
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'maxResults' => $max_results,
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        ];
        
        $url .= '?' . http_build_query($params);
        
        // Make API request
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = $error['error']['message'] ?? 'Unknown error';
            throw new \Exception("API returned status {$status_code}: {$error_message}");
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Debug: log raw API response
        error_log('Google Calendar API Response: ' . print_r($data, true));
        
        return $data['items'] ?? [];
    }
    
    /**
     * Save or update a Google Calendar event as a WordPress post
     * 
     * @param array $google_event Event data from Google Calendar API
     * @return int|false Post ID on success, false on failure
     */
    private static function save_event($google_event)
    {
        if (empty($google_event['id'])) {
            return false;
        }
        
        $google_event_id = sanitize_text_field($google_event['id']);
        
        // Check if event already exists
        $existing_posts = get_posts([
            'post_type' => 'calendar_event',
            'meta_key' => '_google_event_id',
            'meta_value' => $google_event_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        $post_id = !empty($existing_posts) ? $existing_posts[0]->ID : 0;
        
        // Parse event data
        $title = !empty($google_event['summary']) ? $google_event['summary'] : 'Untitled Event';
        $description = !empty($google_event['description']) ? $google_event['description'] : '';
        $location = !empty($google_event['location']) ? $google_event['location'] : '';
        
        // Debug: log event data
        error_log('Google Event: ' . print_r([
            'id' => $google_event['id'] ?? 'no-id',
            'summary' => $google_event['summary'] ?? 'no-summary',
            'description' => $google_event['description'] ?? 'no-description',
            'location' => $google_event['location'] ?? 'no-location',
        ], true));
        
        // Parse dates
        $start_date = self::parse_google_date($google_event['start'] ?? []);
        $end_date = self::parse_google_date($google_event['end'] ?? []);
        $is_all_day = !empty($google_event['start']['date']); // All-day events use 'date' instead of 'dateTime'
        
        // Prepare post data
        $post_data = [
            'ID' => $post_id,
            'post_title' => sanitize_text_field($title),
            'post_content' => wp_kses_post($description),
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'meta_input' => [
                '_google_event_id' => $google_event_id,
                '_event_start_date' => $start_date,
                '_event_end_date' => $end_date,
                '_event_location' => sanitize_text_field($location),
                '_event_is_all_day' => $is_all_day ? '1' : '0',
                '_google_calendar_link' => esc_url_raw($google_event['htmlLink'] ?? ''),
                '_last_synced' => current_time('mysql')
            ]
        ];
        
        // Insert or update post
        if ($post_id) {
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
        }
        
        return is_wp_error($result) ? false : $result;
    }
    
    /**
     * Parse Google Calendar date format to MySQL datetime
     * 
     * @param array $date_data Date data from Google Calendar (dateTime or date)
     * @return string MySQL datetime format (Y-m-d H:i:s)
     */
    private static function parse_google_date($date_data)
    {
        if (empty($date_data)) {
            return '';
        }
        
        // All-day events use 'date' field (YYYY-MM-DD)
        if (!empty($date_data['date'])) {
            return date('Y-m-d 00:00:00', strtotime($date_data['date']));
        }
        
        // Timed events use 'dateTime' field (RFC3339)
        if (!empty($date_data['dateTime'])) {
            return date('Y-m-d H:i:s', strtotime($date_data['dateTime']));
        }
        
        return '';
    }
    
    /**
     * Test connection to Google Calendar API
     * 
     * @param string $api_key Google API Key
     * @param string $calendar_id Google Calendar ID
     * @return array Result with success status and message
     */
    public static function test_connection($api_key, $calendar_id)
    {
        if (empty($api_key) || empty($calendar_id)) {
            return [
                'success' => false,
                'message' => 'API Key and Calendar ID are required'
            ];
        }
        
        $calendar_id_encoded = urlencode($calendar_id);
        $url = "https://www.googleapis.com/calendar/v3/calendars/{$calendar_id_encoded}?key={$api_key}";
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $calendar_name = $data['summary'] ?? 'Unknown';
            
            return [
                'success' => true,
                'message' => "Successfully connected to calendar: {$calendar_name}",
                'calendar_name' => $calendar_name
            ];
        } else {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = $error['error']['message'] ?? 'Unknown error';
            
            return [
                'success' => false,
                'message' => "Connection failed (HTTP {$status_code}): {$error_message}"
            ];
        }
    }
}
