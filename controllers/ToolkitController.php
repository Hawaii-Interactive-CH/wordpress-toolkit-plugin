<?php

namespace Toolkit\controllers;

// Prevent direct access.
defined('ABSPATH') or exit;

use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;

class ToolkitController
{
    /**
     * Get all events
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_events(WP_REST_Request $request)
    {
        $args = [
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 100,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Filter by date range
        if ($request->get_param('start_date') || $request->get_param('end_date')) {
            $meta_query = ['relation' => 'AND'];
            
            if ($request->get_param('start_date')) {
                $meta_query[] = [
                    'key' => '_event_start_date',
                    'value' => $request->get_param('start_date'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ];
            }
            
            if ($request->get_param('end_date')) {
                $meta_query[] = [
                    'key' => '_event_start_date',
                    'value' => $request->get_param('end_date'),
                    'compare' => '<=',
                    'type' => 'DATETIME'
                ];
            }
            
            $args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($args);
        $events = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $source_post_id = get_post_meta($post_id, '_wp_event_source_post_id', true);
                $source_post_type = get_post_meta($post_id, '_wp_event_source_post_type', true);
                
                $events[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'start_date' => get_post_meta($post_id, '_event_start_date', true),
                    'end_date' => get_post_meta($post_id, '_event_end_date', true),
                    'location' => get_post_meta($post_id, '_event_location', true),
                    'is_all_day' => get_post_meta($post_id, '_event_is_all_day', true) === '1',
                    'google_event_id' => get_post_meta($post_id, '_google_event_id', true),
                    'google_calendar_link' => get_post_meta($post_id, '_google_calendar_link', true),
                    'last_synced' => get_post_meta($post_id, '_last_synced', true),
                    'source_post_id' => $source_post_id ?: null,
                    'source_post_type' => $source_post_type ?: null,
                    'source_link' => $source_post_id ? get_permalink($source_post_id) : null,
                ];
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $events,
            'total' => $query->found_posts,
        ], 200);
    }

    /**
     * Get upcoming events
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_upcoming(WP_REST_Request $request)
    {
        $limit = $request->get_param('limit') ?: 10;
        $now = current_time('mysql');

        $args = [
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_event_start_date',
                    'value' => $now,
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ]
            ]
        ];

        $query = new \WP_Query($args);
        $events = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $source_post_id = get_post_meta($post_id, '_wp_event_source_post_id', true);
                $source_post_type = get_post_meta($post_id, '_wp_event_source_post_type', true);
                
                $events[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'start_date' => get_post_meta($post_id, '_event_start_date', true),
                    'end_date' => get_post_meta($post_id, '_event_end_date', true),
                    'location' => get_post_meta($post_id, '_event_location', true),
                    'is_all_day' => get_post_meta($post_id, '_event_is_all_day', true) === '1',
                    'google_event_id' => get_post_meta($post_id, '_google_event_id', true),
                    'google_calendar_link' => get_post_meta($post_id, '_google_calendar_link', true),
                    'source_post_id' => $source_post_id ?: null,
                    'source_post_type' => $source_post_type ?: null,
                    'source_link' => $source_post_id ? get_permalink($source_post_id) : null,
                ];
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $events,
            'total' => $query->found_posts,
        ], 200);
    }

    /**
     * Get upcoming events with a period before today
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_upcoming_with_period(WP_REST_Request $request)
    {
        $limit = $request->get_param('limit') ?: 10;
        $before = $request->get_param('before') ?: 0;
        
        $now = current_time('mysql');
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$before} days", strtotime($now)));

        $args = [
            'post_type' => 'calendar_event',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'meta_value',
            'meta_key' => '_event_start_date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_event_start_date',
                    'value' => $start_date,
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ]
            ]
        ];

        $query = new \WP_Query($args);
        $events = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $source_post_id = get_post_meta($post_id, '_wp_event_source_post_id', true);
                $source_post_type = get_post_meta($post_id, '_wp_event_source_post_type', true);
                
                $events[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'start_date' => get_post_meta($post_id, '_event_start_date', true),
                    'end_date' => get_post_meta($post_id, '_event_end_date', true),
                    'location' => get_post_meta($post_id, '_event_location', true),
                    'is_all_day' => get_post_meta($post_id, '_event_is_all_day', true) === '1',
                    'google_event_id' => get_post_meta($post_id, '_google_event_id', true),
                    'google_calendar_link' => get_post_meta($post_id, '_google_calendar_link', true),
                    'source_post_id' => $source_post_id ?: null,
                    'source_post_type' => $source_post_type ?: null,
                    'source_link' => $source_post_id ? get_permalink($source_post_id) : null,
                ];
            }
            wp_reset_postdata();
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $events,
            'total' => $query->found_posts,
            'period' => [
                'before_days' => $before,
                'start_date' => $start_date,
                'current_date' => $now
            ]
        ], 200);
    }

    /**
     * Get a single event
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_event(WP_REST_Request $request)
    {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'calendar_event') {
            return new WP_Error(
                'event_not_found',
                __('Événement non trouvé', 'toolkit'),
                ['status' => 404]
            );
        }

        $source_post_id = get_post_meta($post->ID, '_wp_event_source_post_id', true);
        $source_post_type = get_post_meta($post->ID, '_wp_event_source_post_type', true);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => apply_filters('the_content', $post->post_content),
                'excerpt' => $post->post_excerpt,
                'start_date' => get_post_meta($post->ID, '_event_start_date', true),
                'end_date' => get_post_meta($post->ID, '_event_end_date', true),
                'location' => get_post_meta($post->ID, '_event_location', true),
                'is_all_day' => get_post_meta($post->ID, '_event_is_all_day', true) === '1',
                'google_event_id' => get_post_meta($post->ID, '_google_event_id', true),
                'google_calendar_link' => get_post_meta($post->ID, '_google_calendar_link', true),
                'last_synced' => get_post_meta($post->ID, '_last_synced', true),
                'source_post_id' => $source_post_id ?: null,
                'source_post_type' => $source_post_type ?: null,
                'source_link' => $source_post_id ? get_permalink($source_post_id) : null,
            ]
        ], 200);
    }

    /**
     * Permission callback - allow public access
     * 
     * @return bool
     */
    public function permission_callback()
    {
        return true; // Public access
    }
}
