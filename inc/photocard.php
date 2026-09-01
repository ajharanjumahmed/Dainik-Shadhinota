<?php
/**
 * Photocard Generator
 *
 * Renders a 1080x1080 share card: featured image, gradient scrim, headline,
 * optional overlay and logo. Output is cached to
 * /uploads/khobor-photocards/{id}-{hash}.png and regenerated only when
 * something that affects the image changes.
 *
 * ---------------------------------------------------------------------------
 * A NOTE ON BANGLA, because it determines which renderer you want
 * ---------------------------------------------------------------------------
 * Bengali is a complex script. Correct rendering needs a shaping engine that
 * can (a) reorder pre-base vowel signs — ি ে ৈ ো ৌ are *stored* after their
 * consonant but must *display* before it — and (b) apply the font's GSUB
 * tables to fuse conjuncts (ক + ্ + ষ into ক্ষ) instead of leaving a bare
 * hasant visible.
 *
 * PHP GD draws glyphs through FreeType with no HarfBuzz layer, so it does
 * neither. Imagick's annotateImage() has the same limitation. Only the Pango
 * delegate shapes text properly, so that is the preferred path here; the
 * others are kept so the feature degrades instead of fatally erroring, and
 * an admin notice explains what is missing.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KHOBOR_PHOTOCARD_SIZE      = 1080;
const KHOBOR_PHOTOCARD_MAX_LINES = 3;

/**
 * Card layout, in pixels on the 1080x1080 canvas.
 *
 * White masthead strip on top, a gold-bordered image box in the upper middle,
 * and a red panel carrying the headline. The image box deliberately straddles
 * the white/red boundary, which is what gives the card its layered look.
 *
 * @return array
 */
function khobor_photocard_layout() {
	return apply_filters(
		'khobor_photocard_layout',
		array(
			'masthead_top'    => 24,
			'masthead_height' => 68,

			'box_x'           => 74,
			'box_y'           => 104,
			'box_w'           => 932,
			'box_h'           => 620,
			'box_border'      => 9,
			'box_radius'      => 20,

			'red_top'         => 592,

			'headline_top'    => 772,
			'headline_bottom' => 968,
			'headline_pad'    => 80,

			'footer_baseline' => 1040,
			'footer_pad'      => 74,
			'footer_size'     => 26,
		)
	);
}

/**
 * Card colours. All overridable from the Customizer or via the filter.
 *
 * @return array
 */
function khobor_photocard_colors() {
	return apply_filters(
		'khobor_photocard_colors',
		array(
			'paper'    => '#ffffff',
			'panel'    => khobor_sanitize_hex( khobor_option( 'photocard_accent', '#b00d16' ), '#b00d16' ),
			'border'   => khobor_sanitize_hex( khobor_option( 'photocard_border', '#f7b500' ), '#f7b500' ),
			'headline' => khobor_sanitize_hex( khobor_option( 'photocard_title_color', '#ffffff' ), '#ffffff' ),
			'footer'   => '#ffffff',
		)
	);
}

/**
 * Filename stem for generated cards, e.g. "dainikshadhinota-photocard".
 *
 * Used for both the cached file on disk and the browser's download name, so a
 * saved card is identifiable instead of being a bare hash.
 *
 * @return string
 */
function khobor_photocard_filename_base() {
	$name = strtolower( khobor_photocard_site_slug() );
	return (string) apply_filters( 'khobor_photocard_filename_base', $name . '-photocard' );
}

/**
 * Site name reduced to bare letters and digits, e.g. "DainikShadhinota".
 *
 * Falls back to the domain when the site title has no Latin characters at all
 * — a Bangla-only title would otherwise reduce to an empty string.
 *
 * @return string
 */
function khobor_photocard_site_slug() {
	$name = preg_replace( '/[^A-Za-z0-9]/', '', (string) get_bloginfo( 'name' ) );

	if ( '' === $name ) {
		$host = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$name = preg_replace( '/[^A-Za-z0-9]/', '', (string) strtok( $host, '.' ) );
	}

	return ( '' === $name ) ? 'Khobor' : $name;
}

/**
 * Name the browser saves a downloaded card under.
 *
 * Deliberately not built from the post slug: Bengali slugs are stored
 * percent-encoded, so post_name turns into an unreadable
 * "%e0%a6%86%e0%a6%ae..." string. The post ID is short, stable and readable.
 *
 * @param WP_Post $post Post.
 * @return string e.g. "DainikShadhinota Photocard 42.png"
 */
function khobor_photocard_download_name( WP_Post $post ) {
	return (string) apply_filters(
		'khobor_photocard_download_name',
		sprintf( '%s Photocard %d.png', khobor_photocard_site_slug(), $post->ID ),
		$post
	);
}

/**
 * The three footer strings: date, call to action, site domain.
 *
 * @param WP_Post $post Post.
 * @return array
 */
function khobor_photocard_footer_text( WP_Post $post ) {
	$domain = wp_parse_url( home_url(), PHP_URL_HOST );

	return apply_filters(
		'khobor_photocard_footer_text',
		array(
			'left'   => function_exists( 'khobor_bangla_date' ) ? khobor_bangla_date( $post->post_date ) : '',
			'center' => __( '« বিস্তারিত কমেন্টে »', 'khobor' ),
			'right'  => preg_replace( '/^www\./', '', (string) $domain ),
		),
		$post
	);
}

// ---------------------------------------------------------------------------
// Capability detection
// ---------------------------------------------------------------------------

function khobor_photocard_imagick_available() {
	return extension_loaded( 'imagick' ) && class_exists( 'Imagick' );
}

function khobor_photocard_gd_available() {
	return extension_loaded( 'gd' ) && function_exists( 'imagecreatetruecolor' );
}

/**
 * Which text renderer this server can use, best first.
 *
 * - 'pango'   Imagick built with the Pango delegate. Shapes complex scripts.
 * - 'imagick' Imagick without Pango. Latin only; mangles Bangla.
 * - 'gd'      GD + FreeType. Latin only; mangles Bangla.
 * - false     No image library at all.
 *
 * @return string|false
 */
