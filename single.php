<?php
/**
 * Single Post Template
 *
 * @package Khobor
 */
get_header();

$sidebar_pos = khobor_option( 'sidebar_layout', 'right' );
$has_sidebar = ( 'none' !== $sidebar_pos );
?>
<div class="khobor-container khobor-layout khobor-layout--<?php echo esc_attr( $sidebar_pos ); ?>">
	<div class="khobor-content">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content/content-single' );

			// Comments: Facebook Comments toggle, otherwise WP default.
			if ( khobor_option( 'enable_fb_comments', false ) ) :
				$url = get_permalink();
				?>
				<div id="fb-root"></div>
				<div class="khobor-fb-comments"
				     data-href="<?php echo esc_url( $url ); ?>"
				     data-numposts="10"
				     data-width="100%"></div>
				<script async defer crossorigin="anonymous"
				        src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v17.0"></script>
				<?php
			elseif ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
		endwhile;
		?>
	</div>

	<?php if ( $has_sidebar ) : ?>
		<aside class="khobor-sidebar" role="complementary">
			<?php khobor_ad_zone( 'sidebar_top' ); ?>
			<?php dynamic_sidebar( 'sidebar-primary' ); ?>
			<?php get_template_part( 'template-parts/sections/most-read' ); ?>
			<?php khobor_ad_zone( 'sidebar_middle' ); ?>
		</aside>
	<?php endif; ?>
</div>

<?php
/*
 * View counter. Only needed when PHP isn't already counting the view on load —
 * see khobor_counts_views_on_page_load(). Behind a full-page cache the PHP
 * counter never runs, so this ping becomes the accurate one.
 */
if ( ! khobor_counts_views_on_page_load() ) :
	$post_id = get_queried_object_id();
	?>
	<script>
	(function(){
		if (window.KhoborData && KhoborData.postId === <?php echo (int) $post_id; ?>) {
			fetch(KhoborData.restUrl + 'khobor/v1/view', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': KhoborData.restNonce
				},
				body: JSON.stringify({ post_id: <?php echo (int) $post_id; ?> }),
				keepalive: true
			}).catch(function(){});
		}
	})();
	</script>
	<?php
endif;

get_footer();
