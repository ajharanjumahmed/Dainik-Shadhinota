<?php
/**
 * Section: Lead news (homepage hero block).
 *
 * Layout: 1 big lead post on the left (category: "main lead news"),
 *         up to 4 smaller cards on the right (category: "lead news").
 *
 * @package Khobor
 */

$main_lead_cat = get_term_by( 'name', 'লিড নিউজ', 'category' );
$lead_cat      = get_term_by( 'name', 'লিড-নিউজ', 'category' );

// Fetch the single latest post from "main lead news".
$main_lead_post = null;
if ( $main_lead_cat ) {
	$main_query = new WP_Query(
		array(
			'posts_per_page'      => 1,
			'post_status'         => 'publish',
			'cat'                 => $main_lead_cat->term_id,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( $main_query->have_posts() ) {
		$main_lead_post = $main_query->posts[0];
	}
	wp_reset_postdata();
}

// Fetch up to 4 latest posts from "lead news".
$side_posts = array();
if ( $lead_cat ) {
	$side_query = new WP_Query(
		array(
			'posts_per_page'      => 4,
			'post_status'         => 'publish',
			'cat'                 => $lead_cat->term_id,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( $side_query->have_posts() ) {
		$side_posts = $side_query->posts;
	}
	wp_reset_postdata();
}

if ( ! $main_lead_post && empty( $side_posts ) ) {
	return;
}
?>
<section class="khobor-lead">
	<div class="khobor-lead__grid">
		<?php if ( $main_lead_post ) : ?>
			<div class="khobor-lead__primary">
				<?php
				global $post;
				$post = $main_lead_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				get_template_part( 'template-parts/content/content-card', 'large' );
				wp_reset_postdata();
				?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $side_posts ) ) : ?>
			<div class="khobor-lead__secondary">
				<?php
				foreach ( $side_posts as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					get_template_part( 'template-parts/content/content-card' );
				}
				wp_reset_postdata();
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
