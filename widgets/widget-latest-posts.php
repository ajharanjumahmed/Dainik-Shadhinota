<?php
/**
 * Widget: Latest Posts.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Khobor_Widget_Latest_Posts extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'khobor_latest_posts',
			__( 'Khobor: Latest Posts', 'khobor' ),
			array( 'description' => __( 'List of the most recent posts.', 'khobor' ) )
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'সর্বশেষ খবর', 'khobor' );
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$show_thumb = ! empty( $instance['show_thumb'] );

		echo wp_kses_post( $args['before_widget'] );
		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$q = new WP_Query(
			array( 'posts_per_page' => $count, 'post_status' => 'publish', 'no_found_rows' => true )
		);
		if ( $q->have_posts() ) {
			echo '<ul>';
			while ( $q->have_posts() ) {
				$q->the_post();
				if ( $show_thumb ) {
					get_template_part( 'template-parts/content/content-card', 'list' );
				} else {
					printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
				}
			}
			echo '</ul>';
			wp_reset_postdata();
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'সর্বশেষ খবর', 'khobor' );
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$show_thumb = ! empty( $instance['show_thumb'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'khobor' ); ?></label>
			<input class="widefat" type="text"
			       id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label><?php esc_html_e( 'Number of posts:', 'khobor' ); ?></label>
			<input class="tiny-text" type="number" min="1" max="20"
			       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
			       value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<input type="checkbox"
			       id="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'show_thumb' ) ); ?>"
			       value="1" <?php checked( $show_thumb ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_thumb' ) ); ?>"><?php esc_html_e( 'Show thumbnails', 'khobor' ); ?></label>
		</p>
		<?php
	}

	public function update( $new, $old ) {
		return array(
			'title' => sanitize_text_field( $new['title'] ?? '' ),
			'count' => absint( $new['count'] ?? 5 ),
			'show_thumb' => ! empty( $new['show_thumb'] ),
		);
	}
}
