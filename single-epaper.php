<?php
/**
 * Single E-Paper Template
 *
 * Renders PDF as a flipbook using PDF.js (page → canvas) and
 * StPageFlip (turning animation).
 *
 * @package Khobor
 */
get_header();
?>
<div class="khobor-container khobor-epaper">
	<?php
	while ( have_posts() ) :
		the_post();
		$pdf_url   = get_post_meta( get_the_ID(), '_khobor_epaper_pdf_url', true );
		$pdf_date  = get_post_meta( get_the_ID(), '_khobor_epaper_date', true );
		$pdf_pages = (int) get_post_meta( get_the_ID(), '_khobor_epaper_pages', true );
		?>
		<header class="khobor-epaper__header">
			<h1 class="khobor-epaper__title"><?php the_title(); ?></h1>
			<?php if ( $pdf_date ) : ?>
				<p class="khobor-epaper__date">
					<?php
					$formatted = mysql2date( 'j F, Y', $pdf_date );
					echo esc_html( khobor_maybe_bangla( $formatted ) );
					?>
				</p>
			<?php endif; ?>
		</header>

		<?php if ( $pdf_url ) : ?>
			<div class="khobor-flipbook-wrap">
				<?php // Scroll container: keeps a zoomed book inside the box instead of
				// letting it overflow across the controls below. ?>
				<div class="khobor-flipbook__viewport" id="khobor-flipbook-viewport">
					<div class="khobor-flipbook"
					     id="khobor-flipbook"
					     data-pdf="<?php echo esc_url( $pdf_url ); ?>"
					     data-pages="<?php echo (int) $pdf_pages; ?>">
						<div class="khobor-flipbook__loading">
							<?php esc_html_e( 'লোড হচ্ছে…', 'khobor' ); ?>
						</div>
					</div>
				</div>

				<div class="khobor-flipbook__controls">
					<button type="button" class="khobor-btn" data-flip="prev" aria-label="<?php esc_attr_e( 'Previous page', 'khobor' ); ?>">◀</button>
					<span class="khobor-flipbook__pageinfo" id="khobor-flipbook-pageinfo">–</span>
					<button type="button" class="khobor-btn" data-flip="next" aria-label="<?php esc_attr_e( 'Next page', 'khobor' ); ?>">▶</button>
					<button type="button" class="khobor-btn khobor-btn--ghost" data-flip="zoom-out" aria-label="<?php esc_attr_e( 'Zoom out', 'khobor' ); ?>">−</button>
					<button type="button" class="khobor-btn khobor-btn--ghost khobor-flipbook__zoomlevel" data-flip="zoom-reset" id="khobor-flipbook-zoomlevel" aria-label="<?php esc_attr_e( 'Reset zoom', 'khobor' ); ?>">100%</button>
					<button type="button" class="khobor-btn khobor-btn--ghost" data-flip="zoom-in" aria-label="<?php esc_attr_e( 'Zoom in', 'khobor' ); ?>">＋</button>
					<a class="khobor-btn khobor-btn--ghost" href="<?php echo esc_url( $pdf_url ); ?>" download>
						<?php esc_html_e( 'ডাউনলোড', 'khobor' ); ?>
					</a>
				</div>
			</div>
		<?php else : ?>
			<p class="khobor-epaper__missing">
				<?php esc_html_e( 'এই ইপেপারের সাথে কোনো PDF যুক্ত নেই।', 'khobor' ); ?>
			</p>
		<?php endif; ?>

		<?php if ( get_the_content() ) : ?>
			<div class="khobor-epaper__notes">
				<?php the_content(); ?>
			</div>
		<?php endif; ?>
	<?php endwhile; ?>
</div>
<?php get_footer();
