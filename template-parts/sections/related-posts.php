<?php
/**
 * Section: Related posts (same primary category, excluding current).
 *
 * @package Khobor
 */
$post_id = get_the_ID();
$cat     = khobor_primary_category();
if ( ! $cat ) {
	return;
}

$related = new WP_Query(
	array(
		'post_status'         => 'publish',
		'posts_per_page'      => 4,
		'cat'                 => $cat->term_id,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'no_found_rows'       => true,
	)
);

if ( ! $related->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="khobor-related">
	<h2 class="khobor-section-title khobor-section-title--bar">
		<?php
		/* translators: %s: category name */
		printf( esc_html__( 'আরও %s এর খবর', 'khobor' ), esc_html( $cat->name ) );
		?>
	</h2>
	<div class="khobor-grid khobor-grid--4">
		<?php while ( $related->have_posts() ) :
			$related->the_post();
			get_template_part( 'template-parts/content/content-card' );
		endwhile; ?>
	</div>
</section>
<?php
wp_reset_postdata();
