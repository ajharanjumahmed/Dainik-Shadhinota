<?php
/**
 * Footer template
 *
 * @package Khobor
 */
?>
	</main><!-- #khobor-main -->

	<?php khobor_ad_zone( 'footer' ); ?>

	<footer id="khobor-colophon" class="khobor-footer" role="contentinfo">
		<div class="khobor-container khobor-footer__top">
			<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
				<?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
					<div class="khobor-footer__col khobor-footer__col--<?php echo (int) $i; ?>">
						<?php dynamic_sidebar( 'footer-' . $i ); ?>
					</div>
				<?php endif; ?>
			<?php endfor; ?>
		</div>

		<div class="khobor-footer__bar">
			<div class="khobor-container">
				<div class="khobor-footer__credit">
					<?php
					/* translators: 1: year, 2: site name. */
					printf(
						esc_html__( '© %1$s %2$s. সকল অধিকার সংরক্ষিত।', 'khobor' ),
						esc_html( khobor_maybe_bangla( gmdate( 'Y' ) ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</div>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => 'nav',
							'menu_class'     => 'khobor-footer__menu',
							'depth'          => 1,
						)
					);
				}
				?>
			</div>
		</div>
	</footer>
</div><!-- #khobor-page -->

<?php wp_footer(); ?>
</body>
</html>
