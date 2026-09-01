<?php
/**
 * AJAX Handlers
 *
 * Infinite scroll and category filtering on archive pages.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint: GET /khobor/v1/posts?cat=&paged=&tag=&search=
 */
function khobor_register_posts_endpoint() {
	register_rest_route(
		'khobor/v1',
		'/posts',
		array(
			'methods'             => 'GET',
			'callback'            => 'khobor_rest_get_posts',
			'permission_callback' => '__return_true',
			'args'                => array(
				'cat'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'tag'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'search' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'paged'  => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
				'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 10 ),
			),
		)
	);
}
add_action( 'rest_api_init', 'khobor_register_posts_endpoint' );

/**
 * Posts endpoint handler. Returns rendered HTML cards + meta info.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function khobor_rest_get_posts( WP_REST_Request $req ) {
	$args = array(
		'post_status'    => 'publish',
		'posts_per_page' => min( 20, $req->get_param( 'per_page' ) ),
		'paged'          => max( 1, (int) $req->get_param( 'paged' ) ),
	);

	$cat = $req->get_param( 'cat' );
	if ( $cat ) {
		$args['category_name'] = $cat;
	}
	$tag = $req->get_param( 'tag' );
	if ( $tag ) {
		$args['tag'] = $tag;
	}
	$search = $req->get_param( 'search' );
	if ( $search ) {
		$args['s'] = $search;
	}

	$query = new WP_Query( $args );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/content/content-card' );
		}
	}
	$html = ob_get_clean();
	wp_reset_postdata();

	return rest_ensure_response(
		array(
			'html'        => $html,
			'found'       => (int) $query->found_posts,
			'page'        => (int) $args['paged'],
			'total_pages' => (int) $query->max_num_pages,
			'has_more'    => ( $args['paged'] < $query->max_num_pages ),
		)
	);
}
