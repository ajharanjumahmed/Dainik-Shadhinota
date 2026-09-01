<?php
/**
 * Khobor Theme - Functions and Bootstrap
 *
 * This file is intentionally thin. It only loads modular include files
 * from the inc/ directory. All real logic lives in those files.
 *
 * @package Khobor
 * @since   1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Theme constants.
define( 'KHOBOR_VERSION', '1.0.1' );
define( 'KHOBOR_DIR', trailingslashit( get_template_directory() ) );
define( 'KHOBOR_URI', trailingslashit( get_template_directory_uri() ) );
define( 'KHOBOR_INC', KHOBOR_DIR . 'inc/' );
define( 'KHOBOR_ASSETS', KHOBOR_URI . 'assets/' );

/**
 * Load a modular include file from inc/.
 *
 * @param string $file File name relative to inc/, without extension.
 */
function khobor_require( $file ) {
	$path = KHOBOR_INC . $file . '.php';
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

/*
 * Modular includes. Order matters: helpers and setup first,
 * then features that depend on them.
 */
$khobor_modules = array(
	'helpers',            // Generic helper functions used everywhere.
	'theme-setup',        // after_setup_theme: supports, image sizes, menus.
	'security',           // Hardening: disable XML-RPC option, remove version, etc.
	'enqueue',            // Front-end CSS/JS enqueuing.
	'menus',              // Nav menu walker and mega-menu logic.
	'sidebars',           // Register sidebars.
	'widgets',            // Register custom widgets.
	'customizer',         // Customizer settings: colors, logo, layout.
	'cpt-epaper',         // Custom Post Type: E-paper.
	'view-counter',       // Native post view counting for "Most Read".
	'reading-time',       // Auto-estimate reading time.
	'bangla-numbers',     // Convert digits to Bangla numerals.
	'breaking-news',      // Breaking news ticker data source.
	'photocard',          // Server-side photocard generator (Imagick).
	'ad-zones',           // Custom ad zone manager.
	'schema',             // NewsArticle + Breadcrumb JSON-LD.
	'seo',                // SEO meta defaults (compat with Yoast / RankMath).
	'image-optimization', // srcset, picture/WebP wiring, lazy loading.
	'template-tags',      // Reusable template tag functions.
	'ajax-handlers',      // AJAX category filter, infinite scroll, view ping.
	'prayer-times',       // Prayer times widget logic and cron.
	'elementor-compat',   // Elementor widget registration.
	'gutenberg-blocks',   // Gutenberg block registration.
);

foreach ( $khobor_modules as $khobor_module ) {
	khobor_require( $khobor_module );
}

// Custom widget classes.
$khobor_widget_files = array(
	'widget-popular-posts',
	'widget-latest-posts',
	'widget-prayer-times',
	'widget-category-posts',
	'widget-ad-zone',
);

foreach ( $khobor_widget_files as $khobor_widget_file ) {
	$widget_path = KHOBOR_DIR . 'widgets/' . $khobor_widget_file . '.php';
	if ( file_exists( $widget_path ) ) {
		require_once $widget_path;
	}
}
