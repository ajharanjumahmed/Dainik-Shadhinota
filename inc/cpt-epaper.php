<?php
/**
 * E-Paper Custom Post Type
 *
 * Holds uploaded PDF newspapers rendered with PDF.js + StPageFlip.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the epaper CPT.
 */
function khobor_register_epaper_cpt() {

	$labels = array(
		'name'               => _x( 'E-Papers', 'post type general name', 'khobor' ),
		'singular_name'      => _x( 'E-Paper', 'post type singular name', 'khobor' ),
		'menu_name'          => _x( 'E-Papers', 'admin menu', 'khobor' ),
		'add_new'            => __( 'Add New', 'khobor' ),
		'add_new_item'       => __( 'Add New E-Paper', 'khobor' ),
		'edit_item'          => __( 'Edit E-Paper', 'khobor' ),
		'new_item'           => __( 'New E-Paper', 'khobor' ),
		'view_item'          => __( 'View E-Paper', 'khobor' ),
		'search_items'       => __( 'Search E-Papers', 'khobor' ),
		'not_found'          => __( 'No e-papers found', 'khobor' ),
		'not_found_in_trash' => __( 'No e-papers found in trash', 'khobor' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'epaper' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-book',
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
	);

	register_post_type( 'epaper', $args );
}
add_action( 'init', 'khobor_register_epaper_cpt' );

/**
 * Meta box for the PDF URL.
 */
function khobor_epaper_meta_box() {
	add_meta_box(
		'khobor_epaper_pdf',
		__( 'E-Paper PDF', 'khobor' ),
		'khobor_epaper_meta_box_render',
		'epaper',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'khobor_epaper_meta_box' );

function khobor_epaper_meta_box_render( $post ) {
	wp_nonce_field( 'khobor_epaper_meta', 'khobor_epaper_nonce' );
	$pdf_url     = get_post_meta( $post->ID, '_khobor_epaper_pdf_url', true );
	$pdf_pages   = get_post_meta( $post->ID, '_khobor_epaper_pages', true );
	$pdf_date    = get_post_meta( $post->ID, '_khobor_epaper_date', true );
	?>
	<p>
		<label for="khobor_epaper_pdf_url"><strong><?php esc_html_e( 'PDF URL', 'khobor' ); ?></strong></label><br>
		<input type="url"
		       id="khobor_epaper_pdf_url"
		       name="khobor_epaper_pdf_url"
		       value="<?php echo esc_attr( $pdf_url ); ?>"
		       class="large-text"
		       placeholder="https://example.com/uploads/2026/05/paper.pdf">
		<button type="button" class="button khobor-media-pick" data-target="khobor_epaper_pdf_url" data-type="application/pdf">
			<?php esc_html_e( 'Choose PDF from Media Library', 'khobor' ); ?>
		</button>
	</p>
	<p>
		<label for="khobor_epaper_date"><strong><?php esc_html_e( 'Issue date', 'khobor' ); ?></strong></label><br>
		<input type="date"
		       id="khobor_epaper_date"
		       name="khobor_epaper_date"
		       value="<?php echo esc_attr( $pdf_date ); ?>">
	</p>
	<p>
		<label for="khobor_epaper_pages"><strong><?php esc_html_e( 'Page count (optional, auto-detected)', 'khobor' ); ?></strong></label><br>
		<input type="number"
		       min="1"
		       id="khobor_epaper_pages"
		       name="khobor_epaper_pages"
		       value="<?php echo esc_attr( $pdf_pages ); ?>">
	</p>
	<p class="description">
		<?php esc_html_e( 'Upload a PDF newspaper. The frontend renders it as a flippable book using PDF.js + StPageFlip.', 'khobor' ); ?>
	</p>
	<?php
}

/**
 * Save meta on post save.
 *
 * @param int $post_id Post ID.
 */
function khobor_epaper_save_meta( $post_id ) {
	if ( ! isset( $_POST['khobor_epaper_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( wp_unslash( $_POST['khobor_epaper_nonce'] ), 'khobor_epaper_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['khobor_epaper_pdf_url'] ) ) {
		update_post_meta(
			$post_id,
			'_khobor_epaper_pdf_url',
			esc_url_raw( wp_unslash( $_POST['khobor_epaper_pdf_url'] ) )
		);
	}
	if ( isset( $_POST['khobor_epaper_date'] ) ) {
		update_post_meta(
			$post_id,
			'_khobor_epaper_date',
			sanitize_text_field( wp_unslash( $_POST['khobor_epaper_date'] ) )
		);
	}
	if ( isset( $_POST['khobor_epaper_pages'] ) ) {
		update_post_meta(
			$post_id,
			'_khobor_epaper_pages',
			absint( $_POST['khobor_epaper_pages'] )
		);
	}
}
add_action( 'save_post_epaper', 'khobor_epaper_save_meta' );
