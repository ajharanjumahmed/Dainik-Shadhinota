<?php
/**
 * Front Page Template
 *
 * Used as the homepage when "Your latest posts" is selected in
 * Settings → Reading, or when no static front page is set.
 *
 * @package Khobor
 */
get_header();
?>

<div class="khobor-container khobor-homepage">

	<?php if ( is_active_sidebar( 'home-top' ) ) : ?>
		<div class="khobor-home-top">
			<?php dynamic_sidebar( 'home-top' ); ?>
		</div>
	<?php endif; ?>

	<?php khobor_ad_zone( 'home_top' ); ?>

	<?php get_template_part( 'template-parts/sections/lead-news' ); ?>

	<div class="khobor-home-layout">
		<div class="khobor-home-main">
			<?php if ( is_active_sidebar( 'home-sections' ) ) : ?>
				<?php dynamic_sidebar( 'home-sections' ); ?>
			<?php endif; ?>
		</div>

		<aside class="khobor-sidebar khobor-home-side" role="complementary">
			<!-- <?php get_template_part( 'template-parts/sections/most-read' ); ?> -->
			<?php
			if ( is_active_sidebar( 'sidebar-primary' ) ) {
				dynamic_sidebar( 'sidebar-primary' );
			}
			?>
		</aside>
	</div>

	<?php khobor_ad_zone( 'home_bottom' ); ?>

	<?php if ( is_active_sidebar( 'home-bottom' ) ) : ?>
		<div class="khobor-home-bottom">
			<?php dynamic_sidebar( 'home-bottom' ); ?>
		</div>
	<?php endif; ?>
</div>

<?php get_footer();
