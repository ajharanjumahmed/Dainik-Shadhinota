<?php
/**
 * Basic SEO Defaults
 *
 * Only fires if no major SEO plugin is active. Output: meta description,
 * Open Graph, Twitter Card. Robust enough to give the theme decent SEO
 * out of the box; SEO plugins will replace these automatically.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output meta tags in <head>.
 */
function khobor_meta_tags() {
	if ( khobor_seo_plugin_active() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = '';
	$url   = '';
	$image = '';
	$type  = 'website';

	if ( is_singular() ) {
		$post  = get_queried_object();
		$desc  = khobor_excerpt( $post, 160 );
		$url   = get_permalink( $post );
		$type  = is_singular( 'post' ) ? 'article' : 'website';

		if ( has_post_thumbnail( $post ) ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'khobor-hero' );
			if ( $src ) {
				$image = $src[0];
			}
		}
	} elseif ( is_category() || is_tag() ) {
		$term = get_queried_object();
		$desc = term_description( $term );
		if ( ! $desc ) {
			/* translators: %s: term name */
			$desc = sprintf( __( 'সব %s সংক্রান্ত খবর।', 'khobor' ), $term->name );
		}
		$desc = wp_strip_all_tags( $desc );
		$url  = get_term_link( $term );
	} else {
		$desc = get_bloginfo( 'description' );
		$url  = home_url( '/' );
	}

	if ( empty( $image ) ) {
		$image = khobor_publisher_logo_url();
	}

	echo "\n<!-- Khobor SEO -->\n";
	if ( $desc ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( get_locale() ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	}
	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
	}

	// Article-specific.
	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c', $post ) ) );
		printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c', $post ) ) );
		$cat = khobor_primary_category( $post );
		if ( $cat ) {
			printf( '<meta property="article:section" content="%s">' . "\n", esc_attr( $cat->name ) );
		}
	}

	// Canonical.
	if ( $url ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}
	echo "<!-- /Khobor SEO -->\n";
}
add_action( 'wp_head', 'khobor_meta_tags', 4 );
