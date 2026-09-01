<?php
/**
 * Content: compact list-style row.
 *
 * @package Khobor
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'khobor-card khobor-card--list' ); ?>>
	<a class="khobor-card__media khobor-card__media--sq" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php khobor_post_thumbnail( null, 'khobor-thumb-sq' ); ?>
	</a>
	<div class="khobor-card__body">
		<h4 class="khobor-card__title khobor-card__title--list">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h4>
		<div class="khobor-card__meta">
			<span><?php echo esc_html( khobor_time_ago() ); ?></span>
		</div>
	</div>
</article>
