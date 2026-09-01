<?php
/**
 * Section: Most read (uses _khobor_views meta).
 *
 * @package Khobor
 */
// Posts from the last 30 days ordered by view count descending, topped up with
// recent posts when too few have views. See khobor_get_popular_posts().
$khobor_popular_ids = khobor_get_popular_posts( 5, 30 );

if ( empty( $khobor_popular_ids ) ) {
	return;
}

$query = new WP_Query(
	array(
		'post__in'            => $khobor_popular_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => count( $khobor_popular_ids ),
		'post_status'         => 'publish',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	)
);
?>
<section class="khobor-most-read">
	<h2 class="khobor-section-title khobor-section-title--bar">
		<?php esc_html_e( 'সবচেয়ে বেশি পঠিত', 'khobor' ); ?>
	</h2>
	<ol class="khobor-most-read__list">
		<?php
		$rank = 0;
		while ( $query->have_posts() ) :
			$query->the_post();
			$rank++;
			?>
			<li class="khobor-most-read__item">
				<span class="khobor-most-read__rank"><?php echo esc_html( khobor_maybe_bangla( $rank ) ); ?></span>
				<a class="khobor-most-read__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</li>
		<?php endwhile; ?>
	</ol>
</section>
<?php
wp_reset_postdata();
