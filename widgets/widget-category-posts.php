<?php
/**
 * Widget: Category Posts.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Khobor_Widget_Category_Posts extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'khobor_category_posts',
			__( 'Khobor: Category Posts', 'khobor' ),
			array( 'description' => __( 'Latest posts from a chosen category. Choose Grid layout for Homepage Sections.', 'khobor' ) )
		);
	}

	public function widget( $args, $instance ) {
		$cat_id = absint( $instance['cat_id'] ?? 0 );
		if ( ! $cat_id ) {
			return;
		}
		$cat = get_term( $cat_id, 'category' );
		if ( ! $cat || is_wp_error( $cat ) ) {
			return;
		}

		$title  = ! empty( $instance['title'] ) ? $instance['title'] : $cat->name;
		$count  = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 4;
		$layout = $instance['layout'] ?? 'grid-4';

		$q = new WP_Query(
			array(
				'cat'            => $cat_id,
				'posts_per_page' => $count,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			)
		);

		if ( ! $q->have_posts() ) {
			return;
		}

		echo wp_kses_post( $args['before_widget'] );

		if ( 'list' === $layout ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="khobor-cat-widget__list">';
			while ( $q->have_posts() ) {
				$q->the_post();
				get_template_part( 'template-parts/content/content-card', 'list' );
			}
			echo '</div>';
		} else {
			$cols = ( 'grid-3' === $layout ) ? 3 : 4;
			$cat_link = get_term_link( $cat );
			?>
			<section class="khobor-cat-block khobor-cat-block--<?php echo esc_attr( $cat->slug ); ?>">
				<header class="khobor-cat-block__header">
					<h2 class="khobor-section-title khobor-section-title--bar">
						<a href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $title ); ?></a>
					</h2>
					<a class="khobor-cat-block__all" href="<?php echo esc_url( $cat_link ); ?>">
						<?php esc_html_e( 'সব দেখুন', 'khobor' ); ?> →
					</a>
				</header>
				<div class="khobor-grid khobor-grid--<?php echo esc_attr( $cols ); ?>">
					<?php while ( $q->have_posts() ) :
						$q->the_post();
						get_template_part( 'template-parts/content/content-card' );
					endwhile; ?>
				</div>
			</section>
			<?php
		}

		wp_reset_postdata();
		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$title  = $instance['title']  ?? '';
		$cat_id = $instance['cat_id'] ?? 0;
		$count  = $instance['count']  ?? 4;
		$layout = $instance['layout'] ?? 'grid-4';
		?>
		<p>
			<label><?php esc_html_e( 'Title (optional, defaults to category name):', 'khobor' ); ?></label>
			<input class="widefat" type="text"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label><?php esc_html_e( 'Category:', 'khobor' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'name'              => $this->get_field_name( 'cat_id' ),
					'selected'          => $cat_id,
					'show_option_none'  => __( '— Select —', 'khobor' ),
					'option_none_value' => 0,
					'hide_empty'        => false,
					'class'             => 'widefat',
				)
			);
			?>
		</p>
		<p>
			<label><?php esc_html_e( 'Number of posts:', 'khobor' ); ?></label>
			<input class="tiny-text" type="number" min="1" max="20"
			       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
			       value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<label><?php esc_html_e( 'Layout:', 'khobor' ); ?></label>
			<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
				<option value="grid-4" <?php selected( $layout, 'grid-4' ); ?>><?php esc_html_e( 'Grid – 4 columns', 'khobor' ); ?></option>
				<option value="grid-3" <?php selected( $layout, 'grid-3' ); ?>><?php esc_html_e( 'Grid – 3 columns', 'khobor' ); ?></option>
				<option value="list"   <?php selected( $layout, 'list' ); ?>><?php esc_html_e( 'List', 'khobor' ); ?></option>
			</select>
		</p>
		<?php
	}

	public function update( $new, $old ) {
		$allowed = array( 'grid-4', 'grid-3', 'list' );
		return array(
			'title'  => sanitize_text_field( $new['title']  ?? '' ),
			'cat_id' => absint( $new['cat_id'] ?? 0 ),
			'count'  => min( 20, max( 1, absint( $new['count'] ?? 4 ) ) ),
			'layout' => in_array( $new['layout'] ?? '', $allowed, true ) ? $new['layout'] : 'grid-4',
		);
	}
}
