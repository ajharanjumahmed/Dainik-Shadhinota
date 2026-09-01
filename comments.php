<?php
/**
 * Comments Template.
 *
 * @package Khobor
 */
if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="khobor-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="khobor-comments__title">
			<?php
			$num = number_format_i18n( get_comments_number() );
			/* translators: %s: number of comments. */
			printf( esc_html( _n( '%s টি মন্তব্য', '%s টি মন্তব্য', get_comments_number(), 'khobor' ) ), esc_html( khobor_maybe_bangla( $num ) ) );
			?>
		</h2>

		<ol class="khobor-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size'=> 48,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php if ( comments_open() ) :
		comment_form(
			array(
				'class_form'  => 'khobor-comment-form',
				'title_reply' => __( 'মন্তব্য করুন', 'khobor' ),
				'label_submit'=> __( 'মন্তব্য পাঠান', 'khobor' ),
			)
		);
	endif; ?>
</section>
