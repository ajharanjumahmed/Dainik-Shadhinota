<?php
/**
 * Gutenberg Block Registration
 *
 * Server-rendered blocks (so they work in classic themes too).
 * Blocks: khobor/news-card, khobor/category-section, khobor/featured-news.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register blocks.
 */
function khobor_register_blocks() {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// khobor/featured-news.
	register_block_type(
		'khobor/featured-news',
		array(
			'render_callback' => 'khobor_block_featured_news_render',
			'attributes'      => array(
				'category' => array( 'type' => 'string', 'default' => '' ),
				'count'    => array( 'type' => 'number', 'default' => 1 ),
			),
		)
	);

	// khobor/category-section.
	register_block_type(
		'khobor/category-section',
		array(
			'render_callback' => 'khobor_block_category_section_render',
			'attributes'      => array(
				'category' => array( 'type' => 'string', 'default' => '' ),
				'count'    => array( 'type' => 'number', 'default' => 6 ),
				'title'    => array( 'type' => 'string', 'default' => '' ),
			),
		)
	);

	// khobor/news-card (single post).
	register_block_type(
		'khobor/news-card',
		array(
			'render_callback' => 'khobor_block_news_card_render',
			'attributes'      => array(
				'postId' => array( 'type' => 'number', 'default' => 0 ),
			),
		)
	);
}
add_action( 'init', 'khobor_register_blocks' );

function khobor_block_featured_news_render( $attrs ) {
	$args = array(
		'posts_per_page' => max( 1, (int) ( $attrs['count'] ?? 1 ) ),
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	);
	if ( ! empty( $attrs['category'] ) ) {
		$args['category_name'] = sanitize_title( $attrs['category'] );
	}
	$q = new WP_Query( $args );
	ob_start();
	if ( $q->have_posts() ) {
		echo '<div class="khobor-block-featured">';
		while ( $q->have_posts() ) {
			$q->the_post();
			get_template_part( 'template-parts/content/content-card', 'large' );
		}
		echo '</div>';
	}
	wp_reset_postdata();
	return ob_get_clean();
}

function khobor_block_category_section_render( $attrs ) {
	$args = array(
		'posts_per_page' => max( 1, (int) ( $attrs['count'] ?? 6 ) ),
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	);
	if ( ! empty( $attrs['category'] ) ) {
		$args['category_name'] = sanitize_title( $attrs['category'] );
	}
	$q     = new WP_Query( $args );
	$title = ! empty( $attrs['title'] ) ? $attrs['title'] : ( ! empty( $attrs['category'] ) ? $attrs['category'] : __( 'News', 'khobor' ) );
	ob_start();
	if ( $q->have_posts() ) {
		?>
		<section class="khobor-block-category">
			<h2 class="khobor-section-title"><?php echo esc_html( $title ); ?></h2>
			<div class="khobor-grid khobor-grid--3">
				<?php while ( $q->have_posts() ) :
					$q->the_post();
					get_template_part( 'template-parts/content/content-card' );
				endwhile; ?>
			</div>
		</section>
		<?php
	}
	wp_reset_postdata();
	return ob_get_clean();
}

function khobor_block_news_card_render( $attrs ) {
	$post_id = (int) ( $attrs['postId'] ?? 0 );
	if ( ! $post_id || ! get_post( $post_id ) ) {
		return '';
	}
	global $post;
	$saved = $post;
	$post  = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	setup_postdata( $post );
	ob_start();
	get_template_part( 'template-parts/content/content-card' );
	$html = ob_get_clean();
	$post = $saved; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	wp_reset_postdata();
	return $html;
}
