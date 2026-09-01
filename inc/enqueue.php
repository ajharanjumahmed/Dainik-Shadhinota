<?php
/**
 * Asset Enqueuing
 *
 * Centralizes all CSS/JS loading. Defers non-critical JS, supports
 * conditional loading (only loads flipbook on e-paper pages, only
 * loads ticker JS on the homepage, etc.).
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end assets.
 */
function khobor_enqueue_assets() {

	// Bangla fonts. Hosted by Google Fonts; admin can override with a self-hosted copy via the customizer.
	$fonts_url = 'https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;500;600;700&display=swap';
	wp_enqueue_style( 'khobor-fonts', $fonts_url, array(), null );

	// Main stylesheet (theme header). WP loads this automatically only if no main css is enqueued; we explicitly enqueue our compiled main.css instead.
	wp_enqueue_style(
		'khobor-main',
		KHOBOR_ASSETS . 'css/main.css',
		array(),
		KHOBOR_VERSION
	);

	// RTL fallback (not required for Bangla but ships in case admin enables RTL).
	wp_style_add_data( 'khobor-main', 'rtl', 'replace' );

	// Main JS bundle.
	wp_enqueue_script(
		'khobor-main',
		KHOBOR_ASSETS . 'js/main.js',
		array(),
		KHOBOR_VERSION,
		true
	);

	// Pass server-side data to JS.
	wp_localize_script(
		'khobor-main',
		'KhoborData',
		array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'restUrl'        => esc_url_raw( rest_url() ),
			'nonce'          => wp_create_nonce( 'khobor_nonce' ),
			'restNonce'      => wp_create_nonce( 'wp_rest' ),
			'isSingular'     => is_singular() ? 1 : 0,
			'postId'         => is_singular() ? get_the_ID() : 0,
			'photocardSizes' => array( '1080x1080' ),
			'i18n'           => array(
				'loadMore'    => __( 'Load More', 'khobor' ),
				'loading'     => __( 'Loading…', 'khobor' ),
				'noMore'      => __( 'No more posts', 'khobor' ),
				'generating'  => __( 'Generating photocard…', 'khobor' ),
				'photocardOk' => __( 'Photocard ready', 'khobor' ),
				'photocardErr'=> __( 'Could not generate photocard', 'khobor' ),
			),
		)
	);

	// Ticker JS only where it appears (header on all pages, but conditionally).
	if ( khobor_option( 'enable_ticker', true ) ) {
		wp_enqueue_script(
			'khobor-ticker',
			KHOBOR_ASSETS . 'js/ticker.js',
			array( 'khobor-main' ),
			KHOBOR_VERSION,
			true
		);
	}

	// Font-size adjuster - only on singular content.
	if ( is_singular( array( 'post' ) ) ) {
		wp_enqueue_script(
			'khobor-font-size',
			KHOBOR_ASSETS . 'js/font-size.js',
			array(),
			KHOBOR_VERSION,
			true
		);

		// Photocard is rendered on a canvas in the browser: it is the only text
		// engine available here that shapes Bengali correctly.
		if ( khobor_option( 'enable_photocard', true ) ) {
			wp_enqueue_script(
				'khobor-photocard',
				KHOBOR_ASSETS . 'js/photocard.js',
				array( 'khobor-main' ),
				KHOBOR_VERSION,
				true
			);
		}
	}

	// E-Paper flipbook - only on epaper singular pages.
	if ( is_singular( 'epaper' ) ) {
		// PDF.js (local vendor copy).
		wp_enqueue_script(
			'khobor-pdfjs',
			KHOBOR_ASSETS . 'vendor/pdfjs/pdf.js',
			array(),
			'3.11.174',
			true
		);
		// StPageFlip (local vendor copy).
		wp_enqueue_script(
			'khobor-stpageflip',
			KHOBOR_ASSETS . 'vendor/stpageflip/page-flip.browser.js',
			array(),
			'2.0.7',
			true
		);
		// Our flipbook wiring.
		wp_enqueue_script(
			'khobor-flipbook',
			KHOBOR_ASSETS . 'js/flipbook.js',
			array( 'khobor-pdfjs', 'khobor-stpageflip' ),
			KHOBOR_VERSION,
			true
		);
		wp_localize_script(
			'khobor-flipbook',
			'KhoborFlipbook',
			array(
				'workerSrc' => KHOBOR_ASSETS . 'vendor/pdfjs/pdf.worker.js',
			)
		);
	}

	// Threaded comments (default WP behavior).
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'khobor_enqueue_assets' );

/**
 * Add async / defer to specific scripts. We defer all non-critical theme JS.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function khobor_defer_scripts( $tag, $handle ) {
	$defer_handles = array(
		'khobor-ticker',
		'khobor-font-size',
		'khobor-flipbook',
		'khobor-pdfjs',
		'khobor-stpageflip',
	);
	if ( in_array( $handle, $defer_handles, true ) ) {
		return str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'khobor_defer_scripts', 10, 2 );

/**
 * Preconnect to font CDN to shave a few ms off LCP.
 */
function khobor_resource_hints( $hints, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$hints[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
		$hints[] = 'https://fonts.googleapis.com';
	}
	return $hints;
}
add_filter( 'wp_resource_hints', 'khobor_resource_hints', 10, 2 );

/**
 * Admin assets (for the photocard overlay uploader and ad zones admin pages).
 *
 * @param string $hook Current admin page hook.
 */
function khobor_admin_assets( $hook ) {
	$screen     = get_current_screen();
	$is_epaper  = $screen && 'epaper' === $screen->post_type;

	// Load on theme admin screens and on the E-Paper post editor.
	if ( false === strpos( $hook, 'khobor' ) && ! $is_epaper ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style(
		'khobor-admin',
		KHOBOR_ASSETS . 'css/admin.css',
		array(),
		KHOBOR_VERSION
	);
	wp_enqueue_script(
		'khobor-admin',
		KHOBOR_ASSETS . 'js/admin.js',
		array( 'jquery' ),
		KHOBOR_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'khobor_admin_assets' );
