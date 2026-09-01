<?php
/**
 * 404 Not Found Template.
 *
 * @package Khobor
 */
get_header();
?>
<div class="khobor-container khobor-404">
	<div class="khobor-404__inner">
		<div class="khobor-404__code">৪০৪</div>
		<h1 class="khobor-404__title"><?php esc_html_e( 'পাতাটি খুঁজে পাওয়া যায়নি', 'khobor' ); ?></h1>
		<p class="khobor-404__text">
			<?php esc_html_e( 'দুঃখিত, আপনি যে পাতাটি খুঁজছেন সেটি সরিয়ে নেওয়া হয়েছে বা কখনোই ছিল না।', 'khobor' ); ?>
		</p>
		<?php get_search_form(); ?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="khobor-btn khobor-btn--primary">
			<?php esc_html_e( 'মূলপাতায় ফিরে যান', 'khobor' ); ?>
		</a>
	</div>
</div>
<?php get_footer();
