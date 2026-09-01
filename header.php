<?php
/**
 * Header template
 *
 * @package Khobor
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="khobor-skip-link screen-reader-text" href="#khobor-main">
	<?php esc_html_e( 'Skip to content', 'khobor' ); ?>
</a>

<div id="khobor-page" class="khobor-page">
	<header id="khobor-masthead" class="khobor-masthead" role="banner">

		<?php get_template_part( 'template-parts/header/top-bar' ); ?>
		<?php get_template_part( 'template-parts/header/logo-area' ); ?>
		<?php get_template_part( 'template-parts/header/main-nav' ); ?>

		<?php khobor_ad_zone( 'header' ); ?>

		<?php
		// Breaking news ticker — only on homepage by default.
		if ( is_front_page() || is_home() ) :
			get_template_part( 'template-parts/header/breaking-ticker' );
			khobor_ad_zone( 'below_ticker' );
		endif;
		?>
	</header><!-- #khobor-masthead -->

	<?php if ( ! is_front_page() && ! is_home() ) : ?>
		<div class="khobor-container khobor-breadcrumb-wrap">
			<?php khobor_breadcrumbs(); ?>
		</div>
	<?php endif; ?>

	<main id="khobor-main" class="khobor-main" tabindex="-1">
