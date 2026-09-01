<?php
/**
 * Widget: Ad Zone.
 *
 * Lets the admin render any registered ad zone inside any widget area.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Khobor_Widget_Ad_Zone extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'khobor_ad_zone',
			__( 'Khobor: Ad Zone', 'khobor' ),
			array( 'description' => __( 'Renders a configured Khobor ad zone.', 'khobor' ) )
		);
	}

	public function widget( $args, $instance ) {
		$zone = ! empty( $instance['zone'] ) ? $instance['zone'] : '';
		if ( ! $zone ) {
			return;
		}
		echo wp_kses_post( $args['before_widget'] );
		khobor_ad_zone( $zone );
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$zone  = $instance['zone'] ?? '';
		$zones = khobor_ad_zones_list();
		?>
		<p>
			<label><?php esc_html_e( 'Zone:', 'khobor' ); ?></label>
			<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'zone' ) ); ?>">
				<option value=""><?php esc_html_e( '— Select —', 'khobor' ); ?></option>
				<?php foreach ( $zones as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $zone, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'Manage zone code under Appearance → Khobor Ad Zones.', 'khobor' ); ?>
		</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'zone' => sanitize_key( $new['zone'] ?? '' ),
		);
	}
}