function khobor_photocard_text_engine() {
	if ( khobor_photocard_imagick_available() ) {
		try {
			if ( Imagick::queryFormats( 'PANGO' ) ) {
				return 'pango';
			}
		} catch ( \Exception $e ) {
			// Fall through to the unshaped Imagick path.
		}
		return 'imagick';
	}
	if ( khobor_photocard_gd_available() ) {
		return 'gd';
	}
	return false;
}

/**
 * Can this server lay out Bengali (or any complex script) correctly?
 *
 * @return bool
 */
function khobor_photocard_shapes_complex_text() {
	return 'pango' === khobor_photocard_text_engine();
}

// No admin notice about shaping: the front-end photocard is drawn on a canvas
// in the browser (assets/js/photocard.js), which shapes Bengali correctly on
// every platform. The server renderers below only matter if PHP generates a
// card directly, so a dashboard-wide warning would be misleading.

// ---------------------------------------------------------------------------
// Paths and assets
// ---------------------------------------------------------------------------

/**
 * Resolve the upload directory used for cached photocards.
 *
 * @return array|WP_Error { 'dir', 'url' }
 */
function khobor_photocard_storage() {
	$uploads = wp_get_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'upload_dir', $uploads['error'] );
	}
	$dir = trailingslashit( $uploads['basedir'] ) . 'khobor-photocards';
	$url = trailingslashit( $uploads['baseurl'] ) . 'khobor-photocards';
	if ( ! file_exists( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'mkdir', __( 'Could not create the photocard directory.', 'khobor' ) );
	}
	return array( 'dir' => $dir, 'url' => $url );
}

function khobor_photocard_overlay_url() {
	$default = KHOBOR_ASSETS . 'img/photocard-default-overlay.png';
	return khobor_option( 'photocard_overlay', $default );
}

/**
 * Find a usable Bengali TTF. Checks theme assets first, then system paths.
 *
 * @return string|false Absolute path, or false if none found.
 */
function khobor_photocard_font_path() {
	$candidates = array(
		// Preferred: static Bold cut (download from Google Fonts → place here).
		KHOBOR_DIR . 'assets/fonts/NotoSansBengali-Bold.ttf',
		// Variable font that ships with the theme download.
		KHOBOR_DIR . 'assets/fonts/NotoSansBengali-VariableFont_wdth,wght.ttf',
		// Other common Bengali fonts a developer might have dropped in.
		KHOBOR_DIR . 'assets/fonts/HindSiliguri-Bold.ttf',
		KHOBOR_DIR . 'assets/fonts/SolaimanLipi.ttf',
		// Linux server system fonts.
		'/usr/share/fonts/truetype/noto/NotoSansBengali-Bold.ttf',
		'/usr/share/fonts/truetype/lohit-bengali/Lohit-Bengali.ttf',
	);

	/**
	 * Filter the candidate font list.
	 *
	 * @param string[] $candidates Absolute paths, first match wins.
	 */
	$candidates = apply_filters( 'khobor_photocard_font_candidates', $candidates );

	foreach ( $candidates as $path ) {
		if ( file_exists( $path ) && is_readable( $path ) ) {
			return $path;
		}
	}
	return false;
}

/**
 * Font family name for Pango, which resolves fonts through fontconfig by name
 * rather than by file path.
 *
 * @return string
 */
function khobor_photocard_font_family() {
	return (string) apply_filters( 'khobor_photocard_font_family', 'Noto Sans Bengali' );
}

/**
 * Map a site URL to an absolute local file path.
 *
 * @param string $url URL to resolve.
 * @return string|null
 */
function khobor_photocard_resolve_local_path( $url ) {
	$uploads = wp_get_upload_dir();
	if ( 0 === strpos( $url, $uploads['baseurl'] ) ) {
		return str_replace( $uploads['baseurl'], $uploads['basedir'], $url );
	}
	if ( 0 === strpos( $url, KHOBOR_URI ) ) {
		return str_replace( KHOBOR_URI, KHOBOR_DIR, $url );
	}
	if ( 0 === strpos( $url, site_url() ) ) {
		return str_replace( site_url(), ABSPATH, $url );
	}
	return null;
}

// ---------------------------------------------------------------------------
// Text layout
// ---------------------------------------------------------------------------

/**
 * Truncate without splitting a grapheme cluster.
 *
 * mb_substr() counts code points, so cutting mid-cluster can strand a combining
 * mark — a lone vowel sign with nothing to attach to. Graphemes are the unit a
 * reader perceives as one character, which is what we want to count.
 *
 * @param string $text  Text.
 * @param int    $limit Max clusters.
 * @return string
 */
function khobor_photocard_truncate( $text, $limit = 140 ) {
	if ( function_exists( 'grapheme_strlen' ) ) {
		if ( grapheme_strlen( $text ) <= $limit ) {
			return $text;
		}
		return rtrim( grapheme_substr( $text, 0, $limit ) ) . '…';
	}
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}
	return rtrim( mb_substr( $text, 0, $limit ) ) . '…';
}

/**
 * Move Bengali pre-base vowel signs in front of their consonant cluster.
 *
 * Bengali stores ি ে ৈ ো ৌ *after* the consonant they attach to, but they must
 * *display* before it. A real shaping engine (Pango/HarfBuzz) does that
 * reordering; FreeType on its own draws the codepoints in storage order, so
 * পুলিশের comes out as পুলিশরে.
 *
 * This rewrites the string into visual order so a non-shaping renderer lands
 * the vowel signs on the correct side. It also splits the two-part vowels,
 * which are a pre-base and a post-base piece fused into one codepoint:
 *
 *     ো (U+09CB) = ে + া        ৌ (U+09CC) = ে + ৗ
 *
 * This is a mitigation, not a shaper: conjunct formation and reph positioning
 * still need real GSUB support. Never apply it on the Pango path — the text
 * would be reordered twice.
 *
 * @param string $text Logical-order Bengali.
 * @return string Visual-order approximation.
 */
