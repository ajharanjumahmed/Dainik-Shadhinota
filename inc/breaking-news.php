<?php
/**
 * Breaking News Ticker
 *
 * Source: posts in a category slug 'breaking' if it exists,
 * otherwise the N latest posts. Cached for 5 minutes.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get breaking news items.
 *
 * @param int $limit Max items.
 * @return array Array of ['title' => string, 'url' => string].
 */
function khobor_get_breaking_news( $limit = 10 ) {
	$cache_key = 'khobor_breaking_' . (int) $limit;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$args = array(
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
		'fields'         => 'ids',
	);

	// Prefer "breaking" category if admin set one up.
	$breaking_cat = get_term_by( 'slug', 'breaking', 'category' );
	if ( $breaking_cat && ! is_wp_error( $breaking_cat ) ) {
		$args['cat'] = $breaking_cat->term_id;
	}

	$ids   = get_posts( $args );
	$items = array();
	foreach ( $ids as $id ) {
		$items[] = array(
			'title' => get_the_title( $id ),
			'url'   => get_permalink( $id ),
		);
	}

	set_transient( $cache_key, $items, 5 * MINUTE_IN_SECONDS );
	return $items;
}

/**
 * Render the ticker markup.
 */
function khobor_render_breaking_ticker() {
	if ( ! khobor_option( 'enable_ticker', true ) ) {
		return;
	}
	$items = khobor_get_breaking_news( 10 );
	if ( empty( $items ) ) {
		return;
	}
	?>
	<div class="khobor-ticker" role="region" aria-label="<?php esc_attr_e( 'Breaking news', 'khobor' ); ?>">
		<span class="khobor-ticker__label"><?php esc_html_e( 'সর্বশেষ', 'khobor' ); ?></span>
		<div class="khobor-ticker__viewport">
			<ul class="khobor-ticker__track" id="khobor-ticker-track">
				<?php foreach ( $items as $item ) : ?>
					<li class="khobor-ticker__item">
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<button class="khobor-ticker__pause" type="button" aria-label="<?php esc_attr_e( 'Pause ticker', 'khobor' ); ?>">⏸</button>
	</div>
	<?php
}

/**
 * REST endpoint to fetch breaking news (used by JS auto-refresh).
 */
function khobor_register_breaking_endpoint() {
	register_rest_route(
		'khobor/v1',
		'/breaking',
		array(
			'methods'             => 'GET',
			'callback'            => function () {
				return rest_ensure_response( khobor_get_breaking_news( 10 ) );
			},
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'khobor_register_breaking_endpoint' );

/**
 * Invalidate the cache when posts change.
 *
 * @param int $post_id Post ID.
 */
function khobor_breaking_clear_cache( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'khobor_breaking_10' );
}
add_action( 'save_post_post', 'khobor_breaking_clear_cache' );
add_action( 'deleted_post', 'khobor_breaking_clear_cache' );
