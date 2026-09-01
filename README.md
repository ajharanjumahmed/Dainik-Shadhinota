# Khobor — Bangla Newspaper WordPress Theme

A high-performance, SEO-optimized WordPress theme designed for Bangla-language
news publishers. Built with native WordPress patterns (no proprietary builder
dependency) and compatible with Elementor and Gutenberg.

## Features

**Editorial**
- Dynamic homepage with lead news, latest news, and an auto-adapting category-block section that picks up whatever categories the admin sets up
- Breaking news ticker (5-minute auto-refresh, REST-backed)
- Most-read sidebar block powered by a native view counter
- Single-article view with breadcrumbs, primary-category badge, author byline + bio block, related posts, share buttons, tags
- E-Paper custom post type with PDF.js + StPageFlip flipbook renderer
- Server-side photocard generator (1080×1080, PHP + Imagick) — admin can replace the default overlay PNG from the Customizer

**Reader controls**
- Font-size adjuster (A− / A / A+) with localStorage persistence
- Optional dark-mode toggle
- Reading-time estimate on every article

**Bangla-specific**
- Hind Siliguri + Noto Sans Bengali fonts pre-wired
- Date and number conversion to Bangla numerals
- All strings translation-ready (.pot file in `languages/`)

**Performance & SEO**
- Native lazy-loading + `decoding=async` on all images
- LCP image marked `fetchpriority="high"` and preloaded
- Auto WebP `<picture>` source when a sibling `.webp` exists on disk (works with ShortPixel / Imagify / Smush / LiteSpeed)
- Custom image sizes tuned for editorial cards (`khobor-card`, `khobor-card-lg`, `khobor-hero`, `khobor-photocard`)
- JSON-LD NewsArticle + BreadcrumbList schema (skipped if Yoast or RankMath is active)
- Open Graph + Twitter Card defaults
- Asset version strings stripped for caching

**Monetization**
- 11 named ad zones managed under **Appearance → Khobor Ad Zones**
- Middle-of-article ad auto-injects after the third paragraph
- A widget for dropping any ad zone into any sidebar

**Security defaults**
- File editor disabled (`DISALLOW_FILE_EDIT`)
- Version generator removed from `<head>` and feeds
- Optional XML-RPC kill switch (Customizer)
- REST `/users` blocked for unauthenticated requests
- Sensible response headers (`X-Content-Type-Options`, `Referrer-Policy`, etc.)

**Other**
- Prayer-times sidebar widget (Aladhan API, 12-hour cache)
- Five custom widgets: Popular Posts, Latest Posts, Prayer Times, Category Posts, Ad Zone
- Three Gutenberg blocks: featured news, category section, news card
- Comments: native WordPress threaded comments by default, with a Facebook Comments toggle

## Requirements

- WordPress 6.0+
- PHP 7.4+ (8.x recommended)
- For the photocard generator: PHP Imagick extension + a Bengali TTF (see `assets/fonts/README.md`)
- For e-paper: drop PDF.js and StPageFlip into `assets/vendor/` (see READMEs there)

## Installation

See [`docs/INSTALL.md`](docs/INSTALL.md).

## Customization

See [`docs/CUSTOMIZATION.md`](docs/CUSTOMIZATION.md).

## Changelog

See [`docs/CHANGELOG.md`](docs/CHANGELOG.md).

## License

GPL v2 or later.
