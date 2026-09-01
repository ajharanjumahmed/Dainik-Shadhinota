<?php
/**
 * Content: large lead card.
 *
 * @package Khobor
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'khobor-card khobor-card--lead' ); ?>>
	<a class="khobor-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php khobor_post_thumbnail( null, 'khobor-card-lg' ); ?>
		<?php $cat = khobor_primary_category(); if ( $cat ) : ?>
			<span class="khobor-card__cat-badge khobor-card__cat-badge--lead"><?php echo esc_html( $cat->name ); ?></span>
		<?php endif; ?>
	</a>

	<div class="khobor-card__body">
		<h2 class="khobor-card__title khobor-card__title--lead">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>
		<p class="khobor-card__excerpt khobor-card__excerpt--lead"><?php echo esc_html( khobor_excerpt( null, 550 ) ); ?></p>
		<div class="khobor-card__meta">
			<?php khobor_the_post_author(); ?>
			<span class="khobor-card__sep">·</span>
			<?php khobor_the_post_date(); ?>
		</div>
	</div>
</article>
