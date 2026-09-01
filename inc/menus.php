<?php
/**
 * Navigation Menus
 *
 * Custom walker outputs accessible, ARIA-compliant dropdown menus
 * that work without JavaScript (CSS hover) but enhance with JS for
 * mobile and keyboard users.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom nav walker for the primary menu.
 */
class Khobor_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Start menu level.
	 *
	 * @param string   $output Output.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent  = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class=\"sub-menu khobor-submenu\" role=\"menu\">\n";
	}

	/**
	 * Start each menu element.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 * @param int      $id     Menu ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$indent  = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$has_children = in_array( 'menu-item-has-children', $classes, true );
		if ( $has_children ) {
			$classes[] = 'has-submenu';
		}

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names . ' role="none">';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target )     . '"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn )        . '"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_url( $item->url )         . '"' : '';
		$attributes .= ' role="menuitem"';

		if ( $has_children ) {
			$attributes .= ' aria-haspopup="true" aria-expanded="false"';
		}

		$title       = apply_filters( 'the_title', $item->title, $item->ID );
		$title       = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$item_output  = isset( $args->before ) ? $args->before : '';
		$item_output .= '<a' . $attributes . '>';
		$item_output .= ( isset( $args->link_before ) ? $args->link_before : '' ) . $title . ( isset( $args->link_after ) ? $args->link_after : '' );

		if ( $has_children && 0 === $depth ) {
			$item_output .= ' <span class="khobor-caret" aria-hidden="true">▾</span>';
		}

		$item_output .= '</a>';
		$item_output .= isset( $args->after ) ? $args->after : '';

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

/**
 * Helper to render the primary menu with our walker.
 *
 * @param string $location Menu location.
 * @param array  $args     Extra args.
 */
function khobor_render_menu( $location = 'primary', $args = array() ) {
	if ( ! has_nav_menu( $location ) ) {
		echo '<ul class="khobor-nav khobor-nav--fallback"><li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Set up a menu', 'khobor' ) . '</a></li></ul>';
		return;
	}

	$defaults = array(
		'theme_location'  => $location,
		'container'       => false,
		'menu_class'      => 'khobor-nav',
		'fallback_cb'     => false,
		'walker'          => new Khobor_Nav_Walker(),
		'depth'           => 3,
	);
	wp_nav_menu( wp_parse_args( $args, $defaults ) );
}
