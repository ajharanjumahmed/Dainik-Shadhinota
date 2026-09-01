<?php
/**
 * Section: Author bio + their other recent posts.
 *
 * @package Khobor
 */
$author_id = (int) get_the_author_meta( 'ID' );
if ( ! $author_id ) {
	return;
}
$desc = get_the_author_meta( 'description', $author_id );
?>
<section class="khobor-author-bio">
	<div class="khobor-author-bio__inner">
		<?php echo get_avatar( $author_id, 88, '', '', array( 'class' => 'khobor-author-bio__avatar' ) ); ?>
		<div class="khobor-author-bio__text">
			<h3 class="khobor-author-bio__name">
				<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
					<?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?>
				</a>
			</h3>
			<?php if ( $desc ) : ?>
				<p class="khobor-author-bio__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<a class="khobor-author-bio__more" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
				<?php esc_html_e( 'এই লেখকের আরও খবর →', 'khobor' ); ?>
			</a>
		</div>
	</div>

	<?php
	$other = new WP_Query(
		array(
			'author'         => $author_id,
			'posts_per_page' => 3,
			'post__not_in'   => array( get_the_ID() ),
			'no_found_rows'  => true,
		)
	);
	if ( $other->have_posts() ) :
		?>
		<ul class="khobor-author-bio__list">
			<?php while ( $other->have_posts() ) :
				$other->the_post();
				?>
				<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
			<?php endwhile; ?>
		</ul>
		<?php
		wp_reset_postdata();
	endif;
	?>
</section>
