<?php
/**
 * Template Tags
 *
 * Reusable functions called from theme templates.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print formatted post date in Bangla format.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_the_post_date( $post = null ) {
	$post   = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$iso    = get_the_date( 'c', $post );
	$bangla = khobor_maybe_bangla( get_the_date( 'j F, Y', $post ) );
	printf(
		'<time class="khobor-post-date" datetime="%s">%s</time>',
		esc_attr( $iso ),
		esc_html( $bangla )
	);
}

/**
 * Print post author link.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_the_post_author( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$author_id = (int) $post->post_author;
	printf(
		'<span class="khobor-post-author"><a href="%s">%s</a></span>',
		esc_url( get_author_posts_url( $author_id ) ),
		esc_html( get_the_author_meta( 'display_name', $author_id ) )
	);
}

/**
 * Print primary category badge linked to its archive.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_the_primary_category( $post = null ) {
	$cat = khobor_primary_category( $post );
	if ( ! $cat ) {
		return;
	}
	printf(
		'<a class="khobor-cat-badge" href="%s">%s</a>',
		esc_url( get_term_link( $cat ) ),
		esc_html( $cat->name )
	);
}

/**
 * Inline brand/UI icon markup.
 *
 * Inline SVG rather than an icon font or remote images: no extra request, no
 * FOUT, and `currentColor` means the glyph follows the button's hover colour
 * for free. Paths are the standard Simple Icons brand marks (24x24 viewBox).
 *
 * @param string $name Icon slug: facebook, x, whatsapp, telegram, youtube,
 *                     instagram, link.
 * @param int    $size Pixel width/height.
 * @return string SVG markup, or '' for an unknown slug.
 */
function khobor_social_icon( $name, $size = 16 ) {
	$paths = array(
		'facebook'  => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
		'x'         => 'M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z',
		'whatsapp'  => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z',
		'telegram'  => 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z',
		'youtube'   => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814ZM9.545 15.568V8.432L15.818 12l-6.273 3.568Z',
		'instagram' => 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.2952 1.6361.4959 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.1478-.2652 2.9105-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.0044 11.977.0018 8.718.008 8.31.0223 7.0301.0844m.1401 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.2277.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.0053-4.848-.0607M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077',
		'link'      => 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	// The link glyph is an outline, the brand marks are solid.
	$style = ( 'link' === $name )
		? 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
		: 'fill="currentColor"';

	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" %2$s aria-hidden="true" focusable="false"><path d="%3$s"/></svg>',
		absint( $size ),
		$style,
		$paths[ $name ]
	);
}

/**
 * Render share buttons.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_share_buttons( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$url   = rawurlencode( get_permalink( $post ) );
	$title = rawurlencode( get_the_title( $post ) );
	?>
	<div class="khobor-share">
		<span class="khobor-share__label"><?php esc_html_e( 'শেয়ার করুন', 'khobor' ); ?></span>
		<a class="khobor-share__btn khobor-share__btn--fb"
		   href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>"
		   target="_blank"
		   rel="noopener"
		   aria-label="Facebook"><?php echo khobor_social_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<a class="khobor-share__btn khobor-share__btn--tw"
		   href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>&text=<?php echo $title; ?>"
		   target="_blank"
		   rel="noopener"
		   aria-label="X"><?php echo khobor_social_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<a class="khobor-share__btn khobor-share__btn--wa"
		   href="https://api.whatsapp.com/send?text=<?php echo $title . '%20' . $url; ?>"
		   target="_blank"
		   rel="noopener"
		   aria-label="WhatsApp"><?php echo khobor_social_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<a class="khobor-share__btn khobor-share__btn--tg"
		   href="https://t.me/share/url?url=<?php echo $url; ?>&text=<?php echo $title; ?>"
		   target="_blank"
		   rel="noopener"
		   aria-label="Telegram"><?php echo khobor_social_icon( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<button class="khobor-share__btn khobor-share__btn--copy"
		        type="button"
		        data-copy="<?php echo esc_url( get_permalink( $post ) ); ?>"
		        aria-label="<?php esc_attr_e( 'Copy link', 'khobor' ); ?>"><?php echo khobor_social_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	</div>
	<?php
}

/**
 * Render photocard button.
 *
 * @param int|WP_Post $post Post.
 */
function khobor_photocard_button( $post = null ) {
	if ( ! khobor_option( 'enable_photocard', true ) ) {
		return;
	}
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	?>
	<button
		class="khobor-photocard-btn"
		type="button"
		data-post-id="<?php echo esc_attr( $post->ID ); ?>"
		aria-label="<?php esc_attr_e( 'Generate Photocard', 'khobor' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
			<rect x="3" y="3" width="18" height="18" rx="2"/>
			<circle cx="8.5" cy="8.5" r="1.5"/>
			<polyline points="21 15 16 10 5 21"/>
		</svg>
		<span><?php esc_html_e( 'Generate Photocard', 'khobor' ); ?></span>
	</button>
	<?php
	// Payload for the browser-side renderer. The card is drawn on a canvas
	// because the browser is the only text engine in this stack that shapes
	// Bengali correctly — see inc/photocard.php.
	$khobor_pc_data = function_exists( 'khobor_photocard_client_data' ) ? khobor_photocard_client_data( $post ) : null;
	if ( $khobor_pc_data ) :
		?>
		<script type="application/json" id="khobor-photocard-data">
			<?php
			// JSON_HEX_TAG so a headline containing "</script>" can't close the
			// block early.
			echo wp_json_encode( $khobor_pc_data, JSON_HEX_TAG | JSON_HEX_AMP );
			?>
		</script>
		<?php
	endif;
	?>
	<div class="khobor-photocard-result" hidden></div>
	<?php
}

/**
 * Render font-size adjuster controls.
 */
function khobor_font_sizer() {
	if ( ! khobor_option( 'enable_font_sizer', true ) ) {
		return;
	}
	?>
	<div class="khobor-font-sizer" role="group" aria-label="<?php esc_attr_e( 'Adjust font size', 'khobor' ); ?>">
		<span class="khobor-font-sizer__label"><?php esc_html_e( 'ফন্ট', 'khobor' ); ?>:</span>
		<button type="button" class="khobor-font-sizer__btn" data-step="-1" aria-label="<?php esc_attr_e( 'Smaller font', 'khobor' ); ?>">A−</button>
		<button type="button" class="khobor-font-sizer__btn khobor-font-sizer__btn--reset" data-step="0" aria-label="<?php esc_attr_e( 'Reset font', 'khobor' ); ?>">A</button>
		<button type="button" class="khobor-font-sizer__btn" data-step="1" aria-label="<?php esc_attr_e( 'Larger font', 'khobor' ); ?>">A+</button>
	</div>
	<?php
}

/**
 * Get a localized "X minutes ago" style time string from a post.
 *
 * @param int|WP_Post $post Post.
 * @return string
 */
function khobor_time_ago( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$diff = human_time_diff( get_post_time( 'U', false, $post ), current_time( 'timestamp' ) );
	$diff = khobor_maybe_bangla( $diff );
	/* translators: %s: human-readable time. */
	return sprintf( __( '%s আগে', 'khobor' ), $diff );
}

/**
 * Get an HTML "View N" string.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function khobor_views_text( $post_id ) {
	$count = khobor_get_views( $post_id );
	$count = khobor_maybe_bangla( number_format_i18n( $count ) );
	/* translators: %s: number of views. */
	return sprintf( __( '%s জন পড়েছেন', 'khobor' ), $count );
}
