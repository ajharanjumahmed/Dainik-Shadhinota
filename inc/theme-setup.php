<?php
/**
 * Theme Setup
 *
 * Registers theme supports, image sizes, menus, and i18n.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme supports and core features.
 */
function khobor_setup() {

	// i18n: load translation files from /languages.
	load_theme_textdomain( 'khobor', KHOBOR_DIR . 'languages' );

	// Title tag managed by WP.
	add_theme_support( 'title-tag' );

	// Featured images on posts and pages.
	add_theme_support( 'post-thumbnails' );

	// HTML5 markup output.
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	// Custom logo support.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// Automatic feed links in <head>.
	add_theme_support( 'automatic-feed-links' );

	// Editor styles (Gutenberg).
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	// Block features.
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );

	// Selective refresh for widgets in Customizer.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Custom image sizes optimized for news layouts.
	// Naming convention: khobor-{purpose}.
	add_image_size( 'khobor-card', 500, 280, true );           // Card thumbnails (matches reference site).
	add_image_size( 'khobor-card-sm', 320, 180, true );        // Small list thumbnails.
	add_image_size( 'khobor-card-lg', 800, 450, true );        // Featured/lead card.
	add_image_size( 'khobor-hero', 1200, 675, true );          // Single post hero.
	add_image_size( 'khobor-photocard', 1080, 1080, true );    // Square crop for photocard generator.
	add_image_size( 'khobor-thumb-sq', 120, 120, true );       // Square mini thumbnail (most-read).

	// Make custom sizes selectable in the editor's image-size dropdown.
	add_filter(
		'image_size_names_choose',
		function ( $sizes ) {
			return array_merge(
				$sizes,
				array(
					'khobor-card'    => __( 'News Card', 'khobor' ),
					'khobor-card-lg' => __( 'News Card (Large)', 'khobor' ),
					'khobor-hero'    => __( 'Article Hero', 'khobor' ),
				)
			);
		}
	);

	// Register nav menu locations.
	register_nav_menus(
		array(
			'primary'   => __( 'Primary Menu (Main Nav)', 'khobor' ),
			'top'       => __( 'Top Bar Menu', 'khobor' ),
			'footer'    => __( 'Footer Menu', 'khobor' ),
			'mobile'    => __( 'Mobile Menu (optional)', 'khobor' ),
		)
	);
}
add_action( 'after_setup_theme', 'khobor_setup' );

/**
 * Set the content width.
 */
function khobor_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'khobor_content_width', 800 );
}
add_action( 'after_setup_theme', 'khobor_content_width', 0 );

/**
 * Flush rewrite rules when the theme is activated so the epaper CPT URLs
 * resolve immediately without requiring a manual Permalinks save.
 */
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

/**
 * Pingback header for posts.
 */
function khobor_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'khobor_pingback_header' );
