<?php
/**
 * Header: Logo + leaderboard ad.
 *
 * @package Khobor
 */
?>
<div class="khobor-logo-area">
	<div class="khobor-container khobor-logo-area__inner">
		<div class="khobor-branding">
			<?php
			if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
				the_custom_logo();
			} else {
				printf(
					'<a href="%1$s" class="khobor-site-title-link" rel="home"><span class="khobor-site-title">%2$s</span></a>',
					esc_url( home_url( '/' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				$desc = get_bloginfo( 'description', 'display' );
				if ( $desc ) {
					printf( '<p class="khobor-site-description">%s</p>', esc_html( $desc ) );
				}
			}
			?>
		</div>

		<div class="khobor-logo-area__ad">
			<?php khobor_ad_zone( 'header' ); ?>
		</div>
	</div>
</div>
