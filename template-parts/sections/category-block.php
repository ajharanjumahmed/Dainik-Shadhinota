<?php
/**
 * Section: Dynamic category block.
 *
 * Iterates active categories and renders a section for each one.
 * Auto-adapts to whatever categories the admin set up.
 *
 * @package Khobor
 */
$cats = khobor_active_categories( 6 );
if ( empty( $cats ) ) {
	return;
}

foreach ( $cats as $index => $cat ) :
	$query = new WP_Query(
		array(
			'cat'            => $cat->term_id,
			'posts_per_page' => 4,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
		)
	);
	if ( ! $query->have_posts() ) {
		continue;
	}
	?>
	<section class="khobor-cat-block khobor-cat-block--<?php echo esc_attr( $cat->slug ); ?>">
		<header class="khobor-cat-block__header">
			<h2 class="khobor-section-title khobor-section-title--bar">
				<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
			</h2>
			<a class="khobor-cat-block__all" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
				<?php esc_html_e( 'সব দেখুন', 'khobor' ); ?> →
			</a>
		</header>
		<div class="khobor-grid khobor-grid--4">
			<?php while ( $query->have_posts() ) :
				$query->the_post();
				get_template_part( 'template-parts/content/content-card' );
			endwhile; ?>
		</div>
	</section>

	<?php
	// Inject a middle ad zone after the 2nd category section.
	if ( 1 === $index ) {
		khobor_ad_zone( 'home_middle' );
	}
	wp_reset_postdata();
endforeach;
