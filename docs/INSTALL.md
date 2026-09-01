# Khobor — Installation Guide

## 1. Upload the theme

In WordPress admin → **Appearance → Themes → Add New → Upload Theme**, upload
the `khobor.zip` file and click **Activate**.

## 2. Drop in the e-paper libraries (optional)

If you plan to use the e-paper feature, two third-party libraries are required.
They are intentionally not bundled with the theme — download once and copy in.

1. **PDF.js** — see `assets/vendor/pdfjs/README.md`. Drop `pdf.min.js` and
   `pdf.worker.min.js` into `assets/vendor/pdfjs/`.
2. **StPageFlip** — see `assets/vendor/stpageflip/README.md`. Drop
   `page-flip.browser.js` into `assets/vendor/stpageflip/`.

If you do not need e-paper, skip this step. The flipbook page will show an
error, but the rest of the theme will work normally.

## 3. Drop in a Bengali font (for the photocard generator)

The photocard generator renders post titles as part of the image. To get
proper Bangla rendering, place a Bengali TTF in `assets/fonts/`. See
`assets/fonts/README.md` for the recommended font (Noto Sans Bengali, free).

If you do not place a font, photocards still generate; titles may render as
boxes/tofu on hosts without a system-wide Bengali font.

## 4. Set up menus

Go to **Appearance → Menus** and create at least one menu, then assign it to
the **Primary Menu (Main Nav)** location. The theme also supports:

- **Top Bar Menu** — small links above the logo (e.g. About, Contact)
- **Footer Menu** — bottom credit-strip menu
- **Mobile Menu** — optional override; if left empty, the primary menu is used on mobile

## 5. Set categories

This theme is built around dynamic categories: it picks up whatever you've
created and renders a section for each one on the homepage automatically.
Create categories under **Posts → Categories** based on your editorial
needs (Politics, Sports, Entertainment, Local, etc.).

Optional: create a category with the slug **`breaking`** to drive the breaking-news ticker. If you don't, the ticker uses your 10 latest posts.

## 6. Customize

Go to **Appearance → Customize → Khobor Theme**. Configure:

- **Brand Colors** — primary, secondary, accent
- **Layout & Features** — toggle the ticker, dark mode, font sizer, reading time, photocard, related posts, author bio, Bangla numerals
- **Photocard Generator** — overlay image and title color
- **Prayer Times** — city, country, calculation method (used by the Prayer Times widget)
- **Social Links** — Facebook, Twitter, YouTube, Instagram, LinkedIn

## 7. Set up ad zones

Go to **Appearance → Khobor Ad Zones** and paste your AdSense / direct-sold
banner code into the zones you want to use. Empty zones render nothing.

Available zones: `header`, `below_ticker`, `home_top`, `home_middle`,
`home_bottom`, `article_top`, `article_middle`, `article_bottom`,
`sidebar_top`, `sidebar_middle`, `footer`.

## 8. Install recommended plugins

The theme has no hard dependencies, but these plugins pair well:

- **ShortPixel / Imagify / Smush / LiteSpeed Cache** — for actual image compression and WebP generation. The theme auto-wires `<picture>` sources when these create `.webp` files next to originals.
- **Yoast SEO** or **RankMath** — the theme detects either and steps out of the way for schema/meta.
- **WP Rocket** or **W3 Total Cache** — page caching. The view counter uses a JS ping so it still works on cached pages.
- **Elementor** — fully compatible; theme exposes a "Khobor News" widget category.
- **Wordfence / Sucuri** — security on top of the theme's hardening defaults.

## 9. Translations

The `languages/khobor.pot` file is the translation template. Generate a
`.po` and compiled `.mo` for your locale (e.g. `bn_BD.po` / `bn_BD.mo`)
with Poedit, then drop both into `languages/`.