function khobor_photocard_reorder_bengali( $text ) {
	$consonant = '\x{0995}-\x{09B9}\x{09DC}-\x{09DF}\x{09F0}-\x{09F1}';

	// A cluster is a consonant, optionally nukta'd, plus any virama-joined
	// consonants that hang off it.
	$cluster = '[' . $consonant . ']\x{09BC}?(?:\x{09CD}[' . $consonant . ']\x{09BC}?)*';

	$reordered = preg_replace_callback(
		'/(' . $cluster . ')([\x{09BF}\x{09C7}\x{09C8}\x{09CB}\x{09CC}])/u',
		static function ( $m ) {
			$two_part = array(
				"\u{09CB}" => array( "\u{09C7}", "\u{09BE}" ), // ো
				"\u{09CC}" => array( "\u{09C7}", "\u{09D7}" ), // ৌ
			);
			if ( isset( $two_part[ $m[2] ] ) ) {
				return $two_part[ $m[2] ][0] . $m[1] . $two_part[ $m[2] ][1];
			}
			return $m[2] . $m[1];
		},
		$text
	);

	return ( null === $reordered ) ? $text : $reordered;
}

/**
 * Prepare a headline for the active renderer.
 *
 * @param string $text Headline.
 * @return string
 */
function khobor_photocard_prepare_text( $text ) {
	if ( khobor_photocard_shapes_complex_text() ) {
		return $text; // Pango shapes from logical order.
	}
	return khobor_photocard_reorder_bengali( $text );
}

/**
 * Rendered width of a string in pixels, via FreeType.
 *
 * @param string $font Absolute font path.
 * @param int    $size Point size.
 * @param string $text Text.
 * @return int
 */
function khobor_photocard_text_width( $font, $size, $text ) {
	$box = @imagettfbbox( $size, 0, $font, $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $box ) {
		return 0;
	}
	// Span all four corners rather than assuming corner 0 is the leftmost:
	// glyphs with a negative left bearing (guillemets, some Bengali marks)
	// otherwise measure narrower than they draw.
	$xs = array( $box[0], $box[2], $box[4], $box[6] );
	return (int) ceil( max( $xs ) - min( $xs ) );
}

/**
 * Word-wrap by measured pixel width.
 *
 * The previous implementation counted characters, which is meaningless for
 * Bengali: combining marks each count as a character but add little or no
 * width, so lines came out wildly uneven.
 *
 * @param string $text      Text.
 * @param string $font      Absolute font path.
 * @param int    $size      Point size.
 * @param int    $max_width Available width in pixels.
 * @return string[]
 */
function khobor_photocard_wrap_text( $text, $font, $size, $max_width ) {
	$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
	if ( empty( $words ) ) {
		return array();
	}

	$lines   = array();
	$current = '';

	foreach ( $words as $word ) {
		$candidate = ( '' === $current ) ? $word : $current . ' ' . $word;

		// Keep a lone over-long word on its own line rather than looping.
		if ( '' === $current || khobor_photocard_text_width( $font, $size, $candidate ) <= $max_width ) {
			$current = $candidate;
			continue;
		}

		$lines[] = $current;
		$current = $word;
	}

	if ( '' !== $current ) {
		$lines[] = $current;
	}

	return $lines;
}

/**
 * Pick the largest font size at which the headline still fits the text box.
 *
 * @param string $text      Text.
 * @param string $font      Absolute font path.
 * @param int    $max_width Available width in pixels.
 * @param int    $max_lines Line budget.
 * @return array { 'size' => int, 'lines' => string[] }
 */
function khobor_photocard_fit_text( $text, $font, $max_width, $max_lines = KHOBOR_PHOTOCARD_MAX_LINES ) {
	$lines = array( $text );

	for ( $size = 52; $size >= 26; $size -= 2 ) {
		$lines = khobor_photocard_wrap_text( $text, $font, $size, $max_width );
		if ( count( $lines ) <= $max_lines ) {
			return array( 'size' => $size, 'lines' => $lines );
		}
	}

	// Still too long at the smallest size: clip and mark the overflow.
	$lines = array_slice( $lines, 0, $max_lines );
	if ( $lines ) {
		$lines[ count( $lines ) - 1 ] .= '…';
	}
	return array( 'size' => 26, 'lines' => $lines );
}

// ---------------------------------------------------------------------------
// Browser-side rendering payload
// ---------------------------------------------------------------------------

/**
 * Everything the front-end canvas renderer needs to draw a card.
 *
 * The browser draws the card because it is the only text engine in this stack
 * that shapes Bengali correctly — see the file header. PHP still owns the
 * layout and copy so both renderers stay in step.
 *
 * Note the text here is in *logical* order, unshaped and unreordered: the
 * browser does its own shaping and would double-reorder a pre-shaped string.
 *
 * @param int|WP_Post $post Post.
 * @return array|null
 */
function khobor_photocard_client_data( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}

	$thumb_id  = get_post_thumbnail_id( $post->ID );
	$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '';

	$logo_id  = get_theme_mod( 'custom_logo' );
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

	$footer = khobor_photocard_footer_text( $post );

	return array(
		'size'     => KHOBOR_PHOTOCARD_SIZE,
		'maxLines' => KHOBOR_PHOTOCARD_MAX_LINES,
		'layout'   => khobor_photocard_layout(),
		'colors'   => khobor_photocard_colors(),
		'title'    => khobor_photocard_truncate( wp_strip_all_tags( $post->post_title ), 140 ),
		'image'    => $thumb_url ? $thumb_url : '',
		'logo'     => $logo_url ? $logo_url : '',
		'overlay'  => khobor_photocard_overlay_url(),
		'footer'   => $footer,
		'font'     => (string) apply_filters( 'khobor_photocard_css_font', "'Noto Serif Bengali', 'Noto Sans Bengali', serif" ),
		'filename' => khobor_photocard_download_name( $post ),
	);
}

// ---------------------------------------------------------------------------
// Main entry point
// ---------------------------------------------------------------------------

/**
 * Generate (or return cached) photocard URL for a post.
 *
 * @param int  $post_id Post ID.
 * @param bool $force   Skip cache.
 * @return string|WP_Error URL on success.
 */
