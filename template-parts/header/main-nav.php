<?php
/**
 * Header: Main navigation bar.
 *
 * @package Khobor
 */
?>
<nav class="khobor-mainnav" role="navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'khobor' ); ?>">
	<div class="khobor-container khobor-mainnav__inner">

		<button class="khobor-menu-toggle"
		        type="button"
		        aria-controls="khobor-primary-menu"
		        aria-expanded="false"
		        aria-label="<?php esc_attr_e( 'Toggle menu', 'khobor' ); ?>">
			<span class="khobor-menu-toggle__bar"></span>
			<span class="khobor-menu-toggle__bar"></span>
			<span class="khobor-menu-toggle__bar"></span>
		</button>

		<div id="khobor-primary-menu" class="khobor-mainnav__menu">
			<?php
			khobor_render_menu(
				'primary',
				array(
					'menu_id'   => 'khobor-primary-menu-list',
					'menu_class' => 'khobor-nav',
				)
			);
			?>
		</div>

		<?php
		// E-paper link at the end of the menu, if a published e-paper exists.
		$latest_epaper = get_posts(
			array(
				'post_type'      => 'epaper',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $latest_epaper ) ) :
			?>
			<a class="khobor-epaper-link" href="<?php echo esc_url( get_permalink( $latest_epaper[0] ) ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
					<path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
				</svg>
				<?php esc_html_e( 'ইপেপার', 'khobor' ); ?>
			</a>
		<?php endif; ?>
	</div>
</nav>
