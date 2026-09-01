<?php
/**
 * Content: standard news card.
 *
 * @package Khobor
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'khobor-card' ); ?>>
	<a class="khobor-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
		<?php khobor_post_thumbnail( null, 'khobor-card' ); ?>
		<?php $cat = khobor_primary_category(); if ( $cat ) : ?>
			<span class="khobor-card__cat-badge"><?php echo esc_html( $cat->name ); ?></span>
		<?php endif; ?>
	</a>

	<div class="khobor-card__body">
		<h3 class="khobor-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<div class="khobor-card__meta">
			<span class="khobor-card__date"><?php echo esc_html( khobor_time_ago() ); ?></span>
			<span class="khobor-card__sep">·</span>
			<span class="khobor-card__read"><?php
				$mins = khobor_reading_time();
				echo esc_html( khobor_maybe_bangla( $mins ) . ' ' . __( 'মিনিট', 'khobor' ) );
			?></span>
		</div>

		<p class="khobor-card__excerpt"><?php echo esc_html( khobor_excerpt( null, 120 ) ); ?></p>
	</div>
</article>