function khobor_generate_photocard( $post_id, $force = false ) {
	$post_id = absint( $post_id );
	$post    = get_post( $post_id );
	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'invalid_post', __( 'Post not found.', 'khobor' ) );
	}

	$engine = khobor_photocard_text_engine();
	if ( false === $engine ) {
		return new WP_Error(
			'no_image_lib',
			__( 'Photocard generation requires the Imagick or GD PHP extension. Please contact your host.', 'khobor' )
		);
	}

	$storage = khobor_photocard_storage();
	if ( is_wp_error( $storage ) ) {
		return $storage;
	}

	// Everything that changes the pixels belongs in the key, or edits to the
	// colour or overlay would keep serving a stale card.
	$cache_key = $post_id . '-' . md5(
		implode(
			'|',
			array(
				$post->post_title,
				get_post_thumbnail_id( $post_id ),
				khobor_photocard_overlay_url(),
				khobor_option( 'photocard_title_color', '#ffffff' ),
				(string) get_theme_mod( 'custom_logo' ),
				$engine,
				'v2', // Bump to invalidate every cached card after a layout change.
			)
		)
	);

	$name     = khobor_photocard_filename_base() . '-' . $cache_key;
	$file     = trailingslashit( $storage['dir'] ) . $name . '.png';
	$file_url = trailingslashit( $storage['url'] ) . $name . '.png';

	if ( ! $force && file_exists( $file ) ) {
		return $file_url;
	}

	if ( 'gd' === $engine ) {
		return khobor_generate_photocard_gd( $post, $file, $file_url );
	}
	return khobor_generate_photocard_imagick( $post, $file, $file_url, $engine );
}

// ---------------------------------------------------------------------------
// Imagick implementation
// ---------------------------------------------------------------------------

/**
 * Render the headline as a transparent Imagick layer using Pango, which shapes
 * complex scripts correctly (reordering, conjuncts, kerning) and wraps for us.
 *
 * @param string $text      Headline.
 * @param int    $max_width Text box width in pixels.
 * @param string $color     Hex colour.
 * @return Imagick|null
 */
function khobor_photocard_pango_layer( $text, $max_width, $color, $font_size = 46, $align = 'center', $weight = 'Bold' ) {
	$markup = sprintf(
		'<span font="%s %s %d" foreground="%s">%s</span>',
		esc_attr( khobor_photocard_font_family() ),
		esc_attr( $weight ),
		(int) $font_size,
		esc_attr( $color ),
		htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' )
	);

	try {
		$layer = new Imagick();
		$layer->setBackgroundColor( new ImagickPixel( 'transparent' ) );
		// Pango measures in its own units: 1 pixel = 1024 pango units.
		$layer->setOption( 'pango:width', (string) ( (int) $max_width * 1024 ) );
		$layer->setOption( 'pango:wrap', 'word' );
		$layer->setOption( 'pango:align', $align );
		$layer->readImage( 'pango:' . $markup );
		$layer->setImageFormat( 'png' );
		return $layer;
	} catch ( \Exception $e ) {
		return null;
	}
}

/**
 * Shrink the headline until its shaped block fits the available height.
 *
 * Pango does the wrapping, so the only free variable is point size.
 *
 * @param string $text      Headline.
 * @param int    $max_width Text box width.
 * @param int    $max_h     Text box height.
 * @param string $color     Hex colour.
 * @return Imagick|null
 */
function khobor_photocard_pango_fit( $text, $max_width, $max_h, $color ) {
	for ( $size = 50; $size >= 28; $size -= 3 ) {
		$layer = khobor_photocard_pango_layer( $text, $max_width, $color, $size );
		if ( ! $layer ) {
			return null;
		}
		if ( $layer->getImageHeight() <= $max_h ) {
			return $layer;
		}
		$layer->clear();
		$layer->destroy();
	}
	return khobor_photocard_pango_layer( $text, $max_width, $color, 28 );
}

