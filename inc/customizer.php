<?php
/**
 * Theme Customizer
 *
 * All admin-configurable theme settings: colors, logo, layout,
 * photocard overlay, ad zones, prayer times city, and feature toggles.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function khobor_customize_register( $wp_customize ) {

	// =====================================================================
	// Khobor Panel.
	// =====================================================================
	$wp_customize->add_panel(
		'khobor_panel',
		array(
			'title'       => __( 'Khobor Theme', 'khobor' ),
			'description' => __( 'All Khobor-specific settings live here.', 'khobor' ),
			'priority'    => 30,
		)
	);

	// ---------- Section: Brand & Colors ----------.
	$wp_customize->add_section(
		'khobor_colors',
		array(
			'title' => __( 'Brand Colors', 'khobor' ),
			'panel' => 'khobor_panel',
		)
	);

	$colors = array(
		'primary'   => array( __( 'Primary (header, links)', 'khobor' ),       '#c8102e' ),
		'secondary' => array( __( 'Secondary (accents)', 'khobor' ),           '#1a1a1a' ),
		'accent'    => array( __( 'Accent (buttons, badges)', 'khobor' ),      '#f7b500' ),
		'text'      => array( __( 'Body text', 'khobor' ),                     '#222222' ),
		'muted'     => array( __( 'Muted text', 'khobor' ),                    '#6b7280' ),
		'bg'        => array( __( 'Page background', 'khobor' ),               '#ffffff' ),
		'surface'   => array( __( 'Card / panel background', 'khobor' ),       '#f8fafc' ),
	);
	foreach ( $colors as $key => $cfg ) {
		$wp_customize->add_setting(
			'khobor_color_' . $key,
			array(
				'default'           => $cfg[1],
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'khobor_color_' . $key,
				array(
					'label'   => $cfg[0],
					'section' => 'khobor_colors',
				)
			)
		);
	}

	// ---------- Section: Layout & Features ----------.
	$wp_customize->add_section(
		'khobor_layout',
		array(
			'title' => __( 'Layout & Features', 'khobor' ),
			'panel' => 'khobor_panel',
		)
	);

	$toggles = array(
		'enable_ticker'      => array( __( 'Show breaking news ticker', 'khobor' ),    true ),
		'enable_dark_mode'   => array( __( 'Enable dark mode toggle', 'khobor' ),      true ),
		'enable_font_sizer'  => array( __( 'Show font size adjuster (A− A A+)', 'khobor' ), true ),
		'enable_reading_time'=> array( __( 'Show reading time on articles', 'khobor' ), true ),
		'enable_photocard'   => array( __( 'Show photocard button on articles', 'khobor' ), true ),
		'enable_related'     => array( __( 'Show related posts on articles', 'khobor' ), true ),
		'enable_author_bio'  => array( __( 'Show author bio on articles', 'khobor' ),  true ),
		'enable_fb_comments' => array( __( 'Use Facebook Comments instead of native', 'khobor' ), false ),
		'enable_bangla_nums' => array( __( 'Convert digits to Bangla numerals', 'khobor' ), true ),
		'disable_xmlrpc'     => array( __( 'Disable XML-RPC (security)', 'khobor' ),   false ),
	);
	foreach ( $toggles as $key => $cfg ) {
		$wp_customize->add_setting(
			'khobor_' . $key,
			array(
				'default'           => $cfg[1],
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
		$wp_customize->add_control(
			'khobor_' . $key,
			array(
				'label'   => $cfg[0],
				'section' => 'khobor_layout',
				'type'    => 'checkbox',
			)
		);
	}

	// Sidebar layout.
	$wp_customize->add_setting(
		'khobor_sidebar_layout',
		array(
			'default'           => 'right',
			'sanitize_callback' => function ( $v ) {
				return in_array( $v, array( 'right', 'left', 'none' ), true ) ? $v : 'right';
			},
		)
	);
	$wp_customize->add_control(
		'khobor_sidebar_layout',
		array(
			'label'   => __( 'Sidebar position', 'khobor' ),
			'section' => 'khobor_layout',
			'type'    => 'select',
			'choices' => array(
				'right' => __( 'Right sidebar', 'khobor' ),
				'left'  => __( 'Left sidebar', 'khobor' ),
				'none'  => __( 'No sidebar (full width)', 'khobor' ),
			),
		)
	);

	// ---------- Section: Photocard ----------.
	$wp_customize->add_section(
		'khobor_photocard',
		array(
			'title' => __( 'Photocard Generator', 'khobor' ),
			'panel' => 'khobor_panel',
			'description' => __( 'Default overlay placed on top of the featured image. Recommended size: 1080×1080 PNG with transparency.', 'khobor' ),
		)
	);
	$wp_customize->add_setting(
		'khobor_photocard_overlay',
		array(
			'default'           => KHOBOR_ASSETS . 'img/photocard-default-overlay.png',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'khobor_photocard_overlay',
			array(
				'label'   => __( 'Overlay image (1080×1080 PNG)', 'khobor' ),
				'section' => 'khobor_photocard',
			)
		)
	);
	$wp_customize->add_setting(
		'khobor_photocard_title_color',
		array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'khobor_photocard_title_color',
			array(
				'label'   => __( 'Title color', 'khobor' ),
				'section' => 'khobor_photocard',
			)
		)
	);

	// ---------- Section: Prayer Times ----------.
	$wp_customize->add_section(
		'khobor_prayer',
		array(
			'title' => __( 'Prayer Times', 'khobor' ),
			'panel' => 'khobor_panel',
			'description' => __( 'Used by the Prayer Times widget. Free public API: aladhan.com.', 'khobor' ),
		)
	);
	$wp_customize->add_setting(
		'khobor_prayer_city',
		array(
			'default'           => 'Dhaka',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'khobor_prayer_city',
		array(
			'label'   => __( 'City', 'khobor' ),
			'section' => 'khobor_prayer',
		)
	);
	$wp_customize->add_setting(
		'khobor_prayer_country',
		array(
			'default'           => 'Bangladesh',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'khobor_prayer_country',
		array(
			'label'   => __( 'Country', 'khobor' ),
			'section' => 'khobor_prayer',
		)
	);
	$wp_customize->add_setting(
		'khobor_prayer_method',
		array(
			'default'           => 1,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		'khobor_prayer_method',
		array(
			'label'   => __( 'Calculation method (Aladhan API)', 'khobor' ),
			'section' => 'khobor_prayer',
			'type'    => 'select',
			'choices' => array(
				1 => 'University of Islamic Sciences, Karachi',
				2 => 'Islamic Society of North America',
				3 => 'Muslim World League',
				4 => 'Umm Al-Qura, Makkah',
				5 => 'Egyptian General Authority',
			),
		)
	);

	// ---------- Section: Social ----------.
	$wp_customize->add_section(
		'khobor_social',
		array(
			'title' => __( 'Social Links', 'khobor' ),
			'panel' => 'khobor_panel',
		)
	);
	$socials = array( 'facebook', 'twitter', 'youtube', 'instagram', 'linkedin' );
	foreach ( $socials as $s ) {
		$wp_customize->add_setting(
			'khobor_social_' . $s,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'khobor_social_' . $s,
			array(
				'label'   => ucfirst( $s ),
				'section' => 'khobor_social',
				'type'    => 'url',
			)
		);
	}
}
add_action( 'customize_register', 'khobor_customize_register' );

/**
 * Output Customizer color choices as CSS variables in <head>.
 */
