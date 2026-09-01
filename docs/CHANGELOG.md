# Changelog

All notable changes to the Khobor theme are documented here.
This project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added
- Editorial layout with lead news, latest news, dynamic category sections
- Dynamic category section that auto-iterates whatever categories the admin has set up
- Breaking news ticker with REST endpoint and 5-minute cache
- Native post view counter (postmeta-based, JS-ping for cache compatibility)
- Most-read sidebar block
- E-Paper custom post type with PDF.js + StPageFlip flipbook renderer
- Server-side photocard generator (PHP + Imagick, 1080×1080)
- Customizer-managed photocard overlay image
- Font-size adjuster (A− / A / A+) with localStorage persistence
- Dark-mode toggle (optional)
- Reading-time estimate
- Prayer times widget (Aladhan API, 12-hour cache)
- Five custom widgets: Popular Posts, Latest Posts, Prayer Times, Category Posts, Ad Zone
- Three Gutenberg blocks: featured news, category section, news card
- Elementor compatibility (auto-loads widgets from `widgets/elementor/`)
- Custom ad zone manager (11 default zones) under Appearance → Khobor Ad Zones
- Mid-article ad auto-injection after the 3rd paragraph
- JSON-LD NewsArticle + BreadcrumbList schema (skipped if SEO plugin active)
- OG + Twitter Card defaults (skipped if SEO plugin active)
- Native lazy-loading + `fetchpriority="high"` on LCP image
- WebP `<picture>` wiring when sibling `.webp` exists
- Custom image sizes: `khobor-card`, `khobor-card-lg`, `khobor-hero`, `khobor-photocard`, `khobor-thumb-sq`, `khobor-card-sm`
- Bangla numeral conversion filter
- Today's-date header line in Bangla / Bengali calendar style
- Five sidebar regions: primary, home-top, home-bottom, four footer columns
- Translation template (`languages/khobor.pot`)
- Security defaults: file editor disabled, generator removed, optional XML-RPC kill switch, REST `/users` restricted for guests, sensible response headers
- Theme constants: `KHOBOR_VERSION`, `KHOBOR_DIR`, `KHOBOR_URI`, `KHOBOR_INC`, `KHOBOR_ASSETS`

### Known limitations
- PDF.js and StPageFlip are not bundled (license-friendly distribution) — see `assets/vendor/*/README.md`
- A Bengali TTF must be available for the photocard generator to render Bangla titles correctly — see `assets/fonts/README.md`
- Imagick is required for the photocard generator; without it the button still appears but returns a clean error
