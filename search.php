<?php
/**
 * Search Results Template.
 *
 * @package Khobor
 */
get_header();
?>
<div class="khobor-container khobor-layout khobor-layout--right">
	<div class="khobor-content">
		<header class="khobor-archive-header">
			<h1 class="khobor-archive-title">
				<?php
				/* translators: %s: search query. */
				printf( esc_html__( 'অনুসন্ধান: %s', 'khobor' ), '<em>' . esc_html( get_search_query() ) . '</em>' );
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="khobor-grid khobor-grid--3">
				<?php while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content-card' );
				endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else :
			get_template_part( 'template-parts/content/content-none' );
		endif; ?>
	</div>

	<aside class="khobor-sidebar" role="complementary">
		<?php dynamic_sidebar( 'sidebar-primary' ); ?>
	</aside>
</div>
<?php get_footer();