function khobor_inline_css_vars() {
	$primary   = khobor_sanitize_hex( khobor_option( 'color_primary',   '#c8102e' ), '#c8102e' );
	$secondary = khobor_sanitize_hex( khobor_option( 'color_secondary', '#1a1a1a' ), '#1a1a1a' );
	$accent    = khobor_sanitize_hex( khobor_option( 'color_accent',    '#f7b500' ), '#f7b500' );
	$text      = khobor_sanitize_hex( khobor_option( 'color_text',      '#222222' ), '#222222' );
	$muted     = khobor_sanitize_hex( khobor_option( 'color_muted',     '#6b7280' ), '#6b7280' );
	$bg        = khobor_sanitize_hex( khobor_option( 'color_bg',        '#ffffff' ), '#ffffff' );
	$surface   = khobor_sanitize_hex( khobor_option( 'color_surface',   '#f8fafc' ), '#f8fafc' );

	$css = sprintf(
		':root{--khobor-primary:%s;--khobor-secondary:%s;--khobor-accent:%s;--khobor-text:%s;--khobor-muted:%s;--khobor-bg:%s;--khobor-surface:%s;}',
		$primary, $secondary, $accent, $text, $muted, $bg, $surface
	);
	printf( '<style id="khobor-vars">%s</style>', $css );
}
add_action( 'wp_head', 'khobor_inline_css_vars', 5 );

/**
 * Live preview JS.
 */
function khobor_customize_preview_js() {
	wp_enqueue_script(
		'khobor-customize-preview',
		KHOBOR_ASSETS . 'js/admin.js',
		array( 'customize-preview', 'jquery' ),
		KHOBOR_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'khobor_customize_preview_js' );
