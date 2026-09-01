<?php
/**
 * Custom Ad Zone Manager
 *
 * Defines named ad zones (e.g. 'header', 'in_article_top', 'sidebar_300x250').
 * Admin pastes HTML/script tags into each zone via an admin page.
 * Theme template calls khobor_ad_zone('header') to render.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical list of zones.
 *
 * @return array<string, string> zone_key => label
 */
function khobor_ad_zones_list() {
	return apply_filters(
		'khobor_ad_zones',
		array(
			'header'           => __( 'Header (top of page, below nav)', 'khobor' ),
			'below_ticker'     => __( 'Below breaking-news ticker', 'khobor' ),
			'home_top'         => __( 'Homepage top (above lead news)', 'khobor' ),
			'home_middle'      => __( 'Homepage middle (between sections)', 'khobor' ),
			'home_bottom'      => __( 'Homepage bottom (above footer)', 'khobor' ),
			'article_top'      => __( 'Single post: above title', 'khobor' ),
			'article_middle'   => __( 'Single post: middle of content', 'khobor' ),
			'article_bottom'   => __( 'Single post: end of content', 'khobor' ),
			'sidebar_top'      => __( 'Sidebar top', 'khobor' ),
			'sidebar_middle'   => __( 'Sidebar middle', 'khobor' ),
			'footer'           => __( 'Footer (above credits)', 'khobor' ),
		)
	);
}

/**
 * Render an ad zone.
 *
 * @param string $key Zone key.
 */
function khobor_ad_zone( $key ) {
	$code = get_option( 'khobor_ad_zone_' . $key, '' );
	if ( empty( trim( $code ) ) ) {
		return;
	}
	// Output without escaping (admins paste real ad scripts).
	// We only allow logged-in admins to set this, so trust is OK.
	?>
	<div class="khobor-ad khobor-ad--<?php echo esc_attr( $key ); ?>" data-zone="<?php echo esc_attr( $key ); ?>">
		<span class="khobor-ad__label"><?php esc_html_e( 'বিজ্ঞাপন', 'khobor' ); ?></span>
		<div class="khobor-ad__slot"><?php echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	</div>
	<?php
}

/**
 * Auto-insert middle-of-article ad after the Nth paragraph.
 *
 * @param string $content Post content.
 * @return string
 */
function khobor_inject_middle_article_ad( $content ) {
	if ( ! is_singular( 'post' ) || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$code = get_option( 'khobor_ad_zone_article_middle', '' );
	if ( empty( trim( $code ) ) ) {
		return $content;
	}

	$paragraphs = explode( '</p>', $content );
	$insert_at  = (int) apply_filters( 'khobor_article_middle_after', 3 );
	if ( count( $paragraphs ) <= $insert_at ) {
		return $content;
	}

	$ad_html = '<div class="khobor-ad khobor-ad--article_middle" data-zone="article_middle">'
		. '<span class="khobor-ad__label">' . esc_html__( 'বিজ্ঞাপন', 'khobor' ) . '</span>'
		. '<div class="khobor-ad__slot">' . $code . '</div>'
		. '</div>';

	$out = '';
	foreach ( $paragraphs as $i => $p ) {
		$out .= $p;
		if ( ! empty( $p ) && $i + 1 < count( $paragraphs ) ) {
			$out .= '</p>';
		}
		if ( $i === $insert_at - 1 ) {
			$out .= $ad_html;
		}
	}
	return $out;
}
add_filter( 'the_content', 'khobor_inject_middle_article_ad', 20 );

/**
 * Admin page under Appearance to manage ad zones.
 */
function khobor_ad_zones_admin_menu() {
	add_submenu_page(
		'themes.php',
		__( 'Khobor Ad Zones', 'khobor' ),
		__( 'Khobor Ad Zones', 'khobor' ),
		'manage_options',
		'khobor-ad-zones',
		'khobor_ad_zones_admin_render'
	);
}
add_action( 'admin_menu', 'khobor_ad_zones_admin_menu' );

function khobor_ad_zones_admin_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$zones = khobor_ad_zones_list();

	// Save handler.
	if ( isset( $_POST['khobor_ad_zones_nonce'] )
		&& wp_verify_nonce( wp_unslash( $_POST['khobor_ad_zones_nonce'] ), 'khobor_ad_zones_save' ) ) {
		foreach ( $zones as $key => $label ) {
			if ( isset( $_POST[ 'khobor_ad_zone_' . $key ] ) ) {
				$value = wp_unslash( $_POST[ 'khobor_ad_zone_' . $key ] );
				// Don't run kses; admin needs to paste raw script tags.
				update_option( 'khobor_ad_zone_' . $key, $value );
			}
		}
		echo '<div class="updated notice"><p>' . esc_html__( 'Ad zones saved.', 'khobor' ) . '</p></div>';
	}
	?>
	<div class="wrap khobor-admin-page">
		<h1><?php esc_html_e( 'Khobor Ad Zones', 'khobor' ); ?></h1>
		<p><?php esc_html_e( 'Paste ad code (AdSense, custom banners, etc.) into each zone. Leave blank to disable.', 'khobor' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'khobor_ad_zones_save', 'khobor_ad_zones_nonce' ); ?>
			<table class="form-table">
				<tbody>
				<?php foreach ( $zones as $key => $label ) : ?>
					<tr>
						<th scope="row">
							<label for="khobor_ad_zone_<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $label ); ?>
							</label>
							<p class="description"><code>khobor_ad_zone('<?php echo esc_html( $key ); ?>')</code></p>
						</th>
						<td>
							<textarea
								id="khobor_ad_zone_<?php echo esc_attr( $key ); ?>"
								name="khobor_ad_zone_<?php echo esc_attr( $key ); ?>"
								rows="5"
								cols="60"
								class="large-text code"><?php echo esc_textarea( get_option( 'khobor_ad_zone_' . $key, '' ) ); ?></textarea>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Ad Zones', 'khobor' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}