function khobor_generate_photocard_imagick( WP_Post $post, string $file, string $file_url, string $engine = 'pango' ) {
	$post_id = $post->ID;
	$size    = KHOBOR_PHOTOCARD_SIZE;
	$L       = khobor_photocard_layout();
	$C       = khobor_photocard_colors();

	// 1. Paper, then the red panel across the lower two-thirds.
	$canvas = new Imagick();
	$canvas->newImage( $size, $size, new ImagickPixel( $C['paper'] ) );
	$canvas->setImageFormat( 'png' );

	$panel = new ImagickDraw();
	$panel->setFillColor( new ImagickPixel( $C['panel'] ) );
	$panel->rectangle( 0, $L['red_top'], $size, $size );
	$canvas->drawImage( $panel );
	$panel->clear();
	$panel->destroy();

	// 2. Masthead, centred on the white strip.
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_path = get_attached_file( $logo_id );
		if ( $logo_path && file_exists( $logo_path ) ) {
			try {
				$logo = new Imagick( $logo_path );
				$logo->setImageBackgroundColor( 'transparent' );
				$logo->resizeImage( 0, $L['masthead_height'], Imagick::FILTER_LANCZOS, 1 );
				$canvas->compositeImage(
					$logo,
					Imagick::COMPOSITE_OVER,
					(int) round( ( $size - $logo->getImageWidth() ) / 2 ),
					$L['masthead_top']
				);
				$logo->clear();
				$logo->destroy();
			} catch ( \Exception $e ) {
				// Logo failed — continue without it.
			}
		}
	}

	// 3. Gold frame with the photo inset inside it, corners matched.
	$frame = new ImagickDraw();
	$frame->setFillColor( new ImagickPixel( $C['border'] ) );
	$frame->roundRectangle(
		$L['box_x'],
		$L['box_y'],
		$L['box_x'] + $L['box_w'],
		$L['box_y'] + $L['box_h'],
		$L['box_radius'],
		$L['box_radius']
	);
	$canvas->drawImage( $frame );
	$frame->clear();
	$frame->destroy();

	$b       = $L['box_border'];
	$inner_w = $L['box_w'] - ( $b * 2 );
	$inner_h = $L['box_h'] - ( $b * 2 );
	$inner_r = max( 0, $L['box_radius'] - $b );

	$thumb_id = get_post_thumbnail_id( $post_id );
	$photo    = null;
	if ( $thumb_id ) {
		$thumb_path = get_attached_file( $thumb_id );
		if ( $thumb_path && file_exists( $thumb_path ) ) {
			try {
				$photo = new Imagick( $thumb_path );
				$photo->setImageBackgroundColor( '#0f172a' );
				$photo->cropThumbnailImage( $inner_w, $inner_h );
			} catch ( \Exception $e ) {
				$photo = null;
			}
		}
	}
	if ( ! $photo ) {
		$photo = new Imagick();
		$photo->newImage( $inner_w, $inner_h, new ImagickPixel( '#0f172a' ) );
	}
	$photo->setImageFormat( 'png' );

	// Overlay goes onto the photo so a watermark lands inside the frame.
	$overlay_path = khobor_photocard_resolve_local_path( khobor_photocard_overlay_url() );
	if ( $overlay_path && file_exists( $overlay_path ) ) {
		try {
			$overlay = new Imagick( $overlay_path );
			$overlay->resizeImage( $inner_w, $inner_h, Imagick::FILTER_LANCZOS, 1 );
			$photo->compositeImage( $overlay, Imagick::COMPOSITE_OVER, 0, 0 );
			$overlay->clear();
			$overlay->destroy();
		} catch ( \Exception $e ) {
			// Overlay failed — continue without it.
		}
	}

	// Round the photo's corners by multiplying in a rounded-rectangle mask.
	try {
		$mask = new Imagick();
		$mask->newImage( $inner_w, $inner_h, new ImagickPixel( 'black' ) );
		$mask_draw = new ImagickDraw();
		$mask_draw->setFillColor( new ImagickPixel( 'white' ) );
		$mask_draw->roundRectangle( 0, 0, $inner_w - 1, $inner_h - 1, $inner_r, $inner_r );
		$mask->drawImage( $mask_draw );
		$mask_draw->clear();
		$mask_draw->destroy();

		$photo->setImageAlphaChannel( Imagick::ALPHACHANNEL_SET );
		$photo->compositeImage( $mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0 );
		$mask->clear();
		$mask->destroy();
	} catch ( \Exception $e ) {
		// Square corners are an acceptable degradation.
	}

	$canvas->compositeImage( $photo, Imagick::COMPOSITE_OVER, $L['box_x'] + $b, $L['box_y'] + $b );
	$photo->clear();
	$photo->destroy();

	// 4. Headline: bold, centred, on the red panel.
	$title    = khobor_photocard_truncate( wp_strip_all_tags( $post->post_title ), 140 );
	$avail_w  = $size - ( $L['headline_pad'] * 2 );
	$avail_h  = $L['headline_bottom'] - $L['headline_top'];
	$font     = khobor_photocard_font_path();

	if ( $title ) {
		$layer = ( 'pango' === $engine )
			? khobor_photocard_pango_fit( $title, $avail_w, $avail_h, $C['headline'] )
			: null;

		if ( $layer ) {
			// Pango already shaped, wrapped and centred the block.
			$canvas->compositeImage(
				$layer,
				Imagick::COMPOSITE_OVER,
				(int) round( ( $size - $layer->getImageWidth() ) / 2 ),
				$L['headline_top'] + (int) round( ( $avail_h - $layer->getImageHeight() ) / 2 )
			);
			$layer->clear();
			$layer->destroy();
		} elseif ( $font ) {
			// Unshaped fallback: correct for Latin, approximate for Bangla.
			$title   = khobor_photocard_prepare_text( $title );
			$fit     = khobor_photocard_fit_text( $title, $font, $avail_w );
			$line_h  = (int) round( $fit['size'] * 1.5 );
			$block_h = count( $fit['lines'] ) * $line_h;

			$draw = new ImagickDraw();
			$draw->setFont( $font );
			$draw->setFontSize( $fit['size'] );
			$draw->setFillColor( new ImagickPixel( $C['headline'] ) );
			$draw->setGravity( Imagick::GRAVITY_NORTH );
			$draw->setTextAlignment( Imagick::ALIGN_CENTER );
			$draw->setTextAntialias( true );

			$y = $L['headline_top'] + (int) round( ( $avail_h - $block_h ) / 2 ) + $fit['size'];
			foreach ( $fit['lines'] as $line ) {
				$canvas->annotateImage( $draw, 0, $y, 0, $line );
				$y += $line_h;
			}
			$draw->clear();
			$draw->destroy();
		}
	}

	// 5. Footer strip: date left, call to action centre, domain right.
	$footer = khobor_photocard_footer_text( $post );
	if ( 'pango' !== $engine ) {
		$footer = array_map( 'khobor_photocard_prepare_text', $footer );
	}
	foreach ( array( 'left', 'center', 'right' ) as $slot ) {
		if ( '' === trim( (string) $footer[ $slot ] ) ) {
			continue;
		}

		if ( 'pango' === $engine ) {
			$strip = khobor_photocard_pango_layer(
				$footer[ $slot ],
				$size - ( $L['footer_pad'] * 2 ),
				$C['footer'],
				$L['footer_size'],
				$slot,
				'Normal'
			);
			if ( $strip ) {
				$x = $L['footer_pad'];
				if ( 'center' === $slot ) {
					$x = (int) round( ( $size - $strip->getImageWidth() ) / 2 );
				} elseif ( 'right' === $slot ) {
					$x = $size - $L['footer_pad'] - $strip->getImageWidth();
				}
				$canvas->compositeImage(
					$strip,
					Imagick::COMPOSITE_OVER,
					$x,
					$L['footer_baseline'] - $strip->getImageHeight()
				);
				$strip->clear();
				$strip->destroy();
				continue;
			}
		}

		if ( $font ) {
			$draw = new ImagickDraw();
			$draw->setFont( $font );
			$draw->setFontSize( $L['footer_size'] );
			$draw->setFillColor( new ImagickPixel( $C['footer'] ) );
			$draw->setTextAntialias( true );

			if ( 'center' === $slot ) {
				$draw->setTextAlignment( Imagick::ALIGN_CENTER );
				$x = (int) round( $size / 2 );
			} elseif ( 'right' === $slot ) {
				$draw->setTextAlignment( Imagick::ALIGN_RIGHT );
				$x = $size - $L['footer_pad'];
			} else {
				$draw->setTextAlignment( Imagick::ALIGN_LEFT );
				$x = $L['footer_pad'];
			}

			$canvas->annotateImage( $draw, $x, $L['footer_baseline'], 0, $footer[ $slot ] );
			$draw->clear();
			$draw->destroy();
		}
	}

	$canvas->setImageCompressionQuality( 90 );
	try {
		$canvas->writeImage( $file );
	} catch ( \Exception $e ) {
		$canvas->clear();
		$canvas->destroy();
		return new WP_Error( 'write_failed', __( 'Could not write photocard.', 'khobor' ) );
	}
	$canvas->clear();
	$canvas->destroy();

	return $file_url;
}

