<?php
/**
 * Image Optimization (Theme Layer)
 *
 * Theme handles: native lazy-loading, srcset, async decoding,
 * <picture> source tags for WebP if available alongside originals.
 *
 * Actual compression / WebP conversion is the job of a plugin like
 * ShortPixel, Imagify, Smush, or LiteSpeed. The theme doesn't try to
 * do it — that's what the WordPress.org review process recommends.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force loading="lazy" and decoding="async" on every <img> in content.
 *
 * @param array $attr Existing attributes.
 * @return array
 */
function khobor_image_attrs( $attr ) {
	if ( empty( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'khobor_image_attrs', 10, 1 );

/**
 * Eager-load the first image on a single article (LCP win) and lazy-load the rest.
 *
 * @param string $content Content.
 * @return string
 */
function khobor_eager_first_image( $content ) {
	if ( ! is_singular() ) {
		return $content;
	}
	$found = false;
	return preg_replace_callback(
		'/<img\b[^>]*>/i',
		function ( $m ) use ( &$found ) {
			$tag = $m[0];
			if ( ! $found ) {
				$found = true;
				$tag   = preg_replace( '/\sloading="lazy"/', ' loading="eager" fetchpriority="high"', $tag );
				if ( false === strpos( $tag, 'loading=' ) ) {
					$tag = str_replace( '<img', '<img loading="eager" fetchpriority="high"', $tag );
				}
			}
			return $tag;
		},
		$content
	);
}
add_filter( 'the_content', 'khobor_eager_first_image', 99 );

/**
 * Wrap content images in <picture> with a WebP <source> if a sibling
 * .webp file exists on disk. Works with ShortPixel / Imagify which
 * write {basename}.webp next to the original.
 *
 * @param string $content Content.
 * @return string
 */
function khobor_wrap_images_with_webp( $content ) {
	if ( is_admin() || is_feed() ) {
		return $content;
	}
	$uploads = wp_get_upload_dir();
	$basedir = $uploads['basedir'];
	$baseurl = $uploads['baseurl'];

	return preg_replace_callback(
		'/<img\b([^>]*)\ssrc="([^"]+)"([^>]*)>/i',
		function ( $m ) use ( $basedir, $baseurl ) {
			$before = $m[1];
			$src    = $m[2];
			$after  = $m[3];

			// Only act on uploads in this site.
			if ( 0 !== strpos( $src, $baseurl ) ) {
				return $m[0];
			}

			$ext = pathinfo( parse_url( $src, PHP_URL_PATH ), PATHINFO_EXTENSION );
			if ( ! in_array( strtolower( $ext ), array( 'jpg', 'jpeg', 'png' ), true ) ) {
				return $m[0];
			}

			$webp_src  = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $src );
			$webp_path = str_replace( $baseurl, $basedir, $webp_src );

			if ( ! file_exists( $webp_path ) ) {
				return $m[0]; // Plugin hasn't produced a WebP. Leave as-is.
			}

			$img = '<img' . $before . ' src="' . esc_url( $src ) . '"' . $after . '>';
			return '<picture><source srcset="' . esc_url( $webp_src ) . '" type="image/webp">' . $img . '</picture>';
		},
		$content
	);
}
add_filter( 'the_content', 'khobor_wrap_images_with_webp', 100 );

/**
 * Preload the LCP image (featured image) on singular posts to improve LCP.
 */
function khobor_preload_featured_image() {
	if ( ! is_singular() || ! has_post_thumbnail() ) {
		return;
	}
	$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'khobor-hero' );
	if ( ! $src ) {
		return;
	}
	printf(
		'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
		esc_url( $src[0] )
	);
}
add_action( 'wp_head', 'khobor_preload_featured_image', 6 );
