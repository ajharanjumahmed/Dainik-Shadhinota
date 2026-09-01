<?php
/**
 * Elementor Compatibility
 *
 * Registers a custom widget category and provides hooks for adding
 * Khobor-branded widgets. Actual widget classes are conditionally
 * required only when Elementor is active.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bail early if Elementor isn't active.
 */
function khobor_elementor_active() {
	return did_action( 'elementor/loaded' );
}

/**
 * Add a "Khobor" widget category in the Elementor sidebar.
 *
 * @param object $elements_manager Elementor manager.
 */
function khobor_add_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'khobor',
		array(
			'title' => __( 'Khobor News', 'khobor' ),
			'icon'  => 'fa fa-newspaper-o',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'khobor_add_elementor_category' );

/**
 * Register Elementor widgets shipped with the theme.
 * Widgets live in /widgets/elementor/ and extend \Elementor\Widget_Base.
 *
 * @param object $widgets_manager Widget manager.
 */
function khobor_register_elementor_widgets( $widgets_manager ) {
	$dir = KHOBOR_DIR . 'widgets/elementor/';
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$files = glob( $dir . '*.php' );
	if ( ! $files ) {
		return;
	}
	foreach ( $files as $f ) {
		require_once $f;
	}
	// Widgets self-register in their files using $widgets_manager->register().
	do_action( 'khobor_register_elementor_widgets', $widgets_manager );
}
add_action( 'elementor/widgets/register', 'khobor_register_elementor_widgets' );

/**
 * Tell Elementor which post types are editable.
 *
 * @param array $cpts CPTs.
 * @return array
 */
function khobor_elementor_cpt_support( $cpts ) {
	$cpts[] = 'epaper';
	return $cpts;
}
add_filter( 'elementor/utils/get_public_post_types', 'khobor_elementor_cpt_support' );
