<?php
/**
 * Search Form Template.
 *
 * @package Khobor
 */
?>
<form role="search" method="get" class="khobor-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="khobor-s">
		<?php esc_html_e( 'Search for:', 'khobor' ); ?>
	</label>
	<input id="khobor-s"
	       type="search"
	       class="khobor-search-form__input"
	       placeholder="<?php esc_attr_e( 'এখানে অনুসন্ধান করুন…', 'khobor' ); ?>"
	       value="<?php echo esc_attr( get_search_query() ); ?>"
	       name="s">
	<button type="submit" class="khobor-search-form__submit" aria-label="<?php esc_attr_e( 'Search', 'khobor' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<circle cx="11" cy="11" r="7"/>
			<line x1="21" y1="21" x2="16.65" y2="16.65"/>
		</svg>
	</button>
</form>
