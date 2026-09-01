<?php
/**
 * Reading Time Estimator
 *
 * Estimates based on word count. Bangla averages roughly the same WPM
 * as English for native readers (~200-250 wpm); we use 200 as a sane default.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimate reading time in whole minutes.
 *
 * @param int|WP_Post $post Post.
 * @param int         $wpm  Words per minute. Default 200.
 * @return int Minutes (min 1).
 */
function khobor_reading_time( $post = null, $wpm = 200 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$cached = get_post_meta( $post->ID, '_khobor_reading_time', true );
	if ( '' !== $cached ) {
		return max( 1, (int) $cached );
	}

	$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

	// Count words for both Latin and Bangla scripts.
	// preg_split with unicode word boundary handles Bangla decently.
	$tokens = preg_split( '/[\s\p{P}]+/u', $content, -1, PREG_SPLIT_NO_EMPTY );
	$words  = is_array( $tokens ) ? count( $tokens ) : 0;

	$minutes = max( 1, (int) ceil( $words / max( 60, $wpm ) ) );
	update_post_meta( $post->ID, '_khobor_reading_time', $minutes );
	return $minutes;
}

/**
 * Recalculate reading time on post save.
 *
 * @param int $post_id Post ID.
 */
function khobor_recalculate_reading_time( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	delete_post_meta( $post_id, '_khobor_reading_time' );
	khobor_reading_time( $post_id );
}
add_action( 'save_post', 'khobor_recalculate_reading_time' );

/**
 * Template tag: print reading time label.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_the_reading_time( $post = null ) {
	if ( ! khobor_option( 'enable_reading_time', true ) ) {
		return;
	}
	$minutes = khobor_reading_time( $post );
	$display = khobor_option( 'enable_bangla_nums', true ) ? khobor_to_bangla_numbers( $minutes ) : $minutes;
	/* translators: %s: number of minutes. */
	printf(
		'<span class="khobor-reading-time"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> %s</span>',
		esc_html( sprintf( __( '%s min read', 'khobor' ), $display ) )
	);
}