// ---------------------------------------------------------------------------
// GD fallback
// ---------------------------------------------------------------------------

/**
 * Load an image file into a GD image, preserving alpha.
 *
 * @param string $path Absolute path.
 * @return GdImage|false
 */
function khobor_gd_load_image( $path ) {
	$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$type = $info ? $info[2] : null;

	switch ( $type ) {
		case IMAGETYPE_JPEG:
			$img = function_exists( 'imagecreatefromjpeg' ) ? @imagecreatefromjpeg( $path ) : false;
			break;
		case IMAGETYPE_PNG:
			$img = function_exists( 'imagecreatefrompng' ) ? @imagecreatefrompng( $path ) : false;
			break;
		case IMAGETYPE_WEBP:
			$img = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : false;
			break;
		case IMAGETYPE_GIF:
			$img = function_exists( 'imagecreatefromgif' ) ? @imagecreatefromgif( $path ) : false;
			break;
		default:
			return false;
	}
	// phpcs:enable WordPress.PHP.NoSilencedErrors

	if ( $img ) {
		// Without these, scaling and copying flatten transparency to black.
		imagealphablending( $img, false );
		imagesavealpha( $img, true );
	}
	return $img;
}

/**
 * Allocate a GD colour from a #rrggbb string.
 *
 * @param GdImage $img GD image.
 * @param string  $hex Hex colour.
 * @return int
 */
function khobor_gd_color( $img, $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return imagecolorallocate(
		$img,
		(int) hexdec( substr( $hex, 0, 2 ) ),
		(int) hexdec( substr( $hex, 2, 2 ) ),
		(int) hexdec( substr( $hex, 4, 2 ) )
	);
}

/**
 * Filled rectangle with rounded corners.
 *
 * @param GdImage $img    Target.
 * @param int     $x      Left.
 * @param int     $y      Top.
 * @param int     $w      Width.
 * @param int     $h      Height.
 * @param int     $radius Corner radius.
 * @param int     $color  Allocated colour.
 * @return void
 */
function khobor_gd_rounded_rect( $img, $x, $y, $w, $h, $radius, $color ) {
	$radius = (int) max( 0, min( $radius, (int) floor( min( $w, $h ) / 2 ) ) );
	$x2     = $x + $w - 1;
	$y2     = $y + $h - 1;

	// Cross of two rectangles, then fill the four corners with quarter discs.
	imagefilledrectangle( $img, $x + $radius, $y, $x2 - $radius, $y2, $color );
	imagefilledrectangle( $img, $x, $y + $radius, $x2, $y2 - $radius, $color );

	if ( $radius > 0 ) {
		$d = $radius * 2;
		imagefilledellipse( $img, $x + $radius, $y + $radius, $d, $d, $color );
		imagefilledellipse( $img, $x2 - $radius, $y + $radius, $d, $d, $color );
		imagefilledellipse( $img, $x + $radius, $y2 - $radius, $d, $d, $color );
		imagefilledellipse( $img, $x2 - $radius, $y2 - $radius, $d, $d, $color );
	}
}

/**
 * Punch transparent rounded corners into a standalone image.
 *
 * The image box sits across the white/red boundary, so the corners cannot be
 * masked by painting a background colour over them — they have to become
 * genuinely transparent and composite over whatever is behind.
 *
 * Only the corner squares are touched, so this stays cheap regardless of the
 * image size.
 *
 * @param GdImage $img    Image to modify in place.
 * @param int     $radius Corner radius.
 * @return void
 */
function khobor_gd_round_corners( $img, $radius ) {
	$w = imagesx( $img );
	$h = imagesy( $img );

	imagealphablending( $img, false );
	imagesavealpha( $img, true );

	$corners = array(
		array( 0,           0,           $radius,         $radius ),         // TL
		array( $w - $radius, 0,          $w - $radius - 1, $radius ),        // TR
		array( 0,           $h - $radius, $radius,        $h - $radius - 1 ), // BL
		array( $w - $radius, $h - $radius, $w - $radius - 1, $h - $radius - 1 ), // BR
	);

	foreach ( $corners as $corner ) {
		list( $ox, $oy, $cx, $cy ) = $corner;

		for ( $x = $ox; $x < $ox + $radius; $x++ ) {
			for ( $y = $oy; $y < $oy + $radius; $y++ ) {
				if ( $x < 0 || $y < 0 || $x >= $w || $y >= $h ) {
					continue;
				}
				$dist = sqrt( ( ( $x - $cx ) ** 2 ) + ( ( $y - $cy ) ** 2 ) );
				if ( $dist <= $radius ) {
					continue;
				}
				// One pixel of feathering keeps the curve from looking jagged.
				$alpha = ( $dist >= $radius + 1 ) ? 127 : (int) round( ( $dist - $radius ) * 127 );
				$rgb   = imagecolorat( $img, $x, $y );
				imagesetpixel(
					$img,
					$x,
					$y,
					imagecolorallocatealpha(
						$img,
						( $rgb >> 16 ) & 0xFF,
						( $rgb >> 8 ) & 0xFF,
						$rgb & 0xFF,
						max( 0, min( 127, $alpha ) )
					)
				);
			}
		}
	}
}

/**
 * Cover-crop an image file to exact dimensions.
 *
 * @param string $path Absolute path.
 * @param int    $w    Target width.
 * @param int    $h    Target height.
 * @return GdImage|null
 */
function khobor_gd_cover_crop( $path, $w, $h ) {
	$src = khobor_gd_load_image( $path );
	if ( ! $src ) {
		return null;
	}

	$sw    = imagesx( $src );
	$sh    = imagesy( $src );
	$scale = max( $w / $sw, $h / $sh );

	$crop_w = (int) round( $w / $scale );
	$crop_h = (int) round( $h / $scale );
	$src_x  = (int) round( ( $sw - $crop_w ) / 2 );
	$src_y  = (int) round( ( $sh - $crop_h ) / 2 );

	$out = imagecreatetruecolor( $w, $h );
	imagealphablending( $out, false );
	imagesavealpha( $out, true );
	imagecopyresampled( $out, $src, 0, 0, $src_x, $src_y, $w, $h, $crop_w, $crop_h );
	imagedestroy( $src );

	return $out;
}

