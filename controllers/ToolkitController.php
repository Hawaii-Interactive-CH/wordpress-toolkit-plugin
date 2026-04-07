<?php

namespace Toolkit\controllers;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use \WP_REST_Request;
use \WP_REST_Response;
use \WP_Error;

class ToolkitController {

	/**
	 * Get all events
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_events( WP_REST_Request $request ) {
		$per_page = absint( $request->get_param( 'per_page' ) ?: 100 );
		$per_page = max( 1, min( $per_page, 100 ) );

		$args = [
			'post_type'      => 'calendar_event',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		// Filter by date range
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		if ( $start_date || $end_date ) {
			$meta_query = [ 'relation' => 'AND' ];

			if ( $start_date ) {
				$meta_query[] = [
					'key'     => '_event_start_date',
					'value'   => sanitize_text_field( wp_unslash( $start_date ) ),
					'compare' => '>=',
					'type'    => 'DATETIME',
				];
			}

			if ( $end_date ) {
				$meta_query[] = [
					'key'     => '_event_start_date',
					'value'   => sanitize_text_field( wp_unslash( $end_date ) ),
					'compare' => '<=',
					'type'    => 'DATETIME',
				];
			}

			$args['meta_query'] = $meta_query;
		}

		$query  = new \WP_Query( $args );
		$events = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$events[] = [
					'id'                    => $post_id,
					'title'                 => get_the_title(),
					'content'               => get_the_content(),
					'excerpt'               => get_the_excerpt(),
					'start_date'            => get_post_meta( $post_id, '_event_start_date', true ),
					'end_date'              => get_post_meta( $post_id, '_event_end_date', true ),
					'location'              => get_post_meta( $post_id, '_event_location', true ),
					'is_all_day'            => get_post_meta( $post_id, '_event_is_all_day', true ) === '1',
				];
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $events,
			'total'   => $query->found_posts,
		], 200 );
	}

	/**
	 * Get upcoming events
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_upcoming( WP_REST_Request $request ) {
		$limit = absint( $request->get_param( 'limit' ) ?: 10 );
		$limit = max( 1, min( $limit, 100 ) );
		$now   = current_time( 'mysql' );

		$args = [
			'post_type'      => 'calendar_event',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_event_start_date',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'DATETIME',
				],
			],
		];

		$query  = new \WP_Query( $args );
		$events = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$events[] = [
					'id'                   => $post_id,
					'title'                => get_the_title(),
					'content'              => get_the_content(),
					'excerpt'              => get_the_excerpt(),
					'start_date'           => get_post_meta( $post_id, '_event_start_date', true ),
					'end_date'             => get_post_meta( $post_id, '_event_end_date', true ),
					'location'             => get_post_meta( $post_id, '_event_location', true ),
					'is_all_day'           => get_post_meta( $post_id, '_event_is_all_day', true ) === '1',
				];
			}
			wp_reset_postdata();
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $events,
			'total'   => $query->found_posts,
		], 200 );
	}

	/**
	 * Get a single event
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_event( WP_REST_Request $request ) {
		$id   = absint( $request->get_param( 'id' ) );
		$post = get_post( $id );

		if ( ! $post || 'calendar_event' !== $post->post_type ) {
			return new WP_Error(
				'event_not_found',
				__( 'Event not found', 'wp-theme-toolkit' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => [
				'id'                   => $post->ID,
				'title'                => $post->post_title,
				'content'              => apply_filters( 'the_content', $post->post_content ),
				'excerpt'              => $post->post_excerpt,
				'start_date'           => get_post_meta( $post->ID, '_event_start_date', true ),
				'end_date'             => get_post_meta( $post->ID, '_event_end_date', true ),
				'location'             => get_post_meta( $post->ID, '_event_location', true ),
				'is_all_day'           => get_post_meta( $post->ID, '_event_is_all_day', true ) === '1',
			],
		], 200 );
	}

	/**
	 * Permission callback - allow public access
	 *
	 * @return bool
	 */
	public function permission_callback() {
		return true; // Public access
	}
}
