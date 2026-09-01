<?php
/**
 * Native Post View Counter
 *
 * Lightweight counter using post meta + transient deduplication.
 * Avoids the WP-PostViews plugin. Views are counted via JS ping
 * (so cached pages still increment correctly).
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get current view count.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function khobor_get_views( $post_id ) {
	return (int) get_post_meta( $post_id, '_khobor_views', true );
}

/**
 * Add 1 to a post's view count in a single atomic query.
 *
 * Using "meta_value = meta_value + 1" in SQL rather than a read-modify-write
 * in PHP keeps concurrent hits on the same post from overwriting each other,
 * and halves the number of queries per view.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function khobor_bump_views( $post_id ) {
	global $wpdb;

	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta}
			 SET meta_value = meta_value + 1
			 WHERE post_id = %d AND meta_key = '_khobor_views'",
			$post_id
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	// First ever view for this post: no row to increment yet.
	if ( ! $updated ) {
		add_post_meta( $post_id, '_khobor_views', 1, true );
	}

	wp_cache_delete( $post_id, 'post_meta' );
}

/**
 * Increment view count, with simple dedupe by visitor (IP+UA hash, 1 hour TTL).
 *
 * @param int  $post_id      Post ID.
 * @param bool $return_count Read the resulting count back (costs one query).
 *                           Page loads don't need it; the REST endpoint does.
 * @return int New count, or 0 when $return_count is false.
 */
function khobor_increment_views( $post_id, $return_count = true ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return 0;
	}

	// Don't count bots crudely.
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
	if ( $ua && preg_match( '/(bot|spider|crawler|slurp|curl|wget|preview)/i', $ua ) ) {
		return $return_count ? khobor_get_views( $post_id ) : 0;
	}

	$ip          = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
	$fingerprint = md5( $ip . $ua . $post_id );
	$transient   = 'khobor_view_' . $fingerprint;

	if ( get_transient( $transient ) ) {
		return $return_count ? khobor_get_views( $post_id ) : 0;
	}
	set_transient( $transient, 1, HOUR_IN_SECONDS );

	khobor_bump_views( $post_id );

	return $return_count ? khobor_get_views( $post_id ) : 0;
}

/**
 * REST endpoint: POST /khobor/v1/view  body: { post_id }
 * Used by the JS view-ping; works on cached pages.
 */
function khobor_register_view_endpoint() {
	register_rest_route(
		'khobor/v1',
		'/view',
		array(
			'methods'             => 'POST',
			'callback'            => function ( WP_REST_Request $req ) {
				$post_id = absint( $req->get_param( 'post_id' ) );
				if ( ! $post_id || ! get_post( $post_id ) ) {
					return new WP_Error( 'invalid_post', 'Invalid post', array( 'status' => 400 ) );
				}
				$count = khobor_increment_views( $post_id );
				return rest_ensure_response( array( 'count' => $count ) );
			},
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'khobor_register_view_endpoint' );

/**
 * Should views be counted during the PHP page load?
 *
 * True (default) suits sites with no full-page cache: PHP runs on every view,
 * so counting there is free and the browser needs no extra request.
 *
 * Turn this OFF if you put a full-page cache in front of the site
 * (WP Super Cache, LiteSpeed, Cloudflare APO, Varnish). PHP stops running for
 * cached hits, so the JS ping in single.php becomes the only accurate counter:
 *
 *     add_filter( 'khobor_count_views_on_page_load', '__return_false' );
 *
 * Only one of the two paths runs. Both at once means every article view pays
 * for a second full WordPress bootstrap that the dedupe transient then throws
 * away.
 *
 * @return bool
 */
function khobor_counts_views_on_page_load() {
	return (bool) apply_filters( 'khobor_count_views_on_page_load', true );
}

/**
 * Count the view during the PHP request (non-cached environments like XAMPP).
 */
function khobor_track_view_on_load() {
	if ( ! khobor_counts_views_on_page_load() ) {
		return;
	}
	if ( is_singular( 'post' ) && ! is_admin() && ! is_preview() ) {
		khobor_increment_views( get_queried_object_id(), false );
	}
}
add_action( 'wp', 'khobor_track_view_on_load' );

/**
 * Most-viewed post IDs, newest window first. Cached.
 *
 * One flat SQL query instead of WP_Query's meta_query machinery, cached for a
 * few minutes so the homepage and sidebar don't re-sort postmeta on every hit.
 * If fewer than $count posts have any views yet, the list is topped up with
 * recent posts so the widget never renders short.
 *
 * @param int $count Number of posts.
 * @param int $days  Only consider posts published in the last N days.
 * @return int[] Post IDs, most viewed first.
 */
function khobor_get_popular_posts( $count = 5, $days = 30 ) {
	global $wpdb;

	$count = max( 1, absint( $count ) );
	$days  = max( 1, absint( $days ) );

	$cache_key = "khobor_popular_{$count}_{$days}";
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$after = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

	// CAST is needed because meta_value is a string column: without it "9"
	// sorts above "10".
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm
			         ON pm.post_id = p.ID AND pm.meta_key = '_khobor_views'
			 WHERE p.post_type = 'post'
			   AND p.post_status = 'publish'
			   AND p.post_date_gmt > %s
			 ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC, p.post_date_gmt DESC
			 LIMIT %d",
			$after,
			$count
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$ids = array_map( 'absint', (array) $ids );

	// Top up with recent posts when not enough articles have views yet.
	if ( count( $ids ) < $count ) {
		$fill = get_posts(
			array(
				'posts_per_page' => $count - count( $ids ),
				'post_status'    => 'publish',
				'post__not_in'   => $ids,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);
		$ids  = array_merge( $ids, array_map( 'absint', $fill ) );
	}

	set_transient( $cache_key, $ids, 5 * MINUTE_IN_SECONDS );

	return $ids;
}

/**
 * Drop the cached popular lists when posts change, so unpublished or deleted
 * articles can't linger in the list until the transient expires.
 *
 * @param int $post_id Post ID.
 */
function khobor_clear_popular_cache( $post_id ) {
	global $wpdb;

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( wp_using_ext_object_cache() ) {
		// Transients live in the object cache, not wp_options.
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'transient' );
		}
		return;
	}

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE '_transient_khobor_popular_%'
		    OR option_name LIKE '_transient_timeout_khobor_popular_%'"
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
add_action( 'save_post_post', 'khobor_clear_popular_cache' );
add_action( 'deleted_post', 'khobor_clear_popular_cache' );

/**
 * Make view count sortable in admin.
 */
function khobor_views_admin_column( array $columns ): array {
	$columns['khobor_views'] = __( 'Views', 'khobor' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'khobor_views_admin_column' );

function khobor_views_admin_column_content( string $column, int $post_id ): void {
	if ( 'khobor_views' === $column ) {
		echo esc_html( number_format_i18n( khobor_get_views( $post_id ) ) );
	}
}
add_action( 'manage_post_posts_custom_column', 'khobor_views_admin_column_content', 10, 2 );

function khobor_views_admin_column_sortable( array $columns ): array {
	$columns['khobor_views'] = 'khobor_views';
	return $columns;
}
add_filter( 'manage_edit-post_sortable_columns', 'khobor_views_admin_column_sortable' );

function khobor_views_admin_column_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'khobor_views' === $query->get( 'orderby' ) ) {
		$query->set( 'meta_key', '_khobor_views' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'khobor_views_admin_column_orderby' );
