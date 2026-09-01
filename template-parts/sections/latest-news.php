<?php
/**
 * Section: Latest news grid.
 *
 * @package Khobor
 */
$paged = max( 1, (int) ( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ?: 1 ) );

$query = new WP_Query(
	array(
		'posts_per_page' => 9,
		'post_status'    => 'publish',
		'offset'         => 5, // skip the 5 lead posts.
		'paged'          => $paged,
		'no_found_rows'  => true,
	)
);

if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="khobor-latest">
	<h2 class="khobor-section-title khobor-section-title--bar">
		<?php esc_html_e( 'সর্বশেষ সংবাদ', 'khobor' ); ?>
	</h2>
	<div class="khobor-grid khobor-grid--3">
		<?php while ( $query->have_posts() ) :
			$query->the_post();
			get_template_part( 'template-parts/content/content-card' );
		endwhile; ?>
	</div>
	<div class="khobor-latest__more">
		<a class="khobor-btn khobor-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/?s=' ) ); ?>">
			<?php esc_html_e( 'আরও দেখুন', 'khobor' ); ?>
		</a>
	</div>
</section>
<?php
wp_reset_postdata();
