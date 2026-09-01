<?php
/**
 * Page Template.
 *
 * @package Khobor
 */
get_header();
?>
<div class="khobor-container khobor-layout khobor-layout--right">
	<div class="khobor-content">
		<?php while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'khobor-page' ); ?>>
				<header class="khobor-page__header">
					<h1 class="khobor-page__title"><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="khobor-page__hero">
						<?php the_post_thumbnail( 'khobor-hero' ); ?>
					</figure>
				<?php endif; ?>

				<div class="khobor-page__body">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="khobor-page-links">' . esc_html__( 'Pages:', 'khobor' ),
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>

	<aside class="khobor-sidebar" role="complementary">
		<?php dynamic_sidebar( 'sidebar-primary' ); ?>
	</aside>
</div>
<?php get_footer();
