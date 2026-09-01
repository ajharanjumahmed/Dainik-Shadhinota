<?php
/**
 * Widget: Popular Posts (sorts by _khobor_views).
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Khobor_Widget_Popular_Posts extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'khobor_popular_posts',
			__( 'Khobor: Popular Posts', 'khobor' ),
			array( 'description' => __( 'Most viewed posts in the last N days.', 'khobor' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'জনপ্রিয়', 'khobor' );
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$days  = ! empty( $instance['days'] )  ? absint( $instance['days'] )  : 30;

		echo wp_kses_post( $args['before_widget'] );
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Shared with template-parts/sections/most-read.php: one cached ranking
		// so the widget and the sidebar section never disagree.
		$ids = khobor_get_popular_posts( $count, $days );

		$q = $ids ? new WP_Query(
			array(
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'post_status'         => 'publish',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		) : null;

		if ( $q && $q->have_posts() ) {
			echo '<ul class="khobor-most-read__list">';
			$rank = 0;
			while ( $q->have_posts() ) {
				$q->the_post();
				$rank++;
				printf(
					'<li class="khobor-most-read__item"><span class="khobor-most-read__rank">%s</span><a class="khobor-most-read__link" href="%s">%s</a></li>',
					esc_html( khobor_maybe_bangla( $rank ) ),
					esc_url( get_permalink() ),
					esc_html( get_the_title() )
				);
			}
			echo '</ul>';
			wp_reset_postdata();
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'জনপ্রিয়', 'khobor' );
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$days  = ! empty( $instance['days'] )  ? absint( $instance['days'] )  : 30;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'khobor' ); ?>
			</label>
			<input class="widefat" type="text"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
				<?php esc_html_e( 'Number of posts:', 'khobor' ); ?>
			</label>
			<input class="tiny-text" type="number" min="1" max="20"
			       id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
			       value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>">
				<?php esc_html_e( 'From the last N days:', 'khobor' ); ?>
			</label>
			<input class="tiny-text" type="number" min="1" max="365"
			       id="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'days' ) ); ?>"
			       value="<?php echo esc_attr( $days ); ?>">
		</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( $new['title'] ?? '' ),
			'count' => absint( $new['count'] ?? 5 ),
			'days'  => absint( $new['days']  ?? 30 ),
		);
	}
}