/**
 * Draw text horizontally anchored at left, centre, or right.
 *
 * @param GdImage $img   Target.
 * @param int     $size  Point size.
 * @param string  $font  Font path.
 * @param string  $text  Text.
 * @param int     $x     Anchor x.
 * @param int     $y     Baseline y.
 * @param int     $color Allocated colour.
 * @param string  $align left|center|right.
 * @return void
 */
function khobor_gd_text( $img, $size, $font, $text, $x, $y, $color, $align = 'left', $bold = false ) {
	if ( '' === trim( (string) $text ) ) {
		return;
	}
	$width = khobor_photocard_text_width( $font, $size, $text );

	if ( 'center' === $align ) {
		$x -= (int) round( $width / 2 );
	} elseif ( 'right' === $align ) {
		$x -= $width;
	}

	$x = (int) $x;
	$y = (int) $y;

	// Faux bold. Only a Regular cut of Noto Sans Bengali ships with the theme
	// (and FreeType renders a variable font at its default instance), so weight
	// is emulated by smearing the glyphs a pixel. Drop a real
	// NotoSansBengali-Bold.ttf into assets/fonts/ and this is skipped.
	$offsets = array( array( 0, 0 ) );
	if ( $bold ) {
		$spread  = $size >= 34 ? 2 : 1;
		$offsets = array();
		for ( $dx = 0; $dx <= $spread; $dx++ ) {
			for ( $dy = 0; $dy <= $spread; $dy++ ) {
				$offsets[] = array( $dx, $dy );
			}
		}
	}

	foreach ( $offsets as $o ) {
		imagettftext( $img, $size, 0, $x + $o[0], $y + $o[1], $color, $font, $text );
	}
}

/**
 * Largest footer size at which the three strings don't collide.
 *
 * Summed widths aren't enough: the middle string is centred on the canvas
 * while the outer two are pinned to the margins, so the real test is whether
 * the three occupied spans stay clear of each other.
 *
 * @param string   $font   Font path.
 * @param string[] $parts  left/center/right strings.
 * @param int      $canvas Canvas width.
 * @param int      $pad    Side padding.
 * @param int      $start  Preferred size.
 * @return int
 */
function khobor_photocard_fit_footer( $font, array $parts, $canvas, $pad, $start = 26 ) {
	$gap = 20;

	for ( $size = $start; $size > 14; $size-- ) {
		$wl = khobor_photocard_text_width( $font, $size, (string) $parts['left'] );
		$wc = khobor_photocard_text_width( $font, $size, (string) $parts['center'] );
		$wr = khobor_photocard_text_width( $font, $size, (string) $parts['right'] );

		$left_end     = $pad + $wl;
		$center_start = (int) round( ( $canvas - $wc ) / 2 );
		$center_end   = $center_start + $wc;
		$right_start  = $canvas - $pad - $wr;

		if ( $left_end + $gap <= $center_start && $center_end + $gap <= $right_start ) {
			return $size;
		}
	}
	return 14;
}

function khobor_generate_photocard_gd( WP_Post $post, string $file, string $file_url ) {
	$post_id = $post->ID;
	$size    = KHOBOR_PHOTOCARD_SIZE;
	$L       = khobor_photocard_layout();
	$C       = khobor_photocard_colors();

	$canvas = imagecreatetruecolor( $size, $size );
	if ( ! $canvas ) {
		return new WP_Error( 'gd_failed', __( 'GD canvas creation failed.', 'khobor' ) );
	}
	imagealphablending( $canvas, true );

	// 1. Paper, then the red panel across the lower two-thirds.
	imagefilledrectangle( $canvas, 0, 0, $size, $size, khobor_gd_color( $canvas, $C['paper'] ) );
	imagefilledrectangle( $canvas, 0, $L['red_top'], $size, $size, khobor_gd_color( $canvas, $C['panel'] ) );

	// 2. Masthead, centred on the white strip.
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_path = get_attached_file( $logo_id );
		if ( $logo_path && file_exists( $logo_path ) ) {
			$logo = khobor_gd_load_image( $logo_path );
			if ( $logo ) {
				$lw = imagesx( $logo );
				$lh = imagesy( $logo );
				$nh = $L['masthead_height'];
				$nw = (int) round( $lw * ( $nh / $lh ) );
				imagecopyresampled(
					$canvas,
					$logo,
					(int) round( ( $size - $nw ) / 2 ),
					$L['masthead_top'],
					0,
					0,
					$nw,
					$nh,
					$lw,
					$lh
				);
				imagedestroy( $logo );
			}
		}
	}

	// 3. Gold frame, then the photo inset inside it with matching rounded
	//    corners. The frame straddles the white/red boundary.
	khobor_gd_rounded_rect(
		$canvas,
		$L['box_x'],
		$L['box_y'],
		$L['box_w'],
		$L['box_h'],
		$L['box_radius'],
		khobor_gd_color( $canvas, $C['border'] )
	);

	$b        = $L['box_border'];
	$inner_w  = $L['box_w'] - ( $b * 2 );
	$inner_h  = $L['box_h'] - ( $b * 2 );
	$inner_r  = max( 0, $L['box_radius'] - $b );
	$thumb_id = get_post_thumbnail_id( $post_id );
	$photo    = null;

	if ( $thumb_id ) {
		$thumb_path = get_attached_file( $thumb_id );
		if ( $thumb_path && file_exists( $thumb_path ) ) {
			$photo = khobor_gd_cover_crop( $thumb_path, $inner_w, $inner_h );
		}
	}

	if ( $photo ) {
		// The optional overlay is composited onto the photo, not the whole card,
		// so a watermark PNG lands inside the frame.
		$overlay_path = khobor_photocard_resolve_local_path( khobor_photocard_overlay_url() );
		if ( $overlay_path && file_exists( $overlay_path ) ) {
			$overlay = khobor_gd_load_image( $overlay_path );
			if ( $overlay ) {
				imagealphablending( $photo, true );
				imagecopyresampled(
					$photo,
					$overlay,
					0,
					0,
					0,
					0,
					$inner_w,
					$inner_h,
					imagesx( $overlay ),
					imagesy( $overlay )
				);
				imagedestroy( $overlay );
			}
		}

		khobor_gd_round_corners( $photo, $inner_r );

		imagealphablending( $canvas, true );
		imagecopy( $canvas, $photo, $L['box_x'] + $b, $L['box_y'] + $b, 0, 0, $inner_w, $inner_h );
		imagedestroy( $photo );
	} else {
		// No featured image: leave a flat panel rather than an empty gold slab.
		khobor_gd_rounded_rect(
			$canvas,
			$L['box_x'] + $b,
			$L['box_y'] + $b,
			$inner_w,
			$inner_h,
			$inner_r,
			khobor_gd_color( $canvas, '#0f172a' )
		);
	}

	$font = khobor_photocard_font_path();

	// 4. Headline: bold, centred, on the red panel.
	$title = khobor_photocard_prepare_text(
		khobor_photocard_truncate( wp_strip_all_tags( $post->post_title ), 140 )
	);
	if ( $font && $title ) {
		$ink       = khobor_gd_color( $canvas, $C['headline'] );
		$avail_w   = $size - ( $L['headline_pad'] * 2 );
		$fit       = khobor_photocard_fit_text( $title, $font, $avail_w );
		$line_h    = (int) round( $fit['size'] * 1.5 );
		$block_h   = count( $fit['lines'] ) * $line_h;
		$area_h    = $L['headline_bottom'] - $L['headline_top'];

		// Vertically centre the block in the headline area; first baseline sits
		// one cap-height down from the block top.
		$y = $L['headline_top'] + (int) round( ( $area_h - $block_h ) / 2 ) + $fit['size'];

		foreach ( $fit['lines'] as $line ) {
			khobor_gd_text( $canvas, $fit['size'], $font, $line, (int) round( $size / 2 ), $y, $ink, 'center', true );
			$y += $line_h;
		}
	}

	// 5. Footer strip: date left, call to action centre, domain right.
	if ( $font ) {
		$footer = array_map( 'khobor_photocard_prepare_text', khobor_photocard_footer_text( $post ) );
		$ink    = khobor_gd_color( $canvas, $C['footer'] );
		$base   = $L['footer_baseline'];
		$fs     = khobor_photocard_fit_footer( $font, $footer, $size, $L['footer_pad'], $L['footer_size'] );

		khobor_gd_text( $canvas, $fs, $font, $footer['left'], $L['footer_pad'], $base, $ink, 'left' );
		khobor_gd_text( $canvas, $fs, $font, $footer['center'], (int) round( $size / 2 ), $base, $ink, 'center' );
		khobor_gd_text( $canvas, $fs, $font, $footer['right'], $size - $L['footer_pad'], $base, $ink, 'right' );
	}

	// 6. Save. The card is deliberately opaque, so no imagesavealpha() here.
	$ok = imagepng( $canvas, $file, 6 );
	imagedestroy( $canvas );

	if ( ! $ok ) {
		return new WP_Error( 'write_failed', __( 'Could not write photocard.', 'khobor' ) );
	}

	return $file_url;
}

