<?php
/**
 * Generic Helper Functions
 *
 * Small, broadly useful utilities used across the theme.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safely get a post excerpt of a given character length.
 * Strips shortcodes, tags, and entities.
 *
 * @param int|WP_Post $post   Post ID or object.
 * @param int         $length Character length. Default 120.
 * @return string Trimmed excerpt.
 */
function khobor_excerpt( $post = null, $length = 120 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$raw = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
	$raw = wp_strip_all_tags( strip_shortcodes( $raw ) );
	$raw = preg_replace( '/\s+/u', ' ', $raw );
	$raw = trim( $raw );

	if ( 0 === $length || mb_strlen( $raw ) <= $length ) {
		return $raw;
	}

	return mb_substr( $raw, 0, $length ) . '…';
}

/**
 * Get a post's primary category. Uses Yoast / RankMath primary if available,
 * otherwise falls back to the first assigned category.
 *
 * @param int|WP_Post $post Post.
 * @return WP_Term|null
 */
function khobor_primary_category( $post = null ) {
	$post_id = get_post_field( 'ID', get_post( $post ) );
	if ( ! $post_id ) {
		return null;
	}

	// Yoast SEO.
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$primary = new WPSEO_Primary_Term( 'category', $post_id );
		$term_id = $primary->get_primary_term();
		if ( $term_id ) {
			$term = get_term( $term_id, 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}
	}

	// RankMath.
	$rm_primary = get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( $rm_primary ) {
		$term = get_term( (int) $rm_primary, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
	}

	$cats = get_the_category( $post_id );
	if ( ! empty( $cats ) ) {
		return $cats[0];
	}
	return null;
}

/**
 * Output a placeholder image URL for posts without thumbnails.
 *
 * @return string
 */
function khobor_placeholder_image_url() {
	return KHOBOR_ASSETS . 'img/placeholder.png';
}

/**
 * Print a featured image with safe fallback to placeholder.
 *
 * @param int|WP_Post $post Post.
 * @param string      $size Image size.
 * @param array       $attr Attributes for the img tag.
 */
function khobor_post_thumbnail( $post = null, $size = 'khobor-card', $attr = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}

	$defaults = array(
		'class'   => 'khobor-thumb',
		'loading' => 'lazy',
		'alt'     => the_title_attribute( array( 'echo' => false, 'post' => $post ) ),
	);
	$attr = wp_parse_args( $attr, $defaults );

	if ( has_post_thumbnail( $post ) ) {
		echo get_the_post_thumbnail( $post, $size, $attr );
	} else {
		printf(
			'<img src="%1$s" alt="%2$s" class="%3$s" loading="lazy" decoding="async" />',
			esc_url( khobor_placeholder_image_url() ),
			esc_attr( $attr['alt'] ),
			esc_attr( $attr['class'] . ' khobor-thumb--placeholder' )
		);
	}
}

/**
 * Get categories that are actually in use (have posts).
 * Used by dynamic homepage section builders so they auto-pick
 * whatever categories the admin set up.
 *
 * @param int $limit Max categories.
 * @return WP_Term[]
 */
function khobor_active_categories( $limit = 6 ) {
	$cache_key = 'khobor_active_categories_' . (int) $limit;
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'number'     => $limit,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( is_wp_error( $terms ) ) {
		$terms = array();
	}

	set_transient( $cache_key, $terms, HOUR_IN_SECONDS );
	return $terms;
}

/**
 * Sanitize a hex color, falling back to default if invalid.
 *
 * @param string $color   Input color.
 * @param string $default Default fallback.
 * @return string
 */
function khobor_sanitize_hex( $color, $default = '#c8102e' ) {
	$color = trim( (string) $color );
	if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
		return $color;
	}
	return $default;
}

/**
 * Get a Customizer setting with a default.
 *
 * @param string $key Setting key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function khobor_option( $key, $default = '' ) {
	return get_theme_mod( 'khobor_' . $key, $default );
}
