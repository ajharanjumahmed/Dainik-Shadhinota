<?php
/**
 * Content: single news article.
 *
 * @package Khobor
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'khobor-article' ); ?>>

	<?php khobor_ad_zone( 'article_top' ); ?>

	<header class="khobor-article__header">
		<?php khobor_the_primary_category(); ?>

		<h1 class="khobor-article__title"><?php the_title(); ?></h1>

		<?php if ( has_excerpt() ) : ?>
			<p class="khobor-article__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>

		<div class="khobor-article__meta">
			<div class="khobor-article__meta-left">
				<?php
				$avatar = get_avatar( get_the_author_meta( 'ID' ), 36, '', '', array( 'class' => 'khobor-article__avatar' ) );
				echo wp_kses_post( $avatar );
				?>
				<div class="khobor-article__byline">
					<?php khobor_the_post_author(); ?>
					<div class="khobor-article__meta-row">
						<?php khobor_the_post_date(); ?>
						<?php if ( khobor_option( 'enable_reading_time', true ) ) : ?>
							<span class="khobor-meta-sep">·</span>
							<?php khobor_the_reading_time(); ?>
						<?php endif; ?>
						<span class="khobor-meta-sep">·</span>
						<span class="khobor-views"><?php echo esc_html( khobor_views_text( get_the_ID() ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="khobor-article__meta-right">
				<?php khobor_font_sizer(); ?>
			</div>
		</div>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="khobor-article__hero">
			<?php the_post_thumbnail( 'khobor-hero', array( 'fetchpriority' => 'high' ) ); ?>
			<?php
			$caption = get_the_post_thumbnail_caption();
			if ( $caption ) : ?>
				<figcaption><?php echo esc_html( $caption ); ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php endif; ?>

	<div class="khobor-article__body">
		<?php
		the_content();
		wp_link_pages(
			array(
				'before' => '<nav class="khobor-page-links">' . esc_html__( 'Pages:', 'khobor' ),
				'after'  => '</nav>',
			)
		);
		?>
	</div>

	<?php khobor_ad_zone( 'article_bottom' ); ?>

	<footer class="khobor-article__footer">
		<?php khobor_share_buttons(); ?>
		<?php khobor_photocard_button(); ?>

		<?php
		$tags = get_the_tags();
		if ( $tags ) : ?>
			<div class="khobor-article__tags">
				<span class="khobor-article__tags-label"><?php esc_html_e( 'ট্যাগ:', 'khobor' ); ?></span>
				<?php foreach ( $tags as $t ) : ?>
					<a href="<?php echo esc_url( get_term_link( $t ) ); ?>" class="khobor-tag-pill">#<?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</footer>
</article>

<?php
if ( khobor_option( 'enable_author_bio', true ) ) {
	get_template_part( 'template-parts/sections/author-bio' );
}
if ( khobor_option( 'enable_related', true ) ) {
	get_template_part( 'template-parts/sections/related-posts' );
}
?>
