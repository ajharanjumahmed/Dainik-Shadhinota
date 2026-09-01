<?php
/**
 * Widget: Prayer Times.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Khobor_Widget_Prayer_Times extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'khobor_prayer_times',
			__( 'Khobor: Prayer Times', 'khobor' ),
			array( 'description' => __( 'Daily prayer times from Aladhan API.', 'khobor' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'নামাজের সময়সূচি', 'khobor' );
		$city  = ! empty( $instance['city'] )  ? $instance['city']  : '';
		$country = ! empty( $instance['country'] ) ? $instance['country'] : '';

		$times = khobor_fetch_prayer_times( $city, $country );

		echo wp_kses_post( $args['before_widget'] );
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( is_wp_error( $times ) ) {
			echo '<p class="khobor-prayer-times__error">' . esc_html__( 'Could not load prayer times.', 'khobor' ) . '</p>';
		} else {
			$labels = khobor_prayer_labels();
			$city_display = $city ?: khobor_option( 'prayer_city', 'Dhaka' );
			echo '<p class="khobor-prayer-times__city">' . esc_html( $city_display ) . ' · ' . esc_html( khobor_maybe_bangla( current_time( 'j F, Y' ) ) ) . '</p>';
			echo '<div class="khobor-prayer-times__list">';
			foreach ( $times as $key => $time ) {
				$bn_label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
				printf(
					'<div class="khobor-prayer-times__row"><span class="khobor-prayer-times__name">%s</span><span class="khobor-prayer-times__time">%s</span></div>',
					esc_html( $bn_label ),
					esc_html( khobor_maybe_bangla( khobor_format_prayer_time( $time ) ) )
				);
			}
			echo '</div>';
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title   = ! empty( $instance['title'] )   ? $instance['title']   : __( 'নামাজের সময়সূচি', 'khobor' );
		$city    = ! empty( $instance['city'] )    ? $instance['city']    : '';
		$country = ! empty( $instance['country'] ) ? $instance['country'] : '';
		?>
		<p>
			<label><?php esc_html_e( 'Title:', 'khobor' ); ?></label>
			<input class="widefat" type="text"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label><?php esc_html_e( 'City (overrides global):', 'khobor' ); ?></label>
			<input class="widefat" type="text"
			       name="<?php echo esc_attr( $this->get_field_name( 'city' ) ); ?>"
			       value="<?php echo esc_attr( $city ); ?>"
			       placeholder="<?php echo esc_attr( khobor_option( 'prayer_city', 'Dhaka' ) ); ?>">
		</p>
		<p>
			<label><?php esc_html_e( 'Country (overrides global):', 'khobor' ); ?></label>
			<input class="widefat" type="text"
			       name="<?php echo esc_attr( $this->get_field_name( 'country' ) ); ?>"
			       value="<?php echo esc_attr( $country ); ?>"
			       placeholder="<?php echo esc_attr( khobor_option( 'prayer_country', 'Bangladesh' ) ); ?>">
		</p>
		<p class="description">
			<?php esc_html_e( 'Leave blank to use the global Customizer setting.', 'khobor' ); ?>
		</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title'   => sanitize_text_field( $new['title']   ?? '' ),
			'city'    => sanitize_text_field( $new['city']    ?? '' ),
			'country' => sanitize_text_field( $new['country'] ?? '' ),
		);
	}
}
