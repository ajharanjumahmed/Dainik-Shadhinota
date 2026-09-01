<?php
/**
 * Schema.org Structured Data
 *
 * Outputs JSON-LD for NewsArticle on single posts and BreadcrumbList
 * site-wide. Skipped if Yoast or RankMath is active (they output their own).
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether an SEO plugin is already outputting schema.
 *
 * @return bool
 */
function khobor_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
}

/**
 * Output NewsArticle JSON-LD on single posts.
 */
function khobor_news_article_schema() {
	if ( ! is_singular( 'post' ) || khobor_seo_plugin_active() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post ) {
		return;
	}

	$image = '';
	if ( has_post_thumbnail( $post ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), 'khobor-hero' );
		if ( $src ) {
			$image = $src[0];
		}
	}

	$author = get_the_author_meta( 'display_name', $post->post_author );

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'NewsArticle',
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post ),
		),
		'headline'         => get_the_title( $post ),
		'description'      => khobor_excerpt( $post, 200 ),
		'image'            => $image ? array( $image ) : array(),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author,
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => khobor_publisher_logo_url(),
			),
		),
		'inLanguage'       => get_bloginfo( 'language' ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'khobor_news_article_schema', 30 );

/**
 * Output BreadcrumbList JSON-LD when relevant.
 */
function khobor_breadcrumb_schema() {
	if ( is_front_page() || khobor_seo_plugin_active() ) {
		return;
	}
	$items = khobor_get_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}

	$list = array();
	foreach ( $items as $i => $item ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['label'],
			'item'     => $item['url'],
		);
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'khobor_breadcrumb_schema', 31 );

/**
 * Build breadcrumb items for current request.
 *
 * @return array<int, array{label:string,url:string}>
 */
function khobor_get_breadcrumb_items() {
	$items = array(
		array( 'label' => __( 'মূলপাতা', 'khobor' ), 'url' => home_url( '/' ) ),
	);

	if ( is_singular( 'post' ) ) {
		$cat = khobor_primary_category();
		if ( $cat ) {
			$items[] = array( 'label' => $cat->name, 'url' => get_term_link( $cat ) );
		}
		$items[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_singular( 'epaper' ) ) {
		$items[] = array( 'label' => __( 'ইপেপার', 'khobor' ), 'url' => get_post_type_archive_link( 'epaper' ) );
		$items[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_category() ) {
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => get_term_link( get_queried_object() ) );
	} elseif ( is_tag() ) {
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => get_term_link( get_queried_object() ) );
	} elseif ( is_search() ) {
		$items[] = array( 'label' => sprintf( __( 'Search: %s', 'khobor' ), get_search_query() ), 'url' => '' );
	} elseif ( is_page() ) {
		$items[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	}

	return $items;
}

/**
 * Render visible breadcrumb HTML.
 */
function khobor_breadcrumbs() {
	$items = khobor_get_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	?>
	<nav class="khobor-breadcrumb" aria-label="Breadcrumb">
		<ol>
			<?php foreach ( $items as $i => $item ) :
				$is_last = ( $i === count( $items ) - 1 );
				?>
				<li>
					<?php if ( $is_last || empty( $item['url'] ) ) : ?>
						<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Publisher logo URL for schema. Falls back to a square site icon.
 *
 * @return string
 */
function khobor_publisher_logo_url() {
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}
	if ( has_site_icon() ) {
		return get_site_icon_url( 512 );
	}
	return KHOBOR_ASSETS . 'img/logo.svg';
}
