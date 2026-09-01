<?php
/**
 * Header: Top Bar (above logo area).
 *
 * @package Khobor
 */
?>
<div class="khobor-topbar">
	<div class="khobor-container khobor-topbar__inner">
		<div class="khobor-topbar__date">
			<?php echo khobor_today_dateline(); // already escaped inside function. ?>
		</div>

		<?php if ( has_nav_menu( 'top' ) ) : ?>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'top',
					'container'      => 'nav',
					'menu_class'     => 'khobor-topbar__menu',
					'depth'          => 1,
				)
			);
			?>
		<?php endif; ?>

		<div class="khobor-topbar__social">
			<?php
			$socials = array(
				'facebook'  => array( 'url' => khobor_option( 'social_facebook' ),  'label' => 'Facebook' ),
				'twitter'   => array( 'url' => khobor_option( 'social_twitter' ),   'label' => 'Twitter' ),
				'youtube'   => array( 'url' => khobor_option( 'social_youtube' ),   'label' => 'YouTube' ),
				'instagram' => array( 'url' => khobor_option( 'social_instagram' ), 'label' => 'Instagram' ),
			);
			foreach ( $socials as $key => $s ) :
				if ( empty( $s['url'] ) ) {
					continue;
				}
				?>
				<a class="khobor-social khobor-social--<?php echo esc_attr( $key ); ?>"
				   href="<?php echo esc_url( $s['url'] ); ?>"
				   target="_blank"
				   rel="noopener"
				   aria-label="<?php echo esc_attr( $s['label'] ); ?>">
					<span class="khobor-social__glyph"><?php echo esc_html( strtoupper( substr( $key, 0, 1 ) ) ); ?></span>
				</a>
			<?php endforeach; ?>

			<button type="button"
			        class="khobor-search-toggle"
			        aria-label="<?php esc_attr_e( 'Search', 'khobor' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<circle cx="11" cy="11" r="7"/>
					<line x1="21" y1="21" x2="16.65" y2="16.65"/>
				</svg>
			</button>

			<?php if ( khobor_option( 'enable_dark_mode', true ) ) : ?>
				<button type="button"
				        class="khobor-darkmode-toggle"
				        aria-label="<?php esc_attr_e( 'Toggle dark mode', 'khobor' ); ?>"
				        aria-pressed="false">🌓</button>
			<?php endif; ?>
		</div>
	</div>

	<div class="khobor-search-panel" hidden>
		<div class="khobor-container">
			<?php get_search_form(); ?>
		</div>
	</div>
</div>