// ---------------------------------------------------------------------------
// REST endpoint
// ---------------------------------------------------------------------------

function khobor_register_photocard_endpoint() {
	register_rest_route(
		'khobor/v1',
		'/photocard',
		array(
			'methods'             => 'POST',
			'callback'            => 'khobor_photocard_rest_callback',
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_id' => array( 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ),
				'force'   => array( 'required' => false, 'type' => 'boolean' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'khobor_register_photocard_endpoint' );

/**
 * Handle a photocard request.
 *
 * Public by design (readers generate their own share cards), but image
 * generation is expensive, so uncached renders are rate limited and only
 * editors may bypass the cache.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function khobor_photocard_rest_callback( WP_REST_Request $req ) {
	$post_id = absint( $req->get_param( 'post_id' ) );
	if ( ! $post_id ) {
		return new WP_Error( 'invalid_post', 'post_id required', array( 'status' => 400 ) );
	}

	// Only editors can force a re-render; otherwise anyone could bypass the
	// cache in a loop and pin the CPU.
	$force = (bool) $req->get_param( 'force' ) && current_user_can( 'edit_posts' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		$bucket = 'khobor_pc_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$hits   = (int) get_transient( $bucket );
		if ( $hits > 20 ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many photocard requests. Please try again shortly.', 'khobor' ),
				array( 'status' => 429 )
			);
		}
		set_transient( $bucket, $hits + 1, MINUTE_IN_SECONDS * 10 );
	}

	$url = khobor_generate_photocard( $post_id, $force );
	if ( is_wp_error( $url ) ) {
		return new WP_Error( $url->get_error_code(), $url->get_error_message(), array( 'status' => 500 ) );
	}
	return rest_ensure_response( array( 'url' => $url ) );
}

// ---------------------------------------------------------------------------
// Cache invalidation
// ---------------------------------------------------------------------------

/**
 * Delete cached photocards for a post.
 *
 * @param int $post_id Post ID.
 */
function khobor_photocard_invalidate( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	$storage = khobor_photocard_storage();
	if ( is_wp_error( $storage ) ) {
		return;
	}
	$post_id = absint( $post_id );
	$base    = khobor_photocard_filename_base();
	$pattern = trailingslashit( $storage['dir'] ) . $base . '-' . $post_id . '-*.png';

	// glob's "*" would let "…-4-*" also match "…-42-abcdef.png", so confirm the
	// post id is followed by the hash separator rather than more digits.
	$expect = '/^' . preg_quote( $base, '/' ) . '-' . $post_id . '-[a-f0-9]{32}\.png$/';

	foreach ( (array) glob( $pattern ) as $f ) {
		if ( preg_match( $expect, basename( $f ) ) ) {
			wp_delete_file( $f );
		}
	}
}
add_action( 'save_post_post', 'khobor_photocard_invalidate' );
add_action( 'deleted_post', 'khobor_photocard_invalidate' );

/**
 * A re-cropped or replaced image changes every card that features it.
 *
 * @param int $attachment_id Attachment ID.
 */
function khobor_photocard_invalidate_by_attachment( $attachment_id ) {
	$parent = wp_get_post_parent_id( $attachment_id );
	if ( $parent ) {
		khobor_photocard_invalidate( $parent );
	}
}
add_action( 'edit_attachment', 'khobor_photocard_invalidate_by_attachment' );
add_action( 'delete_attachment', 'khobor_photocard_invalidate_by_attachment' );
