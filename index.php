<?php
/**
 * Main template (loop fallback).
 *
 * @package Khobor
 */
get_header();

$sidebar_pos = khobor_option( 'sidebar_layout', 'right' );
$has_sidebar = ( 'none' !== $sidebar_pos );
?>
<div class="khobor-container khobor-layout khobor-layout--<?php echo esc_attr( $sidebar_pos ); ?>">

	<div class="khobor-content">
		<?php if ( have_posts() ) : ?>
			<div class="khobor-grid khobor-grid--3">
				<?php while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content-card' );
				endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 2,
					'prev_text' => __( '« পূর্ববর্তী', 'khobor' ),
					'next_text' => __( 'পরবর্তী »', 'khobor' ),
				)
			);
			?>
		<?php else :
			get_template_part( 'template-parts/content/content-none' );
		endif; ?>
	</div>

	<?php if ( $has_sidebar ) : ?>
		<aside class="khobor-sidebar" role="complementary">
			<?php dynamic_sidebar( 'sidebar-primary' ); ?>
			<?php get_template_part( 'template-parts/sections/most-read' ); ?>
		</aside>
	<?php endif; ?>
</div>

<?php
get_footer();
