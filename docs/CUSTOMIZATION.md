# Khobor — Customization Guide

This guide covers the most common questions about adapting the theme to a
specific publication.

## Changing colors

**Appearance → Customize → Khobor Theme → Brand Colors.**

The Customizer writes CSS variables into the `<head>`. The variables are:

```
--khobor-primary    Header bar, links, badges (default red)
--khobor-secondary  Headlines, body strong text (default near-black)
--khobor-accent     Buttons, photocard accents (default gold)
--khobor-text       Body text
--khobor-muted      Meta lines, captions
--khobor-bg         Page background
--khobor-surface    Cards, sidebar widget background
```

If you want deeper changes, edit `assets/css/main.css` — every variable is
declared at the top so it's easy to override.

## Swapping fonts

The theme loads Hind Siliguri and Noto Sans Bengali from Google Fonts. To
self-host or swap to a different family:

1. Open `inc/enqueue.php`
2. Find the `khobor-fonts` `wp_enqueue_style` call and change the URL
3. Open `assets/css/main.css` and update the `font-family` declaration on `body` and the headings

## Photocard overlay

**Appearance → Customize → Khobor Theme → Photocard Generator.**

Upload a 1080×1080 PNG with transparency. The featured image is composited
underneath, the post title is drawn at the bottom, and the site logo is
overlaid in the top-left. Tips:

- Keep the bottom 40% darker (gradient or solid band) so light title text remains readable
- Avoid solid centers — the photo needs to show through
- Bundled default lives at `assets/img/photocard-default-overlay.png`; copy it and use as a starting point

## Ad zones

**Appearance → Khobor Ad Zones** lists every named zone. Paste raw HTML or
ad-network script tags into the textarea for each zone you want active.
Leave a zone blank to disable it.

To call a zone from a custom template:

```php
khobor_ad_zone( 'home_top' );
```

The middle-of-article zone (`article_middle`) auto-injects after the 3rd
paragraph. To change that paragraph offset, drop this in a child theme's
`functions.php`:

```php
add_filter( 'khobor_article_middle_after', function () { return 5; } );
```

To register a custom zone:

```php
add_filter( 'khobor_ad_zones', function ( $zones ) {
	$zones['my_zone'] = 'My Custom Zone';
	return $zones;
} );
```

It will then appear in the admin page and be callable via `khobor_ad_zone('my_zone')`.

## Homepage layout

The homepage is `front-page.php`. It calls four template parts in order:

1. `template-parts/sections/lead-news.php` — 1 large + 4 small cards
2. `template-parts/sections/latest-news.php` — 9-card grid
3. `template-parts/sections/category-block.php` — auto-iterates active categories
4. Sidebar — `home-top`, `home-bottom`, and primary sidebar widget areas

Reorder these or remove some by editing `front-page.php`. The two
`is_active_sidebar()` blocks are gated by widget content, so they
disappear automatically when no widgets are placed.

## View counter

Views are stored in the `_khobor_views` post meta field and counted by
**one** of two paths — never both, since running both makes every article
view pay for a second WordPress bootstrap that is then discarded by the
dedupe transient:

- **PHP page load** (default) — `khobor_track_view_on_load()` on the `wp`
  hook. Correct when nothing caches full pages.
- **JS ping** — the `fetch()` in `single.php` POSTing to
  `/khobor/v1/view`. Required behind a full-page cache (WP Super Cache,
  LiteSpeed, Cloudflare APO, Varnish), because PHP stops running for cached
  hits.

If you add a page cache, switch paths or view counts will silently freeze:

```php
add_filter( 'khobor_count_views_on_page_load', '__return_false' );
```

Repeat views are deduped per visitor by an `IP + user-agent + post` hash
transient with a one-hour TTL.

The "popular" ranking behind both the sidebar section and the Popular Posts
widget comes from `khobor_get_popular_posts( $count, $days )`, cached for 5
minutes and flushed whenever a post is saved or deleted.

To exclude
specific user roles (e.g. logged-in admins), you can short-circuit the
REST endpoint:

```php
add_filter( 'rest_authentication_errors', function ( $r ) {
	if ( ! empty( $r ) ) return $r;
	if ( current_user_can( 'manage_options' ) &&
	     false !== strpos( $_SERVER['REQUEST_URI'] ?? '', '/khobor/v1/view' ) ) {
		return new WP_Error( 'skip_admin', '', array( 'status' => 200 ) );
	}
	return $r;
} );
```

To reset view counts in bulk:

```php
delete_post_meta_by_key( '_khobor_views' );
```

## Reading time

The estimate uses 200 words per minute by default. To tune, filter:

```php
add_filter( 'khobor_reading_time', function ( $minutes, $post ) {
	return /* your calculation */;
}, 10, 2 );
```

Or call directly: `khobor_reading_time( $post_id, 250 );`

## Prayer times

**Appearance → Customize → Khobor Theme → Prayer Times** sets the default
city, country, and calculation method. Individual widget instances can
override the city/country.

Cache is 12 hours per (city, country, method, date) combination. To force
a refresh, the easiest path is to switch the city, save, and switch back.

## Breaking news ticker

Create a category with slug `breaking` and assign the posts you want in the
ticker to it. Without that category, the ticker falls back to your 10 latest
posts. The cache is 5 minutes; it invalidates automatically on post save.

## E-paper publishing workflow

1. Go to **E-Papers → Add New** in the admin
2. Enter a title (e.g. "১৫ মে, ২০২৬ — দৈনিক")
3. Use the **Choose PDF from Media Library** button to attach the day's PDF
4. Optionally set the issue date and page count
5. Publish

The header automatically links the latest e-paper from the **ইপেপার**
button on the right of the main nav.

## Disabling features

Every major feature has an on/off toggle in **Customize → Khobor Theme →
Layout & Features**:

- Breaking news ticker
- Dark mode toggle
- Font size adjuster
- Reading time
- Photocard button
- Related posts
- Author bio
- Facebook Comments (vs. native)
- Bangla numeral conversion
- XML-RPC kill switch

## Child theme

To make persistent code changes without losing them on theme updates,
create a child theme:

```
khobor-child/
├── style.css        (with Template: khobor in the header)
├── functions.php
```

Override any template by copying it into the child theme with the same path.
