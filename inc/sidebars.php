<?php
/**
 * Sidebar / Widget Area Registration
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function khobor_register_sidebars() {

	register_sidebar(
		array(
			'name'          => __( 'Primary Sidebar', 'khobor' ),
			'id'            => 'sidebar-primary',
			'description'   => __( 'Appears on posts, archives, and single pages.', 'khobor' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s khobor-widget">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title khobor-widget__title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Homepage Top', 'khobor' ),
			'id'            => 'home-top',
			'description'   => __( 'Full-width row directly under the header on the homepage.', 'khobor' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s khobor-home-top__widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="khobor-section-title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Homepage Bottom', 'khobor' ),
			'id'            => 'home-bottom',
			'description'   => __( 'Above the footer on the homepage.', 'khobor' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s khobor-home-bottom__widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="khobor-section-title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Homepage Sections', 'khobor' ),
			'id'            => 'home-sections',
			'description'   => __( 'Category post sections below the lead news on the homepage. Add "Khobor: Category Posts" widgets here and reorder them freely.', 'khobor' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '',
			'after_title'   => '',
		)
	);

	// Footer columns.
	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: Footer column number. */
				'name'          => sprintf( __( 'Footer Column %d', 'khobor' ), $i ),
				'id'            => 'footer-' . $i,
				'before_widget' => '<section id="%1$s" class="widget %2$s khobor-footer__widget">',
				'after_widget'  => '</section>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			)
		);
	}
}
add_action( 'widgets_init', 'khobor_register_sidebars' );
