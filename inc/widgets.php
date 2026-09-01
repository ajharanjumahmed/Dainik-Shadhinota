<?php
/**
 * Custom Widget Registration
 *
 * The actual widget classes live in /widgets/. Here we just register them.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function khobor_register_widgets() {
	if ( class_exists( 'Khobor_Widget_Popular_Posts' ) ) {
		register_widget( 'Khobor_Widget_Popular_Posts' );
	}
	if ( class_exists( 'Khobor_Widget_Latest_Posts' ) ) {
		register_widget( 'Khobor_Widget_Latest_Posts' );
	}
	if ( class_exists( 'Khobor_Widget_Prayer_Times' ) ) {
		register_widget( 'Khobor_Widget_Prayer_Times' );
	}
	if ( class_exists( 'Khobor_Widget_Category_Posts' ) ) {
		register_widget( 'Khobor_Widget_Category_Posts' );
	}
	if ( class_exists( 'Khobor_Widget_Ad_Zone' ) ) {
		register_widget( 'Khobor_Widget_Ad_Zone' );
	}
}
add_action( 'widgets_init', 'khobor_register_widgets' );
